<?php

namespace BaksDev\Avito\Promotion\UseCase\Delete\Tests;

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
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

/**
 * @group avito-promotion
 *
 * @depends BaksDev\Avito\Promotion\Controller\Admin\Tests\DeleteControllerTest::class
 */
#[When(env: 'test')]
class AvitoPromotionDeleteTest extends KernelTestCase
{
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

    public static function tearDownAfterClass(): void
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $avitoPromotionEvents = $em->getRepository(AvitoPromotionEvent::class)
            ->findBy(['profile' => UserProfileUid::TEST]);

        foreach ($avitoPromotionEvents as $event)
        {
            $em->remove($event);
        }

        $em->flush();
        $em->clear();
    }
}
