<?php
/*
 *  Copyright 2025.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Avito\Promotion\Repository\CurrentAvitoProductPromotionById;

use BaksDev\Avito\Promotion\Entity\Promotion\AvitoProductPromotion;
use BaksDev\Avito\Promotion\Type\Promotion\AvitoProductPromotionUid;
use BaksDev\Core\Doctrine\ORMQueryBuilder;


final readonly class CurrentAvitoProductPromotionByIdRepository implements CurrentAvitoProductPromotionByIdInterface
{
    public function __construct(private ORMQueryBuilder $ORMQueryBuilder) {}

    /**
     * Метод возвращает рекламную компанию по идентификатору
     */
    public function find(AvitoProductPromotionUid|string $promotion): AvitoProductPromotion|false
    {
        if(is_string($promotion))
        {
            $promotion = new AvitoProductPromotionUid($promotion);
        }

        $orm = $this->ORMQueryBuilder->createQueryBuilder(self::class);

        $orm
            ->select('promotion')
            ->from(AvitoProductPromotion::class, 'promotion')
            ->where('promotion.id = :promotion')
            ->setParameter(
                key: 'promotion',
                value: $promotion,
                type: AvitoProductPromotionUid::TYPE
            );

        return $orm->getOneOrNullResult() ?: false;
    }
}