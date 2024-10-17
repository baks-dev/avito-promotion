<?php
/*
 *  Copyright 2024.  Baks.dev <admin@baks.dev>
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
 *
 */

declare(strict_types=1);

namespace BaksDev\Avito\Promotion\Messenger\Promotion\CreateAvitoProductPromotion;

use BaksDev\Avito\Promotion\Entity\Promotion\AvitoProductPromotion;
use BaksDev\Avito\Promotion\Repository\AllAvitoPromotionCompanyFiltersByProfile\AllAvitoPromotionCompanyFiltersByProfileInterface;
use BaksDev\Avito\Promotion\Repository\AllOrdersByAvitoPromotionCompany\AllOrdersByAvitoPromotionCompanyInterface;
use BaksDev\Avito\Promotion\UseCase\NewEdit\Promotion\AvitoProductPromotionDTO;
use BaksDev\Avito\Promotion\UseCase\NewEdit\Promotion\AvitoProductPromotionHandler;
use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CreateAvitoProductPromotionHandler
{
    protected LoggerInterface $logger;

    public function __construct(
        LoggerInterface $avitoPromotionLogger,
        private AllAvitoPromotionCompanyFiltersByProfileInterface $allCompanies,
        private AllOrdersByAvitoPromotionCompanyInterface $allOrders,
        private AvitoProductPromotionHandler $handler,
        private DeduplicatorInterface $deduplicator,
    )
    {
        $this->logger = $avitoPromotionLogger;
    }

    public function __invoke(CreateAvitoProductPromotionMessage $message): void
    {
        $profile = $message->getProfile();

        /** Получаем все компании по профилю, активные на данный период */
        $promoCompanies = $this->allCompanies
            ->onlyActivePeriod()
            ->profile($profile)
            ->execute();

        if(false === $promoCompanies)
        {
            $this->logger->warning(
                'Действующие рекламные компании не найдены для профиля: '.$profile,
                [__FILE__.':'.__LINE__],
            );

            return;
        }

        $deduplicator = $this->deduplicator
            ->namespace('avito-promotion')
            ->expiresAfter(\DateInterval::createFromDateString('1 day'));

        foreach($promoCompanies as $promoCompany)
        {
            $filters = json_decode($promoCompany['filters'], false, 512, JSON_THROW_ON_ERROR);

            /** Все заказы по фильтрам */
            $orders = $this->allOrders
                ->date('-1 week')
                ->category($promoCompany['promo_category'])
                ->filters($filters)
                ->profile($promoCompany['promo_profile'])
                ->execute();

            if(false === $orders)
            {
                $this->logger->warning(
                    'Не найдено ни одного заказа по фильтрам, указанным в рекламной компании: '
                    .'ID '.$promoCompany['promo_company']
                    .'| название '.$promoCompany['promo_name'],
                    [__FILE__.':'.__LINE__],
                );

                continue;
            }

            /** Получаем все заказы, попадающие под фильтр из рекламной компании */
            foreach($orders as $order)
            {
                $dto = new AvitoProductPromotionDTO();

                $dto->setOffer($order['product_offer_const']);
                $dto->setVariation($order['product_variation_const']);
                $dto->setModification($order['product_modification_const']);
                $dto->setArticle($order['product_article']);

                $dto->setProperty($order['product_offer_const']);
                $dto->setCreated(new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
                $dto->setCompany($promoCompany['promo_company']);
                $dto->setProfile($profile);

                // Добавить к дневному бюджету процент на количество продаж

                /**
                 * Применяем формулу расчета бюджета
                 */
                $calculate = intval(round($promoCompany['promo_budget'] / 100 * $order['orders_count']));
                $budget = $promoCompany['promo_budget'] + $calculate;

                // проверить что бюджет не превысил лимит
                // если превысили лимит - бюджет равен лимит
                if($budget > $promoCompany['promo_limit'])
                {
                    $budget = $promoCompany['promo_limit'];
                }

                $dto->setBudget($budget);

                // добавление заказа для отслеживания повторного обращения к нему из другой рекламной компании
                $deduplicator->deduplication(
                    [
                        $dto->getOffer(),
                        $dto->getVariation(),
                        $dto->getModification(),
                        $profile,
                    ],
                );

                if($deduplicator->isExecuted())
                {
                    $this->logger->warning(
                        'Рекламный продукт с артикулом '.$order['product_article'].' уже сохранен и используется другой в другой рекламной компании',
                        [__FILE__.':'.__LINE__],
                    );

                    continue;
                }

                $promotionProduct = $this->handler->handle($dto);

                if(false === $promotionProduct instanceof AvitoProductPromotion)
                {
                    $this->logger->critical(
                        'Ошибка '.$promotionProduct.' при обновлении рекламного продукта: '.$order['product_article'],
                        [__FILE__.':'.__LINE__],
                    );

                    continue;
                }

                $deduplicator->save();
            }
        }
    }
}
