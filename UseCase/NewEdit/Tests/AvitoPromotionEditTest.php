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

namespace BaksDev\Avito\Promotion\UseCase\NewEdit\Tests;

use BaksDev\Avito\Promotion\Controller\Admin\Tests\EditControllerAdminTest;
use BaksDev\Avito\Promotion\Entity\AvitoPromotion;
use BaksDev\Avito\Promotion\Entity\Event\AvitoPromotionEvent;
use BaksDev\Avito\Promotion\Entity\Event\AvitoPromotionEventInterface;
use BaksDev\Avito\Promotion\Entity\Modify\AvitoPromotionEventModify;
use BaksDev\Avito\Promotion\Type\AvitoPromotionUid;
use BaksDev\Avito\Promotion\UseCase\NewEdit\AvitoPromotionDTO;
use BaksDev\Avito\Promotion\UseCase\NewEdit\AvitoPromotionHandler;
use BaksDev\Avito\Promotion\UseCase\NewEdit\Filter\AvitoPromotionFilterDTO;
use BaksDev\Core\Type\Modify\Modify\ModifyActionUpdate;
use BaksDev\Products\Category\Type\Section\Field\Id\CategoryProductSectionFieldUid;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DependsOnClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
#[Group('avito-promotion')]
class AvitoPromotionEditTest extends KernelTestCase
{
    #[DependsOnClass(EditControllerAdminTest::class)]
    public function testEdit(): void
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

        $editDTO = new AvitoPromotionDTO();
        self::assertInstanceOf(AvitoPromotionEventInterface::class, $editDTO);

        /** Гидрируем сущность из переданной DTO */
        $event->getDto($editDTO);

        /**
         * Проверка существующих данных
         */
        self::assertSame('new_promotion_company', $editDTO->getName());

        /** @var AvitoPromotionFilterDTO $filter1 */
        $filter1 = $editDTO->getFilters()->current();
        self::assertTrue($filter1->getProperty()->equals(CategoryProductSectionFieldUid::TEST));
        self::assertSame('R16', $filter1->getValue());
        self::assertSame('AND', $filter1->getPredicate());

        /** @var AvitoPromotionFilterDTO $filter2 */
        $filter2 = $editDTO->getFilters()->last();
        self::assertTrue($filter2->getProperty()->equals(CategoryProductSectionFieldUid::TEST));
        self::assertSame('summer', $filter2->getValue());
        self::assertSame('AND', $filter2->getPredicate());

        /**
         * Добавляем/редактируем/удаляем данные
         */
        $editDTO->removeFilter($filter1);

        $editDTO->setName('edit_promotion_company');
        self::assertSame('edit_promotion_company', $editDTO->getName());

        $filter3 = new AvitoPromotionFilterDTO();

        $filter3->setProperty(new CategoryProductSectionFieldUid());
        $filter3->setValue('не шипованная');
        $filter3->setPredicate('OR');

        /** Добавляем в коллекцию новый фильтр */
        $editDTO->addFilter($filter3);

        /** @var AvitoPromotionHandler $handler */
        $handler = $container->get(AvitoPromotionHandler::class);
        $avitoPromotion = $handler->handle($editDTO);
        self::assertTrue($avitoPromotion instanceof AvitoPromotion);

        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);

        $modifier = $em->getRepository(AvitoPromotionEventModify::class)
            ->find($avitoPromotion->getEvent());

        self::assertTrue($modifier->equals(ModifyActionUpdate::ACTION));
    }
}
