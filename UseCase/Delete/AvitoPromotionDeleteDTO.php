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

namespace BaksDev\Avito\Promotion\UseCase\Delete;

use BaksDev\Avito\Promotion\Entity\Event\AvitoPromotionEventInterface;
use BaksDev\Avito\Promotion\Type\Event\AvitoPromotionEventUid;
use BaksDev\Avito\Promotion\UseCase\Delete\Modify\AvitoPromotionDeleteModifyDTO;
use BaksDev\Avito\Promotion\UseCase\NewEdit\Filter\AvitoPromotionFilterDTO;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

/** @see AvitoPromotionEvent */
final class AvitoPromotionDeleteDTO implements AvitoPromotionEventInterface
{
    /**
     * Идентификатор события
     */
    #[Assert\Uuid]
    private ?AvitoPromotionEventUid $id = null;

    /**
     * ID профиля пользователя
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private UserProfileUid $profile;

    /**
     * Название рекламной компании
     */
    #[Assert\NotBlank]
    private string $name;

    /**
     * Коллекция свойств для фильтрации и применения услуг продвижения
     */
    #[Assert\Valid]
    private ArrayCollection $filters;

    /**
     * Модификатор
     */
    #[Assert\Valid]
    private AvitoPromotionDeleteModifyDTO $modify;

    public function __construct()
    {
        $this->filters = new ArrayCollection();
        $this->modify = new AvitoPromotionDeleteModifyDTO();
    }

    public function getEvent(): ?AvitoPromotionEventUid
    {
        return $this->id;
    }

    /**
     * Модификатор
     */
    public function getModify(): AvitoPromotionDeleteModifyDTO
    {
        return $this->modify;
    }

    public function getProfile(): UserProfileUid
    {
        return $this->profile;
    }

    public function getFilters(): ArrayCollection
    {
        return $this->filters;
    }

    public function addFilter(AvitoPromotionFilterDTO $filter): void
    {
        $this->filters->add($filter);
    }

    public function removeFilter(AvitoPromotionFilterDTO $filter): void
    {
        $this->filters->removeElement($filter);
    }

    public function getName(): string
    {
        return $this->name;
    }
}
