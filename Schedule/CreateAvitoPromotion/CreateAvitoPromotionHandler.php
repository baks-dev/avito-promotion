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
 *
 */

declare(strict_types=1);

namespace BaksDev\Avito\Promotion\Schedule\CreateAvitoPromotion;

use BaksDev\Avito\Promotion\Messenger\Promotion\FindAvitoPromotionCompany\FindAvitoPromotionCompanyMessage;
use BaksDev\Avito\Repository\AllUserProfilesByActiveToken\AllUserProfilesByTokenRepository;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CreateAvitoPromotionHandler
{
    private LoggerInterface $logger;

    public function __construct(
        LoggerInterface $avitoBoardLogger,
        private MessageDispatchInterface $messageDispatch,
        private AllUserProfilesByTokenRepository $allProfilesByToken,
    ) {
        $this->logger = $avitoBoardLogger;
    }

    public function __invoke(CreateAvitoPromotionMessage $message): void
    {
        /** Получаем все активные профили, у которых активный токен Авито */
        $profiles = $this->allProfilesByToken->findProfilesByActiveToken();

        if (false === $profiles)
        {
            $this->logger->warning(
                'Профили с активными токенами Авито не найдены',
                [__FILE__ . ':' . __LINE__]
            );

            return;
        }

        foreach ($profiles as $profile)
        {
            $this->messageDispatch->dispatch(
                message: new FindAvitoPromotionCompanyMessage($profile),
                transport: (string)$profile
            );
        }
    }
}
