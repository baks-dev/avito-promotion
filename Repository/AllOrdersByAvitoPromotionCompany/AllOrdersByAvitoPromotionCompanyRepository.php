<?php
/*
 *  Copyright 2025.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Avito\Promotion\Repository\AllOrdersByAvitoPromotionCompany;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Orders\Order\Entity\Products\Price\OrderPrice;
use BaksDev\Orders\Order\Type\Status\OrderStatus;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusCanceled;
use BaksDev\Products\Category\Entity\CategoryProduct;
use BaksDev\Products\Category\Type\Id\CategoryProductUid;
use BaksDev\Products\Product\Entity\Category\ProductCategory;
use BaksDev\Products\Product\Entity\Event\ProductEvent;
use BaksDev\Products\Product\Entity\Info\ProductInfo;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Quantity\ProductOfferQuantity;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\Quantity\ProductModificationQuantity;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Products\Product\Entity\Offers\Variation\Quantity\ProductVariationQuantity;
use BaksDev\Products\Product\Entity\Price\ProductPrice;
use BaksDev\Products\Product\Entity\Property\ProductProperty;
use BaksDev\Products\Product\Entity\Trans\ProductTrans;
use DateInterval;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use InvalidArgumentException;

final class AllOrdersByAvitoPromotionCompanyRepository implements AllOrdersByAvitoPromotionCompanyInterface
{
    private array|false $filters = false;

    private array|null $offerFilters = null;

    private array|null $variationFilters = null;

    private array|null $modificationFilters = null;

    private array|null $propertyFilters = null;

    private CategoryProductUid|false $category = false;

    private DateInterval|false $date = false;

    public function __construct(
        private readonly DBALQueryBuilder $DBALQueryBuilder,
    ) {}

    public function byDate(DateInterval $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function forCategory(CategoryProductUid|string $category): self
    {
        if(is_string($category))
        {
            $category = new CategoryProductUid($category);
        }

        $this->category = $category;

        return $this;
    }

    public function byFilters(array $filters): self
    {
        $this->offerFilters = null;
        $this->variationFilters = null;
        $this->modificationFilters = null;
        $this->propertyFilters = null;

        $this->filters = $filters;

        foreach($filters as $filter)
        {
            if($filter->type === null)
            {
                return $this;
            }

            if($filter->type === 'OFFER')
            {
                $this->offerFilters[] = $filter;
            }

            if($filter->type === 'VARIATION')
            {
                $this->variationFilters[] = $filter;
            }

            if($filter->type === 'MODIFICATION')
            {
                $this->modificationFilters[] = $filter;
            }

            if($filter->type === 'PROPERTY')
            {
                $this->propertyFilters[] = $filter;
            }
        }

        return $this;
    }

    /**
     * @return array{
     *   "orders_product": string,
     *   "orders_count": int,
     *   "product_offer_const": string,
     *   "product_variation_const": string,
     *   "product_modification_const": string,
     *   "product_article": string,
     *  }|false
     */
    public function findAll(): array|false
    {

        if($this->category === false)
        {
            throw new InvalidArgumentException('Invalid Argument category');
        }

        if($this->filters === false)
        {
            throw new InvalidArgumentException('Invalid Argument filters');
        }

        if($this->date === false)
        {
            throw new InvalidArgumentException('Invalid Argument date');
        }

        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        /** Заказы */
        $dbal
            ->from(Order::class, 'orders');

        /** Заказы со смещением, заданным в date */
        $date = (new DateTimeImmutable())->sub($this->date);

        /** Активное событие заказа */
        $dbal
            //            ->addSelect('orders_event.created AS order_created')
            //            ->addSelect('orders_event.id AS order_event')
            ->join(
                'orders',
                OrderEvent::class,
                'orders_event',
                '
                    orders_event.id = orders.event AND
                    orders_event.status != :status AND
                    DATE(orders_event.created) >= :date',
            )
            ->setParameter('status', OrderStatusCanceled::class, OrderStatus::TYPE)
            ->setParameter('date', $date, Types::DATE_IMMUTABLE);

        /** Продукт заказа */
        $dbal
            ->leftJoin(
                'orders_event',
                OrderProduct::class,
                'orders_product',
                '
                    orders_product.event = orders_event.id',
            );

        /** Количество заказа */
        $dbal
            //->addSelect('COUNT(orders_price.*) AS orders_count')
            //->addSelect('orders_price.total AS orders_total')
            ->addSelect('SUM(orders_price.total) AS orders_count')
            ->leftJoin(
                'orders_product',
                OrderPrice::class,
                'orders_price',
                '
                    orders_price.product = orders_product.id',
            );

        /**
         * Продукт
         */
        $dbal
            ->addSelect('product_event.main AS orders_product')
            ->join(
                'orders_product',
                ProductEvent::class,
                'product_event',
                'product_event.id = orders_product.product',
            );

        /** Получаем название с учетом настроек локализации */
        $dbal
            //            ->addSelect('product_trans.name AS product_name')
            ->leftJoin(
                'product_event',
                ProductTrans::class,
                'product_trans',
                'product_trans.event = product_event.id AND product_trans.local = :local',
            );


        /** Основной артикул товара */
        $dbal
            ->leftJoin(
                'product_event',
                ProductInfo::class,
                'product_info',
                'product_info.product = product_event.main',
            );

        /**
         * Категория
         */
        $dbal
            ->join(
                'product_event',
                ProductCategory::class,
                'product_category',
                '
                    product_category.event = product_event.id AND 
                    product_category.root IS TRUE AND
                    product_category.category = :category',
            );

        /** Только совпадения с категорией из фильтра */
        $dbal->setParameter('category', $this->category, CategoryProductUid::TYPE);

        /** Категория */
        $dbal
            ->join(
                'product_category',
                CategoryProduct::class,
                'category',
                'category.id = product_category.category',
            );

        /**
         * Offer
         */
        $dbal
            ->addSelect('product_offer.const as product_offer_const')
            ->join(
                'product_event',
                ProductOffer::class,
                'product_offer',
                '
                    product_offer.event = product_event.id AND 
                    product_offer.id = orders_product.offer',
            );

        /** Выражение для фильтра по Offer */
        if($this->offerFilters)
        {
            /**
             * @var object{
             *     'type': string,
             *      'value': string,
             *      'property': string,
             *      'predicate': string } $offer
             */
            foreach($this->offerFilters as $offer)
            {
                $uniqueProperty = uniqid('property', false);
                $uniqueValue = uniqid('value', false);

                $dbal->orWhere("product_offer.category_offer = :$uniqueProperty AND product_offer.value = :$uniqueValue");
                $dbal->setParameter($uniqueProperty, $offer->property);
                $dbal->setParameter($uniqueValue, $offer->value);
            }
        }

        /**
         * Variation
         */
        $dbal
            ->addSelect('product_variation.const as product_variation_const')
            ->join(
                'product_offer',
                ProductVariation::class,
                'product_variation',
                '
                    product_variation.offer = product_offer.id AND
                    product_variation.id = orders_product.variation',
            );

        /** Выражение для фильтра по Variation */
        if($this->variationFilters)
        {
            /**
             * @var object{
             *     'type': string,
             *      'value': string,
             *      'property': string,
             *      'predicate': string } $variation
             */
            foreach($this->variationFilters as $variation)
            {
                $uniqueProperty = uniqid('property', false);
                $uniqueValue = uniqid('value', false);

                $dbal->orWhere("product_variation.category_variation = :$uniqueProperty AND product_variation.value = :$uniqueValue");
                $dbal->setParameter($uniqueProperty, $variation->property);
                $dbal->setParameter($uniqueValue, $variation->value);
            }
        }

        /**
         * Modification
         */
        $dbal
            ->addSelect('product_modification.const as product_modification_const')
            ->join(
                'product_variation',
                ProductModification::class,
                'product_modification',
                '
                    product_modification.variation = product_variation.id AND
                    product_modification.id = orders_product.modification',
            );

        /** Выражение для фильтра по Modification */
        if($this->modificationFilters)
        {
            /**
             * @var object{
             *     'type': string,
             *      'value': string,
             *      'property': string,
             *      'predicate': string } $modification
             */
            foreach($this->modificationFilters as $modification)
            {
                $uniqueProperty = uniqid('property', false);
                $uniqueValue = uniqid('value', false);

                $dbal->orWhere("product_modification.category_modification = :$uniqueProperty AND product_modification.value = :$uniqueValue");
                $dbal->setParameter($uniqueProperty, $modification->property);
                $dbal->setParameter($uniqueValue, $modification->value);
            }
        }

        /** Фильтр по Property */
        $filtersOR = null;

        /**
         * @var object{
         *     'type': string,
         *      'value': string,
         *      'property': string,
         *      'predicate': string } $property
         */
        foreach($this->propertyFilters as $property)
        {

            /**
             * Ожидаемое поведение при предикате AND (И)
             *
             * Если значения выбраны в рамках одного свойства - результатом будет пустой массив
             *
             * Пример:
             * Свойство: Тип автомобиля
             * Значения: легковой, автобус
             *
             * Результат: не может быть товара, со свойствами Тип автомобиля -> легковой И автобус
             *
             * Если значения выбраны в рамках разных свойств - результатом будет массив товаров с указанными свойствами
             *
             * Пример:
             * Свойство: Тип автомобиля
             * Значения: автобус
             * Свойство: Сезонность
             * Значения: зимние
             *
             * Результат: Товар со свойствами Тип автомобиля -> автобус И зимние
             */
            if($property->predicate === 'AND')
            {
                $uniqueTable = uniqid('t', false);
                $uniqueField = uniqid('f', false);
                $uniqueValue = uniqid('v', false);

                $dbal
                    //->addSelect('product_property_'.$uniqueTable.'.value'.' AS product_property_value')
                    ->join(
                        'orders_product',
                        ProductProperty::class,
                        'product_property_'.$uniqueTable,
                        '
                            product_property_'.$uniqueTable.'.field = :'.$uniqueField.' AND 
                            product_property_'.$uniqueTable.'.value = :'.$uniqueValue.' AND
                            product_property_'.$uniqueTable.'.event = orders_product.product'
                    );

                $dbal->setParameter($uniqueField, $property->property);
                $dbal->setParameter($uniqueValue, $property->value);
            }

            /**
             * Парсинг фильтров с предикатом OR для объединения нескольких свойств с одинаковыми значениями product_property.field, но разными значениями product_property.value
             *
             * Пример выражения для объединения таблиц:
             *
             * - несколько свойств с одинаковыми значениями product_property.field, но разными значениями product_property.value:
             * (product_property.field = :field AND (product_property.value = :bus OR product_property.value = :passenger) AND
             * product_property.event = orders_product.product) OR
             *
             * - одно свойство:
             * (product_property.field = :field AND (product_property.value = :winter) AND product_property.event = orders_product.product)
             */
            if($property->predicate === 'OR')
            {
                $uniqueField = uniqid('f', false);
                $uniqueValue = uniqid('v', false);

                $filtersOR[] = '(product_property.field = :'.$uniqueField.' AND product_property.value = :'.$uniqueValue.')';

                $dbal->setParameter($uniqueField, $property->property);
                $dbal->setParameter($uniqueValue, $property->value);
            }
        }


        /**
         * Ожидаемое поведение при предикате OR (ИЛИ)
         *
         * Будут найдены товары, включающие любое значение свойства, независимо от принадлежности значения конкретному свойству
         *
         * Пример:
         * Свойство: Тип автомобиля
         * Значения: легковой, автобус, зимние
         * Свойство: Сезонность
         * Значения: зимние
         *
         * Результат: Товары со значениями свойств Тип автомобиля -> автобус ИЛИ Тип автомобиля -> автобус ИЛИ Сезонность -> зимние
         */
        if($filtersOR)
        {
            $conditionOR = '('.implode(' OR ', $filtersOR).') AND product_property.event = orders_product.product';

            $dbal
                //->addSelect('product_property.value AS product_property_value')
                //->addSelect('product_property.field AS product_property_const')
                ->join(
                    'orders_product',
                    ProductProperty::class,
                    'product_property',
                    $conditionOR,
                );
        }

        /**
         * Артикул продукта
         */
        $dbal->addSelect("
            COALESCE(
                product_modification.article,
                product_variation.article,
                product_offer.article,
                product_info.article
            ) AS product_article
        ");

        /**
         * Базовая Цена товара
         */
        $dbal->leftJoin(
            'orders_product',
            ProductPrice::class,
            'product_price',
            'product_price.event = orders_product.product',
        );

        /**
         * Наличие и резерв торгового предложения
         */
        $dbal
            //            ->addSelect('product_offer_quantity.quantity AS quantity')
            //            ->addSelect('product_offer_quantity.reserve AS reserve')
            ->leftJoin(
                'product_offer',
                ProductOfferQuantity::class,
                'product_offer_quantity',
                'product_offer_quantity.offer = product_offer.id',
            );

        /**
         * Наличие и резерв множественного варианта
         */
        $dbal->leftJoin(
            'product_variation',
            ProductVariationQuantity::class,
            'product_variation_quantity',
            'product_variation_quantity.variation = product_variation.id',
        );

        $dbal->leftJoin(
            'product_modification',
            ProductModificationQuantity::class,
            'product_modification_quantity',
            'product_modification_quantity.modification = product_modification.id',
        );

        /** Только заказы, у которых есть остатки */
        $dbal->andWhere('
            (
                CASE
                   WHEN product_modification_quantity.quantity > 0 AND product_modification_quantity.quantity > product_modification_quantity.reserve 
                   THEN (product_modification_quantity.quantity - product_modification_quantity.reserve)
                
                   WHEN product_variation_quantity.quantity > 0 AND product_variation_quantity.quantity > product_variation_quantity.reserve 
                   THEN (product_variation_quantity.quantity - product_variation_quantity.reserve)
                
                   WHEN product_offer_quantity.quantity > 0 AND product_offer_quantity.quantity > product_offer_quantity.reserve 
                   THEN (product_offer_quantity.quantity - product_offer_quantity.reserve)
                  
                   WHEN product_price.quantity > 0 AND product_price.quantity > product_price.reserve 
                   THEN (product_price.quantity - product_price.reserve)
                 
                   ELSE 0
                END
            ) > 0
        ');


        /** DEBUG */
        //        $dbal
        //            ->addSelect('product_offer.value as product_offer_value')
        //            ->addSelect('product_variation.value as product_variation_value')
        //            ->addSelect('product_modification.value as product_modification_value')
        //        ;


        $dbal->allGroupByExclude();

        $result = $dbal->fetchAllAssociative();

        return empty($result) ? false : $result;
    }
}
