<?php

namespace Shopware\Bepado\Components\ProductQuery;

use Doctrine\ORM\QueryBuilder;
use Bepado\SDK\Struct\Product;
use Shopware\Bepado\Components\Exceptions\NoLocalProductException;

class LocalProductQuery extends BaseProductQuery
{

    /**
     * @return \Shopware\CustomModels\Bepado\ConfigRepository
     */
    private function getConfigRepository()
    {
        if (!$this->configRepo) {
            $this->configRepo = $this->manager->getRepository('Shopware\CustomModels\Bepado\Config');
        }
        return $this->configRepo;
    }

    /**
     * @return QueryBuilder
     */
    public function getProductQuery()
    {
        $repo = $this->getConfigRepository();
        $exportPriceCustomerGroup = $repo->getConfig('priceGroupForPriceExport', 'EK');
        $exportPurchasePriceCustomerGroup = $repo->getConfig('priceGroupForPurchasePriceExport', 'EK');
        $exportPriceColumn = $repo->getConfig('priceFieldForPriceExport', 'price');
        $exportPurchasePriceColumn = $repo->getConfig('priceFieldForPurchasePriceExport', 'basePrice');

        $builder = $this->manager->createQueryBuilder();

        $builder->from('Shopware\CustomModels\Bepado\Attribute', 'at');
        $builder->join('at.article', 'a');
        $builder->join('a.mainDetail', 'd');
        $builder->leftJoin('a.supplier', 's');
        $builder->leftJoin('d.prices', 'exportPrice', 'with', "exportPrice.from = 1 AND exportPrice.customerGroupKey = :priceCustomerGroup");
        $builder->leftJoin('d.prices', 'exportPurchasePrice', 'with', "exportPurchasePrice.from = 1 AND exportPurchasePrice.customerGroupKey = :purchasePriceCustomerGroup");
        $builder->join('a.tax', 't');
        $builder->join('d.attribute', 'attribute');
        $builder->leftJoin('d.unit', 'u');
        $builder->select(array(
            'a.id as localId',
            'at.shopId as shopId',
            'a.id as sourceId',
            'd.ean',
            'a.name as title',
            'a.description as shortDescription',
            's.name as vendor',
            't.tax / 100 as vat',
            'at.freeDelivery as freeDelivery',

            'd.releaseDate as deliveryDate',
            'd.inStock as availability',
            "exportPrice.{$exportPriceColumn}  as price",
            "exportPurchasePrice.{$exportPurchasePriceColumn} as purchasePrice",
            $this->productDescriptionField . ' as longDescription',

            'd.width',
            'd.height',
            'd.len as length',

            'd.weight',
            'u.unit',
            'd.purchaseUnit as volume',
            'd.referenceUnit as base',
            'at.categories as categories',
            'at.fixedPrice as fixedPrice'
        ));

        $builder->setParameter('priceCustomerGroup', $exportPriceCustomerGroup);
        $builder->setParameter('purchasePriceCustomerGroup', $exportPurchasePriceCustomerGroup);

        return $builder;
    }

    /**
     * @param array $rows
     * @return array
     */
    public function getBepadoProducts($rows)
    {
        $products = array();
        foreach ($rows as $row) {
            $products[] = $this->getBepadoProduct($row);
        }
        return $products;
    }

    /**
     * @param $row
     * @return Product
     */
    public function getBepadoProduct($row)
    {
        $row = $this->prepareCommonAttributes($row);

        if (!empty($row['shopId'])) {
            throw new NoLocalProductException("Product {$row['title']} is not a local product");
        }

        $router = $this->getRouter();
        if ($router) {
            // Assemble the route for the article url.
            // @todo: The shop to point to needs to be configurable.
            $row['url'] = $this->router->assemble(
                array(
                    'module' => 'frontend',
                    'controller' => 'detail',
                    'sArticle' => $row['sourceId']
                )
            );
        }


        $row['images'] = $this->getImagesById($row['localId']);
        unset($row['localId']);

        $product = new Product(
            $row
        );
        return $product;
    }
}

