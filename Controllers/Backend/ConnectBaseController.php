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

namespace ShopwarePlugins\Connect\Controllers\Backend;

use \Shopware\Connect\Struct\Product;
use Shopware\CustomModels\Connect\Attribute;
use ShopwarePlugins\Connect\Components\ConnectExport;
use ShopwarePlugins\Connect\Components\ErrorHandler;
use ShopwarePlugins\Connect\Components\ImageImport;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Price;
use ShopwarePlugins\Connect\Components\Config;
use ShopwarePlugins\Connect\Components\Validator\ProductAttributesValidator\ProductsAttributesValidator;
use ShopwarePlugins\Connect\Components\Marketplace\MarketplaceSettingsApplier;
use \ShopwarePlugins\Connect\Components\Marketplace\MarketplaceSettings;
use ShopwarePlugins\Connect\Components\ProductStream\ProductStreamService;
use Doctrine\ORM\NoResultException;
use ShopwarePlugins\Connect\Components\SnHttpClient;
use ShopwarePlugins\Connect\Subscribers\Connect;
use Shopware\Connect\SDK;

/**
 * Class ConnectBaseController
 */
class ConnectBaseController extends \Shopware_Controllers_Backend_ExtJs
{
    /**
     * @var \ShopwarePlugins\Connect\Components\ConnectFactory
     */
    private $factory;

    /** @var  \ShopwarePlugins\Connect\Components\Config */
    private $configComponent;

    /**
     * @var \ShopwarePlugins\Connect\Components\Marketplace\MarketplaceSettingsApplier
     */
    private $marketplaceSettingsApplier;

    /**
     * @var \Shopware\Connect\SDK
     */
    private $sdk;

    /**
     * @var \ShopwarePlugins\Connect\Components\SnHttpClient
     */
    private $snHttpClient;

    /**
     * @var ProductStreamService
     */
    private $productStreamService;

    /**
     * @return \Shopware\Components\Model\ModelManager
     */
    public function getModelManager()
    {
        return Shopware()->Models();
    }

    /**
     * @return \Shopware\Connect\SDK
     */
    public function getSDK()
    {
        if ($this->sdk === null) {
            $this->sdk = Shopware()->Bootstrap()->getResource('ConnectSDK');
        }

        return $this->sdk;
    }

