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

namespace BaksDev\Avito\Promotion\Schedule;

use BaksDev\Avito\Promotion\Messenger\Schedules\FindOrdersByAvitoPromotionCompany\FindOrdersByAvitoPromotionCompanyMessage;
use BaksDev\Avito\Repository\AllUserProfilesByActiveToken\AllUserProfilesByTokenRepository;
use BaksDev\Core\Messenger\MessageDelay;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

/** @see FindOrdersByAvitoPromotionCompanyHandler */
#[AsCronTask('0 3 * * *', jitter: 60)]
final readonly class FindProfileWithActiveAvitoTokenCron
{
    public function __construct(
        #[Target('avitoPromotionLogger')] private LoggerInterface $logger,
        private MessageDispatchInterface $messageDispatch,
        private AllUserProfilesByTokenRepository $allProfilesByToken,
    ) {}

    public function __invoke(): void
    {
        /** Получаем все активные профили, у которых активный токен Авито */
        $profiles = $this->allProfilesByToken->findProfilesByActiveToken();

        if(false === $profiles->valid())
        {
            $this->logger->warning(
                'Профили с активными токенами Авито не найдены',
                [__FILE__.':'.__LINE__],
            );

            return;
        }

        foreach($profiles as $profile)
        {
            $this->messageDispatch->dispatch(
                message: new FindOrdersByAvitoPromotionCompanyMessage($profile),
                stamps: [new MessageDelay('5 minutes')],
                transport: (string) $profile,
            );
        }
    }
}