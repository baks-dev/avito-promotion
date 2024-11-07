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

namespace BaksDev\Avito\Promotion\Messenger\Schedules\CreateAvitoProductPromotion;

use BaksDev\Avito\Promotion\Type\AvitoPromotionUid;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;

/** @see CreateAvitoProductPromotionHandler */
final readonly class CreateAvitoProductPromotionMessage
{
    private UserProfileUid $profile;

    private ProductUid $product;

    private ProductOfferConst $offer;

    private ProductVariationConst $variation;

    private ProductModificationConst $modification;

    private AvitoPromotionUid $promotion;

    private string $article;

    private int $ordersCount;

    public function __construct(
        AvitoPromotionUid|string $promotion,
        UserProfileUid|string $profile,
        ProductUid|string $product,
        ProductOfferConst|string $offer,
        ProductVariationConst|string $variation,
        ProductModificationConst|string $modification,
        string $article,
        int $ordersCount,
    )
    {
        if(is_string($promotion))
        {
            $promotion = new AvitoPromotionUid($promotion);
        }

        if(is_string($profile))
        {
            $profile = new UserProfileUid($profile);
        }

        if(is_string($product))
        {
            $product = new ProductUid($product);
        }

        if(is_string($offer))
        {
            $offer = new ProductOfferConst($offer);
        }

        if(is_string($variation))
        {
            $variation = new ProductVariationConst($variation);
        }

        if(is_string($modification))
        {
            $modification = new ProductModificationConst($modification);
        }

        $this->promotion = $promotion;
        $this->profile = $profile;

        $this->product = $product;
        $this->offer = $offer;
        $this->variation = $variation;
        $this->modification = $modification;

        $this->article = $article;
        $this->ordersCount = $ordersCount;
    }

    public function getProfile(): UserProfileUid
    {
        return $this->profile;
    }

    public function getProduct(): ProductUid
    {
        return $this->product;
    }

    public function getOffer(): ProductOfferConst
    {
        return $this->offer;
    }

    public function getVariation(): ProductVariationConst
    {
        return $this->variation;
    }

    public function getModification(): ProductModificationConst
    {
        return $this->modification;
    }

    public function getPromotion(): AvitoPromotionUid
    {
        return $this->promotion;
    }

    public function getOrdersCount(): false|int
    {
        return $this->ordersCount;
    }

    public function getArticle(): string
    {
        return $this->article;
    }
}
