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
 */

declare(strict_types=1);

namespace BaksDev\Avito\Promotion\Messenger\Promotion\CreateAvitoProductPromotion;

use BaksDev\Avito\Promotion\Entity\Promotion\AvitoProductPromotion;
use BaksDev\Avito\Promotion\Repository\AllAvitoPromotionCompanyFiltersByProfile\AllAvitoPromotionCompanyFiltersByProfileInterface;
use BaksDev\Avito\Promotion\Repository\AllOrdersByAvitoPromotionCompany\AllOrdersByAvitoPromotionCompanyInterface;
use BaksDev\Avito\Promotion\UseCase\NewEdit\Promotion\AvitoProductPromotionDTO;
use BaksDev\Avito\Promotion\UseCase\NewEdit\Promotion\AvitoProductPromotionHandler;
use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use DateInterval;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CreateAvitoProductPromotionHandler
{
    private LoggerInterface $logger;

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
                sprintf('Действующие рекламные компании не найдены для профиля: %s', $profile),
                [__FILE__.':'.__LINE__],
            );

            return;
        }

        $deduplicator = $this->deduplicator
            ->namespace('avito-promotion')
            ->expiresAfter(DateInterval::createFromDateString('1 day'));

        foreach($promoCompanies as $promoCompany)
        {
            $filters = json_decode($promoCompany['filters'], false, 512, JSON_THROW_ON_ERROR);

            /** Все заказы по фильтрам */
            $orders = $this->allOrders
                ->date(DateInterval::createFromDateString('1 week'))
                ->category($promoCompany['promo_category'])
                ->filters($filters)
                ->profile($promoCompany['promo_profile'])
                ->execute();

            if(false === $orders)
            {
                $this->logger->warning(
                    sprintf(
                        'Не найдено ни одного заказа по фильтрам, указанным в рекламной компании: ID %s | название %s',
                        $promoCompany['promo_company'],
                        $promoCompany['promo_name']
                    ),
                    [__FILE__.':'.__LINE__],
                );

                continue;
            }

            /** Получаем все заказы, попадающие под фильтр из рекламной компании */
            foreach($orders as $order)
            {
                // добавление заказа для отслеживания повторного обращения к нему из другой рекламной компании
                $deduplicator->deduplication(
                    [
                        $order['orders_product'],
                        $order['product_offer_const'],
                        $order['product_variation_const'],
                        $order['product_modification_const'],
                        $profile,
                        self::class
                    ]
                );

                if($deduplicator->isExecuted())
                {
                    $this->logger->warning(
                        sprintf(
                            'Рекламный продукт с артикулом %s уже сохранен и используется другой в другой рекламной компании',
                            $order['product_article']
                        ),
                        [__FILE__.':'.__LINE__],
                    );

                    continue;
                }

                $dto = new AvitoProductPromotionDTO();

                // уникальные идентификаторы записи AvitoProductPromotion
                $dto->setProduct($order['orders_product']);
                $dto->setOffer($order['product_offer_const']);
                $dto->setVariation($order['product_variation_const']);
                $dto->setModification($order['product_modification_const']);
                $dto->setArticle($order['product_article']);

                // информация для отправки на api Авито
                $dto->setProperty($order['product_offer_const']);
                $dto->setCreated(new DateTimeImmutable());
                $dto->setCompany($promoCompany['promo_company']);
                $dto->setProfile($profile);

                /**
                 * Применяем формулу расчета бюджета - Добавить к дневному бюджету процент на количество проданного товара
                 */
                $formula = (int) round(($promoCompany['promo_budget'] / 100 * $order['orders_count']));
                $budget = $promoCompany['promo_budget'] + $formula;

                // проверить что бюджет не превысил лимит
                if($budget > $promoCompany['promo_limit'])
                {
                    // если превысили лимит - бюджет равен лимит
                    $budget = $promoCompany['promo_limit'];
                }

                $dto->setBudget($budget);


                $promotionProduct = $this->handler->handle($dto);

                if(false === $promotionProduct instanceof AvitoProductPromotion)
                {
                    $this->logger->critical(
                        sprintf(
                            'avito-promotion: Ошибка %s при обновлении рекламного продукта: %s',
                            $promotionProduct,
                            $order['product_article']
                        ),
                        [__FILE__.':'.__LINE__],
                    );

                    continue;
                }

                $deduplicator->save();
            }
        }
    }
}
