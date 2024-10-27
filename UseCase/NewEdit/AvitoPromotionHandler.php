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

namespace BaksDev\Avito\Promotion\UseCase\NewEdit;

use BaksDev\Avito\Promotion\Entity\AvitoPromotion;
use BaksDev\Avito\Promotion\Entity\Event\AvitoPromotionEvent;
use BaksDev\Avito\Promotion\Messenger\AvitoPromotionMessage;
use BaksDev\Core\Entity\AbstractHandler;
use BaksDev\Core\Messenger\MessageDelay;
use DateInterval;
use Random\Randomizer;

final class AvitoPromotionHandler extends AbstractHandler
{
    public function handle(AvitoPromotionDTO $command): AvitoPromotion|string
    {
        $this->setCommand($command);

        $this->preEventPersistOrUpdate(AvitoPromotion::class, AvitoPromotionEvent::class);

        /** Валидация всех объектов */
        if($this->validatorCollection->isInvalid())
        {
            return $this->validatorCollection->getErrorUniqid();
        }

        $this->flush();


        /** Отправляем сообщение в шину */

        $Randomizer = new Randomizer();
        $delay = sprintf('%s seconds', $Randomizer->getInt(1, 60));

        $this->messageDispatch->dispatch(
            message: new AvitoPromotionMessage($this->main->getId(), $this->main->getEvent(), $command->getEvent()),
            stamps: [new MessageDelay(DateInterval::createFromDateString($delay))],
            transport: 'avito-promotion',
        );

        return $this->main;
    }
}
