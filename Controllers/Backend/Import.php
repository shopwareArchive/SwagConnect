<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use ShopwarePlugins\Connect\Components\ConfigFactory;

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

    private $remoteCategoryRepository;

    private $autoCategoryResolver;

    private $categoryRepository;

    private $logger;

    public function getImportedProductCategoriesTreeAction()
    {
        $parent = $this->request->getParam('categoryId', 'root');
        $hideMapped = (bool) $this->request->getParam('hideMappedProducts', false);

        $query = $this->request->getParam('remoteCategoriesQuery', '');
        $node = $this->request->getParam('id');

        if (trim($query) !== '') {
            try {
                $categories = $this->getCategoryExtractor()->getNodesByQuery($hideMapped, $query, $parent, $node);
                $this->View()->assign([
                    'success' => true,
                    'data' => $categories,
                ]);
            } catch (\InvalidArgumentException $e) {
                $this->View()->assign([
                    'success' => false,
                    'message' => $e->getMessage(),
                ]);
            }

            return;
        }

        switch ($parent) {
            case 'root':
                $categories = $this->getCategoryExtractor()->getMainNodes($hideMapped);
                break;
            case is_numeric($parent):
                $categories = $this->getCategoryExtractor()->getStreamsByShopId($parent);
                break;
            case strpos($parent, '_stream_') > 0:
                list($shopId, $stream) = explode('_stream_', $parent);
                $categories = $this->getCategoryExtractor()->getRemoteCategoriesTreeByStream($stream, $shopId, $hideMapped);
                break;
            default:
                // given id must have following structure:
                // shopId5~/english/boots/nike
                // shopId is required parameter to fetch all child categories of this parent
                // $matches[2] gives us only shopId as a int
                preg_match('/^(shopId(\d+)~)(stream~(.*)~)(.*)$/', $node, $matches);
                if (empty($matches)) {
                    $this->View()->assign([
                        'success' => false,
                        'message' => 'Node must contain shopId and stream',
                    ]);

                    return;
                }
                $categories = $this->getCategoryExtractor()->getRemoteCategoriesTree($parent, false, $hideMapped, $matches[2], $matches[4]);
        }

        $this->View()->assign([
            'success' => true,
            'data' => $categories,
        ]);
    }

    public function loadArticlesByRemoteCategoryAction()
    {
        $category = $this->request->getParam('category', null);
        $shopId = $this->request->getParam('shopId', 0);
        $limit = (int) $this->request->getParam('limit', 10);
        $offset = (int) $this->request->getParam('start', 0);
        $hideMapped = (bool) $this->request->getParam('hideMappedProducts', false);
        $searchQuery = $this->request->getParam('remoteArticlesQuery', '');

        $stream = $this->request->getParam('stream', null);

        if (strpos($category, '_stream_') > 0) {
            $stream = explode('_stream_', $category);
            $stream = $stream[1];
            $category = null;
        }

        $query = $this->getProductToRemoteCategoryRepository()->findArticlesByRemoteCategory($category,
            $shopId,
            $stream,
            $limit,
            $offset,
            $hideMapped,
            $searchQuery);

        $query->setHydrationMode($query::HYDRATE_OBJECT);

        $paginator = new \Doctrine\ORM\Tools\Pagination\Paginator($query);
        $totalCount = $paginator->count();
        $this->View()->assign([
            'success' => true,
            'data' => $query->getArrayResult(),
            'total' => $totalCount,
        ]);
    }

    public function loadBothArticleTypesAction()
    {
        $categoryId = (int) $this->request->getParam('categoryId', 0);
        $limit = (int) $this->request->getParam('limit', 10);
        $offset = (int) $this->request->getParam('start', 0);
        $showOnlyConnectArticles = $this->request->getParam('showOnlyConnectArticles', null);
        $query = $this->request->getParam('localArticlesQuery', '');

        $result = $this->getImportService()->findBothArticlesType(
            $categoryId,
            $query,
            $showOnlyConnectArticles ? true : false,
            $limit,
            $offset
        );

        $this->View()->assign([
            'success' => true,
            'data' => $result['data'],
            'total' => $result['total'],
        ]);
    }

    public function assignArticlesToCategoryAction()
    {
        $categoryId = (int) $this->request->getParam('categoryId', 0);
        $articleIds = $this->request->getParam('articleIds', []);

        $snippets = Shopware()->Snippets()->getNamespace('backend/connect/view/main');

        if ($categoryId == 0 || empty($articleIds)) {
            $this->View()->assign([
                'success' => false,
                'message' => $snippets->get(
                    'import/message/category_has_children',
                    'Invalid category or articles',
                    true
                ),
            ]);

            return;
        }

        if ($this->getImportService()->hasCategoryChildren($categoryId)) {
            $this->View()->assign([
                'success' => false,
                'message' => $snippets->get(
                    'import/message/category_has_children',
                    'Category has subcategories, please make sure you have selected single one',
                    true
                ),
            ]);

            return;
        }

        try {
            $this->getImportService()->assignCategoryToArticles($categoryId, $articleIds);
        } catch (\RuntimeException $e) {
            $this->getLogger()->write(true, $e->getMessage(), $e);
            $this->View()->assign([
                'success' => false,
                'message' => $snippets->get(
                    'import/message/failed_product_to_category_assignment',
                    'Category could not be assigned to products!',
                    true
                ),
            ]);

            return;
        }

        $this->View()->assign([
            'success' => true
        ]);
    }

    /**
     * Unassign all categories from articles
     */
    public function unassignRemoteArticlesFromLocalCategoryAction()
    {
        $articleIds = $this->request->getParam('articleIds', []);

        try {
            $this->getImportService()->unAssignArticleCategories($articleIds);
        } catch (\Exception $e) {
            $this->getLogger()->write(true, $e->getMessage(), $e);
            $this->View()->assign([
                'success' => false,
                'error' => 'Categories could not be unassigned from products!',
            ]);

            return;
        }

        $this->View()->assign([
            'success' => true
        ]);
    }

    public function assignRemoteToLocalCategoryAction()
    {
        $localCategoryId = (int) $this->request->getParam('localCategoryId', 0);
        $remoteCategoryKey = $this->request->getParam('remoteCategoryKey', null);
        $remoteCategoryLabel = $this->request->getParam('remoteCategoryLabel', null);

        if ($localCategoryId == 0 || !$remoteCategoryKey || !$remoteCategoryLabel) {
            $this->View()->assign([
                'success' => false,
                'error' => 'Invalid local or remote category',
            ]);

            return;
        }

        try {
            $categories = $this->getImportService()->createCategoriesFromRemoteCategoires($localCategoryId, $remoteCategoryKey, $remoteCategoryLabel);
        } catch (\RuntimeException $e) {
            $this->getLogger()->write(true, $e->getMessage(), $e);
            $this->View()->assign([
                'success' => false,
                'error' => 'Remote category could not be mapped to local category!',
            ]);

            return;
        }

        $this->View()->assign([
            'success' => true,
            'categories' => json_encode($categories)
        ]);
    }

    public function assignCategoriesToProductsAction()
    {
        $categories = json_decode($this->request->getParam('categories', []));

        try {
            $this->getImportService()->assignCategoriesToProducts($categories);
        } catch (\RuntimeException $e) {
            $this->getLogger()->write(true, $e->getMessage(), $e);
            $this->View()->assign([
                'success' => false,
                'error' => 'Remote category could not be mapped to local category!',
            ]);

            return;
        }

        $this->View()->assign([
            'success' => true
        ]);
    }

    /**
     * Unassign all remote articles from local category
     */
    public function unassignRemoteToLocalCategoryAction()
    {
        $localCategoryId = (int) $this->request->getParam('localCategoryId', 0);

        if ($localCategoryId == 0) {
            $this->View()->assign([
                'success' => false,
                'error' => 'Invalid local or remote category',
            ]);

            return;
        }

        try {
            $articleIds = $this->getImportService()->findRemoteArticleIdsByCategoryId($localCategoryId);
            $this->getImportService()->unAssignArticleCategories($articleIds);
        } catch (\Exception $e) {
            $this->getLogger()->write(true, $e->getMessage(), $e);
            $this->View()->assign([
                'success' => false,
                'error' => 'Products from remote category could not be unassigned!',
            ]);

            return;
        }

        $this->View()->assign([
            'success' => true
        ]);
    }

    public function activateArticlesAction()
    {
        $articleIds = $this->request->getParam('ids', 0);

        try {
            $this->getImportService()->activateArticles($articleIds);
        } catch (\Exception $e) {
            $this->getLogger()->write(true, $e->getMessage(), $e);
            $this->View()->assign([
                'success' => false,
                'error' => 'There is a problem with products activation!',
            ]);

            return;
        }

        $this->View()->assign([
            'success' => true,
        ]);
    }

    /**
     * Deactivates connect categories
     */
    public function deactivateCategoryAction()
    {
        $categoryId = $this->request->getParam('categoryId', 0);

        if (!$categoryId) {
            return $this->View()->assign([
                'success' => false,
                'error' => 'Please select a category for deactivation',
            ]);
        }

        /** @var \Shopware\Models\Category\Category $category */
        $category = $this->getCategoryRepository()->findOneBy(['id' => $categoryId]);

        if (!$category) {
            $this->View()->assign([
                'success' => false,
                'error' => 'Please select a valid category for deactivation',
            ]);
        }

        $categoryIds = $this->getCategoryExtractor()->getCategoryIdsCollection($category);

        $changedCount = 0;
        if (count($categoryIds) > 0) {
            $changedCount = $this->getImportService()->deactivateLocalCategoriesByIds($categoryIds);
        }

        $this->View()->assign([
            'success' => true,
            'deactivatedCategoriesCount' => $changedCount,
        ]);
    }

    /**
     * @return Enlight_View|Enlight_View_Default
     */
    public function getSuppliersAction()
    {
        $suppliers = [];
        $pdoGateway = $this->getPdoGateway();

        foreach ($pdoGateway->getConnectedShopIds() as $shopId) {
            $configuration = $pdoGateway->getShopConfiguration($shopId);
            $suppliers = [
                'id' => $shopId,
                'name' => $configuration->displayName,
                'logoUrl' => $configuration->logoUrl,
            ];
        }

        return $this->View()->assign([
            'success' => true,
            'data' => $suppliers,
        ]);
    }

    public function recreateRemoteCategoriesAction()
    {
        $this->getAutoCategoryReverter()->recreateRemoteCategories();

        return $this->View()->assign([
            'success' => true,
        ]);
    }

    private function getCategoryExtractor()
    {
        if (!$this->categoryExtractor) {
            $modelManager = Shopware()->Models();
            $this->categoryExtractor = new \ShopwarePlugins\Connect\Components\CategoryExtractor(
                $modelManager->getRepository('Shopware\CustomModels\Connect\Attribute'),
                $this->getAutoCategoryResolver(),
                $this->getPdoGateway(),
                new \ShopwarePlugins\Connect\Components\RandomStringGenerator(),
                Shopware()->Db()
            );
        }

        return $this->categoryExtractor;
    }

    /**
     * @return \Shopware\Connect\Gateway\PDO
     */
    private function getPdoGateway()
    {
        return new \Shopware\Connect\Gateway\PDO(Shopware()->Db()->getConnection());
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
        return $this->container->get('swagconnect.import_service');
    }

    private function getAutoCategoryReverter()
    {
        return $this->container->get('swagconnect.auto_category_reverter');
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
                $this->getRemoteCategoryRepository(),
                ConfigFactory::getConfigInstance()
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
