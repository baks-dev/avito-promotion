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

namespace BaksDev\Avito\Promotion\Repository\AllAvitoPromotionCompanyByProfile;

use BaksDev\Avito\Promotion\Entity\AvitoPromotion;
use BaksDev\Avito\Promotion\Entity\Event\AvitoPromotionEvent;
use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Services\Paginator\PaginatorInterface;
use BaksDev\Products\Category\Entity\CategoryProduct;
use BaksDev\Products\Category\Entity\Cover\CategoryProductCover;
use BaksDev\Products\Category\Entity\Event\CategoryProductEvent;
use BaksDev\Products\Category\Entity\Trans\CategoryProductTrans;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use InvalidArgumentException;

final class AllAvitoPromotionCompanyByProfile implements AllAvitoPromotionCompanyByProfileInterface
{
    private UserProfileUid|false $profile = false;

    public function __construct(
        private readonly DBALQueryBuilder $DBALQueryBuilder,
        private readonly PaginatorInterface $pagination,
    ) {}

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

    public function findWithPaginator(): PaginatorInterface
    {
        if(false === $this->profile)
        {
            throw new InvalidArgumentException('Invalid Argument profile');
        }

        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        /** Рекламные компании Авито */
        $dbal
            ->select('avito_promotion.id')
            ->from(AvitoPromotion::class, 'avito_promotion');

        /** Активное событие */
        $dbal
            ->addSelect('avito_promotion_event.id AS event')
            ->addSelect('avito_promotion_event.name AS promo_name')
            ->addSelect('avito_promotion_event.profile AS promo_profile')
            ->join(
                'avito_promotion',
                AvitoPromotionEvent::class,
                'avito_promotion_event',
                'avito_promotion_event.id = avito_promotion.event',
            );

        $dbal
            ->where('avito_promotion_event.profile = :profile')
            ->setParameter('profile', $this->profile, UserProfileUid::TYPE);

        /** Категория */
        $dbal->join(
            'avito_promotion_event',
            CategoryProduct::class,
            'category',
            'category.id = avito_promotion_event.category',
        );

        /** События категории */
        $dbal->join(
            'category',
            CategoryProductEvent::class,
            'category_event',
            'category_event.id = category.event',
        );

        /** Обложка */
        $dbal->addSelect('category_cover.ext');
        $dbal->addSelect('category_cover.cdn');
        $dbal->leftJoin(
            'category_event',
            CategoryProductCover::class,
            'category_cover',
            'category_cover.event = category_event.id',
        );

        $dbal->addSelect(
            "
			CASE
			   WHEN category_cover.name IS NOT NULL THEN
					CONCAT ( '/upload/".$dbal->table(CategoryProductCover::class)."' , '/', category_cover.name)
			   ELSE NULL
			END AS cover",
        );

        /** Перевод категории */
        $dbal->addSelect('category_trans.name as category_name');
        $dbal->addSelect('category_trans.description as category_description');

        $dbal->leftJoin(
            'category_event',
            CategoryProductTrans::class,
            'category_trans',
            'category_trans.event = category_event.id AND category_trans.local = :local',
        );

        return $this->pagination->fetchAllAssociative($dbal);
    }

    public function find(): array|false
    {
        if(false === $this->profile)
        {
            throw new InvalidArgumentException('Invalid Argument profile');
        }

        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        /** Рекламные компании Авито */
        $dbal
            ->select('avito_promotion.id')
            ->from(AvitoPromotion::class, 'avito_promotion');

        /** Активное событие */
        $dbal
            ->addSelect('avito_promotion_event.id AS event')
            ->addSelect('avito_promotion_event.name AS promo_name')
            ->addSelect('avito_promotion_event.profile AS promo_profile')
            ->join(
                'avito_promotion',
                AvitoPromotionEvent::class,
                'avito_promotion_event',
                'avito_promotion_event.id = avito_promotion.event',
            );

        $dbal
            ->where('avito_promotion_event.profile = :profile')
            ->setParameter('profile', $this->profile, UserProfileUid::TYPE);

        /** Категория */
        $dbal->join(
            'avito_promotion_event',
            CategoryProduct::class,
            'category',
            'category.id = avito_promotion_event.category',
        );

        /** События категории */
        $dbal->join(
            'category',
            CategoryProductEvent::class,
            'category_event',
            'category_event.id = category.event',
        );

        /** Обложка */
        $dbal->addSelect('category_cover.ext');
        $dbal->addSelect('category_cover.cdn');
        $dbal->leftJoin(
            'category_event',
            CategoryProductCover::class,
            'category_cover',
            'category_cover.event = category_event.id',
        );

        $dbal->addSelect(
            "
			CASE
			   WHEN category_cover.name IS NOT NULL THEN
					CONCAT ( '/upload/".$dbal->table(CategoryProductCover::class)."' , '/', category_cover.name)
			   ELSE NULL
			END AS cover",
        );

        /** Перевод категории */
        $dbal->addSelect('category_trans.name as category_name');
        $dbal->addSelect('category_trans.description as category_description');

        $dbal->leftJoin(
            'category_event',
            CategoryProductTrans::class,
            'category_trans',
            'category_trans.event = category_event.id AND category_trans.local = :local',
        );

        $result = $dbal->fetchAllAssociative();

        if(empty($result))
        {
            return false;
        }

        return $result;
    }
}
