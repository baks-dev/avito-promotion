<?php

namespace BaksDev\Avito\Promotion\UseCase\NewEdit\Promotion\Tests;

use BaksDev\Avito\Promotion\Entity\Promotion\AvitoProductPromotion;
use BaksDev\Avito\Promotion\Type\AvitoPromotionUid;
use BaksDev\Avito\Promotion\UseCase\NewEdit\Promotion\AvitoProductPromotionDTO;
use BaksDev\Avito\Promotion\UseCase\NewEdit\Promotion\AvitoProductPromotionHandler;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

/**
 * @group avito-promotion
 * @group avito-product-promotion
 *
 * @depends BaksDev\Avito\Promotion\UseCase\NewEdit\Promotion\Tests\AvitoProductPromotionNewTest::class
 */
#[When(env: 'test')]
class AvitoProductPromotionEditTest extends KernelTestCase
{
    public function testEdit(): void
    {
        $container = self::getContainer();
        $em = $container->get(EntityManagerInterface::class);

        /**
         * @var AvitoProductPromotion $productPromotion
         */
        $productPromotion = $em->getRepository(AvitoProductPromotion::class)->findOneBy([
            'offer' => ProductOfferConst::TEST,
            'variation' => ProductVariationConst::TEST,
            'modification' => ProductModificationConst::TEST,
        ]);

        self::assertNotNull($productPromotion);

        $editDTO = new AvitoProductPromotionDTO();

        $productPromotion->getDto($editDTO);

        // обновляем дату
        $editDTO->setCreated($created = new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
        self::assertSame($created, $editDTO->getCreated());

        // обновляем бюджет
        $updateBudget = $editDTO->getBudget() + 100;

        $editDTO->setBudget($updateBudget);
        self::assertSame($updateBudget, $editDTO->getBudget());

        /** @var AvitoProductPromotionHandler $handler */
        $handler = $container->get(AvitoProductPromotionHandler::class);
        $avitoPromotion = $handler->handle($editDTO);
        self::assertTrue($avitoPromotion instanceof AvitoProductPromotion);
    }

    public static function tearDownAfterClass(): void
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
}
