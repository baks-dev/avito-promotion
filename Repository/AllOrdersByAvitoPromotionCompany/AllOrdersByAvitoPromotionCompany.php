<?php

declare(strict_types=1);

namespace BaksDev\Avito\Promotion\Repository\AllOrdersByAvitoPromotionCompany;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Orders\Order\Entity\Products\Price\OrderPrice;
use BaksDev\Orders\Order\Type\Status\OrderStatus;
use BaksDev\Orders\Order\Type\Status\OrderStatus\OrderStatusCompleted;
use BaksDev\Products\Category\Entity\CategoryProduct;
use BaksDev\Products\Category\Type\Id\CategoryProductUid;
use BaksDev\Products\Product\Entity\Category\ProductCategory;
use BaksDev\Products\Product\Entity\Event\ProductEvent;
use BaksDev\Products\Product\Entity\Info\ProductInfo;
use BaksDev\Products\Product\Entity\Offers\Price\ProductOfferPrice;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Quantity\ProductOfferQuantity;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\Price\ProductModificationPrice;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\Quantity\ProductModificationQuantity;
use BaksDev\Products\Product\Entity\Offers\Variation\Price\ProductVariationPrice;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Products\Product\Entity\Offers\Variation\Quantity\ProductVariationQuantity;
use BaksDev\Products\Product\Entity\Price\ProductPrice;
use BaksDev\Products\Product\Entity\Property\ProductProperty;
use BaksDev\Products\Product\Entity\Trans\ProductTrans;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use InvalidArgumentException;

final class AllOrdersByAvitoPromotionCompany implements AllOrdersByAvitoPromotionCompanyInterface
{
    private UserProfileUid|false $profile = false;

    private array|false $filters = false;

    private array|false $offerFilters = false;

    private array|false $variationFilters = false;

    private array|false $modificationFilters = false;

    private array|false $propertyFilters = false;

    private CategoryProductUid|false $category = false;

    private string|false $date = false;

    public function __construct(
        private readonly DBALQueryBuilder $DBALQueryBuilder,
    ) {}

