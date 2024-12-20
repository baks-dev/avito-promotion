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

namespace BaksDev\Avito\Promotion\Repository\AllOrdersByAvitoPromotionCompany;

use BaksDev\Products\Category\Type\Id\CategoryProductUid;
use DateInterval;

/**
 * Репозиторий возвращает все заказы, попадающие под фильтр из рекламной компании по соответствию Offer, Variation, Modification, Property.
 */
interface AllOrdersByAvitoPromotionCompanyInterface
{
    /**
     * Фильтры заказов
     *
     * @param array<int, object<'type', string, 'value', string, 'property', string, 'predicate', string >> $filters
     */
    public function byFilters(array $filters): self;

    /**
     * Смещение по времени, относительно текущей даты
     */
    public function byDate(DateInterval $date): self;

    /**
     * Категория товара
     */
    public function forCategory(CategoryProductUid|string $category): self;

    //    /**
    //     * Профиль пользователя
    //     */
    //    public function forProfile(UserProfile|UserProfileUid|string $profile): self;

    /**
     * Заказы, попадающие под фильтр из рекламной компании
     *
     * @return array{
     *   "orders_product": string,
     *   "orders_count": int,
     *   "product_offer_const": string,
     *   "product_variation_const": string,
     *   "product_modification_const": string,
     *   "product_article": string,
     *  }|false
     */
    public function findAll(): array|false;
}
