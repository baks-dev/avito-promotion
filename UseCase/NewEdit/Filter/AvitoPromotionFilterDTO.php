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

namespace BaksDev\Avito\Promotion\UseCase\NewEdit\Filter;

use BaksDev\Avito\Promotion\Entity\Filter\AvitoPromotionFilterInterface;
use BaksDev\Avito\Promotion\Type\Filter\AvitoPromotionFilterUid;
use BaksDev\Products\Category\Type\Section\Field\Id\CategoryProductSectionFieldUid;
use Symfony\Component\Validator\Constraints as Assert;

/** @see AvitoPromotionFilter */
final class AvitoPromotionFilterDTO implements AvitoPromotionFilterInterface
{
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private AvitoPromotionFilterUid $id;

    /**
     * Связь на свойство продукта
     */
    #[Assert\Uuid]
    private ?CategoryProductSectionFieldUid $property = null;

    #[Assert\NotBlank]
    private ?string $value = null;

    #[Assert\NotBlank]
    private ?string $predicate = null;

    public function __construct()
    {
        $this->id = clone new AvitoPromotionFilterUid();
    }

    public function getId(): AvitoPromotionFilterUid
    {
        return $this->id;
    }

    public function setId(AvitoPromotionFilterUid $id): void
    {
        $this->id = $id;
    }

    public function getProperty(): ?CategoryProductSectionFieldUid
    {
        return $this->property;
    }

    public function setProperty(?CategoryProductSectionFieldUid $property): void
    {
        $this->property = $property;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): void
    {
        $this->value = $value;
    }

    public function getPredicate(): ?string
    {
        return $this->predicate;
    }

    public function setPredicate(?string $predicate): void
    {
        $this->predicate = $predicate;
    }

}
