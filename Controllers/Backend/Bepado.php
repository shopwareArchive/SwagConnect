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

    private $helper;

    /**
     * @return \Shopware\Bepado\Helper
     */
    public function getHelper()
    {
        if($this->helper === null) {
            $request = $this->Request();
            $imagePath = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath();
            $imagePath .= '/media/image/';
            $this->helper = new \Shopware\Bepado\Helper(
                $this->getModelManager(), $imagePath
            );
        }
        return $this->helper;
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

    private function getListQueryBuilder($filter, $order)
    {
        $repository = $this->getArticleRepository();

        $builder = $repository->createQueryBuilder('a');
        $builder->join('a.mainDetail', 'd');
        $builder->leftJoin('d.prices', 'p', 'with', "p.from = 1 AND p.customerGroupKey = 'EK'");
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
        ));
        foreach($filter as $key => $rule) {
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
                case 'exportStatus':
                    $builder->where('at.bepadoExportStatus LIKE :status')
                        ->setParameter('status', $rule['value']);
                    break;
                case 'active':
                    $builder->where('a.active LIKE :active')
                        ->setParameter('active', $rule['value']);
                    break;
                default:
                    continue;
            }
        }
        $builder->addOrderBy($order);
        return $builder;
    }

    public function getExportListAction()
    {
        $builder = $this->getListQueryBuilder(
            (array)$this->Request()->getParam('filter', array()),
            $this->Request()->getParam('sort', array())
        );
        $builder->addSelect(array(
            'at.bepadoExportStatus as exportStatus',
            'at.bepadoExportMessage as exportMessage',
            'at.bepadoCategories'
        ));
        $builder->andWhere('at.bepadoShopId IS NULL');

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

    public function getImportListAction()
    {
        $builder = $this->getListQueryBuilder(
            (array)$this->Request()->getParam('filter', array()),
            $this->Request()->getParam('sort', array())
        );
        $builder->addSelect(array(
            'at.bepadoShopId',
            'at.bepadoSourceId'
        ));
        $builder->andWhere('at.bepadoShopId IS NOT NULL');

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
            $data[$key]['leaf'] = empty($category['childrenCount']);
        }

        $this->View()->assign(array(
            'success' => true, 'data' => $data, 'total' => $count
        ));
    }

    public function setMappingListAction()
    {
        $rows = $this->Request()->getPost('rows');
        if($rows === null) {
            $rows = json_decode($this->Request()->getRawBody(), true);
        }
        $rows = !isset($rows[0]) ? array($rows) : $rows;
        $helper = $this->getHelper();
        foreach($rows as $row) {
            $result = $helper->getCategoryModelById($row['id']);
            if($result !== null) {
                $result->getAttribute()->setBepadoMapping($row['mapping']);
            }
        }
        Shopware()->Models()->flush();
    }

    public function insertOrUpdateProductAction()
    {
        $sdk = $this->getSDK();
        $ids = $this->Request()->getPost('ids');
        $helper = $this->getHelper();

        foreach($ids as $id) {
            $model = $helper->getArticleModelById($id);
            if($model === null) {
                continue;
            }
            $attribute = $model->getAttribute();

            $status = $attribute->getBepadoExportStatus();
            if(empty($status) || $status == 'delete' || $status == 'error') {
                $status = 'insert';
            } else {
                $status = 'update';
            }
            $attribute->setBepadoExportStatus(
                $status
            );

            $categories = $helper->getRowProductCategoriesById($id);
            $attribute->setBepadoCategories(
                serialize($categories)
            );

            Shopware()->Models()->flush($model);
            try {
                if($status == 'insert') {
                    $sdk->recordInsert($id);
                } else {
                    $sdk->recordUpdate($id);
                }
            } catch(Exception $e) {
                $attribute->setBepadoExportStatus(
                    'error'
                );
                $attribute->setBepadoExportMessage(
                    $e->getMessage()
                );
                Shopware()->Models()->flush($model);
            }
        }
    }

    public function deleteProductAction()
    {
        $sdk = $this->getSDK();
        $ids = $this->Request()->getPost('ids');
        foreach($ids as $id) {
            $sdk->recordDelete($id);
            $model = $this->getHelper()->getArticleModelById($id);
            if($model !== null) {
                $attribute = $model->getAttribute();
                $attribute->setBepadoExportStatus(
                    'delete'
                );
            }
        }
        Shopware()->Models()->flush();
    }

    public function updateProductAction()
    {
        $ids = $this->Request()->getPost('ids');
        $active = (bool)$this->Request()->get('active');
        foreach($ids as $id) {
            $model = $this->getHelper()->getArticleModelById($id);
            if($model !== null) {
                $model->setActive($active);
            }
        }
        Shopware()->Models()->flush();
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