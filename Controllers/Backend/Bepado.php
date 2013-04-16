<?php
/**
 * Shopware 4.0
 * Copyright © 2013 shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

use \Bepado\SDK\Struct\Product;

/**
 * @category  Shopware
 * @package   Shopware\Plugins\SwagBepado
 * @copyright Copyright (c) 2013, shopware AG (http://www.shopware.de)
 * @author    Heiner Lohaus
 */
class Shopware_Controllers_Backend_Bepado extends Shopware_Controllers_Backend_ExtJs
{
    /**
     * @return Shopware\Components\Model\ModelManager
     */
    public function getModelManager()
    {
        return Shopware()->Models();
    }

    /**
     * @return \Bepado\SDK\SDK
     */
    public function getSDK()
    {
        return Shopware()->Bootstrap()->getResource('BepadoSDK');
    }

    public function getCategoryListAction()
    {
        $sdk = $this->getSDK();

        $list = $sdk->getCategories();
        $parent = $this->Request()->get('node');
        $count = $parent == 1 ? 1 : substr_count($parent, '/') + 1;
        $parent = $parent == 1 ? '/' : $parent;

        $data = array();
        foreach($list as $id => $name) {
            if(strpos($id, $parent) !== 0
              || substr_count($id, '/') != $count) {
                continue;
            }
            $data[] = array(
                'id' => $id,
                'name' => $name
            );
        }

        $this->View()->assign(array(
            'success' => true,
            'data' => $data
        ));
    }

    public function getExportListAction()
    {
        $repository = 'Shopware\Models\Article\Article';
        /** @var $repository Shopware\Models\Article\Repository */
        $repository = Shopware()->Models()->getRepository($repository);

        $builder = $repository->createQueryBuilder('a');
        $builder->join('a.mainDetail', 'd');
        $builder->join('d.prices', 'p', 'with', "p.from = 1 AND p.customerGroupKey = 'EK'");
        $builder->join('d.attribute', 'at');
        $builder->leftJoin('a.supplier', 's');
        $builder->leftJoin('a.tax', 't');

        $builder->select(array(
            'a.id',
            'd.number as number',
            'd.inStock as inStock',
            'a.name as name',
            's.name as supplier',
            'a.active as active',
            't.tax as tax',
            'p.price * (100 + t.tax) / 100 as price',
            'at.bepadoExport as status',
        ));

        $filter = $this->Request()->getParam('filter', array());
        if (isset($filter[0]['property']) && $filter[0]['property'] == 'search') {
            $builder->where('d.number LIKE :search')
                ->orWhere('a.name LIKE :search')
                ->orWhere('s.name LIKE :search')
                ->setParameter('search', $filter[0]['value']);
            unset($filter[0]);
        }
        if (isset($filter[0]['property']) && $filter[0]['property'] == 'categoryId') {
            $builder->join('a.categories', 'c', 'with', 'c.id = :categoryId')
                    ->setParameter('categoryId', $filter[0]['value']);
            unset($filter[0]);
        }
        $builder->addFilter($filter);

        $builder->addOrderBy($this->Request()->getParam('sort', array()));

        $query = $builder->getQuery();

        $query->setFirstResult($this->Request()->getParam('start'));
        $query->setMaxResults($this->Request()->getParam('limit'));

        $total = Shopware()->Models()->getQueryCount($query);
        $data = $query->getArrayResult();

        $this->View()->assign(array(
            'success' => true,
            'data' => $data,
            'total' => $total
        ));
    }

    public function addAllProductsAction()
    {
        $sdk = $this->getSDK();

        $builder = $this->getProductQueryBuilder();
        $builder->where('d.inStock > 0');

        $query = $builder->getQuery();
        $result = $query->iterate(array(), Doctrine\ORM\Query::HYDRATE_ARRAY);
        foreach($result as $row) {
            $product = $this->getProductByRowData($row);
            $sdk->recordUpdate($product);
        }
    }

