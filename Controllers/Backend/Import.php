<?php
/**
 * Shopware 5
 * Copyright (c) shopware AG
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

/**
 * Class Shopware_Controllers_Backend_Import
 */
class Shopware_Controllers_Backend_Import extends Shopware_Controllers_Backend_ExtJs
{
    private $categoryExtractor;

    /**
     * @var \Shopware\CustomModels\Connect\ProductToRemoteCategory
     */
    private $productToRemoteCategoryRepository;

    /**
     * @var \ShopwarePlugins\Connect\Components\ImportService
     */
    private $importService;

    private $remoteCategoryRepository;

    private $autoCategoryResolver;

    private $categoryRepository;

    private $logger;

    public function getImportedProductCategoriesTreeAction()
    {
        $parent = $this->request->getParam('id', 'root');
        if ($parent == 'root') {
            $categories = $this->getCategoryExtractor()->getMainNodes();
        } elseif (is_numeric($parent)) {
            $categories = $this->getCategoryExtractor()->extractByShopId($parent);
        } else {
            $categories = $this->getCategoryExtractor()->getRemoteCategoriesTree($parent, false, true);
        }

        $this->View()->assign(array(
            'success' => true,
            'data' => $categories,
        ));
    }

    public function loadArticlesByRemoteCategoryAction()
    {
        $category = $this->request->getParam('category', null);
        $shopId = $this->request->getParam('shopId', 0);
        $limit = (int)$this->request->getParam('limit', 10);
        $offset = (int)$this->request->getParam('start', 0);

        $query = $this->getProductToRemoteCategoryRepository()->findArticlesByRemoteCategory($category, $shopId, $limit, $offset);
        $query->setHydrationMode($query::HYDRATE_OBJECT);

        $paginator = new \Doctrine\ORM\Tools\Pagination\Paginator($query);
        $totalCount = $paginator->count();
        $this->View()->assign(array(
            'success' => true,
            'data' => $query->getArrayResult(),
            'total' => $totalCount,
        ));
    }

    public function loadBothArticleTypesAction()
    {
        $categoryId = (int)$this->request->getParam('categoryId', 0);
        $limit = (int)$this->request->getParam('limit', 10);
        $offset = (int)$this->request->getParam('start', 0);
        $showOnlyConnectArticles = $this->request->getParam('showOnlyConnectArticles', null);

        $result = $this->getImportService()->findBothArticlesType(
            $categoryId,
            $showOnlyConnectArticles ? true : false,
            $limit,
            $offset
        );

        $this->View()->assign(array(
            'success' => true,
            'data' => $result['data'],
            'total' => $result['total'],
        ));
    }

    public function assignArticlesToCategoryAction()
    {
        $categoryId = (int)$this->request->getParam('categoryId', 0);
        $articleIds = $this->request->getParam('articleIds', array());
        if ($categoryId == 0 || empty($articleIds)) {
            $this->View()->assign(array(
                'success' => false,
                'error' => 'Invalid category or articles',
            ));
            return;
        }

        try {
            $this->getImportService()->assignCategoryToArticles($categoryId, $articleIds);
        } catch (\RuntimeException $e) {
            $this->getLogger()->write(true, $e->getMessage(), $e);
            $this->View()->assign(array(
                'success' => false,
                'error' => 'Category could not be assigned to products!',
            ));
            return;
        }

        $this->View()->assign(array(
            'success' => true
        ));
    }

    /**
     * Unassign all categories from articles
     */
    public function unassignRemoteFromLocalCategoryAction()
    {
        $articleIds = $this->request->getParam('articleIds', array());

        try {
            $this->getImportService()->unAssignArticleCategories($articleIds);
        } catch (\Exception $e) {
            $this->getLogger()->write(true, $e->getMessage(), $e);
            $this->View()->assign(array(
                'success' => false,
                'error' => 'Categories could not be unassigned from products!',
            ));
            return;
        }

        $this->View()->assign(array(
            'success' => true
        ));
    }

