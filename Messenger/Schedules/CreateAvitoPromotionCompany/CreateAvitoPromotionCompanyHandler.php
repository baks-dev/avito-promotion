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

namespace BaksDev\Avito\Promotion\Messenger\Schedules\CreateAvitoPromotionCompany;

use BaksDev\Avito\Board\Api\GetIdByArticleRequest;
use BaksDev\Avito\Promotion\Api\CreatePromotionCompanyRequest;
use BaksDev\Avito\Promotion\Messenger\Schedules\AvitoProductPromotionMessage;
use BaksDev\Avito\Promotion\Repository\CurrentAvitoPromotion\CurrentAvitoPromotionInterface;
use BaksDev\Avito\Promotion\UseCase\NewEdit\Promotion\AvitoProductPromotionDTO;
use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use BaksDev\Core\Messenger\MessageDelay;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Reference\Money\Type\Money;
use DateInterval;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CreateAvitoPromotionCompanyHandler
{
    private LoggerInterface $logger;

    public function __construct(
        LoggerInterface $avitoBoardLogger,
        private CurrentAvitoPromotionInterface $CurrentAvitoPromotion,
        private GetIdByArticleRequest $GetIdByArticleRequest,
        private CreatePromotionCompanyRequest $request,
        private MessageDispatchInterface $messageDispatch,
        private DeduplicatorInterface $deduplicator,
    )
    {
        $this->logger = $avitoBoardLogger;
    }

    /**
     * Метод создает рекламную компанию Avito на указанное объявление с указанным бюджетом
     */
    public function __invoke(AvitoProductPromotionMessage $message): void
    {
        $deduplicator = $this->deduplicator
            ->namespace('avito-promotion')
            ->expiresAfter(DateInterval::createFromDateString('1 day'))
            ->deduplication([$message->getId(), self::class]);

        if($deduplicator->isExecuted())
        {
            return;
        }

        $promotionProduct = $this->CurrentAvitoPromotion->find($message->getId());

        if(false === $promotionProduct)
        {
            return;
        }

        $deduplicator->save();

        $avitoProductPromotionDTO = new AvitoProductPromotionDTO();
        $promotionProduct->getDto($avitoProductPromotionDTO);

        /** Получаем идентификатор объявления по артикулу */
        $identifier = $this->GetIdByArticleRequest
            ->profile($avitoProductPromotionDTO->getProfile())
            ->find($avitoProductPromotionDTO->getArticle());


        if(false === $identifier)
        {
            $this->logger->critical(
                sprintf('avito-promotion: Не найден идентификатор объявления по артикулу %s', $avitoProductPromotionDTO->getArticle()),
                [__FILE__.':'.__LINE__]
            );

            return;
        }

        $budget = new Money($avitoProductPromotionDTO->getBudget());

        //        // @TODO удалить в проде
        //        dump('Отправляем запрос на создание рекламной компании');
        //        dump(__FILE__.':'.__LINE__);
        //        dd('-----------');

        $created = $this->request
            ->profile($avitoProductPromotionDTO->getProfile())
            ->article($avitoProductPromotionDTO->getArticle())
            ->identifier($identifier)
            ->budget($budget)
            ->create();

        dd($created);

        if(false === $created)
        {
            $this->logger->critical(
                sprintf('avito-promotion: Ошибка при создании рекламной компании для продукта с артикулом %s', $avitoProductPromotionDTO->getArticle()),
                [__FILE__.':'.__LINE__]
            );

            $this->messageDispatch
                ->dispatch(
                    message: $message,
                    // задержка 1 час для повторного запроса на создание компании
                    stamps: [new MessageDelay('1 hour')],
                    transport: (string) $avitoProductPromotionDTO->getProfile(),
                );

            return;
        }


        $this->logger->info(
            sprintf('Добавили рекламную компанию %s для артикула %s', $created, $avitoProductPromotionDTO->getArticle()),
            [__FILE__.':'.__LINE__]
        );
    }
}
