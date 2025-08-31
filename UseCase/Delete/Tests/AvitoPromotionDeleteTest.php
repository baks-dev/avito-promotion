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

namespace BaksDev\Avito\Promotion\UseCase\Delete\Tests;

use BaksDev\Avito\Promotion\Controller\Admin\Tests\DeleteAdminControllerTest;
use BaksDev\Avito\Promotion\Entity\AvitoPromotion;
use BaksDev\Avito\Promotion\Entity\Event\AvitoPromotionEvent;
use BaksDev\Avito\Promotion\Entity\Event\AvitoPromotionEventInterface;
use BaksDev\Avito\Promotion\Type\AvitoPromotionUid;
use BaksDev\Avito\Promotion\UseCase\Delete\AvitoPromotionDeleteDTO;
use BaksDev\Avito\Promotion\UseCase\Delete\AvitoPromotionDeleteHandler;
use BaksDev\Avito\Promotion\UseCase\NewEdit\Filter\AvitoPromotionFilterDTO;
use BaksDev\Products\Category\Type\Section\Field\Id\CategoryProductSectionFieldUid;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DependsOnClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
#[Group('avito-promotion')]
class AvitoPromotionDeleteTest extends KernelTestCase
{
    public static function tearDownAfterClass(): void
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $avitoPromotionEvents = $em->getRepository(AvitoPromotionEvent::class)
            ->findBy(['profile' => UserProfileUid::TEST]);

        foreach($avitoPromotionEvents as $event)
        {
            $em->remove($event);
        }

        $em->flush();
        $em->clear();
    }

    #[DependsOnClass(DeleteAdminControllerTest::class)]
    public function testDelete(): void
    {
        $container = self::getContainer();
        $em = $container->get(EntityManagerInterface::class);

        /**
         * Корень
         *
         * @var AvitoPromotion $main
         */
        $main = $em
            ->getRepository(AvitoPromotion::class)
            ->find(AvitoPromotionUid::TEST);

        self::assertNotNull($main);

        /**
         * Активное событие события
         *
         * @var AvitoPromotionEvent $event
         */
        $event = $em
            ->getRepository(AvitoPromotionEvent::class)
            ->find($main->getEvent());

        self::assertNotNull($event);

        $deleteCommand = new AvitoPromotionDeleteDTO();
        self::assertInstanceOf(AvitoPromotionEventInterface::class, $deleteCommand);

        /** Гидрируем сущность из переданной DTO */
        $event->getDto($deleteCommand);

        /** Проверка профиля */
        self::assertTrue($deleteCommand->getProfile()->equals(UserProfileUid::TEST));
        self::assertSame('edit_promotion_company', $deleteCommand->getName());

        /**
         * Проверка существующих фильтров
         */
        /** @var AvitoPromotionFilterDTO $filter1 */
        $filter1 = $deleteCommand->getFilters()->current();
        self::assertTrue($filter1->getProperty()->equals(CategoryProductSectionFieldUid::TEST));
        self::assertSame('summer', $filter1->getValue());
        self::assertSame('AND', $filter1->getPredicate());

        /** @var AvitoPromotionFilterDTO $filter2 */
        $filter2 = $deleteCommand->getFilters()->last();
        self::assertTrue($filter2->getProperty()->equals(CategoryProductSectionFieldUid::TEST));
        self::assertSame('не шипованная', $filter2->getValue());
        self::assertSame('OR', $filter2->getPredicate());

        /** @var AvitoPromotionDeleteHandler $handler */
        $handler = $container->get(AvitoPromotionDeleteHandler::class);
        $avitoPromotion = $handler->handle($deleteCommand);
        self::assertTrue($avitoPromotion instanceof AvitoPromotion);

        /** Проверка того, что корень был удален*/
        $main = $em
            ->getRepository(AvitoPromotion::class)
            ->find(AvitoPromotionUid::TEST);

        self::assertNull($main);
    }
}
