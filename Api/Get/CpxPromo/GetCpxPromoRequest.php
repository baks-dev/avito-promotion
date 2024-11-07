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

namespace BaksDev\Avito\Promotion\Api\Get\CpxPromo;

use BaksDev\Avito\Api\AvitoApi;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(public: true)]
final class GetCpxPromoRequest extends AvitoApi
{
    /**
     * Метод позволяет получить детализированную информацию о действующих и доступных
     * ценах целевого действия (в копейках), бюджетах (в копейках) и преимуществах перед конкурентами.
     *
     * @see https://developers.avito.ru/api-catalog/cpxpromo/documentation#operation/getBids
     */
    public function get(int $itemId): array|false
    {
        $response = $this->tokenHttpClient()->request(
            'GET',
            '/cpxpromo/1/getBids/'.$itemId,
        );

        $result = $response->toArray(false);

        if($response->getStatusCode() !== 200)
        {
            $this->logger->critical('avito-promotion: Ошибка получения детализированной информации о действующих и доступных ставках и бюджетах продукта: '.$itemId,
                [
                    self::class.':'.__LINE__,
                    $result,
                ]);

            return false;
        }


        return $result;
    }
}
