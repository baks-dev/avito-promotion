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

namespace BaksDev\Avito\Promotion\Api;

use BaksDev\Avito\Api\AvitoApi;
use BaksDev\Avito\Promotion\Entity\Promotion\AvitoProductPromotion;
use BaksDev\Avito\Promotion\UseCase\NewEdit\Promotion\AvitoProductPromotionDTO;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(public: true)]
final class CreatePromotionCompanyRequest extends AvitoApi
{
    /**
     * Создание новой кампании
     *
     * @see https://developers.avito.ru/api-catalog/autostrategy/documentation#operation/createAutostrategyCampaign
     */
    public function create(AvitoProductPromotion $product): int|false
    {
        if($this->isExecuteEnvironment() === false)
        {
            return false;
        }

        $dto = new AvitoProductPromotionDTO();
        $product->getDto($dto);

        $body = [
            'description' => 'максимум продаж',
            'startTime' => $dto->getCreated()->format('Y-m-d\TH:i:s\Z'),
            'finishTime' => $dto->getCreated()->modify('+1 day')->format('Y-m-d\TH:i:s\Z'),
            'budget' => $dto->getBudget(),
            'campaignType' => 'AS',
            'title' => $dto->getArticle(),
            'items' => [$dto->getArticle()],
        ];

        $request = $this->tokenHttpClient()->request(
            'POST',
            '/autostrategy/v1/campaign/create',
            [
                'json' => $body,
            ],
        );

        if($request->getStatusCode() !== 200)
        {
            $this->logger->critical(
                'Ошибка '.$request->getStatusCode().' при создании рекламной компании для продукта с артикулом'.$dto->getArticle(),
                [__FILE__.':'.__LINE__],
            );

            return false;
        }

        $result = $request->toArray();

        /**
         * Созданная рекламная компания
         *
         * @var array{
         *  "balance": int,
         *  "budget": int,
         *  "campaignId": int,
         *  "campaignType": "AS",
         *  "createTime": string,
         *  "description": string,
         *  "finishTime": string,
         *  "itemsCount": int,
         *  "startTime": string,
         *  "statusId": int,
         *  "title": string,
         *  "updateTime": string,
         *  "userId": int,
         *  "version": int,
         * } $created
         */
        $created = current($result['campaign']);

        return $created['campaignId'];
    }
}
