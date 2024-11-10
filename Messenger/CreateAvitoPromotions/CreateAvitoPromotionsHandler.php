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
 */

declare(strict_types=1);

namespace BaksDev\Avito\Promotion\Messenger\CreateAvitoPromotions;

use BaksDev\Avito\Board\Api\GetIdByArticleRequest;
use BaksDev\Avito\Promotion\Api\Get\PromotionPrice\AvitoPromotionPriceDTO;
use BaksDev\Avito\Promotion\Api\Get\PromotionPrice\GetAvitoPromotionPriceRequest;
use BaksDev\Avito\Promotion\Api\Post\ApplyPromotions\ApplyAvitoPromotionsRequest;
use BaksDev\Avito\Promotion\Messenger\Schedules\AvitoProductPromotionMessage;
use BaksDev\Avito\Promotion\Repository\CurrentAvitoProductPromotionById\CurrentAvitoProductPromotionByIdInterface;
use BaksDev\Avito\Promotion\UseCase\NewEdit\Promotion\AvitoProductPromotionDTO;
use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use BaksDev\Core\Messenger\MessageDelay;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use DateInterval;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CreateAvitoPromotionsHandler
{
    private LoggerInterface $logger;

    public function __construct(
        LoggerInterface $avitoBoardLogger,
        private DeduplicatorInterface $deduplicator,
        private MessageDispatchInterface $messageDispatch,
        private CurrentAvitoProductPromotionByIdInterface $currentAvitoProductPromotion,
        private GetIdByArticleRequest $getIdByArticleRequest,
        private GetAvitoPromotionPriceRequest $getAvitoPromotionPriceRequest,
        private ApplyAvitoPromotionsRequest $getAvailableAvitoPromotionsRequest,
    )
    {
        $this->logger = $avitoBoardLogger;
    }

    /**
     * Метод:
     * - получает список услуг для продвижения продукта и их цены в Авито
     * - определяет подходящую услугу по ее стоимости и установленному бюджету
     * - отправляет запрос на подключение списка услуг, соответствующих установленному бюджету
     */
    public function __invoke(AvitoProductPromotionMessage $message): void
    {
        $deduplicator = $this->deduplicator
            ->namespace('avito-promotion')
            ->expiresAfter(DateInterval::createFromDateString('23 hours + 55 minutes'))
            ->deduplication([$message->getId(), self::class]);

        if($deduplicator->isExecuted())
        {
            return;
        }

        $promotionProduct = $this->currentAvitoProductPromotion->find($message->getId());

        if(false === $promotionProduct)
        {
            return;
        }

        $deduplicator->save();

        $avitoProductPromotionDTO = new AvitoProductPromotionDTO();
        $promotionProduct->getDto($avitoProductPromotionDTO);

        /** Получаем идентификатор объявления по артикулу */
        $identifier = $this->getIdByArticleRequest
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

        $promoBudget = $avitoProductPromotionDTO->getBudget();

        $avitoPromotionPrice = $this->getAvitoPromotionPriceRequest
            ->profile($avitoProductPromotionDTO->getProfile())
            ->get([$identifier]);

        if(false === $avitoPromotionPrice)
        {
            $this->messageDispatch->dispatch(
                $message,
                [new MessageDelay('10 minutes')],
                'avito-promotion'
            );

            return;
        }

        if(false === $avitoPromotionPrice->valid())
        {
            return;
        }

        /** @var AvitoPromotionPriceDTO $avitoPromotionPriceDTO */
        $avitoPromotionPriceDTO = $avitoPromotionPrice->current();

        $availablePromotionList = $this->searchAvailablePromotions($avitoPromotionPriceDTO, $promoBudget);

        if(is_null($availablePromotionList))
        {
            return;
        }

        $promotions = $this->getAvailableAvitoPromotionsRequest
            ->slugs($availablePromotionList)
            ->put($avitoPromotionPriceDTO->getId());

        if(false === $promotions)
        {
            $this->logger->critical(
                sprintf('avito-promotion: Ошибка применение услуг продвижения для продукта с артикулом %s', $avitoProductPromotionDTO->getArticle()),
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
            sprintf('Применили услугу продвижения для продукта с артикулом %s', $avitoProductPromotionDTO->getArticle()),
            [__FILE__.':'.__LINE__]
        );
    }

    /**
     * Метод определяет подходящую услугу продвижения по ее цене и добавляет ее в список услуг, доступных для подключения.
     * Доступные по цене услуги не повторяются.
     */
    private function searchAvailablePromotions(
        AvitoPromotionPriceDTO $avitoPromotionPriceDTO,
        int $budget
    ): array|null
    {

        $availablePromo = null;

        /**
         * Список услуг увеличения просмотров на 1 дней
         */

        $oneDayPromoViewing = array_filter($avitoPromotionPriceDTO->getVas(), function(array $priceInfo) {
            return str_ends_with($priceInfo['slug'], '_1');
        });

        if(!empty($oneDayPromoViewing))
        {
            // сортируем услуги - от самой дорогой к самой дешёвой
            usort($oneDayPromoViewing, fn($promoA, $promoB) => $promoA['price'] > $promoB['price'] ? -1 : 1);

            foreach($oneDayPromoViewing as $promo)
            {
                if($budget >= $promo['price'])
                {
                    $availablePromo[] = $promo['slug'];
                    $budget -= $promo['price'];
                    break;
                }
            }
        }


        /**
         * Список услуг "XL-объявление"
         */

        $oneDayPromoXl = array_filter($avitoPromotionPriceDTO->getVas(), function(array $priceInfo) {
            return $priceInfo['slug'] === 'xl';
        });

        foreach($oneDayPromoXl as $promo)
        {
            if($budget >= $promo['price'])
            {
                $availablePromo[] = $promo['slug'];
                $budget -= $promo['price'];
                break;
            }
        }


        /**
         * Список услуг стикерпаков
         */

        $oneDayPromoStickerPack = array_filter($avitoPromotionPriceDTO->getVas(), function(array $priceInfo) {
            return str_starts_with($priceInfo['slug'], 'stickerpack_');
        });

        if(!empty($oneDayPromoStickerPack))
        {
            usort($oneDayPromoStickerPack, fn($promoA, $promoB) => $promoA['price'] > $promoB['price'] ? -1 : 1);

            foreach($oneDayPromoStickerPack as $promo)
            {
                if($budget >= $promo['price'])
                {
                    $availablePromo[] = $promo['slug'];
                    $budget -= $promo['price'];
                    break;
                }
            }
        }


        return $availablePromo;
    }
}
