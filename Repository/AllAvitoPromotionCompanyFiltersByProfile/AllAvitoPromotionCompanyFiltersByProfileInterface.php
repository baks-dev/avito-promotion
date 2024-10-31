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

namespace BaksDev\Avito\Promotion\Repository\AllAvitoPromotionCompanyFiltersByProfile;

use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;

/**
 * Находит все рекламные компании для Авито по профилю, активные на данный период
 */
interface AllAvitoPromotionCompanyFiltersByProfileInterface
{
    /**
     * Рекламные компании, попадающая в период
     */
    public function onlyActivePeriod(): self;

    /**
     * Профиль пользователя
     */
    public function forProfile(UserProfile|UserProfileUid|string $profile): self;

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
    public function find(): array|false;
}