    /**
     * @return \ShopwarePlugins\Connect\Components\Helper
     */
    public function getHelper()
    {
        if ($this->factory === null) {
            $this->factory = new \ShopwarePlugins\Connect\Components\ConnectFactory();
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
     * Will return a category model for the given id. If the attribute should not exist
     * it will be created
     *
     * @param $id
     * @return null|\Shopware\Models\Category\Category
     */
    private function getCategoryModelById($id)
    {
        $categoryModel = $this->getCategoryRepository()->find($id);
        if (!$categoryModel || !$categoryModel->getAttribute()) {
            $attribute = new \Shopware\Models\Attribute\Category();
            $attribute->setCategory($categoryModel);
            $this->getModelManager()->persist($attribute);
            $this->getModelManager()->flush($attribute);
        }

        return $categoryModel;
    }

    /**
     * @return ImageImport
     */
    public function getImageImport()
    {
        return new ImageImport(
            Shopware()->Models(),
            $this->getHelper(),
            $this->get('thumbnail_manager'),
            new \ShopwarePlugins\Connect\Components\Logger(Shopware()->Db())
        );
    }

    /**
     * When the backend module is being loaded, update connect products.
     *
     * It might be considerable to move this to e.g. the lifecycle events of the products
     */
    public function indexAction()
    {
        $this->getHelper()->updateConnectProducts();

        parent::loadAction();
    }

    /**
     * When the backend module is being loaded, update connect products.
     *
     * It might be considerable to move this to e.g. the lifecycle events of the products
     */
    public function refreshConnectItemsAction()
    {
        $this->getHelper()->updateConnectProducts();
    }

    /**
     * If the price type is purchase or both
     * and shopware is 5.2 or greater
     * insert detailPurchasePrice in connect config table
     * when priceFieldForPurchasePriceExport is empty
     */
    private function updatePurchasePriceField()
    {
        $field = $this->getConfigComponent()->getConfig('priceFieldForPurchasePriceExport');
        if ($field) {
            return;
        }

        if (!method_exists('Shopware\Models\Article\Detail', 'setPurchasePrice')) {
            return;
        }

        if ($this->getSDK()->getPriceType() == \Shopware\Connect\SDK::PRICE_TYPE_PURCHASE
            || $this->getSDK()->getPriceType() == \Shopware\Connect\SDK::PRICE_TYPE_BOTH
        ) {
            $this->getConfigComponent()->setConfig(
                'priceFieldForPurchasePriceExport',
                'detailPurchasePrice',
                null,
                'export'
            );
        }
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
        $builder = $this->getModelManager()->createQueryBuilder();
        $builder->from('Shopware\CustomModels\Connect\Attribute', 'at');
        $builder->join('at.article', 'a');
        $builder->join('a.mainDetail', 'd');
        $builder->leftJoin('d.prices', 'p', 'with', "p.from = 1 AND p.customerGroupKey = 'EK'");
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
            'at.category'
        ));

        // show only main variant in export/import lists
        $builder->groupBy('at.articleId');

        foreach($filter as $key => $rule) {
            switch($rule['property']) {
                case 'search':
                    $builder->andWhere('d.number LIKE :search OR a.name LIKE :search OR s.name LIKE :search')
                        ->setParameter('search', $rule['value']);
                    break;
                case 'categoryId':
                    $builder->join('a.categories', 'c', 'with', 'c.id = :categoryId OR c.path LIKE :categoryPath')
                        ->setParameter('categoryId', $rule['value'])
                        ->setParameter('categoryPath', '%|' . $rule['value'] . '|%');
                    break;
                case 'supplierId':
                    $builder->andWhere('a.supplierId = :supplierId')
                        ->setParameter('supplierId', $rule['value']);
                    break;
                case 'exportStatus':
                    $builder->andWhere('at.exportStatus LIKE :status')
                        ->setParameter('status', $rule['value']);
                    break;
                case 'active':
                    $builder->andWhere('a.active LIKE :active')
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
     * Get all products exported to connect
     */
    public function getExportListAction()
    {
        $builder = $this->getListQueryBuilder(
            (array)$this->Request()->getParam('filter', array()),
            $this->Request()->getParam('sort', array())
        );
        $builder->addSelect(array(
            'at.exportStatus as exportStatus',
            'at.exportMessage as exportMessage',
            'at.category'
        ));
        $builder->andWhere('at.shopId IS NULL');

        $query = $builder->getQuery();

        $query->setFirstResult($this->Request()->getParam('start'));
        $query->setMaxResults($this->Request()->getParam('limit'));

        $countResult = array_map('current', $builder->select(array('COUNT(DISTINCT at.articleId) as current'))->orderBy("current")->getQuery()->getScalarResult());
        $total = array_sum($countResult);
        // todo@sb: find better solution. getQueryCount method counts s_plugin_connect_items.id like they are not grouped by article id
//        $total = Shopware()->Models()->getQueryCount($query);
        $data = $query->getArrayResult();

        $this->View()->assign(array(
            'success' => true,
            'data' => $data,
            'total' => $total
        ));
    }

    public function getExportStatusAction()
    {
        $attrRepo = $this->getModelManager()->getRepository('Shopware\CustomModels\Connect\Attribute');

        $syncedItems = $attrRepo->countStatus(array(
            Attribute::STATUS_SYNCED,
        ));

        $totalItems = $attrRepo->countStatus(array(
            Attribute::STATUS_INSERT,
            Attribute::STATUS_UPDATE,
            Attribute::STATUS_SYNCED,
        ));

        $this->View()->assign(array(
            'success' => true,
            'data' => $syncedItems,
            'total' => $totalItems,
        ));
    }

    /**
     * Get all products imported from connect
     */
    public function getImportListAction()
    {
        $filter = (array)$this->Request()->getParam('filter', array());
        $sort = $this->Request()->getParam('sort', array());

        foreach ($sort as $key => $currentSorter) {
            if ($currentSorter['property'] == 'category') {
                unset($sort[$key]);
            }
        }

        $builder = $this->getListQueryBuilder(
            $filter,
            $sort
        );
        $builder->addSelect(array(
            'at.shopId',
            'at.sourceId',
            'at.exportStatus as connectStatus',
        ));
        $builder->andWhere('at.shopId IS NOT NULL');

        $builder->addOrderBy('at.category', 'ASC');

        $query = $builder->getQuery();

        $query->setFirstResult($this->Request()->getParam('start'));
        $query->setMaxResults($this->Request()->getParam('limit'));

        $countResult = array_map('current', $builder->select(array('COUNT(DISTINCT at.articleId) as current'))->orderBy("current")->getQuery()->getScalarResult());
        $total = array_sum($countResult);
        $total = array_sum($countResult);
        // todo@sb: find better solution. getQueryCount method counts s_plugin_connect_items.id like they are not grouped by article id
//        $total = Shopware()->Models()->getQueryCount($query);
        $data = $query->getArrayResult();

        $this->View()->assign(array(
            'success' => true,
            'data' => $data,
            'total' => $total
        ));
    }

    /**
     * Import parts of the connect category tree to shopware
     */
    public function importConnectCategoriesAction()
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
        $parentCategory = $this->getCategoryModelById($toCategory);
        $parentCategory->getAttribute()->setConnectImportMapping($fromCategory);
        $entityManager->flush();

        try {
            $entityManager->getConnection()->beginTransaction();
            $this->importConnectCategories($fromCategory, $toCategory);
            $entityManager->getConnection()->commit();
        } catch (\Exception $e) {
            $entityManager->getConnection()->rollback();
            throw new \RuntimeException("Could not import categories", 0, $e);
        }

    }

    /**
     * Will import a connect category tree into shopware.
     *
     * @param $fromCategory
     * @param $toCategory
     */
    public function importConnectCategories($fromCategory, $toCategory)
    {
        $categoriesToImport = $this->getFlatConnectCategories($fromCategory);
        $toCategoryModel = $this->getCategoryRepository()->find($toCategory);
        $entityManager = $this->getModelManager();

        /*
         * The import string allows to identify categories, which have already been imported for
         * this exact import. This does not prevent the user from importing the same sub-tree
         * into multiple shopware categories. But it does prevent him from importing the same sub-tree
         * into the same category multiple times
         */
        $importString = $fromCategory . '-' . $toCategory;
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
                array('connectImported' => $importString, 'connectImportMapping' => $id),
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
                $attribute->setConnectImportMapping($id);
                $attribute->setConnectImported($importString);
                $category->setAttribute($attribute);

                Shopware()->Models()->persist($category);
                Shopware()->Models()->persist($attribute);
            }


            // Store the new category model in out $mappings array
            $mappings[$id] = $category;
        }

        Shopware()->Models()->flush();
    }

