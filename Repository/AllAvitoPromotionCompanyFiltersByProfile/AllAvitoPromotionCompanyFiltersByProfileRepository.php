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

namespace BaksDev\Avito\Promotion\Repository\AllAvitoPromotionCompanyFiltersByProfile;

use BaksDev\Avito\Entity\AvitoToken;
use BaksDev\Avito\Entity\Event\AvitoTokenEvent;
use BaksDev\Avito\Promotion\Entity\AvitoPromotion;
use BaksDev\Avito\Promotion\Entity\Event\AvitoPromotionEvent;
use BaksDev\Avito\Promotion\Entity\Filter\AvitoPromotionFilter;
use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Products\Category\Entity\Offers\CategoryProductOffers;
use BaksDev\Products\Category\Entity\Offers\Variation\CategoryProductVariation;
use BaksDev\Products\Category\Entity\Offers\Variation\Modification\CategoryProductModification;
use BaksDev\Products\Category\Entity\Section\Field\CategoryProductSectionField;
use BaksDev\Users\Profile\UserProfile\Entity\Info\UserProfileInfo;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Users\Profile\UserProfile\Type\UserProfileStatus\Status\UserProfileStatusActive;
use BaksDev\Users\Profile\UserProfile\Type\UserProfileStatus\UserProfileStatus;
use InvalidArgumentException;

final class AllAvitoPromotionCompanyFiltersByProfileRepository implements AllAvitoPromotionCompanyFiltersByProfileInterface
{
    private UserProfileUid|false $profile = false;

    private bool $active = false;

    public function __construct(
        private readonly DBALQueryBuilder $DBALQueryBuilder,
    ) {}

    public function onlyActivePeriod(): self
    {
        $this->active = true;

        return $this;
    }

    public function profile(UserProfile|UserProfileUid|string $profile): self
    {
        if($profile instanceof UserProfile)
        {
            $profile = $profile->getId();
        }

        if(is_string($profile))
        {
            $profile = new UserProfileUid($profile);
        }

        $this->profile = $profile;

        return $this;
    }

    /**
     * Метод возвращает все активные на данный период рекламные компании профиля
     *
     * @return array{
     *   "promo_company": string,
     *   "event": string,
     *   "promo_name": string,
     *   "promo_profile": int,
     *   "promo_category": string,
     *   "promo_budget": int,
     *   "promo_limit": int,
     *   "filters": string,
     *  }|false
     */
    public function execute(): array|false
    {
        if(false === $this->profile)
        {
            throw new InvalidArgumentException('Invalid Argument profile');
        }

        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class);

        /** Рекламные компании Авито */
        $dbal
            ->select('avito_promotion.id AS promo_company')
            ->from(AvitoPromotion::class, 'avito_promotion')
            ->addGroupBy('avito_promotion.id');

        /** Активное событие */
        $dbal
            ->addSelect('avito_promotion_event.id AS event')
            ->addSelect('avito_promotion_event.name AS promo_name')
            ->addSelect('avito_promotion_event.profile AS promo_profile')
            ->addSelect('avito_promotion_event.category AS promo_category')
            ->addSelect('avito_promotion_event.budget AS promo_budget')
            ->addSelect('avito_promotion_event.budget_limit AS promo_limit')
            ->join(
                'avito_promotion',
                AvitoPromotionEvent::class,
                'avito_promotion_event',
                'avito_promotion_event.id = avito_promotion.event AND avito_promotion_event.profile = :profile',
            )
            ->setParameter('profile', $this->profile, UserProfileUid::TYPE)
            ->addGroupBy('avito_promotion_event.id');


        /**
         * Компания, попадающая в указанный период
         */

        if(true === $this->active)
        {
            $dbal->andWhere("avito_promotion_event.date_end > CURRENT_DATE");
        }

        /** Проверка существования токена для Авито */
        $dbal
            ->join(
                'avito_promotion_event',
                AvitoToken::class,
                'avito_token',
                'avito_promotion_event.profile = avito_promotion_event.profile',
            );


        /** Проверка активности токена для Авито */
        $dbal
            ->join(
                'avito_token',
                AvitoTokenEvent::class,
                'avito_token_event',
                '
                        avito_token_event.id = avito_token.event AND
                        avito_token_event.active = TRUE',
            );


        /** Проверка активности профиля */
        $dbal
            ->join(
                'avito_promotion_event',
                UserProfileInfo::class,
                'users_profile_info',
                '
                users_profile_info.profile = avito_promotion_event.profile AND
                users_profile_info.status = :status',
            )
            ->setParameter(
                'status',
                UserProfileStatusActive::class,
                UserProfileStatus::TYPE
            )
            ->addGroupBy('users_profile_info.status');


        /** Получаем фильтры для каждой компании */
        $dbal
            ->leftJoin(
                'avito_promotion_event',
                AvitoPromotionFilter::class,
                'avito_promotion_filter',
                'avito_promotion_filter.event = avito_promotion_event.id',
            );

        /** Offer */
        $dbal
            ->leftJoin(
                'avito_promotion_filter',
                CategoryProductOffers::class,
                'category_offer',
                'category_offer.id = avito_promotion_filter.property',
            );

        /** Variation */
        $dbal
            ->leftJoin(
                'avito_promotion_filter',
                CategoryProductVariation::class,
                'product_variation_field',
                'product_variation_field.id = avito_promotion_filter.property',
            );

        /** Modification */
        $dbal
            ->leftJoin(
                'avito_promotion_filter',
                CategoryProductModification::class,
                'product_modification_field',
                'product_modification_field.id = avito_promotion_filter.property',
            );

        /** Property */
        $dbal
            ->leftJoin(
                'avito_promotion_filter',
                CategoryProductSectionField::class,
                'product_category_section_field',
                'product_category_section_field.const = avito_promotion_filter.property',
            );

        $dbal->addSelect(
            "
            JSON_AGG 
                (DISTINCT
                    JSONB_BUILD_OBJECT
                    (
                        'type',
                            (CASE
                                WHEN category_offer.id IS NOT NULL THEN 'OFFER'
                                WHEN product_variation_field.id IS NOT NULL THEN 'VARIATION'
                                WHEN product_modification_field.id IS NOT NULL THEN 'MODIFICATION'
                                WHEN product_category_section_field.const IS NOT NULL THEN 'PROPERTY'
                                ELSE NULL
                            END),
                        'property', avito_promotion_filter.property,
                        'value', avito_promotion_filter.value,
                        'predicate', avito_promotion_filter.predicate
                    )
                )
            AS filters",
        );

        $result = $dbal->fetchAllAssociative();

        return empty($result) ? false : $result;

    }
}
