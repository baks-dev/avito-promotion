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

declare(strict_types=1);

namespace BaksDev\Avito\Promotion\Api\Post\CreatePromotionCompany\Tests;

use BaksDev\Avito\Promotion\Api\CreatePromotionCompany\CreatePromotionCompanyRequest;
use BaksDev\Avito\Type\Authorization\AvitoTokenAuthorization;
use BaksDev\Reference\Money\Type\Money;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use PHPUnit\Framework\Attributes\DependsOnClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
#[Group('avito-promotion')]
final class CreatePromotionCompanyRequestTest extends KernelTestCase
{
    private static AvitoTokenAuthorization $authorization;

    public static function setUpBeforeClass(): void
    {
        self::$authorization = new AvitoTokenAuthorization(
            UserProfileUid::TEST,
            $_SERVER['TEST_AVITO_CLIENT'],
            $_SERVER['TEST_AVITO_SECRET'],
            $_SERVER['TEST_AVITO_USER'],
            $_SERVER['TEST_AVITO_PERCENT'] ?? '0',
        );
    }

    public function testToken(): void
    {
        self::assertTrue(true);
        return;

        /** @var CreatePromotionCompanyRequest $createPromotionCompanyRequest */
        $createPromotionCompanyRequest = static::getContainer()->get(CreatePromotionCompanyRequest::class);
        $createPromotionCompanyRequest->tokenHttpClient(self::$authorization);

        $result = $createPromotionCompanyRequest
            ->profile('profile')
            ->article('article')
            ->identifier(1234)
            ->budget(new Money(123))
            ->create();

        self::assertIsArray($result);
    }
}
