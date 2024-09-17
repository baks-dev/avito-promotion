<?php
/*
 *  Copyright 2022.  Baks.dev <admin@baks.dev>
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *  http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *   limitations under the License.
 *
 */

namespace BaksDev\Avito\Promotion\Controller\Admin\Tests;

use BaksDev\Avito\Promotion\Entity\AvitoPromotion;
use BaksDev\Avito\Promotion\Type\AvitoPromotionUid;
use BaksDev\Users\User\Tests\TestUserAccount;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

/**
 * @group avito-promotion
 * @group avito-promotion-edit
 *
 * @depends BaksDev\Avito\Promotion\UseCase\NewEdit\Tests\AvitoPromotionNewTest::class
 */
#[When(env: 'test')]
final class EditControllerTest extends WebTestCase
{
    private const string ROLE = 'ROLE_AVITO_PROMOTION_EDIT';

    private static string $url = '/admin/avito-promotion/company/edit/%s';

    public static function setUpBeforeClass(): void
    {
        $container = self::getContainer();

        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);

        /**
         * Находим корень
         *
         * @var AvitoPromotion $main
         */
        $main = $em
            ->getRepository(AvitoPromotion::class)
            ->find(AvitoPromotionUid::TEST);

        self::assertNotNull($main);

        /**
         * Подставляем активное событие в URL
         */
        self::$url = sprintf(self::$url, $main->getEvent());

        $em->clear();
    }

    /** Доступ по роли */
    public function testRoleSuccessful(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        foreach (TestUserAccount::getDevice() as $device)
        {
            $client->setServerParameter('HTTP_USER_AGENT', $device);

            $usr = TestUserAccount::getModer(self::ROLE);

            $client->loginUser($usr, 'user');

            $client->request('GET', self::$url);

            self::assertResponseIsSuccessful();
        }

        self::assertTrue(true);
    }

    /** Доступ по роли ROLE_ADMIN */
    public function testRoleAdminSuccessful(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        foreach (TestUserAccount::getDevice() as $device)
        {
            $client->setServerParameter('HTTP_USER_AGENT', $device);
            $usr = TestUserAccount::getAdmin();

            $client->loginUser($usr, 'user');

            $client->request('GET', self::$url);

            self::assertResponseIsSuccessful();
        }

        self::assertTrue(true);
    }

    /** Доступ по роли ROLE_USER */
    public function testRoleUserDeny(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        foreach (TestUserAccount::getDevice() as $device)
        {
            $client->setServerParameter('HTTP_USER_AGENT', $device);

            $usr = TestUserAccount::getUsr();
            $client->loginUser($usr, 'user');

            $client->request('GET', self::$url);

            self::assertResponseStatusCodeSame(403);
        }

        self::assertTrue(true);
    }

    /** Доступ без роли */
    public function testGuestFiled(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        foreach (TestUserAccount::getDevice() as $device)
        {
            $client->setServerParameter('HTTP_USER_AGENT', $device);

            $client->request('GET', self::$url);

            // Full authentication is required to access this resource
            self::assertResponseStatusCodeSame(401);
        }

        self::assertTrue(true);
    }
}
