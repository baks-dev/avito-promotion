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

namespace BaksDev\Avito\Promotion\Repository\AllAvitoPromotionByPromotionCompany;

use BaksDev\Avito\Promotion\Entity\AvitoPromotion;
use BaksDev\Avito\Promotion\Type\AvitoPromotionUid;
use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Services\Paginator\PaginatorInterface;

interface AllAvitoPromotionByPromotionCompanyInterface
{
    /** Идентификатор корня рекламной компании */
    public function byPromotionCompany(AvitoPromotion|AvitoPromotionUid|string $promoCompany): self;

    /** Метод получает список рекламируемых продуктов в настоящий момент */
    public function queryBuilder(): DBALQueryBuilder;

    public function findPaginator(): PaginatorInterface;

    /**
     * @return array{
     * "promo_product_id": string,
     * "product_article": string,
     * "product_name": string,
     * "category_name": string|null,
     * "product_offer_value": string|null,
     * "product_offer_postfix": string|null,
     * "product_offer_reference": string|null,
     * "product_variation_value": string|null,
     * "product_variation_postfix": string|null,
     * "product_variation_reference": string|null,
     * "product_modification_value": string|null,
     * "product_modification_postfix": string|null,
     * "product_modification_reference": string|null
     * }|false
     */
    public function find(): array|false;

}