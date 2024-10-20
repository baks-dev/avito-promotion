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

namespace BaksDev\Avito\Promotion\UseCase\NewEdit\Promotion\Tests;

use BaksDev\Avito\Promotion\Entity\Promotion\AvitoProductPromotion;
use BaksDev\Avito\Promotion\UseCase\NewEdit\Promotion\AvitoProductPromotionDTO;
use BaksDev\Avito\Promotion\UseCase\NewEdit\Promotion\AvitoProductPromotionHandler;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use DateTimeImmutable;
use DateTimeZone;
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
    public static function tearDownAfterClass(): void
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

    public function testEdit(): void
    {
        $container = self::getContainer();
        $em = $container->get(EntityManagerInterface::class);

        /**
         * @var AvitoProductPromotion $productPromotion
         */
        $productPromotion = $em->getRepository(AvitoProductPromotion::class)->findOneBy([
            'profile' => UserProfileUid::TEST,
        ]);

        self::assertNotNull($productPromotion);

        $editDTO = new AvitoProductPromotionDTO();

        $productPromotion->getDto($editDTO);

        // обновляем дату
        $editDTO->setCreated($created = new DateTimeImmutable('now', new DateTimeZone('UTC')));
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
}
