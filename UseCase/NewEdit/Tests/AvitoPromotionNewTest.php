<?php

namespace BaksDev\Avito\Promotion\UseCase\NewEdit\Tests;

use BaksDev\Avito\Promotion\Entity\AvitoPromotion;
use BaksDev\Avito\Promotion\Entity\Event\AvitoPromotionEvent;
use BaksDev\Avito\Promotion\Entity\Event\AvitoPromotionEventInterface;
use BaksDev\Avito\Promotion\Entity\Filter\AvitoPromotionFilterInterface;
use BaksDev\Avito\Promotion\Entity\Modify\AvitoPromotionEventModify;
use BaksDev\Avito\Promotion\Entity\Promotion\AvitoProductPromotion;
use BaksDev\Avito\Promotion\Type\AvitoPromotionUid;
use BaksDev\Avito\Promotion\UseCase\NewEdit\AvitoPromotionDTO;
use BaksDev\Avito\Promotion\UseCase\NewEdit\AvitoPromotionHandler;
use BaksDev\Avito\Promotion\UseCase\NewEdit\Filter\AvitoPromotionFilterDTO;
use BaksDev\Core\Type\Modify\Modify\ModifyActionNew;
use BaksDev\Products\Category\Type\Id\CategoryProductUid;
use BaksDev\Products\Category\Type\Section\Field\Id\CategoryProductSectionFieldUid;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

/**
 * @group avito-promotion
 *
 * @depends BaksDev\Avito\Promotion\Controller\Admin\Tests\NewControllerTest::class
 */
#[When(env: 'test')]
class AvitoPromotionNewTest extends KernelTestCase
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
        $newDTO = new AvitoPromotionDTO();
        self::assertInstanceOf(AvitoPromotionEventInterface::class, $newDTO);

        $newDTO->setProfile($profile = new UserProfileUid());
        self::assertSame($profile, $newDTO->getProfile());

        $newDTO->setCategory($category = new CategoryProductUid());
        self::assertSame($category, $newDTO->getCategory());

        $newDTO->setName('new_promotion_company');
        self::assertSame('new_promotion_company', $newDTO->getName());

        $newDTO->setBudget(100);
        self::assertSame(100, $newDTO->getBudget());

        $newDTO->setBudgetLimit(1000);
        self::assertSame(1000, $newDTO->getBudgetLimit());

        $newDTO->setDateEnd($date = new \DateTimeImmutable('now'));
        self::assertSame($date, $newDTO->getDateEnd());

        /** Фильтры для рекламной компании */
        $filter1 = new AvitoPromotionFilterDTO();
        self::assertInstanceOf(AvitoPromotionFilterInterface::class, $filter1);

        $filter1->setProperty($property = new CategoryProductSectionFieldUid());
        self::assertSame($property, $filter1->getProperty());

        $filter1->setValue('R16');
        self::assertSame('R16', $filter1->getValue());

        $filter1->setPredicate('AND');
        self::assertSame('AND', $filter1->getPredicate());

        $filter2 = new AvitoPromotionFilterDTO();
        self::assertInstanceOf(AvitoPromotionFilterInterface::class, $filter2);

        $filter2->setProperty($property = new CategoryProductSectionFieldUid());
        self::assertSame($property, $filter2->getProperty());

        $filter2->setValue('summer');
        self::assertSame('summer', $filter2->getValue());

        $filter2->setPredicate('AND');
        self::assertSame('AND', $filter2->getPredicate());

        /** Добавляем все фильтры */
        $newDTO->addFilter($filter1);
        $newDTO->addFilter($filter2);

        $container = self::getContainer();

        /** @var AvitoPromotionHandler $handler */
        $handler = $container->get(AvitoPromotionHandler::class);
        $avitoPromotion = $handler->handle($newDTO);
        self::assertTrue($avitoPromotion instanceof AvitoPromotion);

        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);

        $modifier = $em->getRepository(AvitoPromotionEventModify::class)
            ->find($avitoPromotion->getEvent());

        self::assertTrue($modifier->equals(ModifyActionNew::ACTION));
    }
}
