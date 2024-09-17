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

namespace BaksDev\Avito\Promotion\Entity\Promotion;

use BaksDev\Avito\Promotion\Type\AvitoPromotionUid;
use BaksDev\Avito\Promotion\Type\Promotion\AvitoProductPromotionUid;
use BaksDev\Core\Entity\EntityState;
use BaksDev\Products\Category\Type\Section\Field\Id\CategoryProductSectionFieldUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

/** @see AvitoProductPromotionDTO */
#[ORM\Entity]
#[ORM\Index(columns: ['company'])]
#[ORM\Index(columns: ['profile'])]
#[ORM\Table(name: 'avito_promotion_product')]
class AvitoProductPromotion extends EntityState
{
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: AvitoProductPromotionUid::TYPE)]
    private AvitoProductPromotionUid $id;

    #[ORM\Column(type: Types::STRING, nullable: false)]
    private string $article;

    /** Константа ТП */
    #[ORM\Column(type: ProductOfferConst::TYPE, nullable: false)]
    private ?ProductOfferConst $offer;

    /** Константа множественного варианта */
    #[ORM\Column(type: ProductVariationConst::TYPE, nullable: true)]
    private ?ProductVariationConst $variation = null;

    /** Константа модификации множественного варианта */
    #[ORM\Column(type: ProductModificationConst::TYPE, nullable: true)]
    private ?ProductModificationConst $modification = null;

    /** Константа модификации множественного варианта */
    #[ORM\Column(type: CategoryProductSectionFieldUid::TYPE, nullable: true)]
    private ?CategoryProductSectionFieldUid $property = null;

    /**  */
    #[ORM\Column(type: AvitoPromotionUid::TYPE, nullable: false)]
    private AvitoPromotionUid $company;

    /**  */
    #[ORM\Column(type: UserProfileUid::TYPE, nullable: false)]
    private UserProfileUid $profile;

    /**  */
    #[ORM\Column(type: Types::INTEGER, nullable: false)]
    private int $budget;

    #[Assert\NotBlank]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: false)]
    private \DateTimeImmutable $created;

    public function __construct()
    {
        $this->id = new AvitoProductPromotionUid();
    }

    public function __toString(): string
    {
        return (string)$this->id;
    }

    public function getId(): AvitoProductPromotionUid
    {
        return $this->id;
    }

    /** Гидрирует переданную DTO, вызывая ее сеттеры */
    public function getDto($dto): mixed
    {
        if ($dto instanceof AvitoProductPromotionInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    /** Гидрирует сущность переданной DTO */
    public function setEntity($dto): mixed
    {
        if ($dto instanceof AvitoProductPromotionInterface || $dto instanceof self)
        {
            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function getBudget(): int
    {
        return $this->budget;
    }

    public function getProfile(): UserProfileUid
    {
        return $this->profile;
    }

    public function getArticle(): string
    {
        return $this->article;
    }

    public function getCreated(): \DateTimeImmutable
    {
        return $this->created;
    }
}
