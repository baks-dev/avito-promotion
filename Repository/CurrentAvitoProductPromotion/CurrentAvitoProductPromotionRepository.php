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

namespace BaksDev\Avito\Promotion\Repository\CurrentAvitoProductPromotion;

use BaksDev\Avito\Promotion\Entity\Promotion\AvitoProductPromotion;
use BaksDev\Core\Doctrine\ORMQueryBuilder;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use InvalidArgumentException;

final class CurrentAvitoProductPromotionRepository implements CurrentAvitoProductPromotionInterface
{
    private UserProfileUid|false $profile = false;

    private ProductUid|false $product = false;

    private ProductOfferConst|false $offer = false;

    private ProductVariationConst|false $variation = false;

    private ProductModificationConst|false $modification = false;

    public function __construct(
        readonly private ORMQueryBuilder $ORMQueryBuilder
    ) {}

    /** Идентификатор профиля пользователя */
    public function forProfile(UserProfileUid|string $profile): self
    {
        if(is_string($profile))
        {
            $profile = new UserProfileUid($profile);
        }

        $this->profile = $profile;

        return $this;
    }

    /** Идентификатор продукта */
    public function forProduct(ProductUid|string $product): self
    {
        if(is_string($product))
        {
            $product = new ProductUid($product);
        }

        $this->product = $product;
        return $this;

    }

    /** Константа ТОРГОВОГО ПРЕДЛОЖЕНИЯ */
    public function forOffer(ProductOfferConst|string $offer): self
    {
        if(is_string($offer))
        {
            $offer = new ProductOfferConst($offer);
        }

        $this->offer = $offer;
        return $this;

    }

    /** Константа ВАРИАНТА торгового предложения */
    public function forVariation(ProductVariationConst|string $variation): self
    {
        if(is_string($variation))
        {
            $variation = new ProductVariationConst($variation);
        }

        $this->variation = $variation;
        return $this;

    }

    /** Константа МОДИФИКАЦИИ варианта торгового предложения */
    public function forModification(ProductModificationConst|string $modification): self
    {
        if(is_string($modification))
        {
            $modification = new ProductModificationConst($modification);
        }

        $this->modification = $modification;
        return $this;

    }

    /**
     * Метод находит уникальный рекламный продукт по параметрам: profile, product, offer, variation, modification
     */
    public function find(): AvitoProductPromotion|false
    {
        if(false === $this->profile)
        {
            throw new InvalidArgumentException('Пропущен обязательный параметр запроса: profile');
        }

        if(false === $this->product)
        {
            throw new InvalidArgumentException('Пропущен обязательный параметр запроса: product');
        }

        $orm = $this->ORMQueryBuilder->createQueryBuilder(self::class);

        $orm
            ->select('avito_promotion_product')
            ->from(AvitoProductPromotion::class, 'avito_promotion_product')
            ->where('avito_promotion_product.profile = :profile')
            ->andWhere('avito_promotion_product.product = :product')
            ->setParameter('profile', $this->profile, UserProfileUid::TYPE)
            ->setParameter('product', $this->product, ProductUid::TYPE);

        if(false === $this->offer)
        {
            $orm->andWhere('avito_promotion_product.offer IS NULL');
        }
        else
        {
            $orm
                ->andWhere('avito_promotion_product.offer = :offer')
                ->setParameter('offer', $this->offer, ProductOfferConst::TYPE);
        }

        if(false === $this->variation)
        {
            $orm->andWhere('avito_promotion_product.variation = :variation');
        }
        else
        {
            $orm
                ->andWhere('avito_promotion_product.variation = :variation')
                ->setParameter('variation', $this->variation, ProductVariationConst::TYPE);
        }

        if(false === $this->modification)
        {
            $orm->andWhere('avito_promotion_product.modification = :modification');
        }
        else
        {
            $orm
                ->andWhere('avito_promotion_product.modification = :modification')
                ->setParameter('modification', $this->modification, ProductModificationConst::TYPE);
        }

        $result = $orm->getOneOrNullResult();

        return $result ?? false;
    }
}