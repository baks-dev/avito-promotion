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

namespace BaksDev\Avito\Promotion\Api\Tests;

use BaksDev\Avito\Board\Api\GetIdByArticleRequest;
use BaksDev\Avito\Promotion\Api\FindPromotionAvitoProductRequest;
use BaksDev\Avito\Type\Authorization\AvitoTokenAuthorization;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use PHPUnit\Framework\Attributes\DependsOnClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
#[Group('avito-promotion')]
class FindPromotionAvitoProductRequestTest extends KernelTestCase
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


    public function testUseCase(): void
    {
        /** @var GetIdByArticleRequest $GetIdByArticleRequest */
        $GetIdByArticleRequest = static::getContainer()->get(GetIdByArticleRequest::class);
        $GetIdByArticleRequest->tokenHttpClient(self::$authorization);

        $identifier = $GetIdByArticleRequest->find('PL01-17-225-55-101R');

        /** @var FindPromotionAvitoProductRequest $FindPromotionAvitoProductRequest */
        $FindPromotionAvitoProductRequest = self::getContainer()->get(FindPromotionAvitoProductRequest::class);
        $FindPromotionAvitoProductRequest->tokenHttpClient(self::$authorization);

        $PromotionAvitoProduct = $FindPromotionAvitoProductRequest->find($identifier);

        self::assertNotFalse($PromotionAvitoProduct);

    }

}