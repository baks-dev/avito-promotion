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

namespace BaksDev\Avito\Promotion\Repository\AllProductCategoryWithAvitoPromotionCompanyFilters;

use BaksDev\Avito\Promotion\Entity\AvitoPromotion;
use BaksDev\Avito\Promotion\Entity\Event\AvitoPromotionEvent;
use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Services\Paginator\PaginatorInterface;
use BaksDev\Products\Category\Entity\CategoryProduct;
use BaksDev\Products\Category\Entity\Cover\CategoryProductCover;
use BaksDev\Products\Category\Entity\Event\CategoryProductEvent;
use BaksDev\Products\Category\Entity\Info\CategoryProductInfo;
use BaksDev\Products\Category\Entity\Trans\CategoryProductTrans;

final class AllProductCategoryWithAvitoPromotionCompanyFilters implements AllProductCategoryWithAvitoPromotionCompanyFiltersInterface
{
    private bool $active = false;

    public function __construct(
        private readonly DBALQueryBuilder $DBALQueryBuilder,
        private readonly PaginatorInterface $pagination,
    ) {}

    /** Только активные категории */
    public function onlyActive(): self
    {
        $this->active = true;
        return $this;
    }

    public function findWithPaginator(): PaginatorInterface
    {
        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        /** Категория */
        $dbal
            ->select('category.id AS category_id')
            ->from(CategoryProduct::class, 'category');

        /** Активное событие категории */
        $dbal
            ->addSelect('category_event.id AS event')
            ->join(
                'category',
                CategoryProductEvent::class,
                'category_event',
                'category_event.id = category.event'
            );

        /** Выбираем только активные категории */
        if ($this->active)
        {
            $dbal->join(
                'category_event',
                CategoryProductInfo::class,
                'info',
                '
                    info.event = category_event.id AND 
                    info.active IS TRUE',
            );
        }

        /** Перевод */
        $dbal
            ->addSelect('category_trans.name AS category_name')
            ->addSelect('category_trans.description as category_description')
            ->leftJoin(
                'category_event',
                CategoryProductTrans::class,
                'category_trans',
                '
                    category_trans.event = category.event AND 
                    category_trans.local = :local'
            );

        /** Обложка категории */
        $dbal
            ->addSelect('category_cover.ext')
            ->addSelect('category_cover.cdn')
            ->leftJoin(
                'category_event',
                CategoryProductCover::class,
                'category_cover',
                'category_cover.event = category_event.id',
            );

        $dbal->addSelect(
            "
            CASE
			   WHEN category_cover.name IS NOT NULL THEN
					CONCAT ( '/upload/" . $dbal->table(CategoryProductCover::class) . "' , '/', category_cover.name)
			   ELSE NULL
			END AS cover"
        );

        /** Совпадение с рекламным предложением */
        $dbal
            ->addSelect('avito_promotion AS avito_promotion_id')
            ->leftJoin(
                'category_event',
                AvitoPromotion::class,
                'avito_promotion',
                '
                category_event.category = avito_promotion.id',
            );

        /** Активное событие рекламного предложения */
        $dbal
            ->leftJoin(
                'avito_promotion',
                AvitoPromotionEvent::class,
                'avito_promotion_event',
                '
                    avito_promotion_event.id = avito_promotion.event',
            );

        return $this->pagination->fetchAllAssociative($dbal);
    }
}