    public function date(string $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function category(CategoryProductUid|string $category): self
    {
        if (is_string($category))
        {
            $category = new CategoryProductUid($category);
        }

        $this->category = $category;

        return $this;
    }

    public function filters(array $filters): self
    {
        $this->filters = $filters;

        foreach ($filters as $filter)
        {
            if ($filter->type === null)
            {
                return $this;
            }

            if ($filter->type === 'OFFER')
            {
                $this->offerFilters[] = $filter;
            }

            if ($filter->type === 'VARIATION')
            {
                $this->variationFilters[] = $filter;
            }
            if ($filter->type === 'MODIFICATION')
            {
                $this->modificationFilters[] = $filter;
            }

            if ($filter->type === 'PROPERTY')
            {
                $this->propertyFilters[] = $filter;
            }
        }

        return $this;
    }

    public function profile(UserProfile|UserProfileUid|string $profile): self
    {
        if ($profile instanceof UserProfile)
        {
            $profile = $profile->getId();
        }

        if (is_string($profile))
        {
            $profile = new UserProfileUid($profile);
        }

        $this->profile = $profile;

        return $this;
    }

    /**
     * @return array{
     *   "orders_product": string,
     *   "orders_count": int,
     *   "orders_total": int,
     *   "product_name": string,
     *   "product_offer_value": string,
     *   "product_offer_const": string,
     *   "product_variation_value": string,
     *   "product_variation_const": string,
     *   "product_modification_value": string,
     *   "product_modification_const": string,
     *   "product_property_value": string,
     *   "product_property_const": string,
     *   "product_article": string,
     *   "product_stock": int
     *  }|false
     */
    public function execute(): array|false
    {

        if ($this->category === false)
        {
            throw new InvalidArgumentException('Invalid Argument category');
        }

        if ($this->filters === false)
        {
            throw new InvalidArgumentException('Invalid Argument filters');
        }

        if ($this->profile === false)
        {
            throw new InvalidArgumentException('Invalid Argument profile');
        }

        if ($this->date === false)
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
        $date = new \DateTimeImmutable($this->date);
        $date = $date->format('Y-m-d H:i:s');

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
                    orders_event.status = :status AND
                    orders_event.created > :date
                    /** orders_event.created + INTERVAL \'7 days\' > CURRENT_DATE */
                    '
            )
            ->setParameter('status', OrderStatusCompleted::STATUS, OrderStatus::TYPE)
            ->setParameter('date', $date);

        /** Продукт заказа */
        $dbal
            ->addSelect('orders_product.product AS orders_product')
            ->leftJoin(
                'orders_event',
                OrderProduct::class,
                'orders_product',
                '
                    orders_product.event = orders_event.id'
            );

        /** Количество заказа */
        $dbal
            ->addSelect('COUNT(orders_price.*) AS orders_count')
            ->addSelect('SUM(orders_price.total) AS orders_total')
//            ->addSelect('orders_price.total AS orders_total')
            ->leftJoin(
                'orders_product',
                OrderPrice::class,
                'orders_price',
                '
                    orders_price.product = orders_product.id'
            );

        /**
         * Продукт
         */
        $dbal
            ->join(
                'orders_product',
                ProductEvent::class,
                'product_event',
                'product_event.id = orders_product.product'
            );

        /** Получаем название с учетом настроек локализации */
        $dbal
            ->addSelect('product_trans.name AS product_name')
            ->leftJoin(
                'product_event',
                ProductTrans::class,
                'product_trans',
                'product_trans.event = product_event.id AND product_trans.local = :local'
            );

        /** Основной артикул товара */
        $dbal
            ->leftJoin(
                'product_event',
                ProductInfo::class,
                'product_info',
                'product_info.product = product_event.main'
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
                    product_category.category = :category'
            );

        /** Только совпадения с категорией из фильтра */
        $dbal->setParameter('category', $this->category, CategoryProductUid::TYPE);

        /** Категория */
        $dbal
            ->join(
                'product_category',
                CategoryProduct::class,
                'category',
                'category.id = product_category.category'
            );

        /**
         * Offer
         */
        $dbal
            ->addSelect('product_offer.value as product_offer_value')
            ->addSelect('product_offer.const as product_offer_const')
            ->join(
                'product_event',
                ProductOffer::class,
                'product_offer',
                '
                    product_offer.event = product_event.id AND 
                    product_offer.id = orders_product.offer'
            );

        /** Выражение для фильтра по Offer */
        $offerFilter = $this->getOffer('product_offer.category_offer', 'product_offer.value');


        if (false === is_null($offerFilter))
        {
            $dbal->where($offerFilter);
        }

        /**
         * Variation
         */
        $dbal
            ->addSelect('product_variation.value as product_variation_value')
            ->addSelect('product_variation.const as product_variation_const')
            ->join(
                'product_offer',
                ProductVariation::class,
                'product_variation',
                '
                    product_variation.offer = product_offer.id AND
                    product_variation.id = orders_product.variation'
            );

        /** Выражение для фильтра по Variation */
        $variationFilter = $this->getVariation('product_variation.category_variation', 'product_variation.value');

        if (false === is_null($variationFilter))
        {
            $dbal->where($variationFilter);
        }

        /**
         * Modification
         */
        $dbal
            ->addSelect('product_modification.value as product_modification_value')
            ->addSelect('product_modification.const as product_modification_const')
            ->join(
                'product_variation',
                ProductModification::class,
                'product_modification',
                '
                    product_modification.variation = product_variation.id AND
                    product_modification.id = orders_product.modification'
            );

        /** Выражение для фильтра по Modification */
        $modificationFilter = $this->getModification('product_modification.category_modification', 'product_modification.value');

        if (false === is_null($modificationFilter))
        {
            $dbal->where($modificationFilter);
        }

        /** Выражение для фильтра по Property */
        $propertyFilters = $this->getProperties('product_property.field', 'product_property.value', 'product_property.event');

        if (false == is_null($propertyFilters))
        {
            // OR (одно из совпадений) //
            if (false == is_null($propertyFilters->or))
            {

                $dbal
                    ->addSelect('product_property.value AS product_property_value')
                    ->addSelect('product_property.field AS product_property_const')
                    ->join(
                        'orders_product',
                        ProductProperty::class,
                        'product_property',
                        $propertyFilters->or
                    );
            }

            // AND (несколько совпадений)
            if (false == is_null($propertyFilters->and))
            {
                /**
                 * @var object{
                 *      key: string,
                 *      condition: string } $propertyFilter
                 */
                foreach ($propertyFilters->and as $propertyFilter)
                {

                    $key = $propertyFilter->key;
                    $condition = $propertyFilter->condition;
                    $alias = 'product_property_' . $key;

                    $dbal
                        ->addSelect($alias . '.value' . ' AS product_property_value')
                        ->join(
                            'orders_product',
                            ProductProperty::class,
                            $alias,
                            $condition
                        );
                }
            }
        }

        /**
         * Артикул продукта
         */
        $dbal->addSelect(
            "
					CASE
					   WHEN product_modification.article IS NOT NULL 
					   THEN product_modification.article
					   
					   WHEN product_variation.article IS NOT NULL 
					   THEN product_variation.article
					   
					   WHEN product_offer.article IS NOT NULL 
					   THEN product_offer.article
					   
					   WHEN product_info.article IS NOT NULL 
					   THEN product_info.article
					   
					   ELSE NULL
					END AS product_article"
        );

        /**
         * Базовая Цена товара
         */
        $dbal->leftJoin(
            'orders_product',
            ProductPrice::class,
            'product_price',
            'product_price.event = orders_product.product'
        );

        /**
         * Цена торгового предо жения
         */
        $dbal->leftJoin(
            'product_offer',
            ProductOfferPrice::class,
            'product_offer_price',
            'product_offer_price.offer = product_offer.id'
        );

        /**
         * Цена множественного варианта
         */
        $dbal->leftJoin(
            'product_variation',
            ProductVariationPrice::class,
            'product_variation_price',
            'product_variation_price.variation = product_variation.id'
        );

        /**
         * Цена модификации множественного варианта
         */
        $dbal->leftJoin(
            'product_modification',
            ProductModificationPrice::class,
            'product_modification_price',
            'product_modification_price.modification = product_modification.id'
        );

        /** Наличие продукта */
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
                'product_offer_quantity.offer = product_offer.id'
            );

