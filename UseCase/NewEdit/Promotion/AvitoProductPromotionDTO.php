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

namespace BaksDev\Avito\Promotion\UseCase\NewEdit\Promotion;

use BaksDev\Avito\Promotion\Entity\Promotion\AvitoProductPromotionInterface;
use BaksDev\Avito\Promotion\Type\AvitoPromotionUid;
use BaksDev\Avito\Promotion\Type\Promotion\AvitoProductPromotionUid;
use BaksDev\Products\Category\Type\Section\Field\Id\CategoryProductSectionFieldUid;
use BaksDev\Products\Product\Entity\Product;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use DateTimeImmutable;
use Symfony\Component\Validator\Constraints as Assert;

/** @see AvitoProductPromotion */
final class AvitoProductPromotionDTO implements AvitoProductPromotionInterface
{
    /** Идентификатор события */
    #[Assert\Uuid]
    private ?AvitoProductPromotionUid $id = null;

    /** ID продукта (не уникальное) */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private ProductUid $product;

    #[Assert\NotBlank]
    #[Assert\Uuid]
    /** Константа ТП */
    private ?ProductOfferConst $offer = null;

    #[Assert\Uuid]
    /** Константа множественного варианта */
    private ?ProductVariationConst $variation = null;

    #[Assert\Uuid]
    /** Константа модификации множественного варианта */
    private ?ProductModificationConst $modification = null;

    #[Assert\Uuid]
    /** Константа модификации множественного варианта */
    private ?CategoryProductSectionFieldUid $property = null;

    /** Артикул продукта */
    #[Assert\NotBlank]
    private string $article;

    /** Рекламная компания */
    #[Assert\Uuid]
    #[Assert\NotBlank]
    private AvitoPromotionUid $company;

    /** Профиль пользователя */
    #[Assert\NotBlank]
    private UserProfileUid $profile;

    /** Рекламный бюджет на продукт */
    #[Assert\NotBlank]
    private int $budget;

    /** Дата создания рекламного продукта */
    #[Assert\NotBlank]
    private DateTimeImmutable $created;

    public function __construct()
    {
        $this->id = clone new AvitoProductPromotionUid();
    }

    public function getProduct(): ProductUid
    {
        return $this->product;
    }

    public function setProduct(Product|ProductUid|string $product): void
    {
        if($product instanceof Product)
        {
            $product = $product->getId();
        }

        if(is_string($product))
        {
            $product = new ProductUid($product);
        }

        $this->product = $product;
    }

    public function getId(): ?AvitoProductPromotionUid
    {
        return $this->id;
    }

    public function getOffer(): ProductOfferConst
    {
        return $this->offer;
    }

    public function setOffer(ProductOfferConst|string|null $offer): void
    {
        if(is_string($offer))
        {
            $offer = new ProductOfferConst($offer);
        }

        $this->offer = $offer;
    }

    public function getVariation(): ?ProductVariationConst
    {
        return $this->variation;
    }

    public function setVariation(ProductVariationConst|string|null $variation): void
    {
        if(is_string($variation))
        {
            $variation = new ProductVariationConst($variation);
        }

        $this->variation = $variation;
    }

    public function getModification(): ?ProductModificationConst
    {
        return $this->modification;
    }

    public function setModification(ProductModificationConst|string|null $modification): void
    {
        if(is_string($modification))
        {
            $modification = new ProductModificationConst($modification);
        }

        $this->modification = $modification;
    }

    public function getProperty(): ?CategoryProductSectionFieldUid
    {
        return $this->property;
    }

    public function setProperty(CategoryProductSectionFieldUid|string|null $property): void
    {
        if(is_string($property))
        {
            $property = new CategoryProductSectionFieldUid($property);
        }

        $this->property = $property;
    }

    public function getArticle(): string
    {
        return $this->article;
    }

    public function setArticle(string $article): void
    {
        $this->article = $article;
    }

    public function getCompany(): AvitoPromotionUid
    {
        return $this->company;
    }

    public function setCompany(AvitoPromotionUid|string $company): void
    {
        if(is_string($company))
        {
            $company = new AvitoPromotionUid($company);
        }

        $this->company = $company;
    }

    public function getBudget(): int
    {
        return $this->budget;
    }

    public function setBudget(int $budget): void
    {
        $this->budget = $budget;
    }

    public function getCreated(): DateTimeImmutable
    {
        return $this->created;
    }

    public function setCreated(DateTimeImmutable|string $created): void
    {
        if(is_string($created))
        {
            $created = new DateTimeImmutable($created);
        }

        $this->created = $created;
    }

    public function getProfile(): UserProfileUid
    {
        return $this->profile;
    }

    public function setProfile(UserProfileUid|string $profile): void
    {
        if(is_string($profile))
        {
            $profile = new UserProfileUid($profile);
        }

        $this->profile = $profile;
    }


}
