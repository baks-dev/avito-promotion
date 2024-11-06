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

namespace BaksDev\Avito\Promotion\UseCase\NewEdit\Promotion\Tests;

use BaksDev\Avito\Promotion\Entity\Promotion\AvitoProductPromotion;
use BaksDev\Avito\Promotion\Entity\Promotion\AvitoProductPromotionInterface;
use BaksDev\Avito\Promotion\Type\AvitoPromotionUid;
use BaksDev\Avito\Promotion\UseCase\NewEdit\Promotion\AvitoProductPromotionDTO;
use BaksDev\Avito\Promotion\UseCase\NewEdit\Promotion\AvitoProductPromotionHandler;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

/**
 * @group avito-promotion
 * @group avito-product-promotion
 */
#[When(env: 'test')]
class AvitoProductPromotionNewTest extends KernelTestCase
{
    public static function setUpBeforeClass(): void
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $avitoProductPromotions = $em->getRepository(AvitoProductPromotion::class)
            ->findBy(['profile' => UserProfileUid::TEST]);

        foreach($avitoProductPromotions as $promotion)
        {
            $em->remove($promotion);
        }

        $em->flush();
        $em->clear();
    }

    public function testNew(): void
    {
        $newDTO = new AvitoProductPromotionDTO();
        self::assertInstanceOf(AvitoProductPromotionInterface::class, $newDTO);

        $newDTO->setProduct($product = new ProductUid());
        self::assertSame($product, $newDTO->getProduct());

        $newDTO->setOffer($offer = new ProductOfferConst());
        self::assertSame($offer, $newDTO->getOffer());

        $newDTO->setVariation($variation = new ProductVariationConst());
        self::assertSame($variation, $newDTO->getVariation());

        $newDTO->setModification($modification = new ProductModificationConst());
        self::assertSame($modification, $newDTO->getModification());

        $newDTO->setArticle('new_article');
        self::assertSame('new_article', $newDTO->getArticle());

        $newDTO->setCreated($created = new DateTimeImmutable('now', new DateTimeZone('UTC')));
        self::assertSame($created, $newDTO->getCreated());

        $newDTO->setCompany($company = new AvitoPromotionUid());
        self::assertSame($company, $newDTO->getCompany());

        $newDTO->setProfile($profile = new UserProfileUid());
        self::assertSame($profile, $newDTO->getProfile());

        $newDTO->setBudget(100);
        self::assertSame(100, $newDTO->getBudget());

        /** @var AvitoProductPromotionHandler $handler */
        $handler = self::getContainer()->get(AvitoProductPromotionHandler::class);
        $avitoPromotion = $handler->handle($newDTO);
        self::assertTrue($avitoPromotion instanceof AvitoProductPromotion);
    }
}
