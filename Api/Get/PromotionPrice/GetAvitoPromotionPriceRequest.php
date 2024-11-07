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

namespace BaksDev\Avito\Promotion\Api\Get\PromotionPrice;

use BaksDev\Avito\Api\AvitoApi;
use Generator;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(public: true)]
final class GetAvitoPromotionPriceRequest extends AvitoApi
{
    /**
     * Возвращает в ответ список объектов c информацией о стоимости услуг продвижения и доступных значках
     * @see https://developers.avito.ru/api-catalog/item/documentation#operation/vasPrices
     */
    public function get(array $items): Generator|false
    {
        $response = $this->TokenHttpClient()
            ->request(
                'POST',
                '/core/v1/accounts/'.$this->getUser().'/vas/prices',
                [
                    "json" => [
                        "itemIds" => $items,
                    ]
                ],
            );

        $result = $response->toArray(false);

        if($response->getStatusCode() !== 200)
        {
            $this->logger->critical('avito-promotion: Ошибка информации о стоимости услуг продвижения для продукта',
                [
                    self::class.':'.__LINE__,
                    $items,
                    $result,
                ]);

            return false;
        }

        foreach($result as $promotionPrice)
        {
            yield new AvitoPromotionPriceDTO($promotionPrice);
        }
    }
}
