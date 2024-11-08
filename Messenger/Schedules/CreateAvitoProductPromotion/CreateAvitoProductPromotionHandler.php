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

namespace BaksDev\Avito\Promotion\Messenger\Schedules\CreateAvitoProductPromotion;

use BaksDev\Avito\Promotion\Entity\Promotion\AvitoProductPromotion;
use BaksDev\Avito\Promotion\Repository\CurrentAvitoProductPromotion\CurrentAvitoProductPromotionInterface;
use BaksDev\Avito\Promotion\Repository\CurrentAvitoPromotionByEvent\CurrentAvitoPromotionByEventInterface;
use BaksDev\Avito\Promotion\UseCase\NewEdit\AvitoPromotionDTO;
use BaksDev\Avito\Promotion\UseCase\NewEdit\Promotion\AvitoProductPromotionDTO;
use BaksDev\Avito\Promotion\UseCase\NewEdit\Promotion\AvitoProductPromotionHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;


#[AsMessageHandler]
final readonly class CreateAvitoProductPromotionHandler
{
    private LoggerInterface $logger;

    public function __construct(
        LoggerInterface $avitoPromotionLogger,
        private CurrentAvitoPromotionByEventInterface $currentAvitoPromotion,
        private CurrentAvitoProductPromotionInterface $currentAvitoProductPromotion,
        private AvitoProductPromotionHandler $avitoProductPromotionHandler,
    )
    {
        $this->logger = $avitoPromotionLogger;
    }

    /**
     * Метод:
     * - рассчитывает бюджет с учетом проданных товаров за период времени
     * - создает или обновляет рекламный продукт с рассчитанным бюджетом и рекламной компанией
     */
    public function __invoke(CreateAvitoProductPromotionMessage $message): void
    {
        /** Получаем рекламную компанию */
        $avitoPromotionCompany = $this->currentAvitoPromotion->find($message->getPromotion());

        if(false === $avitoPromotionCompany)
        {
            return;
        }

        $avitoPromotionDTO = new AvitoPromotionDTO();
        $avitoPromotionCompany->getDto($avitoPromotionDTO);

        $profile = $avitoPromotionDTO->getProfile();

        $avitoProductPromotionDTO = new AvitoProductPromotionDTO();

        /** Получаем рекламный продукт по его уникальным идентификатором */
        $avitoProductPromotion = $this->currentAvitoProductPromotion
            ->forProfile($profile)
            ->forProduct($message->getProduct())
            ->forOffer($message->getOffer())
            ->forVariation($message->getVariation())
            ->forModification($message->getModification())
            ->find();

        /**
         * INSERT
         * подготавливаем DTO
         */
        if(false === $avitoProductPromotion)
        {
            $avitoProductPromotionDTO
                ->setProfile($profile)
                ->setProduct($message->getProduct())
                ->setOffer($message->getOffer())
                ->setVariation($message->getVariation())
                ->setModification($message->getModification())
                ->setArticle($message->getArticle())
                ->setBudget($avitoPromotionDTO->getBudget());
        }

        /**
         * UPDATE
         * гидрируем DTO
         */
        if($avitoProductPromotion instanceof AvitoProductPromotion)
        {
            $avitoProductPromotion->getDto($avitoProductPromotionDTO);
        }

        /**
         * ФОРМУЛА: к дневному бюджету добавить процент на количество проданного товара
         * - @var $dailyBudget - дневной бюджет из рекламной компании
         * - @var $orderQuantity - количество заказов за один день
         */
        $dailyBudget = $avitoPromotionDTO->getBudget();
        $orderQuantity = $message->getOrdersCount();

        $formula = (int) round(($dailyBudget / 100 * $orderQuantity));
        $promoBudget = $dailyBudget + $formula;

        // если превысили лимит - бюджет равен лимиту бюджета
        if($promoBudget > $avitoPromotionCompany->getBudgetLimit())
        {
            $promoBudget = $avitoPromotionCompany->getBudgetLimit();
        }

        /** Всегда перезаписываемые данные */
        $avitoProductPromotionDTO
            ->setBudget($promoBudget) // рассчитанный бюджет для продукта
            ->setCompany($message->getPromotion()) // идентификатор рекламной компании
            ->setCreated(new \DateTimeImmutable()); // дата

        $result = $this->avitoProductPromotionHandler->handle($avitoProductPromotionDTO);

        if(false === $result instanceof AvitoProductPromotion)
        {
            $this->logger->critical(
                sprintf(
                    'avito-promotion: Ошибка %s при создании/обновлении рекламного продукта: %s',
                    $result,
                    $avitoProductPromotionDTO->getArticle()
                ),
                [self::class.':'.__LINE__],
            );
        }
    }
}
