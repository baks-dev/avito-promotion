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
 */

declare(strict_types=1);

namespace BaksDev\Avito\Promotion\UseCase\NewEdit;

use BaksDev\Avito\Promotion\Entity\Event\AvitoPromotionEventInterface;
use BaksDev\Avito\Promotion\Type\Event\AvitoPromotionEventUid;
use BaksDev\Avito\Promotion\UseCase\NewEdit\Filter\AvitoPromotionFilterDTO;
use BaksDev\Products\Category\Type\Id\CategoryProductUid;
use BaksDev\Products\Category\Type\Section\Field\Id\CategoryProductSectionFieldUid;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

/** @see AvitoPromotionEvent */
final class AvitoPromotionDTO implements AvitoPromotionEventInterface
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
     * Идентификатор локальной категории
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private ?CategoryProductUid $category = null;

    /**
     * Название рекламной компании
     */
    #[Assert\NotBlank]
    private ?string $name = null;

    /** Шаг бюджета */
    #[Assert\NotBlank]
    private int $budget;

    /** Ограничение бюджета */
    #[Assert\NotBlank]
    private int $budgetLimit;

    /** Дата окончания рекламной компании */
    #[Assert\NotBlank]
    private DateTimeImmutable $dateEnd;

    /**
     * Коллекция свойств для фильтрации и применения услуг продвижения
     */
    #[Assert\Valid]
    #[Assert\Count(min: 1)]
    private ArrayCollection $filters;

    // Вспомогательные свойства для формы - не записываются в БД

    // свойства для добавления элемента в коллекцию
    private ?CategoryProductSectionFieldUid $preProperty = null;

    private ?string $preValue = null;

    private ?string $predicatePrototype = 'AND';

    // информация об элементе коллекции для рендеринга в форме
    private ?string $categoryName = null;

    private ArrayCollection $filterValues;

    public function __construct()
    {
        $this->filters = new ArrayCollection();
        $this->filterValues = new ArrayCollection();
        $this->dateEnd = new DateTimeImmutable();
    }

    public function setEvent(AvitoPromotionEventUid $id): void
    {
        $this->id = $id;
    }

    public function getEvent(): ?AvitoPromotionEventUid
    {
        return $this->id;
    }

    public function getProfile(): UserProfileUid
    {
        return $this->profile;
    }

    public function setProfile(UserProfileUid $profile): void
    {
        $this->profile = $profile;
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getCategory(): ?CategoryProductUid
    {
        return $this->category;
    }

    public function setCategory(?CategoryProductUid $category): void
    {
        $this->category = $category;
    }

    public function getDateEnd(): DateTimeImmutable
    {
        return $this->dateEnd;
    }

    public function setDateEnd(DateTimeImmutable $date): void
    {
        $this->dateEnd = $date;
    }

    public function getBudgetLimit(): int
    {
        return $this->budgetLimit;
    }

    public function setBudgetLimit(int $budgetLimit): void
    {
        $this->budgetLimit = $budgetLimit;
    }

    public function getBudget(): int
    {
        return $this->budget;
    }

    public function setBudget(int $budget): void
    {
        $this->budget = $budget;
    }

    // Вспомогательные методы

    public function getPreProperty(): ?CategoryProductSectionFieldUid
    {
        return $this->preProperty;
    }

    public function setPreProperty(?CategoryProductSectionFieldUid $preProperty): void
    {
        $this->preProperty = $preProperty;
    }

    public function getPreValue(): ?string
    {
        return $this->preValue;
    }

    public function setPreValue(?string $preValue): void
    {
        $this->preValue = $preValue;
    }

    public function getPredicatePrototype(): ?string
    {
        return $this->predicatePrototype;
    }

    public function setPredicatePrototype(?string $predicatePrototype): void
    {
        $this->predicatePrototype = $predicatePrototype;
    }

    public function getCategoryName(): ?string
    {
        return $this->categoryName;
    }

    public function setCategoryName(?string $categoryName): void
    {
        $this->categoryName = $categoryName;
    }

    public function getFilterValues(): ArrayCollection
    {
        return $this->filterValues;
    }

    public function addFilterValues($key, $value): void
    {
        $this->filterValues->set($key, $value);
    }
}
