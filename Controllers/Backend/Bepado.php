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
            'at.bepadoExportStatus as exportStatus',
            'at.bepadoExportMessage as exportMessage',
        ));

        $filter = (array)$this->Request()->getParam('filter', array());

        foreach($filter as $rule) {
            switch($rule['property']) {
                case 'search':
                    $builder->where('d.number LIKE :search')
                            ->orWhere('a.name LIKE :search')
                            ->orWhere('s.name LIKE :search')
                            ->setParameter('search', $rule['value']);
                    break;
                case 'categoryId':
                    $builder->join('a.categories', 'c', 'with', 'c.id = :categoryId')
                            ->setParameter('categoryId', $rule['value']);
                    break;
                case 'status':
                    $builder->where('at.bepadoExportStatus LIKE :status')
                            ->setParameter('status', $rule['value']);
                    break;
                default:
                    $builder->addFilter(array($rule));
                    break;
            }
        }

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

    public function getMappingListAction()
    {
        $node = (int)$this->Request()->getParam('node', 1);
        $repository = $this->getCategoryRepository();
        $builder = $repository->createQueryBuilder('c');
        $builder->leftJoin('c.attribute', 'ct');
        $builder->select(array(
            'c.id as id',
            'c.name as name',
            'c.position as position',
            'c.parentId as parentId',
            '(c.right - c.left - 1) / 2 as childrenCount',
            'ct.bepadoMapping as mapping',
        ));
        $builder->where('c.parentId = :parentId');
        $query = $builder->getQuery();
        $query->setParameter('parentId', $node);
        $count = Shopware()->Models()->getQueryCount($query);

        $data = $query->getArrayResult();

        foreach ($data as $key => $category) {
            $data[$key]['text'] = $category['name'];
            $data[$key]['cls'] = 'folder';
            $data[$key]['childrenCount'] = (int)$category['childrenCount'];
            $data[$key]['leaf'] = empty($data[$key]['childrenCount']);
            $data[$key]['allowDrag'] = true;
        }

        $this->View()->assign(array(
            'success' => true, 'data' => $data, 'total' => $count
        ));
    }

    public function setMappingListAction()
    {
        $rows = $this->Request()->getPost('rows');
        $rows = !isset($rows[0]) ? array($rows) : $rows;
        $query = $this->getCategoryModelByIdQuery();
        foreach($rows as $row) {
            /** @var $result \Shopware\Models\Category\Category[] */
            $result = $query->execute(array('id' => $row['id']));
            if(isset($result[0])) {
                $result[0]->getAttribute()->setBepadoMapping($row['mapping']);
            }
        }
        Shopware()->Models()->flush();
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
     * @return \Shopware\Models\Category\Repository
     */
    private function getCategoryRepository()
    {
        $manager = Shopware()->Models();
        $repository = $manager->getRepository(
            'Shopware\Models\Category\Category'
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
        $builder->leftJoin('a.supplier', 's');
        $builder->leftJoin('d.prices', 'p', 'with', "p.from = 1 AND p.customerGroupKey = 'EK'");
        $builder->join('a.tax', 't');
        $builder->leftJoin('d.unit', 'u');
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
            'd.len as length',

            'd.weight',
            'u.unit',
            'd.purchaseUnit as volume',
            'd.referenceUnit as base',
            'at.bepadoExportStatus as status'
            //'images = array()',
        ));
        return $builder;
    }

    /**
     * @return \Doctrine\ORM\Query
     */
    private function getArticleModelByIdQuery()
    {
        $repository = $this->getArticleRepository();
        $builder = $repository->createQueryBuilder('a');
        $builder->join('a.attribute', 'at');
        $builder->addSelect('at');
        $builder->where('a.id = :id');
        $query = $builder->getQuery();
        $query->setHydrationMode($query::HYDRATE_OBJECT);
        return $query;
    }

    /**
     * @return \Doctrine\ORM\Query
     */
    private function getCategoryModelByIdQuery()
    {
        $repository = $this->getCategoryRepository();
        $builder = $repository->createQueryBuilder('c');
        $builder->join('c.attribute', 'ct');
        $builder->addSelect('ct');
        $builder->where('c.id = :id');
        $query = $builder->getQuery();
        $query->setHydrationMode($query::HYDRATE_OBJECT);
        return $query;
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

        // Fix prices
        foreach(array('price', 'purchasePrice', 'vat') as $name) {
            $row[$name] = round($row[$name], 2);
        }

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

    public function insertOrUpdateProductAction()
    {
        $sdk = $this->getSDK();
        $ids = $this->Request()->getPost('ids');

        $builder = $this->getProductQueryBuilder();
        $builder->where('a.id = :id');
        $query = $builder->getQuery();

        $articleQuery = $this->getArticleModelByIdQuery();

        foreach($ids as $id) {
            $result = $query->execute(array('id' => $id));
            $status = $result[0]['status']; $message = null;
            unset($result[0]['status']);
            $product = $this->getProductByRowData($result[0]);
            try {
                if(empty($status) || $status == 'delete') {
                    $sdk->recordInsert($product);
                    $status = 'insert';
                } else {
                    $sdk->recordUpdate($product);
                    $status = 'update';
                }
            } catch(Exception $e) {
                $status = 'error';
                $message = $e->getMessage();
            }

            /** @var $model Shopware\Models\Article\Article[] */
            $model = $articleQuery->execute(array('id' => $id));
            if(isset($model[0])) {
                $attribute = $model[0]->getAttribute();
                $attribute->setBepadoExportStatus(
                    $status
                );
                $attribute->setBepadoExportMessage(
                    $message
                );
            }
        }
        Shopware()->Models()->flush();
    }

    public function deleteProductAction()
    {
        $sdk = $this->getSDK();
        $ids = $this->Request()->getPost('ids');
        $query = $this->getArticleModelByIdQuery();
        foreach($ids as $id) {
            $sdk->recordDelete($id);
            /** @var $model Shopware\Models\Article\Article[] */
            $model = $query->execute(array('id' => $id));
            if(isset($model[0])) {
                $attribute = $model[0]->getAttribute();
                $attribute->setBepadoExportStatus(
                    'delete'
                );
            }
        }
        Shopware()->Models()->flush();
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