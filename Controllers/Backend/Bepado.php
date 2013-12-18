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

    private $factory;

    /**
     * @return \Shopware\Bepado\Helper
     */
    public function getHelper()
    {
        if ($this->factory === null) {
            $this->factory = new \Shopware\Bepado\BepadoFactory();
        }

        return $this->factory->getHelper();
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
     * Lists categories for a given bepado category tree
     */
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

    /**
     * Helper function to return a QueryBuilder for creating the listing queries for the import and export listings
     *
     * @param $filter
     * @param $order
     * @return \Doctrine\ORM\QueryBuilder
     */
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
                case 'supplierId':
                    $builder->where('a.supplierId = :supplierId')
                        ->setParameter('supplierId', $rule['value']);
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

    /**
     * Get all products exported to bepado
     */
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

    /**
     * Get all products imported from bepado
     */
    public function getImportListAction()
    {
        $builder = $this->getListQueryBuilder(
            (array)$this->Request()->getParam('filter', array()),
            $this->Request()->getParam('sort', array())
        );
        $builder->addSelect(array(
            'at.bepadoShopId',
            'at.bepadoSourceId',
            'at.bepadoExportStatus as bepadoStatus',
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

    /**
     * Get all mappings for a given tree
     */
    public function getMappingListAction()
    {
        $node = (int)$this->Request()->getParam('node', 1);
        $repository = $this->getCategoryRepository();

        $builder = $repository->getListQueryBuilder(array(), array(), null, null, true);
        $builder->leftJoin('c.attribute', 'ct');
        $builder->add('select', 'ct.bepadoMapping as mapping', true);

        $builder->where('c.parentId = :parentId');
        $query = $builder->getQuery();
        $query->setParameter('parentId', $node);
        $count = Shopware()->Models()->getQueryCount($query);

        $data = $query->getArrayResult();

        foreach ($data as &$category) {
            $category['text'] = $category['name'];
            $category['cls'] = 'folder';
            $category['childrenCount'] = (int)$category['childrenCount'];
            $category['leaf'] = empty($category['childrenCount']);
        }

        $this->View()->assign(array(
            'success' => true, 'data' => $data, 'total' => $count
        ));
    }

    /**
     * Save all mapped products
     */
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

    /**
     * Import parts of the bepado category tree to shopware
     */
    public function importBepadoCategoriesAction()
    {
        $fromCategory = $this->Request()->getParam('fromCategory');
        $toCategory = $this->Request()->getParam('toCategory');

        $entityManager = $this->getModelManager();
        $helper = $this->getHelper();

        // Make sure that the target category exists
        $toCategoryModel = $this->getCategoryRepository()->find($toCategory);
        if (!$toCategoryModel) {
            throw new \RuntimeException("Category with id  {$toCategory} not found");
        }

        // The user might have changed the mapping without saving and then hit the "importCategories"
        // button. So we save the parent category's mapping first
        $parentCategory = $helper->getCategoryModelById($toCategory);
        $parentCategory->getAttribute()->setBepadoMapping($fromCategory);
        $entityManager->flush();

        try {
            $entityManager->getConnection()->beginTransaction();
            $this->importBepadoCategories($fromCategory, $toCategory);
            $entityManager->getConnection()->commit();
        } catch (\Exception $e) {
            $entityManager->getConnection()->rollback();
            throw new \RuntimeException("Could not import categories", 0, $e);
        }

    }

    /**
     * Will import a bepado category tree into shopware.
     *
     * @param $fromCategory
     * @param $toCategory
     */
    public function importBepadoCategories($fromCategory, $toCategory)
    {
        $categoriesToImport = $this->getFlatBepadoCategories($fromCategory);
        $toCategoryModel = $this->getCategoryRepository()->find($toCategory);
        $entityManager = $this->getModelManager();

        /*
         * The import string allows to identify categories, which have already been imported for
         * this exact import. This does not prevent the user from importing the same sub-tree
         * into multiple shopware categories. But it does prevent him from importing the same sub-tree
         * into the same category multiple times
         */
        $importString = $fromCategory.'-'.$toCategory;

        $currentLevel = 1;
        $mappings = array();
        foreach ($categoriesToImport as $id => $category) {
            $name = $category['name'];
            $parent = $category['parent'];
            $level = $category['level'];

            // Only flush after the level changed - this speeds up the import
            if ($currentLevel != $level) {
                Shopware()->Models()->flush();
            }
            $currentLevel = $level;

            /** @var \Shopware\Models\Category\Category $parentModel */
            if (!$parent) {
                // Top category level - use toCategoryModel
                $parentModel = $toCategoryModel;
            } else {
                // Parent was created before and is referenced in $mappings
                $parentModel = $mappings[$parent];
            }

            // Check if there is already a category attribute for this import
            $categoryAttributes = $entityManager->getRepository('\Shopware\Models\Attribute\Category')->findBy(
                array('bepadoImported' => $importString, 'bepadoMapping' => $id),
                null,
                1
            );

            if (!empty($categoryAttributes)) {
                /** @var \Shopware\Models\Attribute\Category $categoryAttribute */
                $categoryAttribute = array_pop($categoryAttributes);
                $category = $categoryAttribute->getCategory();
            } else {
                // Create category and attribute model
                $category = new \Shopware\Models\Category\Category();
                $category->setName($name);
                $category->setParent($parentModel);

                $attribute = new \Shopware\Models\Attribute\Category();
                $attribute->setBepadoMapping($id);
                $attribute->setBepadoImported($importString);
                $category->setAttribute($attribute);

                Shopware()->Models()->persist($category);
                Shopware()->Models()->persist($attribute);
            }


            // Store the new category model in out $mappings array
            $mappings[$id] = $category;
        }

        Shopware()->Models()->flush();
    }

    /**
     * Returns a flat array of bepado categories
     *
     * @param $rootCategory
     * @return array(
     *      string => array('id' => string, 'name' => string, 'level' => int, 'parent' => string|null)
     * )
     */
    private function getFlatBepadoCategories($rootCategory)
    {
        $sdk = $this->getSDK();
        $bepadoCategories = $sdk->getCategories();

        $categoriesToImport = array();
        foreach ($bepadoCategories as $id => $name) {
            // Skip all entries which do not start with the parent or do not have it at all
            if (strpos($id, $rootCategory) !== 0) {
                continue;
            }

            $level = substr_count(preg_replace("#^{$rootCategory}#", '', $id), '/');

            // Skip the root category
            if ($level == 0) {
                continue;
            }

            $categoriesToImport[$id] = array(
                'id' => $id,
                'name' => $name,
                'level' => $level,
                'parent' => $level == 1 ? null : implode('/', array_slice(explode('/', $id), 0, -1))
            );
        }

        // Sort the categories ascending by their level, so parent categories can be imported first
        uasort(
            $categoriesToImport,
            function ($a, $b) {
                $a = $a['level'];
                $b = $b['level'];
                if ($a == $b) {
                    return 0;
                }
                return ($a < $b) ? -1 : 1;
            }
        );
        return $categoriesToImport;
    }


    /**
     * Save a given mapping of a given category to all subcategories
     */
    public function applyMappingToChildrenAction()
    {
        $categoryId = $this->Request()->getParam('category');
        $mapping = $this->Request()->getParam('mapping');

        $entityManager = $this->getModelManager();

        try {
            $entityManager->getConnection()->beginTransaction();
            $this->applyMappingToChildren($mapping, $categoryId);
            $entityManager->getConnection()->commit();
            $this->View()->assign(array(
                'success' => true
            ));
        } catch (Exception $e) {
            $entityManager->getConnection()->rollback();
            $this->View()->assign(array(
                'message' => $e->getMessage(),
                'success' => false
            ));
        }
    }

    /**
     * Helper that will assign a given mapping to all children of a given category
     *
     * @param $mapping string
     * @param $categoryId int
     */
    private function applyMappingToChildren($mapping, $categoryId)
    {
        $helper = $this->getHelper();
        $ids = $this->getChildCategoriesIds($categoryId);
        $entityManager = $this->getModelManager();

        // First of all try to save the mapping for the parent category. If that fails,
        // it mustn't be done for the child categories
        $parentCategory = $helper->getCategoryModelById($categoryId);
        $parentCategory->getAttribute()->setBepadoMapping($mapping);
        $entityManager->flush();

        // Don't set the children with models in order to speed things up
        $builder = $entityManager->createQueryBuilder();
        $builder->update('\Shopware\Models\Attribute\Category', 'categoryAttribute')
            ->set('categoryAttribute.bepadoMapping',  $builder->expr()->literal($mapping))
            ->where($builder->expr()->in('categoryAttribute.categoryId', $ids));

        $builder->getQuery()->execute();
    }

    /**
     * Helper function which returns the IDs of the child categories of a given parent category
     *
     * @param $parentId int
     * @return array
     */
    private function getChildCategoriesIds($parentId)
    {
        $query = $this->getModelManager()->createQuery('SELECT c.id from Shopware\Models\Category\Category c WHERE c.path LIKE ?1 ');
        $query->setParameter(1, array("%|{$parentId}|%"));
        $result = $query->getResult(Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);

        // Pop IDs from result rows
        return array_map(
            function ($row) {
                return array_pop($row);
            },
            $result
        );
    }

    /**
     * Calles when a product was marked for update in the bepado backend module
     */
    public function insertOrUpdateProductAction()
    {
        $ids = $this->Request()->getPost('ids');
        $helper = $this->getHelper();

        try {
            $errors = $helper->insertOrUpdateProduct($ids, $this->getSDK());
        }catch (\RuntimeException $e) {
            $this->View()->assign(array(
                'success' => false,
                'message' => $e->getMessage()
            ));
            return;
        }

        if (!empty($errors)) {
            $this->View()->assign(array(
                'success' => false,
                'message' => implode("<br>\n", $errors)
            ));
            return;
        }


        $this->View()->assign(array(
            'success' => true
        ));

    }

    /**
     * Delete a product from bepado export
     */
    public function deleteProductAction()
    {
        $sdk = $this->getSDK();
        $ids = $this->Request()->getPost('ids');
        foreach($ids as $id) {
            $model = $this->getHelper()->getArticleModelById($id);
            if($model === null) {
                continue;
            }
            $attribute = $model->getAttribute();
            $sdk->recordDelete($id);
            $attribute->setBepadoExportStatus(
                'delete'
            );
        }
        Shopware()->Models()->flush();
    }

    /**
     * Updates products and flag the for bepado export.
     */
    public function updateProductAction()
    {
        $ids = $this->Request()->getPost('ids');
        $active = (bool)$this->Request()->get('active');
        $unsubscribe = (bool)$this->Request()->get('unsubscribe', false);

        foreach($ids as $id) {
            $model = $this->getHelper()->getArticleModelById($id);
            if($model === null) {
                continue;
            }

            // Unsubscribe the products and delete them locally
            if ($unsubscribe) {



            // Activate / disable products
            } else {
                $attribute = $model->getAttribute();
                if($attribute->getBepadoExportStatus() !== null) {
                    continue;
                }
                $model->setActive($active);
            }
        }
        Shopware()->Models()->flush();
    }

    /**
     * Verify a given api key against the bepado server
     */
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

    /**
     * Get a list of products with remote changes which have not been applied
     */
    public function getChangedProductsAction()
    {
        $builder = $this->getListQueryBuilder(
            (array)$this->Request()->getParam('filter', array()),
            $this->Request()->getParam('sort', array())
        );

        $builder->addSelect(array(
            'at.bepadoLastUpdate',
            'at.bepadoLastUpdateFlag',
            'a.description',
            'a.descriptionLong',
            'a.name'
        ));
        $builder->andWhere('at.bepadoShopId IS NOT NULL')
            ->andWHere('at.bepadoLastUpdateFlag IS NOT NULL')
            ->andWHere('at.bepadoLastUpdateFlag > 0');


        $query = $builder->getQuery();

        $query->setFirstResult($this->Request()->getParam('start'));
        $query->setMaxResults($this->Request()->getParam('limit'));

        $total = Shopware()->Models()->getQueryCount($query);
        $data = $query->getArrayResult();

        foreach ($data as &$datum) {
            $datum['images'] = implode('|', $this->getImagesForArticle($datum['id']));
        }

        $this->View()->assign(array(
            'success' => true,
            'data' => $data,
            'total' => $total
        ));
    }

    public function getImagesForArticle($articleId)
    {

        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->select('media.path')
                ->from('Shopware\Models\Article\Image', 'images')
                ->join('images.media', 'media')
                ->where('images.articleId = :articleId')
                ->andWhere('images.parentId IS NULL')
                ->setParameter('articleId', $articleId)
                ->orderBy('images.main', 'ASC')
                ->addOrderBy('images.position', 'ASC');

        return array_map(function($image) {
                return $image['path'];
            },
            $builder->getQuery()->getArrayResult()
        );
    }

    public function getPriceConfigAction()
    {
        /** @var Shopware\CustomModels\Bepado\ConfigRepository $repo */
        $repo = Shopware()->Models()->getRepository('Shopware\CustomModels\Bepado\Config');


        $data = array(
            array(
                'bepadoField' => 'purchasePrice',
                'customerGroup' => $repo->getConfig('priceGroupForPurchasePriceExport'),
                'priceField' => $repo->getConfig('priceFieldForPurchasePriceExport')
            ),
            array(
                'bepadoField' => 'price',
                'customerGroup' => $repo->getConfig('priceGroupForPriceExport'),
                'priceField' => $repo->getConfig('priceFieldForPriceExport')
            ),
        );

        $this->View()->assign(array(
            'data' => $data
        ));
    }

    /**
     * Will set the configured price/customerGroup settings
     *
     * @throws RuntimeException
     */
    public function savePriceConfigAction()
    {
        $bepadoField = $this->Request()->getParam('bepadoField');
        $customerGroup = $this->Request()->getParam('customerGroup');
        $priceField = $this->Request()->getParam('priceField');


        // Validate customerGroup and price field
        if (empty($customerGroup) || empty($priceField)) {
            throw new \RuntimeException("Customer group and price field may not be empty. Got {$customerGroup} and {$priceField}");
        }

        if (!in_array($priceField, array('price', 'basePrice', 'pseudoPrice'))) {
            throw new \RuntimeException("Unknown price field {$priceField}");
        }

        $customerGroupRepo = $this->getModelManager()->getRepository('Shopware\Models\Customer\Group');
        $group = $customerGroupRepo->findOneBy(array('key' => $customerGroup));
        if (!$group) {
            throw new \RuntimeException("Could not find customer group with key {$customerGroup}");
        }


        /** @var Shopware\CustomModels\Bepado\ConfigRepository $repo */
        $repo = $this->getModelManager()->getRepository('Shopware\CustomModels\Bepado\Config');

        if ($bepadoField == 'purchasePrice') {
            $configGroup = 'priceGroupForPurchasePriceExport';
            $configField = 'priceFieldForPurchasePriceExport';
        } elseif ($bepadoField == 'price') {
            $configGroup = 'priceGroupForPriceExport';
            $configField = 'priceFieldForPriceExport';
        } else {
            throw new \RuntimeException("Unknown field {$bepadoField}");
        }

        $repo->setConfig($configGroup, $customerGroup);
        $repo->setConfig($configField, $priceField);
        $this->getModelManager()->flush();

        $this->View()->assign('success', true);
    }
}
