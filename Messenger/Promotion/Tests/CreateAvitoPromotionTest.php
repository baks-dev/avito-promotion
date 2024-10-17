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

namespace BaksDev\Avito\Promotion\Messenger\Promotion\Tests;

use BaksDev\Avito\Promotion\Repository\AllAvitoPromotionCompanyFiltersByProfile\AllAvitoPromotionCompanyFiltersByProfileInterface;
use BaksDev\Avito\Promotion\Repository\AllOrdersByAvitoPromotionCompany\AllOrdersByAvitoPromotionCompanyInterface;
use BaksDev\Avito\Promotion\UseCase\NewEdit\Promotion\AvitoProductPromotionDTO;
use BaksDev\Avito\Promotion\UseCase\NewEdit\Promotion\AvitoProductPromotionHandler;
use BaksDev\Avito\Repository\AllUserProfilesByActiveToken\AllUserProfilesByTokenRepository;
use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

/**
 * @group avito-promotion
 */
#[When(env: 'test')]
final class CreateAvitoPromotionTest extends KernelTestCase
{
    public static array|false $profiles;

    public static DeduplicatorInterface $deduplicator;

    public static AvitoProductPromotionHandler $handler;

    public static AllAvitoPromotionCompanyFiltersByProfileInterface $allPromotionCompany;

    public static AllOrdersByAvitoPromotionCompanyInterface $allOrders;

    public static function setUpBeforeClass(): void
    {
        $container = self::getContainer();

        /** @var AllUserProfilesByTokenRepository $repo */
        $repo = $container->get(AllUserProfilesByTokenRepository::class);

        $profiles = $repo->findProfilesByActiveToken();

        self::assertNotFalse($profiles, 'Профили с активными токенами Авито не найдены ');

        if($profiles)
        {
            self::$profiles = iterator_to_array($profiles);
        }

        self::$deduplicator = $container->get(DeduplicatorInterface::class);
        self::$handler = $container->get(AvitoProductPromotionHandler::class);
        self::$allPromotionCompany = $container->get(AllAvitoPromotionCompanyFiltersByProfileInterface::class);
        self::$allOrders = $container->get(AllOrdersByAvitoPromotionCompanyInterface::class);
    }

    public function testCreate(): void
    {
        $deduplicator = self::$deduplicator;

        $deduplicator
            ->namespace('avito-promotion')
            ->expiresAfter(\DateInterval::createFromDateString('10 seconds'));

        /** Получаем все активные профили, у которых активный токен Авито */
        foreach(self::$profiles as $profile)
        {
            $promoCompanies = self::$allPromotionCompany
                ->onlyActivePeriod()
                ->profile($profile)
                ->execute();

            self::assertNotFalse($promoCompanies, 'Действующие рекламные компании не найдены для профиля: '.$profile);

            /** Получаем все компании по профилю, активные на данный период */
            foreach($promoCompanies as $promoCompany)
            {
                $filters = json_decode($promoCompany['filters'], false, 512, JSON_THROW_ON_ERROR);

                /** Все заказы по фильтрам */
                $orders = self::$allOrders
                    ->date('-1 week')
                    ->category($promoCompany['promo_category'])
                    ->filters($filters)
                    ->profile($promoCompany['promo_profile'])
                    ->execute();

                if(false === $orders)
                {
                    self::addWarning(
                        'Не найдено ни одного заказа по фильтрам, указанным в рекламной компании:'
                        .'ID '.$promoCompany['promo_company']
                        .' | название '.$promoCompany['promo_name'],
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
                    self::assertSame($budget, $dto->getBudget());

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
                        self::assertTrue(true);
                    }
                }
            }
        }
    }
}
