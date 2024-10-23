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

namespace BaksDev\Avito\Promotion\Entity\Filter;

use BaksDev\Avito\Promotion\Entity\Event\AvitoPromotionEvent;
use BaksDev\Avito\Promotion\Type\Filter\AvitoPromotionFilterUid;
use BaksDev\Core\Entity\EntityEvent;
use BaksDev\Products\Category\Type\Section\Field\Id\CategoryProductSectionFieldUid;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Index(columns: ['property'])]
#[ORM\Table(name: 'avito_promotion_filter')]
class AvitoPromotionFilter extends EntityEvent
{
    /**
     * ID
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: AvitoPromotionFilterUid::TYPE, nullable: false)]
    private AvitoPromotionFilterUid $id;

    /**
     * Идентификатор события
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\ManyToOne(targetEntity: AvitoPromotionEvent::class, inversedBy: 'filters')]
    #[ORM\JoinColumn(name: 'event', referencedColumnName: 'id')]
    private AvitoPromotionEvent $event;

    /**
     * Идентификатор свойства продукта
     */
    #[Assert\Uuid]
    #[ORM\Column(type: CategoryProductSectionFieldUid::TYPE, nullable: false)]
    private CategoryProductSectionFieldUid $property;

    /**
     * Значение свойства продукта
     */
    #[Assert\NotBlank]
    #[ORM\Column(type: Types::TEXT, nullable: false)]
    private string $value;

    /**
     * Предикат
     */
    #[Assert\NotBlank]
    #[ORM\Column(type: Types::TEXT, nullable: false)]
    private string $predicate;

    public function __construct(AvitoPromotionEvent $event)
    {
        $this->id = new AvitoPromotionFilterUid();
        $this->event = $event;
    }

    public function __clone()
    {
        $this->id = clone $this->id;
    }

    public function getId(): AvitoPromotionFilterUid
    {
        return $this->id;
    }

    public function getDto($dto): mixed
    {
        $dto = is_string($dto) && class_exists($dto) ? new $dto() : $dto;

        if($dto instanceof AvitoPromotionFilterInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function setEntity($dto): mixed
    {
        if($dto instanceof AvitoPromotionFilterInterface || $dto instanceof self)
        {
            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }
}