    public function initParamsAction()
    {
        $marketplaceIcon = $this->getConfigComponent()->getConfig('marketplaceIcon', Connect::MARKETPLACE_ICON);
        $marketplaceName = $this->getConfigComponent()->getConfig('marketplaceName', Connect::MARKETPLACE_NAME);
        $marketplaceNetworkUrl = $this->getConfigComponent()->getConfig('marketplaceNetworkUrl', Connect::MARKETPLACE_SOCIAL_NETWORK_URL);
        $defaultMarketplace = $this->getConfigComponent()->getConfig('isDefault', true);
        $isFixedPriceAllowed = 0;
        $priceType = Shopware()->Bootstrap()->getResource('ConnectSDK')->getPriceType();
        if ($priceType === SDK::PRICE_TYPE_BOTH ||
            $priceType === SDK::PRICE_TYPE_RETAIL) {
            $isFixedPriceAllowed = 1;
        }
        $marketplaceIncomingIcon = ($marketplaceName == Connect::MARKETPLACE_NAME ? Connect::MARKETPLACE_GREEN_ICON : $marketplaceIcon);
        $marketplaceLogo = $this->getConfigComponent()->getConfig('marketplaceLogo', Connect::MARKETPLACE_LOGO);
        $purchasePriceInDetail = method_exists('Shopware\Models\Article\Detail', 'setPurchasePrice') ? 1 : 0;

        $this->View()->assign(array(
            'success' => true,
            'data' => [
                'marketplaceName' => $marketplaceName,
                'marketplaceNetworkUrl' => $marketplaceNetworkUrl,
                'marketplaceIcon' => $marketplaceIcon,
                'defaultMarketplace' => $defaultMarketplace,
                'isFixedPriceAllowed' => $isFixedPriceAllowed,
                'marketplaceIncomingIcon' => $marketplaceIncomingIcon,
                'marketplaceLogo' => $marketplaceLogo,
                'purchasePriceInDetail' => $purchasePriceInDetail,
            ]
        ));
    }

    /**
     * Returns a flat array of connect categories
     *
     * @param $rootCategory
     * @return array(
     *      string => array('id' => string, 'name' => string, 'level' => int, 'parent' => string|null)
     * )
     */
    private function getFlatConnectCategories($rootCategory)
    {
        $sdk = $this->getSDK();
        $connectCategories = $sdk->getCategories();

        $categoriesToImport = array();
        foreach ($connectCategories as $id => $name) {
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
     * Returns success true if user could be logged in, or false if something went wrong
     *
     * @return array(
     *      string => bool
     * )
     */
    public function loginAction(){
        /** @var \Shopware\Components\HttpClient\GuzzleHttpClient $client */
        $client = $this->get('http_client');

        $shopwareId = $this->Request()->getParam('shopwareId');
        $password = $this->Request()->getParam('password');
        $host = $this->getHost();

        $loginUrl = $host . '/sdk/pluginCommunication/login';

        // Try to login into connect
        $response = $client->post(
            $loginUrl,
            [
                'content-type' => 'application/x-www-form-urlencoded'
            ],
            'username=' . $shopwareId . '&password=' . $password
        );

        $responseObject = json_decode($response->getBody());

        if($responseObject->success) {
            // Save the data
            $this->getConfigComponent()->setConfig('apiKey', $responseObject->apiKey, null, 'general');
            $this->removeConnectMenuEntry();
            $this->getSDK()->verifySdk();
            $this->getConfigComponent()->setConfig('apiKeyVerified', true);
            $marketplaceSettings = $this->getSDK()->getMarketplaceSettings();
            $this->getMarketplaceApplier()->apply(new MarketplaceSettings($marketplaceSettings));

            $this->View()->assign([
                'success' => true,
                'loginUrl' => 'http://' . $host . '/login/' . $responseObject->loginToken
            ]);

            return;
        }

        $this->View()->assign([
            'success' => false,
            'message' => $responseObject->reason
        ]);
    }

    /**
     * Returns success true if user could be logged in, or false if something went wrong
     *
     * @return array(
     *      string => bool
     * )
     */
    public function registerAction(){
        /** @var \Shopware\Components\HttpClient\GuzzleHttpClient $client */
        $client = $this->get('http_client');

        $shopwareId = $this->Request()->getParam('shopwareID');
        $password = $this->Request()->getParam('password');
        $email = $this->Request()->getParam('email');

        // Enter the valid production url here
        $host = $this->getHost();

        $loginUrl = $host . '/sdk/pluginCommunication/register';

        $response = $client->post(
            $loginUrl,
            [
                'content-type' => 'application/x-www-form-urlencoded'
            ],
            'username=' . $shopwareId .
            '&password=' . $password .
            '&email=' . $email
        );

        $responseObject = json_decode($response->getBody());

        if($responseObject->success) {
            // Save the data
            $this->getConfigComponent()->setConfig('apiKey', $responseObject->apiKey, null, 'general');
            $this->getSDK()->verifySdk();
            $this->getConfigComponent()->setConfig('apiKeyVerified', true);
            $marketplaceSettings = $this->getSDK()->getMarketplaceSettings();
            $this->getMarketplaceApplier()->apply(new MarketplaceSettings($marketplaceSettings));
            $this->removeConnectMenuEntry();
            $this->View()->assign([
                'success' => true,
                'loginUrl' => 'http://' . $host . '/login/' . $responseObject->loginToken
            ]);

            return;
        }

        $this->View()->assign([
            'success' => false,
            'message' => $responseObject->reason
        ]);
    }

    /**
     * Redirects and auto login to SN system
     */
    public function autoLoginAction()
    {
        $response = $this->getSnHttpClient()->sendRequestToConnect(array(), 'account/generate-token');

        $responseBody = json_decode($response->getBody());
        if (!$responseBody->success) {
            throw new \RuntimeException($responseBody->message);
        }

        return $this->redirect('http://' . $this->getHost() . '/login/' . $responseBody->loginToken);
    }

    /**
     * @param bool $loggedIn
     * @throws Zend_Db_Adapter_Exception
     */
    private function removeConnectMenuEntry($loggedIn = true) {
        /** @var Enlight_Components_Db_Adapter_Pdo_Mysql $db */
        $db = Shopware()->Db();

        $result = $db->fetchAssoc("SELECT id, parent, pluginID FROM s_core_menu WHERE controller = 'connect' AND name = 'Register' ORDER BY id DESC LIMIT 1");
        if (empty($result)) {
            return;
        }
        $row = current($result);

        $db->exec("DELETE FROM s_core_menu WHERE id = " . $row['id']);

        $insertSql = "INSERT INTO s_core_menu (
            parent,
            name,
            class,
            pluginID,
            controller,
            action,
            onclick,
            active
          ) VALUES (
            '#parent#',
            '#name#',
            '#class#',
            #pluginID#,
            '#controller#',
            '#action#',
            '#onclick#',
            1
          )";

        $db->exec(strtr($insertSql, [
            '#parent#' => $row['parent'],
            '#name#' => 'Import',
            '#class#' => 'sc-icon-import',
            '#pluginID#' => $row['pluginID'],
            '#controller#' => 'Connect',
            '#onclick#' => '',
            '#action#' => 'Import'
        ]));

        $db->exec(strtr($insertSql, [
            '#parent#' => $row['parent'],
            '#name#' => 'Export',
            '#class#' => 'sc-icon-export',
            '#pluginID#' => $row['pluginID'],
            '#controller#' => 'Connect',
            '#onclick#' => '',
            '#action#' => 'Export'
        ]));

        $db->exec(strtr($insertSql, [
            '#parent#' => $row['parent'],
            '#name#' => 'Settings',
            '#class#' => 'sprite-gear',
            '#pluginID#' => $row['pluginID'],
            '#controller#' => 'Connect',
            '#onclick#' => '',
            '#action#' => 'Settings'
        ]));

        $db->exec(strtr($insertSql, [
            '#parent#' => $row['parent'],
            '#name#' => 'Open Connect',
            '#class#' => 'connect-icon',
            '#pluginID#' => $row['pluginID'],
            '#controller#' => 'Connect',
            '#onclick#' => 'window.open("http://' . $this->getConfigComponent()->getSocialNetworkPrefix() . $this->getConfigComponent()->getConfig('connectDebugHost', 'connect.shopware.com') . '")',
            '#action#' => ''
        ]));
    }

    /**
     * Helper that will assign a given mapping to all children of a given category
     *
     * @param $mapping string
     * @throws \Exception
     * @param $categoryId int
     */
    private function applyMappingToChildren($mapping, $categoryId)
    {
        $helper = $this->getHelper();
        $ids = $this->getChildCategoriesIds($categoryId);
        $entityManager = $this->getModelManager();


        if (!$categoryId) {
            throw new \RuntimeException("Category '{$categoryId}' not found");
        }

        // First of all try to save the mapping for the parent category. If that fails,
        // it mustn't be done for the child categories
        $parentCategory = $this->getCategoryModelById($categoryId);
        $parentCategory->getAttribute()->setConnectExportMapping($mapping);
        $entityManager->flush();

        // Don't set the children with models in order to speed things up
        $builder = $entityManager->createQueryBuilder();
        $builder->update('\Shopware\Models\Attribute\Category', 'categoryAttribute')
            ->set('categoryAttribute.connectExportMapping',  $builder->expr()->literal($mapping))
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
        $result = $query->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);

        // Pop IDs from result rows
        return array_map(
            function ($row) {
                return array_pop($row);
            },
            $result
        );
    }