    /**
     * @return \Shopware\Models\Article\Repository
     */
    private function getArticleRepository()
    {
        $manager = Shopware()->Models();
        $repository = $manager->getRepository(
            'Shopware\Models\Article\Article'
        );
        return $repository;
    }

    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function getProductQueryBuilder()
    {
        $repository = $this->getArticleRepository();
        $builder = $repository->createQueryBuilder('a');
        $builder->join('a.mainDetail', 'd');
        $builder->join('d.attribute', 'at');
        $builder->join('a.supplier', 's');
        $builder->leftJoin('d.prices', 'p', 'with', "p.from = 1 AND p.customerGroupKey = 'EK'");
        $builder->join('a.tax', 't');
        $builder->join('a.unit', 'u');
        $builder->select(array(
            'a.id as sourceId',
            'd.ean',
            'a.name as title',
            'a.description as shortDescription',
            'a.descriptionLong as longDescription',
            's.name as vendor',
            't.tax / 100 as vat',
            'p.basePrice as price',
            'p.price * (100 + t.tax) / 100 as purchasePrice',
            //'"EUR" as currency',
            'd.shippingFree as freeDelivery',
            'd.releaseDate as deliveryDate',
            'd.inStock as availability',

            'd.width',
            'd.height',
            'd.length',

            'd.weight',
            'd.unit',
            'd.purchaseUnit as volume',
            'd.referenceUnit as base',
            'at.bepadoExport as status'
            //'images = array()',
        ));
        return $builder;
    }

    /**
     * @param array $row
     * @return Product
     */
    private function getProductByRowData($row)
    {
        if(isset($row['deliveryDate'])) {
            $row['deliveryDate'] = $row['deliveryDate']->getTimestamp();
        }
        if(empty($row['price'])) {
            $row['price'] = $row['purchasePrice'];
        }
        $row['categories'] = array(
            '/auto_motorrad'
        );

        // Fix attributes
        foreach(array('weight', 'unit', 'base', 'volume') as $name) {
            if(isset($row[$name])) {
                $row['attributes'][$name] = $row[$name];
            }
            unset($row[$name]);
        }

        // Fix dimensions
        if(!empty($row['width']) && !empty($row['height'])) {
            $dimension = array(
                $row['width'], $row['height']
            );
            if(!empty($row['length'])) {
                $dimension[] = $row['length'];
            }
            $row['attributes'][Product::ATTRIBUTE_DIMENSION] = implode('x', $dimension);
        }
        unset($row['width'], $row['height'], $row['length']);

        $product = new Product(
            $row
        );
        return $product;
    }

    public function searchProductAction()
    {
        $sdk = $this->getSDK();
        $search = new Bepado\SDK\Struct\Search(array(
            'query' => $this->Request()->getParam('query')
        ));
        $result = $sdk->search($search);
        $this->View()->assign((array)$result);
    }

    public function insertUpdateProductAction()
    {
        $sdk = $this->getSDK();
        $ids = $this->Request()->getPost('ids');

        $builder = $this->getProductQueryBuilder();
        $builder->where('a.id = :id');
        $query = $builder->getQuery();

        $repository = $this->getArticleRepository();
        $builder = $repository->createQueryBuilder('a');
        $builder->join('a.attribute', 'at');
        $builder->addSelect('at');
        $builder->where('a.id = :id');
        $articleQuery = $builder->getQuery();

        foreach($ids as $id) {
            $result = $query->execute(array('id' => $id));
            $product = $this->getProductByRowData($result[0]);
            $status = null;
            try {
                if(empty($result[0]['status'])) {
                    $sdk->recordInsert($product);
                    $status = 'insert';
                } else {
                    $sdk->recordUpdate($product);
                    $status = 'insert';
                }
            } catch(Exception $e) {
                $status = 'error';
            }

            /** @var $model Shopware\Models\Article\Article */
            $model = $articleQuery->execute(array('id' => $id));
            $model->getAttribute()->setBepadoExport(
                $status
            );
        }
    }

    public function deleteProductAction()
    {
        $sdk = $this->getSDK();
        $ids = $this->Request()->getPost('ids');
        foreach($ids as $id) {
            $sdk->recordDelete($id);
        }
    }

    public function recreateChangesFeedAction()
    {
        $sdk = $this->getSDK();
        $sdk->recreateChangesFeed();
    }

    public function verifyApiKeyAction()
    {
        $sdk = $this->getSDK();
        try {
            $key = $this->Request()->getPost('apiKey');
            $sdk->verifyKey($key);
            $this->View()->assign(array(
                'success' => true
            ));
        } catch (Exception $e) {
            $this->View()->assign(array(
                'message' => $e->getMessage(),
                'success' => false
            ));
        }
    }
}