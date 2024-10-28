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
use BaksDev\Reference\Money\Type\Money;
use DateInterval;
use DateTimeImmutable;
use InvalidArgumentException;
use Prophecy\Exception\Exception;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(public: true)]
final class CreatePromotionCompanyRequest extends AvitoApi
{
    private string|false $article = false;

    private int|false $identifier = false;

    private Money|false $budget = false;

    public function article(string $article): self
    {
        $this->article = $article;

        return $this;
    }

    public function identifier(int $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function budget(Money $budget): self
    {
        $this->budget = $budget;

        return $this;
    }

    /**
     * Метод создает рекламную компанию и возвращает идентификатор компании
     *
     * @see https://developers.avito.ru/api-catalog/autostrategy/documentation#operation/createAutostrategyCampaign
     */
    public function create(): int|false
    {
        if($this->isExecuteEnvironment() === false)
        {
            return false;
        }

        if($this->article === false)
        {
            throw new InvalidArgumentException('Invalid Argument $article');
        }

        if($this->identifier === false)
        {
            throw new InvalidArgumentException('Invalid Argument $identifier');
        }

        if($this->budget === false)
        {
            throw new InvalidArgumentException('Invalid Argument $budget');
        }

        $from = (new DateTimeImmutable())->setTime(0, 0);
        $to = $from->add(DateInterval::createFromDateString('1 day'));

        $body = [
            'budget' => (int) $this->budget->getValue(),
            'campaignType' => 'AS',
            'description' => 'максимум продаж',

            'startTime' => $from->format('Y-m-d\TH:i:s\Z'),
            'finishTime' => $to->format('Y-m-d\TH:i:s\Z'),

            'title' => $this->article,
            'items' => [$this->identifier],
        ];


        try
        {
            $request = $this->tokenHttpClient()->request(
                'POST',
                '/autostrategy/v1/campaign/create',
                [
                    'json' => $body,
                ]
            );

            $result = $request->toArray(false);

        }
        catch(Exception $exception)
        {
            $this->logger->critical(
                sprintf('avito-promotion: Ошибка при создании рекламной компании для продукта с артикулом %s', $this->article),
                [__FILE__.':'.__LINE__, $exception]
            );

            return false;
        }

        if($request->getStatusCode() !== 200)
        {
            $this->logger->critical(
                sprintf('avito-promotion: Ошибка при создании рекламной компании для продукта с артикулом %s', $this->article),
                [__FILE__.':'.__LINE__, $result]
            );

            return false;
        }

        if(!isset($result['campaign']))
        {
            return false;
        }

        $created = current($result['campaign']);

        if(!isset($created['campaignId']))
        {
            return false;
        }

        return $created['campaignId'];
    }
}