    /**
     * Returns article details ids
     * by given article ids
     *
     * @param array $articleIds
     * @return array
     */
    private function getArticleDetailsId(array $articleIds)
    {
        $ids = array();
        foreach ($articleIds as $articleId) {
            /** @var \Shopware\Models\Article\Article $article */
            $article = $this->getConnectExport()->getArticleModelById($articleId);
            if (!$article) {
                continue;
            }

            $details = $article->getDetails();
            //todo@sb: check with article without variants
            if (count($details) == 0) {
                continue;
            }

            /** @var \Shopware\Models\Article\Detail $detail */
            foreach ($details as $detail) {
                $ids[] = $detail->getId();
            }
        }

        return $ids;
    }

    /**
     * Collect all detail ids by given article ids
     */
    public function getDetailIdsAction()
    {
        try {
            $articleIds = $this->Request()->getPost('ids', array());
            if (!is_array($articleIds)) {
                $articleIds = array($articleIds);
            }

            $detailIds = $this->getHelper()->getArticleDetailIds($articleIds);

            $this->View()->assign(array(
                'success' => true,
                'detailIds' => $detailIds
            ));
        } catch (\Exception $e) {
            $this->View()->assign(array(
                'success' => false,
                'message' => $e->getMessage()
            ));
        }
    }

    /**
     * Called when a product variants were marked for update in the connect backend module
     */
    public function insertOrUpdateProductAction()
    {
        // if priceType comes from SN and shopware version is 5.2
        // priceFieldForPurchasePriceExport is empty
        // we need to set it because there isn't customer groups
        // purchasePrice is stored always in article detail
        $this->updatePurchasePriceField();

        $articleDetailIds = $this->Request()->getPost('articleDetailIds');
        $sourceIds = $this->getHelper()->getArticleDetailSourceIds($articleDetailIds);

        $connectExport = $this->getConnectExport();
        try {
            $errors = $connectExport->export($sourceIds);
        }catch (\RuntimeException $e) {
            $this->View()->assign(array(
                'success' => false,
                'messages' => array(ErrorHandler::TYPE_DEFAULT_ERROR => array($e->getMessage()))
            ));
            return;
        }

        if (!empty($errors)) {
            $this->View()->assign(array(
                'success' => false,
                'messages' => $errors
            ));
            return;
        }

        $this->View()->assign(array(
            'success' => true
        ));
    }