        /**
         * Наличие и резерв множественного варианта
         */
        $dbal->leftJoin(
            'product_variation',
            ProductVariationQuantity::class,
            'product_variation_quantity',
            'product_variation_quantity.variation = product_variation.id'
        );

        $dbal->leftJoin(
            'product_modification',
            ProductModificationQuantity::class,
            'product_modification_quantity',
            'product_modification_quantity.modification = product_modification.id'
        );

        //        $dbal->addSelect(
        //            '
        //            CASE
        //			   WHEN product_modification_quantity.quantity > 0 AND product_modification_quantity.quantity > product_modification_quantity.reserve
        //			   THEN (product_modification_quantity.quantity - product_modification_quantity.reserve)
        //
        //			   WHEN product_variation_quantity.quantity > 0 AND product_variation_quantity.quantity > product_variation_quantity.reserve
        //			   THEN (product_variation_quantity.quantity - product_variation_quantity.reserve)
        //
        //			   WHEN product_offer_quantity.quantity > 0 AND product_offer_quantity.quantity > product_offer_quantity.reserve
        //			   THEN (product_offer_quantity.quantity - product_offer_quantity.reserve)
        //
        //			   WHEN product_price.quantity > 0 AND product_price.quantity > product_price.reserve
        //			   THEN (product_price.quantity - product_price.reserve)
        //
        //			   ELSE 0
        //			END AS product_stock'
        //        );

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
            ) != 0
        ');

        $dbal->allGroupByExclude();

        $result = $dbal
            ->enableCache('orders-order', 3600)
            ->fetchAllAssociative();

        if (empty($result))
        {
            return false;
        }

        return $result;
    }

    private function getOffer(string $properAlias, string $valueAlias): string|null
    {

        if (false === $this->offerFilters)
        {
            return null;
        }

        $condition = $this->OR($this->offerFilters, $properAlias, $valueAlias);

        return $condition;
    }

    private function getVariation(string $properAlias, string $valueAlias): string|null
    {

        if (false === $this->variationFilters)
        {
            return null;
        }

        $condition = $this->OR($this->variationFilters, $properAlias, $valueAlias);

        return $condition;
    }

    private function getModification(string $properAlias, string $valueAlias): string|null
    {

        if (false === $this->modificationFilters)
        {
            return null;
        }

        $condition = $this->OR($this->modificationFilters, $properAlias, $valueAlias);

        return $condition;
    }

    /** Объединяющее условие для джойна - применяется в рамках одной таблицы */
    private function OR(array $filters, string $properAlias, string $valueAlias): string
    {
        $condition = null;

        /**
         * @var object{
         *     'type': string,
         *      'value': string,
         *      'property': string,
         *      'predicate': string } $filter
         */
        foreach ($filters as $filter)
        {
            if (null === $condition)
            {
                $condition = "(%s = '%s' AND %s = '%s')";
                $condition = sprintf($condition, $properAlias, $filter->property, $valueAlias, $filter->value);
            }
            else
            {
                $condition .= " %s (%s = '%s' AND %s = '%s')";

                $condition = sprintf($condition, 'OR', $properAlias, $filter->property, $valueAlias, $filter->value);
            }
        }

        return $condition;
    }

    /**
     * @return object{
     *  or: string|null,
     *  and: array|null
     * }
     */
    private function getProperties(string $properAlias, string $valueAlias, ?string $eventAlias = null): object|null
    {
        if (false === $this->propertyFilters)
        {
            return null;
        }

        $count = count($this->propertyFilters);

        $propertyFilters = new \stdClass();
        $propertyFilters->or = null;
        $propertyFilters->and = null;

        /**
         * @var object{
         *     'type': string,
         *      'value': string,
         *      'property': string,
         *      'predicate': string } $filter
         */
        foreach ($this->propertyFilters as $key => $filter)
        {

            // одно из совпадений
            // формируем строку с выражением для одного джойна (выражение чувствительно к количеству фильтров)
            if ($filter->predicate === 'OR')
            {

                if (null === $propertyFilters->or) // формируем первую часть выражения
                {
                    $condition = "%s = '%s' AND (%s = '%s'";
                    $condition = sprintf($condition, $properAlias, $filter->property, $valueAlias, $filter->value);

                    $propertyFilters->or = $condition;
                }
                else // формируем следующие части выражения
                {
                    $condition = $propertyFilters->or . " %s %s = '%s'";

                    $condition = sprintf($condition, 'OR', $valueAlias, $filter->value);

                    $propertyFilters->or = $condition;
                }

                // дополняем выражение при обработке последнего фильтра
                if ($key === $count - 1)
                {
                    $end = $propertyFilters->or . ') AND ' . $eventAlias . ' = orders_product.product';
                    $propertyFilters->or = $end;
                }

                // дополняем выражение в конце, если фильтр только одни
                if ($count < 1)
                {
                    $end = $propertyFilters->or . ')';
                    $propertyFilters->or = $end;
                }
            }

            // несколько совпадений
            // формируем массив выражений для циклических джойнов
            if ($filter->predicate === 'AND')
            {
                $property = new \stdClass();

                $property->key = $filter->value;

                // Генерируем уникальные алиасы свойств
                $uniquePropertyAlias = str_replace('.', '_' . $filter->value . '.', $properAlias);
                $uniqueValueAlias = str_replace('.', '_' . $filter->value . '.', $valueAlias);
                $uniqueEventAlias = str_replace('.', '_' . $filter->value . '.', $eventAlias);

                $condition = "%s = '%s' AND %s = '%s'";

                $condition = sprintf($condition, $uniquePropertyAlias, $filter->property, $uniqueValueAlias, $filter->value);
                $property->condition = $condition . ' AND ' . $uniqueEventAlias . ' = orders_product.product';

                $propertyFilters->and[] = $property;
            }
        }

        return $propertyFilters;
    }
}
