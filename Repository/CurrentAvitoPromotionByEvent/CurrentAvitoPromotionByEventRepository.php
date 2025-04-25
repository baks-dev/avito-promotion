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

namespace BaksDev\Avito\Promotion\Repository\CurrentAvitoPromotionByEvent;

use BaksDev\Avito\Promotion\Entity\AvitoPromotion;
use BaksDev\Avito\Promotion\Entity\Event\AvitoPromotionEvent;
use BaksDev\Avito\Promotion\Type\AvitoPromotionUid;
use BaksDev\Core\Doctrine\ORMQueryBuilder;
use Doctrine\ORM\Query\Expr\Join;

final readonly class CurrentAvitoPromotionByEventRepository implements CurrentAvitoPromotionByEventInterface
{
    public function __construct(private ORMQueryBuilder $ORMQueryBuilder) {}

    /**
     * Метод возвращает активное событие для существующей рекламной компании по идентификатору корня
     */
    public function find(AvitoPromotionUid|string $main): AvitoPromotionEvent|false
    {

        if(is_string($main))
        {
            $main = new AvitoPromotionUid($main);
        }

        $orm = $this->ORMQueryBuilder->createQueryBuilder(self::class);

        $orm
            ->select('event')
            ->from(AvitoPromotionEvent::class, 'event')
            ->where('event.main = :main')
            ->setParameter(
                key: 'main',
                value: $main,
                type: AvitoPromotionUid::TYPE
            );

        $orm->join(
            AvitoPromotion::class,
            'main',
            Join::WITH,
            'main.event = event.id'
        );

        return $orm->getOneOrNullResult() ?: false;
    }
}