    /**
     * Delete a product from connect export
     */
    public function deleteProductAction()
    {
        $sdk = $this->getSDK();
        $ids = $this->Request()->getPost('ids');
        foreach($ids as $id) {
            /** @var \Shopware\Models\Article\Article $model */
            $model = $this->getConnectExport()->getArticleModelById($id);
            if($model === null) {
                continue;
            }
            /** @var \Shopware\Models\Article\Detail $detail */
            foreach ($model->getDetails() as $detail) {
                $attribute = $this->getHelper()->getConnectAttributeByModel($detail);
                $sdk->recordDelete($attribute->getSourceId());
                $attribute->setExportStatus(
                    'delete'
                );
            }
        }
        Shopware()->Models()->flush();
    }

    /**
     * Updates products and flag the for connect export.
     */
    public function updateProductAction()
    {
        $ids = $this->Request()->getPost('ids');
        $active = (bool)$this->Request()->get('active');
        $unsubscribe = (bool)$this->Request()->get('unsubscribe', false);

        if (!$unsubscribe) {
            foreach ($ids as $id) {
                $model = $this->getConnectExport()->getArticleModelById($id);
                if ($model === null) {
                    continue;
                }

                $attribute = $this->getHelper()->getConnectAttributeByModel($model);
                if ($attribute->getExportStatus() !== null) {
                    continue;
                }
                $model->setActive($active);

                foreach ($model->getDetails() as $detail) {
                    $detail->setActive($active);
                }
            }
        } else {
            $unsubscribedProducts = array();
            $sourceIds = $this->getHelper()->getArticleSourceIds($ids);
            $products = $this->getHelper()->getRemoteProducts($sourceIds);

            /** @var \Shopware\Connect\Struct\Product $product */
            foreach ($products as $product) {
                $unsubscribedProducts[] = new \Shopware\Connect\Struct\ProductId(array(
                    'shopId' => $product->shopId,
                    'sourceId' => $product->sourceId
                ));
            }
            if (empty($unsubscribedProducts)) {
                return;
            }
            $this->getSDK()->unsubscribeProducts($unsubscribedProducts);

            $repository = $this->getArticleRepository();
            foreach ($ids as $id) {
                $article = $repository->find($id);
                if (!$article) {
                    continue;
                }
                Shopware()->Models()->remove($article);
            }
        }
        Shopware()->Models()->flush();
    }

