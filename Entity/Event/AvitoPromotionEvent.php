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

namespace BaksDev\Avito\Promotion\Entity\Event;

use BaksDev\Avito\Promotion\Entity\AvitoPromotion;
use BaksDev\Avito\Promotion\Entity\Filter\AvitoPromotionFilter;
use BaksDev\Avito\Promotion\Entity\Modify\AvitoPromotionEventModify;
use BaksDev\Avito\Promotion\Type\AvitoPromotionUid;
use BaksDev\Avito\Promotion\Type\Event\AvitoPromotionEventUid;
use BaksDev\Core\Entity\EntityEvent;
use BaksDev\Products\Category\Type\Id\CategoryProductUid;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

/** @see AvitoPromotionDTO */
#[ORM\Entity]
#[ORM\Index(columns: ['profile'])]
#[ORM\Index(columns: ['category'])]
#[ORM\Table(name: 'avito_promotion_event')]
class AvitoPromotionEvent extends EntityEvent
{
    /**
     * Идентификатор События рекламного предложения
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: AvitoPromotionEventUid::TYPE, nullable: false)]
    private AvitoPromotionEventUid $id;

    /**
     * main - связь с корнем
     */
    #[Assert\Uuid]
    #[ORM\Column(type: AvitoPromotionUid::TYPE, nullable: false)]
    private AvitoPromotionUid $main;

    /**
     * ID профиля пользователя
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Column(type: UserProfileUid::TYPE, nullable: false)]
    private UserProfileUid $profile;

    /**
     * Идентификатор локальной категории
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Column(type: CategoryProductUid::TYPE, nullable: false)]
    private CategoryProductUid $category;

    /**
     *  Название рекламной компании
     */
    #[Assert\NotBlank]
    #[ORM\Column(type: Types::TEXT, nullable: false)]
    private string $name;

    /**
     * Шаг бюджета
     */
    #[Assert\NotBlank]
    #[Assert\Range(min: 1, max: 100)]
    #[ORM\Column(type: Types::INTEGER, nullable: false)]
    private ?int $budget = null;

    /**
     * Ограничение бюджета
     */
    #[Assert\NotBlank]
    #[Assert\Range(min: 101, max: 1000)]
    #[ORM\Column(type: Types::INTEGER, nullable: false)]
    private ?int $budgetLimit = null;

    /**
     * Дата окончания рекламной компании
     */
    #[Assert\NotBlank]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: false)]
    private ?\DateTimeImmutable $dateEnd = null;

    /**
     * Коллекция свойств для фильтрации и применения услуг продвижения
     */
    #[Assert\Valid]
    #[ORM\OneToMany(targetEntity: AvitoPromotionFilter::class, mappedBy: 'event', cascade: ['all'])]
    private Collection $filters;

    /**
     * Модификатор события
     */
    #[ORM\OneToOne(targetEntity: AvitoPromotionEventModify::class, mappedBy: 'event', cascade: ['all'])]
    private AvitoPromotionEventModify $modify;

    public function __construct()
    {
        $this->id = new AvitoPromotionEventUid();
        $this->modify = new AvitoPromotionEventModify($this);

        $this->filters = new ArrayCollection();
    }

    public function __clone(): void
    {
        $this->id = clone $this->id;
    }

    public function __toString(): string
    {
        return (string)$this->id;
    }

    public function getEvent(): AvitoPromotionEventUid
    {
        return $this->id;
    }

    public function setMain(AvitoPromotion|AvitoPromotionUid $main): void
    {
        $this->main = $main instanceof AvitoPromotion ? $main->getId() : $main;
    }

    public function getMain(): AvitoPromotionUid
    {
        return $this->main;
    }

    public function getDto($dto): mixed
    {
        if ($dto instanceof AvitoPromotionEventInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function setEntity($dto): mixed
    {
        if ($dto->getFilters()->isEmpty())
        {
            return false;
        }

        if ($dto instanceof AvitoPromotionEventInterface)
        {

            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function getFilters(): Collection
    {
        return $this->filters;
    }

    public function getBudget(): int
    {
        return $this->budget;
    }

    public function getBudgetLimit(): ?int
    {
        return $this->budgetLimit;
    }
}
