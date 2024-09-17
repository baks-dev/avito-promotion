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

namespace BaksDev\Avito\Promotion\Entity;

use BaksDev\Avito\Promotion\Entity\Event\AvitoPromotionEvent;
use BaksDev\Avito\Promotion\Type\AvitoPromotionUid;
use BaksDev\Avito\Promotion\Type\Event\AvitoPromotionEventUid;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'avito_promotion')]
class AvitoPromotion
{
    /**
     * ID категории
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: AvitoPromotionUid::TYPE, nullable: false)]
    private AvitoPromotionUid $id;

    /**
     * Идентификатор события рекламного предложения
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Column(type: AvitoPromotionEventUid::TYPE, unique: true, nullable: false)]
    private AvitoPromotionEventUid $event;

    public function __construct()
    {
        $this->id = new AvitoPromotionUid();
    }

    public function __toString(): string
    {
        return (string)$this->id;
    }

    public function getId(): AvitoPromotionUid
    {
        return $this->id;
    }

    public function setEvent(AvitoPromotionEvent|AvitoPromotionEventUid $event): void
    {
        $this->event = $event instanceof AvitoPromotionEvent ? $event->getEvent() : $event;
    }

    public function getEvent(): AvitoPromotionEventUid
    {
        return $this->event;
    }
}
