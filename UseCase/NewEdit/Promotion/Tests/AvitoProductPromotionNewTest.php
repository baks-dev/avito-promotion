<?php

namespace BaksDev\Avito\Promotion\UseCase\NewEdit\Promotion\Tests;

use BaksDev\Avito\Promotion\Entity\Promotion\AvitoProductPromotion;
use BaksDev\Avito\Promotion\Entity\Promotion\AvitoProductPromotionInterface;
use BaksDev\Avito\Promotion\Type\AvitoPromotionUid;
use BaksDev\Avito\Promotion\UseCase\NewEdit\Promotion\AvitoProductPromotionDTO;
use BaksDev\Avito\Promotion\UseCase\NewEdit\Promotion\AvitoProductPromotionHandler;
use BaksDev\Products\Category\Type\Section\Field\Id\CategoryProductSectionFieldUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

/**
 * @group avito-promotion
 * @group avito-product-promotion
 *
 */
#[When(env: 'test')]
class AvitoProductPromotionNewTest extends KernelTestCase
{
    public static function setUpBeforeClass(): void
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $avitoProductPromotions = $em->getRepository(AvitoProductPromotion::class)
            ->findBy(['company' => AvitoPromotionUid::TEST]);

        foreach ($avitoProductPromotions as $promotion)
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

        $newDTO->setOffer($offer = new ProductOfferConst());
        self::assertSame($offer, $newDTO->getOffer());

        $newDTO->setVariation($variation = new ProductVariationConst());
        self::assertSame($variation, $newDTO->getVariation());

        $newDTO->setModification($modification = new ProductModificationConst());
        self::assertSame($modification, $newDTO->getModification());

        $newDTO->setProperty($property = new CategoryProductSectionFieldUid());
        self::assertSame($property, $newDTO->getProperty());

        $newDTO->setArticle('new_article');
        self::assertSame('new_article', $newDTO->getArticle());

        $newDTO->setCreated($created = new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
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
