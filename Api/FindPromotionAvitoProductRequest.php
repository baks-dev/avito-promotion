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

namespace BaksDev\Avito\Promotion\Api;

use BaksDev\Avito\Api\AvitoApi;

final class FindPromotionAvitoProductRequest extends AvitoApi
{
    /**
     * Получает действующую платную услугу
     *
     * @see https://developers.avito.ru/api-catalog/item/documentation#operation/getItemInfo
     */
    public function find(int|string $identifier): string|null|false
    {
        $response = $this->TokenHttpClient()
            ->request(
                'GET',
                sprintf('/core/v1/accounts/%s/items/%s/', $this->getUser(), $identifier),
            );

        $result = $response->toArray(false);

        if($response->getStatusCode() !== 200)
        {
            $this->logger->critical(
                sprintf('avito-products: Не удалось получить информацию о платных услугах %s', $identifier),
                [
                    __FILE__.':'.__LINE__,
                    $result,
                ]);

            return false;
        }

        if(empty($result['vas']))
        {
            return null;
        }

        $promotion = current($result['vas']);

        return $promotion['vas_id'];
    }
}
