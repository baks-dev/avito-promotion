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

namespace BaksDev\Avito\Promotion\Repository\AllAvitoPromotionByPromotionCompany;

use BaksDev\Avito\Promotion\Entity\AvitoPromotion;
use BaksDev\Avito\Promotion\Entity\Promotion\AvitoProductPromotion;
use BaksDev\Avito\Promotion\Type\AvitoPromotionUid;
use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Services\Paginator\PaginatorInterface;
use BaksDev\Elastic\Api\Index\ElasticGetIndex;
use BaksDev\Products\Category\Entity\CategoryProduct;
use BaksDev\Products\Category\Entity\Offers\CategoryProductOffers;
use BaksDev\Products\Category\Entity\Offers\Variation\CategoryProductVariation;
use BaksDev\Products\Category\Entity\Offers\Variation\Modification\CategoryProductModification;
use BaksDev\Products\Category\Entity\Trans\CategoryProductTrans;
use BaksDev\Products\Product\Entity\Category\ProductCategory;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Products\Product\Entity\Product;
use BaksDev\Products\Product\Entity\Trans\ProductTrans;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use InvalidArgumentException;

final class AllAvitoPromotionByPromotionCompanyRepository
{
    private AvitoPromotionUid|false $promoCompany = false;

    private ?SearchDTO $search = null;

    public function __construct(
        private readonly DBALQueryBuilder $DBALQueryBuilder,
        private readonly PaginatorInterface $pagination,
        private readonly ?ElasticGetIndex $elasticGetIndex = null,
    ) {}

    public function search(SearchDTO $search): self
    {
        $this->search = $search;

        return $this;
    }

    /** Идентификатор корня рекламной компании */
    public function byPromotionCompany(AvitoPromotion|AvitoPromotionUid|string $promoCompany): self
    {
        if($promoCompany instanceof AvitoPromotion)
        {
            $promoCompany = $promoCompany->getId();
        }

        if(is_string($promoCompany))
        {
            $promoCompany = new AvitoPromotionUid($promoCompany);
        }

        $this->promoCompany = $promoCompany;

        return $this;
    }

    /**
     * Метод получает список рекламируемых продуктов в настоящий момент
     */
    public function queryBuilder(): DBALQueryBuilder
    {

        if(false === $this->promoCompany)
        {
            throw new InvalidArgumentException('Пропущен обязательный параметр запроса: promoCompany');
        }

        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $dbal
            ->addSelect('avito_promotion_product.article AS product_article')
            ->from(AvitoProductPromotion::class, 'avito_promotion_product')
            ->where('avito_promotion_product.company = :promoCompany')
            ->setParameter('promoCompany', $this->promoCompany, AvitoPromotionUid::TYPE)
            // только те продукты, у которых были заказы по результатам анализа
            ->andWhere('DATE(avito_promotion_product.created) >= :created')
            ->setParameter('created', new DateTimeImmutable(), Types::DATE_IMMUTABLE);

        /** Продукт */
        $dbal
            ->join(
                'avito_promotion_product',
                Product::class,
                'product',
                'product.id = avito_promotion_product.product'
            );

        /** Название продукта */
        $dbal
            ->addSelect('product_trans.name AS product_name')
            ->leftJoin(
                'product',
                ProductTrans::class,
                'product_trans',
                '
                    product_trans.event = product.event AND 
                    product_trans.local = :local'
            );

        /** Категория */
        $dbal
            ->join(
                'product',
                ProductCategory::class,
                'product_category',
                '
                    product_category.event = product.event AND 
                    product_category.root = true'
            );

        $dbal->join(
            'product_category',
            CategoryProduct::class,
            'category',
            'category.id = product_category.category'
        );

        /** Название категории */
        $dbal
            ->addSelect('category_trans.name AS category_name')
            ->leftJoin(
                'category',
                CategoryProductTrans::class,
                'category_trans',
                '
                    category_trans.event = category.event AND 
                    category_trans.local = :local'
            );

        /** OFFER */
        $dbal
            ->addSelect('product_offer.value as product_offer_value')
            ->addSelect('product_offer.postfix as product_offer_postfix')
            ->join(
                'avito_promotion_product',
                ProductOffer::class,
                'product_offer',
                '
                    product_offer.event = product.event AND
                    product_offer.const = avito_promotion_product.offer'
            );

        /** ТИП торгового предложения */
        $dbal
            ->addSelect('product_category_offers.reference as product_offer_reference')
            ->join(
                'product_offer',
                CategoryProductOffers::class,
                'product_category_offers',
                'product_category_offers.id = product_offer.category_offer'
            );

        /** VARIATION */
        $dbal
            ->addSelect('product_variation.value as product_variation_value')
            ->addSelect('product_variation.postfix as product_variation_postfix')
            ->join(
                'product_offer',
                ProductVariation::class,
                'product_variation',
                '
                    product_variation.offer = product_offer.id AND
                    product_variation.const = avito_promotion_product.variation'
            );

        /** ТИП варианта торгового предложения */
        $dbal
            ->addSelect('category_offer_variation.reference as product_variation_reference')
            ->join(
                'product_variation',
                CategoryProductVariation::class,
                'category_offer_variation',
                'category_offer_variation.id = product_variation.category_variation'
            );

        /** MODIFICATION */
        $dbal
            ->addSelect('product_modification.value as product_modification_value')
            ->addSelect('product_modification.postfix as product_modification_postfix')
            ->join(
                'product_variation',
                ProductModification::class,
                'product_modification',
                '
                    product_modification.variation = product_variation.id AND
                    product_modification.const = avito_promotion_product.modification'
            );

        /** ТИП модификации множественного варианта */
        $dbal
            ->addSelect('category_offer_modification.reference as product_modification_reference')
            ->join(
                'product_modification',
                CategoryProductModification::class,
                'category_offer_modification',
                'category_offer_modification.id = product_modification.category_modification'
            );

        if($this->search?->getQuery())
        {
            /** Поиск по модификации */
            $result = $this->elasticGetIndex ? $this->elasticGetIndex->handle(ProductModification::class, $this->search->getQuery(), 1) : false;

            if($result)
            {
                $counter = $result['hits']['total']['value'];

                if($counter)
                {
                    /** Идентификаторы */
                    $data = array_column($result['hits']['hits'], "_source");

                    $dbal
                        ->createSearchQueryBuilder($this->search)
                        ->addSearchInArray('product_modification.id', array_column($data, "id"));

                    return $dbal;
                }

                /** Поиск по продукции */
                $result = $this->elasticGetIndex->handle(Product::class, $this->search->getQuery(), 1);

                $counter = $result['hits']['total']['value'];

                if($counter)
                {
                    /** Идентификаторы */
                    $data = array_column($result['hits']['hits'], "_source");

                    $dbal
                        ->createSearchQueryBuilder($this->search)
                        ->addSearchInArray('product.id', array_column($data, "id"));

                    return $dbal;
                }
            }

            $dbal
                ->createSearchQueryBuilder($this->search)
                ->addSearchEqualUid('account.id')
                ->addSearchEqualUid('account.event')
                ->addSearchLike('product_trans.name')
                ->addSearchLike('avito_promotion_product.article');
        }

        return $dbal;
    }

    public function findPaginator(): PaginatorInterface
    {
        $dbal = $this->QueryBuilder();

        return $this->pagination->fetchAllAssociative($dbal);
    }

    public function find(): array|false
    {
        $dbal = $this->QueryBuilder();


        $dbal->setMaxResults(500);

        $result = $dbal->fetchAllAssociative();

        if(empty($result))
        {
            return false;
        }

        return $result;
    }
}