    public function assignRemoteToLocalCategoryAction()
    {
        $localCategoryId = (int)$this->request->getParam('localCategoryId', 0);
        $remoteCategoryKey = $this->request->getParam('remoteCategoryKey', null);
        $remoteCategoryLabel = $this->request->getParam('remoteCategoryLabel', null);

        if ($localCategoryId == 0 || !$remoteCategoryKey || !$remoteCategoryLabel) {
            $this->View()->assign(array(
                'success' => false,
                'error' => 'Invalid local or remote category',
            ));
            return;
        }

        try {
            $this->getImportService()->importRemoteCategory($localCategoryId, $remoteCategoryKey, $remoteCategoryLabel);
        } catch (\RuntimeException $e) {
            $this->getLogger()->write(true, $e->getMessage(), $e);
            $this->View()->assign(array(
                'success' => false,
                'error' => 'Remote category could not be mapped to local category!',
            ));
            return;
        }

        $this->View()->assign(array(
            'success' => true
        ));
    }

    public function activateArticlesAction()
    {
        $articleIds = $this->request->getParam('ids', 0);

        try {
            $this->getImportService()->activateArticles($articleIds);
        } catch (\Exception $e) {
            $this->getLogger()->write(true, $e->getMessage(), $e);
            $this->View()->assign(array(
                'success' => false,
                'error' => 'There is a problem with products activation!',
            ));
            return;
        }

        $this->View()->assign(array(
            'success' => true,
        ));
    }

    private function getCategoryExtractor()
    {
        if (!$this->categoryExtractor) {
            $this->categoryExtractor = new \ShopwarePlugins\Connect\Components\CategoryExtractor(
                Shopware()->Models()->getRepository('Shopware\CustomModels\Connect\Attribute'),
                new \ShopwarePlugins\Connect\Components\CategoryResolver\AutoCategoryResolver(
                    Shopware()->Models(),
                    Shopware()->Models()->getRepository('Shopware\Models\Category\Category'),
                    Shopware()->Models()->getRepository('Shopware\CustomModels\Connect\RemoteCategory')
                ),
                new \Shopware\Connect\Gateway\PDO(Shopware()->Db()->getConnection())
            );
        }

        return $this->categoryExtractor;
    }

    /**
     * @return \Shopware\CustomModels\Connect\ProductToRemoteCategoryRepository
     */
    private function getProductToRemoteCategoryRepository()
    {
        if (!$this->productToRemoteCategoryRepository) {
            $this->productToRemoteCategoryRepository = Shopware()->Models()->getRepository(
                'Shopware\CustomModels\Connect\ProductToRemoteCategory'
            );
        }

        return $this->productToRemoteCategoryRepository;
    }

    /**
     * @return \Shopware\CustomModels\Connect\RemoteCategoryRepository
     */
    private function getRemoteCategoryRepository()
    {
        if (!$this->remoteCategoryRepository) {
            $this->remoteCategoryRepository = Shopware()->Models()->getRepository(
                'Shopware\CustomModels\Connect\RemoteCategory'
            );
        }

        return $this->remoteCategoryRepository;
    }

    /**
     * @return \ShopwarePlugins\Connect\Components\ImportService
     */
    private function getImportService()
    {
        if (!$this->importService) {
            $this->importService = new \ShopwarePlugins\Connect\Components\ImportService(
                $this->getModelManager(),
                $this->container->get('multi_edit.product'),
                $this->getCategoryRepository(),
                $this->getModelManager()->getRepository('Shopware\Models\Article\Article'),
                $this->getRemoteCategoryRepository(),
                $this->getProductToRemoteCategoryRepository(),
                $this->getAutoCategoryResolver(),
                $this->getCategoryExtractor()
            );
        }

        return $this->importService;
    }

    /**
     * @return \ShopwarePlugins\Connect\Components\CategoryResolver\AutoCategoryResolver
     */
    private function getAutoCategoryResolver()
    {
        if (!$this->autoCategoryResolver) {
            $this->autoCategoryResolver = new \ShopwarePlugins\Connect\Components\CategoryResolver\AutoCategoryResolver(
                $this->getModelManager(),
                $this->getCategoryRepository(),
                $this->getRemoteCategoryRepository()
            );
        }

        return $this->autoCategoryResolver;
    }

    /**
     * @return \Shopware\Models\Category\Repository
     */
    private function getCategoryRepository()
    {
        if (!$this->categoryRepository) {
            $this->categoryRepository = $this->getModelManager()->getRepository('Shopware\Models\Category\Category');
        }

        return $this->categoryRepository;
    }

    private function getLogger()
    {
        if (!$this->logger) {
            $this->logger = new \ShopwarePlugins\Connect\Components\Logger(Shopware()->Db());
        }

        return $this->logger;
    }
} 