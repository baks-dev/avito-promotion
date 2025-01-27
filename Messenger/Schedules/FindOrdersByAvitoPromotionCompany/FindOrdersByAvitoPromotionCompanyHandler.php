<?php
/*
 *  Copyright 2025.  Baks.dev <admin@baks.dev>
 *  
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is furnished
 *  to do so, subject to the following conditions:
 *  
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *  
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 */

declare(strict_types=1);

namespace BaksDev\Avito\Promotion\Messenger\Schedules\FindOrdersByAvitoPromotionCompany;

use BaksDev\Avito\Promotion\Messenger\Schedules\CreateAvitoProductPromotion\CreateAvitoProductPromotionMessage;
use BaksDev\Avito\Promotion\Repository\AllAvitoPromotionCompanyFiltersByProfile\AllAvitoPromotionCompanyByProfileInterface;
use BaksDev\Avito\Promotion\Repository\AllOrdersByAvitoPromotionCompany\AllOrdersByAvitoPromotionCompanyInterface;
use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use DateInterval;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class FindOrdersByAvitoPromotionCompanyHandler
{
    public function __construct(
        #[Target('avitoPromotionLogger')] private LoggerInterface $logger,
        private DeduplicatorInterface $deduplicator,
        private MessageDispatchInterface $messageDispatch,
        private AllAvitoPromotionCompanyByProfileInterface $allAvitoPromotionCompanies,
        private AllOrdersByAvitoPromotionCompanyInterface $allOrdersByAvitoPromotionCompany,
    ) {}

    /**
     * Метод:
     * - получает все рекламные компании по профилю пользователя
     * - ищет заказы по фильтру из рекламной компании
     */
    public function __invoke(FindOrdersByAvitoPromotionCompanyMessage $message): void
    {
        $profile = $message->getProfile();

        /** Получаем все рекламные компании по профилю, активные на данный период */
        $avitoPromotionCompanies = $this->allAvitoPromotionCompanies
            ->forProfile($profile)
            ->onlyActivePeriod()
            ->find();

        if(false === $avitoPromotionCompanies)
        {
            $this->logger->warning(
                sprintf('Действующие рекламные компании не найдены для профиля: %s', $profile),
                [__FILE__.':'.__LINE__],
            );

            return;
        }

        $this->deduplicator
            ->namespace('avito-promotion')
            ->expiresAfter(DateInterval::createFromDateString('23 hours'));

        foreach($avitoPromotionCompanies as $promoCompany)
        {
            $filters = json_decode($promoCompany['filters'], false, 512, JSON_THROW_ON_ERROR);

            /** Получаем все заказы, попадающие под фильтр из рекламной компании */
            $orders = $this->allOrdersByAvitoPromotionCompany
                ->byDate(DateInterval::createFromDateString('1 week'))
                ->byFilters($filters)
                ->forCategory($promoCompany['promo_category'])
                ->findAll();

            // если заказы по фильтрам не найдены - переходим к следующей рекламной компании
            if(false === $orders)
            {
                continue;
            }

            /** Создаем рекламный продукт */
            foreach($orders as $order)
            {
                $deduplicator = $this->deduplicator->deduplication(
                    [
                        $profile,
                        $order['orders_product'],
                        $order['product_offer_const'],
                        $order['product_variation_const'],
                        $order['product_modification_const'],
                        self::class
                    ]
                );

                if($deduplicator->isExecuted())
                {
                    $this->logger->warning(
                        sprintf(
                            'Рекламный продукт с артикулом %s уже сохранен и используется в другой рекламной компании',
                            $order['product_article']
                        ),
                        [__FILE__.':'.__LINE__],
                    );

                    continue;
                }

                $this->messageDispatch->dispatch(
                    message: new CreateAvitoProductPromotionMessage(
                        $promoCompany['promo_company'],
                        $profile,
                        $order['orders_product'],
                        $order['product_offer_const'],
                        $order['product_variation_const'],
                        $order['product_modification_const'],
                        $order['product_article'],
                        $order['orders_count'],
                    ),
                    transport: 'avito-promotion',
                );

                $deduplicator->save();
            }
        }
    }
}
