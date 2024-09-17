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

namespace BaksDev\Avito\Promotion\Messenger\Promotion\FindAvitoPromotionCompany;

use BaksDev\Avito\Promotion\Entity\Promotion\AvitoProductPromotion;
use BaksDev\Avito\Promotion\Repository\AllOrdersByAvitoPromotionCompany\AllOrdersByAvitoPromotionCompanyInterface;
use BaksDev\Avito\Promotion\Repository\AllAvitoPromotionCompanyFiltersByProfile\AllAvitoPromotionCompanyFiltersByProfileInterface;
use BaksDev\Avito\Promotion\UseCase\NewEdit\Promotion\AvitoProductPromotionDTO;
use BaksDev\Avito\Promotion\UseCase\NewEdit\Promotion\AvitoProductPromotionHandler;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class FindAvitoPromotionCompanyHandler
{
    protected LoggerInterface $logger;

    public function __construct(
        LoggerInterface $avitoBoardLogger,
        private EntityManagerInterface $em,
        private AllAvitoPromotionCompanyFiltersByProfileInterface $allCompanies,
        private AllOrdersByAvitoPromotionCompanyInterface $allOrders,
        private AvitoProductPromotionHandler $handler,
    ) {
        $this->logger = $avitoBoardLogger;
    }

    public function __invoke(FindAvitoPromotionCompanyMessage $message): void
    {
        $profile = $message->getProfile();

        /** Получаем все компании по профилю, активные на данный период */
        $promoCompanies = $this->allCompanies
            ->onlyActivePeriod()
            ->profile($profile)
            ->execute();

        if (false === $promoCompanies)
        {
            $this->logger->warning(
                'Действующие рекламные компании не найдены для профиля: ' . $profile,
                [__FILE__ . ':' . __LINE__]
            );

            return;
        }

        foreach ($promoCompanies as $promoCompany)
        {
            $filters = json_decode($promoCompany['filters'], false, 512, JSON_THROW_ON_ERROR);

            /** Все заказы по фильтрам */
            $orders = $this->allOrders
                ->date('-1 week')
                ->category($promoCompany['promo_category'])
                ->filters($filters)
                ->profile($promoCompany['promo_profile'])
                ->execute();

            if (false === $orders)
            {
                $this->logger->warning(
                    'Не найдено ни одного заказа по условиям, указанным в рекламной компании: ' . $promoCompany['promo_company'],
                    [__FILE__ . ':' . __LINE__]
                );

                return;
            }

            /** Получаем все заказы, попадающие под фильтр из рекламной компании */
            foreach ($orders as $order)
            {
                $dto = new AvitoProductPromotionDTO();
                $dto->setOffer($order['product_offer_const']);
                $dto->setVariation($order['product_offer_const']);
                $dto->setModification($order['product_offer_const']);
                $dto->setArticle($order['product_article']);

                $dto->setProperty($order['product_offer_const']);
                $dto->setCreated(new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
                $dto->setCompany($promoCompany['promo_company']);
                $dto->setProfile($profile);

                $productPromotion = $this->em->getRepository(AvitoProductPromotion::class)->findOneBy([
                    'offer' => $dto->getOffer(),
                    'variation' => $dto->getVariation(),
                    'modification' => $dto->getModification(),
                ]);

                if (null === $productPromotion)
                {
                    $dto->setBudget($promoCompany['promo_budget']);

                    $create = $this->handler->handle($dto);

                    if (false === $create instanceof AvitoProductPromotion)
                    {
                        $this->logger->warning(
                            'Ошибка ' . $create . ' при сохранении рекламного продукта: ' . $order['product_article'],
                            [__FILE__ . ':' . __LINE__]
                        );

                        return;
                    }
                }
                else
                {

                    // если текущий бюджет больше или равен установленному лимиту в рекламной компании - прекращаем обновлять бюджет
                    if ($productPromotion->getBudget() >= $promoCompany['promo_limit'])
                    {
                        $this->logger->warning(
                            'Превышен бюджет рекламной компании для продукта: ' . $order['product_article'],
                            [__FILE__ . ':' . __LINE__]
                        );

                        return;
                    }

                    // если последнее обновление бюджета было больше недели назад - начинаем бюджет со значения, равному шагу бюджета
                    if ($productPromotion->getCreated() <= $dto->getCreated()->modify('-1 week'))
                    {
                        // шаг бюджета
                        $step = $productPromotion->getBudget();
                        $dto->setBudget($step);

                        $this->logger->warning(
                            'Продукт: ' . $order['product_article'] . ' не обновлял бюджет более недели',
                            [__FILE__ . ':' . __LINE__]
                        );
                    }
                    else // обновляем бюджет
                    {
                        // новый бюджет
                        $budgetUpdate = $promoCompany['promo_budget'] + $productPromotion->getBudget();
                        $dto->setBudget($budgetUpdate);

                        $update = $this->handler->handle($dto);

                        if (false === $update instanceof AvitoProductPromotion)
                        {
                            $this->logger->warning(
                                'Ошибка ' . $create . ' при обновлении рекламного продукта: ' . $order['product_article'],
                                [__FILE__ . ':' . __LINE__]
                            );

                            return;
                        }
                    }
                }
            }
        }
    }
}
