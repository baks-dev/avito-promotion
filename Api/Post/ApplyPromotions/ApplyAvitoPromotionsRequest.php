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

namespace BaksDev\Avito\Promotion\Api\Post\ApplyPromotions;

use BaksDev\Avito\Api\AvitoApi;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(public: true)]
final class ApplyAvitoPromotionsRequest extends AvitoApi
{
    private array|null $slugs = null;

    /** Список идентификаторов услуг */
    public function slugs(array $slugs): self
    {
        $this->slugs = $slugs;

        return $this;
    }

    /**
     * С помощью этого метода вы можете применить к опубликованному объявлению одну или несколько услуг продвижения
     * (например, «XL-объявление», «Выделение цветом» и «До 10 раз больше просмотров на 7 дней»).
     *
     * В рамках одного запроса услуга может быть применена только один раз.
     *
     * @see https://developers.avito.ru/api-catalog/item/documentation#operation/applyVas
     */
    public function put(string $itemId): array|bool
    {
        if(false === $this->isExecuteEnvironment())
        {
            return true;
        }

        if(null === $this->slugs)
        {
            throw new InvalidArgumentException('Не передан обязательны параметр запроса: slugs');
        }

        $response = $this->TokenHttpClient()
            ->request(
                'PUT',
                '/core/v2/items/'.$itemId.'/vas/',
                [
                    "json" => [
                        "slugs" => $this->slugs,
                    ]
                ],
            );

        $result = $response->toArray(false);

        if($response->getStatusCode() !== 200)
        {
            $this->logger->critical('avito-promotion:Ошибка применение услуг продвижения для продукта: '.$itemId,
                [
                    __FILE__.':'.__LINE__,
                    $result,
                ]);

            return false;
        }


        return $result;
    }
}
