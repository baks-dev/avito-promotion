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

namespace BaksDev\Avito\Promotion\Messenger\Schedules\CreateAvitoProductPromotion;

use BaksDev\Avito\Promotion\Entity\Promotion\AvitoProductPromotion;
use BaksDev\Avito\Promotion\Repository\AllAvitoPromotionCompanyFiltersByProfile\AllAvitoPromotionCompanyFiltersByProfileInterface;
use BaksDev\Avito\Promotion\Repository\AllOrdersByAvitoPromotionCompany\AllOrdersByAvitoPromotionCompanyInterface;
use BaksDev\Avito\Promotion\UseCase\NewEdit\Promotion\AvitoProductPromotionDTO;
use BaksDev\Avito\Promotion\UseCase\NewEdit\Promotion\AvitoProductPromotionHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CreateAvitoProductPromotionHandler
{
    private LoggerInterface $logger;

    public function __construct(
        private AllAvitoPromotionCompanyFiltersByProfileInterface $AvitoPromotionCompany,
        private AllOrdersByAvitoPromotionCompanyInterface $OrdersByAvitoPromotionCompany,
        private AvitoProductPromotionHandler $AvitoProductPromotionHandler,
        LoggerInterface $avitoPromotionLogger,
    )
    {
        $this->logger = $avitoPromotionLogger;
    }

    /**
     * Метод получает рекламные компании, анализирует продажи за неделю и создает рекламный продукт обновляя его бюджет
     */
    public function __invoke(CreateAvitoProductPromotionMessage $message): void
    {
        $profile = $message->getProfile();

        /** Получаем все компании по профилю, активные на данный период */
        $promoCompanies = $this->AvitoPromotionCompany
            ->forProfile($profile)
            ->onlyActivePeriod()
            ->find();

        if(false === $promoCompanies)
        {
            $this->logger->warning(
                sprintf('Действующие рекламные компании не найдены для профиля: %s', $profile),
                [__FILE__.':'.__LINE__],
            );

            return;
        }

        foreach($promoCompanies as $promoCompany)
        {
            $filters = json_decode($promoCompany['filters'], false, 512, JSON_THROW_ON_ERROR);

            /** Получаем все заказы, попадающие под фильтр из рекламной компании */
            $orders = $this->OrdersByAvitoPromotionCompany
                ->byDate(\DateInterval::createFromDateString('5 weeks')) // @TODO изменить на 1 week перед релизом
                ->byFilters($filters)
                ->forProfile($promoCompany['promo_profile'])
                ->forCategory($promoCompany['promo_category'])
                ->find();

            dump($orders);

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

            /**
             *  Создаем рекламный продукт с бюджетом на каждый заказ из фильтра
             */
            foreach($orders as $order)
            {
                $AvitoProductPromotionDTO = new AvitoProductPromotionDTO();

                // уникальные идентификаторы записи AvitoProductPromotion
                $AvitoProductPromotionDTO
                    ->setArticle($order['product_article'])
                    ->setProduct($order['orders_product'])
                    ->setOffer($order['product_offer_const'])
                    ->setVariation($order['product_variation_const'])
                    ->setModification($order['product_modification_const']);

                // информация для отправки на api Авито
                $AvitoProductPromotionDTO
                    ->setProperty($order['product_offer_const'])
                    ->setCreated(new \DateTimeImmutable())
                    ->setCompany($promoCompany['promo_company'])
                    ->setProfile($profile);

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

                // @TODO удалить в проде
                dump($budget);
                dump(__FILE__.':'.__LINE__);

                $AvitoProductPromotionDTO->setBudget($budget);

                /**
                 * Сохраняем рекламный продукт с указанным бюджетом
                 */
                $promotionProduct = $this->AvitoProductPromotionHandler->handle($AvitoProductPromotionDTO);

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
                }
            }

            // @TODO удалить в проде
            dump(__FILE__.':'.__LINE__);
            dd('-----------');
        }
    }
}
