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

namespace BaksDev\Avito\Promotion\Api\Get\PromotionPrice;

final readonly class AvitoPromotionPriceDTO
{
    /** Идентификатор объявления на Авито */
    private string $id;

    /** Список объектов, которые содержат информацию о стоимости дополнительных услуг и пакетов дополнительных услуг для каждого объявления. */
    private array $vas;

    public function __construct(array $data)
    {
        $this->id = (string) $data['itemId'];
        $this->vas = $data['vas'];
    }

    /** Идентификатор объявления на Авито */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Список объектов, которые содержат информацию о стоимости дополнительных услуг и пакетов дополнительных услуг для каждого объявления.
     *
     * Структура объекта:
     * - slug – идентификатор услуги или пакета услуг
     * -- highlight — услуга продвижения "Выделить"
     * -- xl – услуга продвижения "XL-объявление"
     * -- x2_1 – пакет "до 2 раз больше просмотров на 1 день"
     * -- x2_7 – пакет "до 2 раз больше просмотров на 7 дней"
     * -- x5_1 – пакет "до 5 раз больше просмотров на 1 день"
     * -- x5_7 – пакет "до 5 раз больше просмотров на 7 дней"
     * -- x10_1 – пакет "до 10 раз больше просмотров на 1 день"
     * -- x10_7 – пакет "до 10 раз больше просмотров на 7 дней"
     * -- x15_1 – пакет "до 15 раз больше просмотров на 1 день"
     * -- x15_7 – пакет "до 15 раз больше просмотров на 7 дней"
     * -- x20_1 – пакет "до 20 раз больше просмотров на 1 день"
     * -- x20_7 – пакет "до 20 раз больше просмотров на 7 дней"
     */
    public function getVas(): array
    {
        return $this->vas;
    }
}
