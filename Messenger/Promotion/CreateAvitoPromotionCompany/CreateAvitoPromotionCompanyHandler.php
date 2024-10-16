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

namespace BaksDev\Avito\Promotion\Messenger\Promotion\CreateAvitoPromotionCompany;

use BaksDev\Avito\Promotion\Api\CreatePromotionCompanyRequest;
use BaksDev\Avito\Promotion\Entity\Promotion\AvitoProductPromotion;
use BaksDev\Avito\Promotion\Messenger\Promotion\AvitoProductPromotionMessage;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Stamp\DelayStamp;

#[AsMessageHandler]
final readonly class CreateAvitoPromotionCompanyHandler
{
    protected LoggerInterface $logger;

    public function __construct(
        LoggerInterface $avitoPromotionLogger,
        private EntityManagerInterface $em,
        private CreatePromotionCompanyRequest $request,
        private MessageDispatchInterface $messageDispatch,
    )
    {
        $this->logger = $avitoPromotionLogger;
    }

    public function __invoke(AvitoProductPromotionMessage $message): void
    {
        /** @var AvitoProductPromotion $promotionProduct */
        $promotionProduct = $this->em->getRepository(AvitoProductPromotion::class)
            ->find($message->getId());

        if($promotionProduct === null)
        {
            $this->logger->critical(
                'Ошибка получения рекламного продукта '.$promotionProduct->getArticle(),
                [__FILE__.':'.__LINE__],
            );

            return;
        }

        // id созданной компании
        $created = $this->request
            ->profile($promotionProduct->getProfile())
            ->create($promotionProduct);

        if(false === $created)
        {
            $this->logger->critical(
                'Ошибка при создании рекламной компании для продукта с артикулом'.$promotionProduct->getArticle(),
                [__FILE__.':'.__LINE__],
            );

            $this->messageDispatch->dispatch(
                message: $message,
                stamps: [new DelayStamp(3600000)], // задержка 1 час для повторного запроса на создание компании
                transport: (string) $promotionProduct->getProfile(),
            );
        }
    }
}