    /**
     * Verify a given api key against the connect server
     */
    public function verifyApiKeyAction()
    {
        /** @var \Shopware\CustomModels\Connect\ConfigRepository $repo */
        $repo = $this->getModelManager()->getRepository('Shopware\CustomModels\Connect\Config');

        $sdk = $this->getSDK();
        try {
            $key = $this->Request()->getPost('apiKey');
            $sdk->verifyKey($key);
            $this->View()->assign(array(
                'success' => true
            ));
            $this->getConfigComponent()->setConfig('apiKeyVerified', true);
            $marketplaceSettings = $sdk->getMarketplaceSettings();
            $this->getMarketplaceApplier()->apply(new MarketplaceSettings($marketplaceSettings));
        } catch (\Exception $e) {
            $this->View()->assign(array(
                'message' => $e->getMessage(),
                'success' => false
            ));
            $this->getConfigComponent()->setConfig('apiKeyVerified', false);
        }

        $this->getModelManager()->flush();

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
            'at.lastUpdate',
            'at.lastUpdateFlag',
            'a.description',
            'a.descriptionLong',
            'a.name'
        ));
        $builder->andWhere('at.shopId IS NOT NULL')
            ->andWHere('at.lastUpdateFlag IS NOT NULL')
            ->andWHere('at.lastUpdateFlag > 0');


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

    /**
     * Apply given changes to product
     *
     * @throws RuntimeException
     */
    public function applyChangesAction()
    {
        $type = $this->Request()->getParam('type');
        $value = $this->Request()->getParam('value');
        $articleId = $this->Request()->getParam('articleId');

        /** @var Article $articleModel */
        $articleModel = $this->getArticleRepository()->find($articleId);

        if (!$articleModel) {
            throw new \RuntimeException("Could not find model for article with id {$articleId}");
        }

        $connectAttribute = $this->getHelper()->getOrCreateConnectAttributeByModel($articleModel);

        $updateFlags = $this->getHelper()->getUpdateFlags();
        $updateFlagsByName = array_flip($updateFlags);
        $flag = $updateFlagsByName[$type];


        switch ($type) {
            case 'shortDescription':
                $articleModel->setDescription($value);
                break;
            case 'longDescription':
                $articleModel->setDescriptionLong($value);
                break;
            case 'name':
                break;
            case 'image':
                $images = explode('|', $value);
                $this->getImageImport()->importImagesForArticle($images, $articleModel);
                break;
            case 'price':
                $netPrice = $value / (1 + ($articleModel->getTax()->getTax()/100));
                $customerGroup = $this->getHelper()->getDefaultCustomerGroup();
                $detail = $articleModel->getMainDetail();

                $detail->getPrices()->clear();
                $price = new Price();
                $price->fromArray(array(
                    'from' => 1,
                    'price' => $netPrice,
                    'basePrice' => $connectAttribute->getPurchasePrice(),
                    'customerGroup' => $customerGroup,
                    'article' => $articleModel
                ));
                $detail->setPrices(array($price));
                break;
        }

        if ($connectAttribute->getLastUpdateFlag() & $flag) {
            $connectAttribute->flipLastUpdateFlag($flag);
        }
        if ($type == 'image') {
            if ($connectAttribute->getLastUpdateFlag() & $updateFlagsByName['imageInitialImport']) {
                $connectAttribute->flipLastUpdateFlag($updateFlagsByName['imageInitialImport']);
            }
        }

        $this->getModelManager()->flush();

        $this->View()->assign('success', true);

    }

    /**
     * Helper: Read images for a given article
     *
     * @param $articleId
     * @return array
     */
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

    /**
     * Returns the connectAttribute data for a given articleId
     *
     * @throws RuntimeException
     * @throws Exception
     */
    public function getConnectDataAction()
    {
        $articleId = $this->Request()->getParam('articleId');

        if (!$articleId) {
            throw new \Exception("Connect: ArticleId empty");
        }

        /** @var Article $articleModel */
        $articleModel = $this->getArticleRepository()->find($articleId);

        if (!$articleModel) {
            throw new \RuntimeException("Could not find model for article with id {$articleId}");
        }

        $data = $this->getHelper()->getOrCreateConnectAttributes($articleModel);

        $data = $this->getModelManager()->toArray($data);
        if (isset($data['articleId'])) {
            $data = array($data);
        }

        $this->View()->assign(array(
            'success' => true,
            'data' => $data
        ));
    }

    /**
     * Save a given connect attribute
     */
    public function saveConnectAttributeAction()
    {
        $data = $this->Request()->getParams();

        /** @var \Shopware\CustomModels\Connect\Attribute $connectAttribute */
        $connectAttribute = $this->getModelManager()->find('Shopware\CustomModels\Connect\Attribute', $data['id']);
        if (!$connectAttribute) {
            throw new \RuntimeException("Could not find connect attribute with id {$data['id']}");
        }

        /** @var \Shopware\Models\Article\Detail $detail */
        foreach ($connectAttribute->getArticle()->getDetails() as $detail) {
            $connectAttribute = $this->getHelper()->getConnectAttributeByModel($detail);
            // Only allow changes in the fixedPrice field if this is a local product
            if (!$connectAttribute->getShopId()) {
                $connectAttribute->setFixedPrice($data['fixedPrice']);
            }
            // Save the update fields
            foreach ($data as $key => $value) {
                if (strpos($key, 'update') === 0) {
                    $setter = 'set' . ucfirst($key);
                    $connectAttribute->$setter($value);
                }
            }
            $this->getModelManager()->persist($connectAttribute);
        }

        $this->getModelManager()->flush();

        $this->View()->assign(array('success' => true));
    }

    /**
     * Saves the changed "connectAllowed" attribute. Saving this attribute should be done
     * by the shipping-module on its own, right now (as of SW 4.2.0) it does not do so.
     *
     * todo: Once the shipping module is fixed, increase the required version of this plugin
     * and remove this and the unnecessary ExtJS extensions
     */
    public function saveShippingAttributeAction()
    {
        $shippingId = $this->Request()->getParam('shippingId');
        $connectAllowed = $this->Request()->getParam('connectAllowed', true);

        if (!$shippingId) {
            return;
        }

        $shippingRepo = $this->getModelManager()->getRepository('\Shopware\Models\Dispatch\Dispatch');
        /** @var \Shopware\Models\Dispatch\Dispatch $shipping */
        $shipping = $shippingRepo->find($shippingId);

        if (!$shipping) {
            return;
        }

        $attribute = $shipping->getAttribute();

        if (!$attribute) {
            $attribute = new \Shopware\Models\Attribute\Dispatch();
            $attribute->setDispatch($shipping);
            $shipping->setAttribute($attribute);
            $this->getModelManager()->persist($attribute);
        }

        $attribute->setConnectAllowed($connectAllowed);

        $this->getModelManager()->flush();

        $this->View()->assign('success', true);
    }

    /**
     * Lists all logs
     */
    public function getLogsAction()
    {
        $params = $this->Request()->getParams();
        $order = $this->Request()->getParam('sort', array(array('property' => 'time', 'direction' => 'DESC')));
        $filters = $this->Request()->getParam('filter');

        $commandFilters = array();
        foreach ($params as $key => $param) {
            if (strpos($key, 'commandFilter_') !== false && $param == 'true') {
                $commandFilters[] = str_replace('commandFilter_', '', $key);
            }
        }

        if (empty($commandFilters)) {
            return;
        }

        foreach ($order as &$rule) {
            if ($rule['property'] == 'time') {
                $rule['property'] = 'id';
            }
            $rule['property'] = 'logs.' . $rule['property'];
        }

        $builder = $this->getModelManager()->createQueryBuilder();
        $builder->select('logs');
        $builder->from('Shopware\CustomModels\Connect\Log', 'logs')
            ->addOrderBy($order)
            ->where('logs.command IN (:commandFilter)')
            ->setParameter('commandFilter', $commandFilters);

        foreach ($filters as $filter) {
            switch ($filter['property']) {
                case 'search':
                    $builder->andWhere(
                        'logs.request LIKE :search OR logs.response LIKE :search'
                    );
                    $builder->setParameter('search', $filter['value']);
                    break;
            }
        }

        switch ($this->Request()->getParam('errorFilter', -1)) {
            case 0:
                $builder->andWhere('logs.isError = 1');
                break;
            case 1:
                $builder->andWhere('logs.isError = 0');
                break;

        }

        $query = $builder->getQuery()
            ->setFirstResult($this->Request()->getParam('start', 0))
            ->setMaxResults($this->Request()->getParam('limit', 25));

        $total = Shopware()->Models()->getQueryCount($query);
        $data = $query->getArrayResult();

        $this->View()->assign(array(
            'success' => true,
            'data' => $data,
            'total' => $total
        ));
    }

    /**
     * Get a list of log commands
     */
    public function getLogCommandsAction()
    {
        $data = $this->getModelManager()->getConnection()->fetchAll(
            'SELECT DISTINCT `command` FROM `s_plugin_connect_log`'
        );

        $data = array_map(function($column) {
            return $column['command'];
        }, $data);

        // Enforce these fields
        foreach (array('fromShop', 'toShop', 'getLastRevision', 'update', 'checkProducts', 'buy', 'reserveProducts', 'confirm') as $value) {
            if (!in_array($value, $data)) {
                $data[] = $value;
            }
        }

        $this->View()->assign(array(
            'success' => true,
            'data' => $data
        ));
    }

    /**
     * Delete all log entries
     */
    public function clearLogAction()
    {
        $connection = $this->getModelManager()->getConnection();
        $connection->exec('TRUNCATE `s_plugin_connect_log`;');
    }

    /**
     * @return ConnectExport
     */
    public function getConnectExport()
    {
        return new ConnectExport(
            $this->getHelper(),
            $this->getSDK(),
            $this->getModelManager(),
            new ProductsAttributesValidator(),
            $this->getConfigComponent(),
            new ErrorHandler()
        );
    }

    /**
     * @return \ShopwarePlugins\Connect\Components\Config
     */
    public function getConfigComponent()
    {
        if ($this->configComponent === null) {
            $this->configComponent = new \ShopwarePlugins\Connect\Components\Config($this->getModelManager());
        }

        return $this->configComponent;
    }

    /**
     * Add given category to products
     */
    public function assignProductsToCategoryAction()
    {
        $articleIds = $this->Request()->getParam('ids');
        $categoryId = (int)$this->Request()->getParam('category');

        /** @var \Shopware\Models\Category\Category $category */
        $category = $this->getCategoryModelById($categoryId);
        if (!is_null($category)) {
            foreach ($articleIds as $id) {
                /** @var \Shopware\Models\Article\Article $article */
                $article = $this->getConnectExport()->getArticleModelById($id);
                if (is_null($article)) {
                    continue;
                }
                $article->addCategory($category);
                $this->getModelManager()->persist($article);
            }
            $this->getModelManager()->flush();
        }

        $this->View()->assign(
            array(
                'success' => true
            )
        );
    }

    public function getStreamListAction()
    {
        $productStreamService = $this->getProductStreamService();

        $result = $productStreamService->getList(
            $this->Request()->getParam('start', 0),
            $this->Request()->getParam('limit', 20)
        );

        $this->View()->assign(
            array(
                'success' => true,
                'data' => $result['data'],
                'total' => $result['total'],
            )
        );
    }

    public function getStreamProductsCountAction()
    {
        $streamId = $this->request->getParam('id', null);

        if ($streamId === null) {
            $this->View()->assign(array(
                'success' => false,
                'message' => 'No stream selected'
            ));
        }

        $streamsAssignments = $this->getStreamAssignments($streamId);

        if (!$streamsAssignments) {
            return;
        }

        $sourceIds = $this->getHelper()->getArticleSourceIds($streamsAssignments->getArticleIds());

        $this->View()->assign(array(
            'success' => true,
            'sourceIds' => $sourceIds
        ));
    }

    public function exportStreamAction()
    {
        $streamIds = $this->request->getParam('streamIds', array());
        $currentStreamIndex = $this->request->getParam('currentStreamIndex', 0);
        $offset = $this->request->getParam('offset', 0);
        $limit = $this->request->getParam('limit', 1);
        $articleDetailIds = $this->request->getParam('articleDetailIds', array());

        $streamId = $streamIds[$currentStreamIndex];

        $productStreamService = $this->getProductStreamService();

        $streamsAssignments = $this->getStreamAssignments($streamId);

        if (!$streamsAssignments) {
            return;
        }

        $sourceIds = $this->getHelper()->getArticleSourceIds($streamsAssignments->getArticleIds());
        $sliced = array_slice($sourceIds, $offset, $limit);

        $exported = $this->exportStreamProducts($streamId, $sliced, $streamsAssignments);

        if (!$exported) {
            return;
        }

        $nextStreamIndex = $currentStreamIndex;
        $newArticleDetailIds = $articleDetailIds;
        $newOffset = $offset + $limit;
        $hasMoreIterations = true;

        $processedStreams = $currentStreamIndex;

        //In this case all the products from a single stream were exported successfully but there are still more streams to be processed.
        if ($newOffset > count($articleDetailIds) && $currentStreamIndex + 1 <= (count($streamIds) - 1)) {
            $productStreamService->changeStatus($streamId, ProductStreamService::STATUS_SUCCESS);
            $productStreamService->log($streamId, 'Success');
            $nextStreamIndex = $currentStreamIndex + 1;

            $streamsAssignments = $this->getStreamAssignments($streamIds[$nextStreamIndex]);

            if (!$streamsAssignments) {
                return;
            }

            $sourceIds = $this->getHelper()->getArticleSourceIds($streamsAssignments->getArticleIds());
            $newArticleDetailIds = $sourceIds;
            $newOffset = 0;
        }

        //In this case all the products from all streams were exported successfully.
        if ($newOffset > count($articleDetailIds) && $currentStreamIndex + 1 > (count($streamIds) - 1)) {
            $productStreamService->changeStatus($streamId, ProductStreamService::STATUS_SUCCESS);
            $hasMoreIterations = false;
            $newOffset = count($articleDetailIds);
            $processedStreams = count($streamIds);
        }


        $this->View()->assign(array(
            'success' => true,
            'articleDetailIds' => $newArticleDetailIds,
            'nextStreamIndex' => $nextStreamIndex,
            'newOffset' => $newOffset,
            'hasMoreIterations' => $hasMoreIterations,
            'processedStreams' => $processedStreams,
        ));
    }

    private function getStreamAssignments($streamId)
    {
        $productStreamService = $this->getProductStreamService();

        try {
            $streamsAssignments = $productStreamService->prepareStreamsAssignments($streamId);
        } catch (\Exception $e) {
            $this->View()->assign(array(
                'success' => false,
                'messages' => array(ErrorHandler::TYPE_DEFAULT_ERROR => array($e->getMessage()))
            ));
            return false;
        }

        return $streamsAssignments;
    }

    private function exportStreamProducts($streamId, $sourceIds, $streamsAssignments)
    {
        $productStreamService = $this->getProductStreamService();
        $connectExport = $this->getConnectExport();

        try {
            $errorMessages = $connectExport->export($sourceIds, $streamsAssignments);
        } catch (\RuntimeException $e) {
            $productStreamService->changeStatus($streamId, ProductStreamService::STATUS_ERROR);
            $this->View()->assign(array(
                'success' => false,
                'messages' => array(ErrorHandler::TYPE_DEFAULT_ERROR => array($e->getMessage()))
            ));
            return false;
        }

        if (!empty($errorMessages)) {
            $productStreamService->changeStatus($streamId, ProductStreamService::STATUS_ERROR);

            $errorMessagesText = "";
            $displayedErrorTypes = array(
                ErrorHandler::TYPE_DEFAULT_ERROR,
                ErrorHandler::TYPE_PRICE_ERROR
            );

            foreach ($displayedErrorTypes as $displayedErrorType) {
                $errorMessagesText .= implode('\n', $errorMessages[$displayedErrorType]);
            }

            $productStreamService->log($streamId, $errorMessagesText);

            $this->View()->assign(array(
                'success' => false,
                'messages' => $errorMessages
            ));
            return false;
        }

        return true;
    }

    /**
     * Deletes products from connect stream export
     */
    public function removeStreamsAction()
    {
        $streamIds = $this->request->getParam('ids', array());

        $productStreamService = $this->getProductStreamService();
        $connectExport = $this->getConnectExport();

        foreach ($streamIds as $streamId) {
            try {
                $removedRecords = array();

                $assignments = $productStreamService->getStreamAssignments($streamId);
                $sourceIds = $this->getHelper()->getArticleSourceIds($assignments->getArticleIds());
                $items = $connectExport->fetchConnectItems($sourceIds);

                foreach ($items as $item) {
                    if ($productStreamService->allowToRemove($assignments, $streamId, $item['articleId'])) {
                        $this->getSDK()->recordDelete($item['sourceId']);
                        $removedRecords[] = $item['sourceId'];

                        $this->getSDK()->recordStreamAssignment(
                            $item['sourceId'],
                            array()
                        );
                    } else {
                        //updates items with the new streams
                        $streamCollection = $assignments->getStreamsByArticleId($item['articleId']);
                        if (!$this->getHelper()->isMainVariant($item['sourceId']) || !$streamCollection) {
                            continue;
                        }

                        //removes current stream from the collection
                        unset($streamCollection[$streamId]);

                        $this->getSDK()->recordStreamAssignment(
                            $item['sourceId'],
                            $streamCollection
                        );
                    }
                }

                $connectExport->updateConnectItemsStatus($removedRecords, Attribute::STATUS_DELETE);

                $productStreamService->changeStatus($streamId, ProductStreamService::STATUS_DELETE);

            } catch (\Exception $e) {
                $productStreamService->changeStatus($streamId, ProductStreamService::STATUS_ERROR);
                $this->View()->assign(array(
                    'success' => false,
                    'message' => $e->getMessage()
                ));
                return;
            }
        }
    }

    private function getMarketplaceApplier()
    {
        if (!$this->marketplaceSettingsApplier) {
            $this->marketplaceSettingsApplier = new MarketplaceSettingsApplier(
                $this->getConfigComponent(),
                Shopware()->Models(),
                Shopware()->Db()
            );
        }

        return $this->marketplaceSettingsApplier;
    }

    /**
     * @return SnHttpClient
     */
    protected function getSnHttpClient()
    {
        if (!$this->snHttpClient) {
            $this->snHttpClient = new SnHttpClient(
                $this->get('http_client'),
                new \Shopware\Connect\Gateway\PDO(Shopware()->Db()->getConnection()),
                $this->getConfigComponent()
            );
        }

        return $this->snHttpClient;
    }

    /**
     * @return string
     */
    protected function getHost()
    {
        $host = $this->getConfigComponent()->getConfig('connectDebugHost', 'connect.shopware.com');
        if ($host) {
            $host = $this->getConfigComponent()->getSocialNetworkPrefix() . $host;
        } else {
            $host = $this->getConfigComponent()->getSocialNetworkPrefix() . $this->getConfigComponent()->getMarketplaceUrl();
        }

        return $host;
    }

    /**
     * @return ProductStreamService
     */
    protected function getProductStreamService()
    {
        if ($this->productStreamService === null) {
            $this->productStreamService = $this->get('swagconnect.product_stream_service');
        }

        return $this->productStreamService;
    }
}
