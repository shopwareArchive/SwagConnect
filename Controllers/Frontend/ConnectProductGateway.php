<?php

/**
 * The product gateway controller is used to forward to a product from connect. It is subshop-aware and will
 * redirect the user to the 'best' subshop available.
 *
 * Class Shopware_Controllers_Frontend_ConnectProductGateway
 */
class Shopware_Controllers_Frontend_ConnectProductGateway extends Enlight_Controller_Action
{
    /** @var  Shopware\Models\Shop\Repository */
    private $shopRepository;

    /** @var  Shopware\Models\Article\Repository */
    private $articleRepository;

    private $articleDetailRepository;

    /** @var  ShopwarePlugins\Connect\Components\ConnectFactory */
    private $factory;

    /** @var  Shopware\Models\Category\Repository */
    private $categoryRepository;

    /**
     * @return \Shopware\Components\Model\ModelRepository
     */
    private function getShopRepository()
    {
        if (!$this->shopRepository) {
            $this->shopRepository = Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop');
        }
        return $this->shopRepository;
    }

    /**
     * @return \Shopware\Components\Model\ModelRepository
     */
    private function getArticleRepository()
    {
        if (!$this->articleRepository) {
            $this->articleRepository = Shopware()->Models()->getRepository('Shopware\Models\Article\Article');
        }
        return $this->articleRepository;
    }

    public function getArticleDetailRepository()
    {
        if (!$this->articleDetailRepository) {
            $this->articleDetailRepository = Shopware()->Models()->getRepository('Shopware\Models\Article\Detail');
        }

        return $this->articleDetailRepository;
    }

    /**
     * @return \Shopware\Models\Category\Repository
     */
    private function getCategoryRepository()
    {
        if (!$this->categoryRepository) {
            $this->categoryRepository = Shopware()->Models()->getRepository('Shopware\Models\Category\Category');
        }
        return $this->categoryRepository;
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
     * Redirect the user to the best subshop with this product
     *
     * - first the subshop of the category mapping is checked, so the user will be redirected to the subshop, the
     *   connect article was originally mapped to.
     * - if the mapping is not available any more, it will redirect to the subshop of the category, the local article is
     *   currently in
     * - in case of any error the user is redirected to the index/index page
     */
    public function productAction()
    {
        $sourceId = $this->Request()->getParam('id');
        $attributeRepository = Shopware()->Models()->getRepository('Shopware\Models\Attribute\Category');

        // if no id was given, forward to start page
        if (!$sourceId) {
            $this->forward('index', 'index');
            return;
        }

        list($articleId, $detailId) = $this->getHelper()->explodeArticleId($sourceId);

        // if no article id was given, forward to start page
        if (!isset($articleId)) {
            $this->forward('index', 'index');
            return;
        }

        /** @var Shopware\Models\Article\Article $articleModel */
        $articleModel = $this->getArticleRepository()->find($articleId);
        if (!$articleModel){
            $this->forward('index', 'index');
            return;
        }

        // if sourceId contains detail id part get detail model
        // if not use main detail
        if ($detailId > 0) {
            /** @var \Shopware\Models\Article\Detail $articleDetailModel */
            $articleDetailModel = $this->getArticleDetailRepository()->find($detailId);
            if (!$articleDetailModel){
                $this->forward('index', 'index');
                return;
            }
        } else {
            $articleDetailModel = $articleModel->getMainDetail();
        }

        $product = $this->getProductById($sourceId);
        // if the product does not exist, forward to start page
        if (empty($product)) {
            $this->forward('index', 'index');
            return;
        }

        $shopId = (int)$this->Request()->getParam('shId');
        if ($shopId > 0) {
            $shop = $this->getShopRepository()->find($shopId);
            if ($shop instanceof \Shopware\Models\Shop\Shop) {
                $this->forwardToArticle($shop->getId(), $articleModel->getId(), $articleDetailModel->getId());
                return;
            }
        }

        // If we have a mapping and can resolve it to a local category, forward to the product
        if (!empty($product->categories)) {
            foreach ($product->categories as $mapping) {
                /** @var Shopware\Models\Attribute\Category $attribute */
                $attribute = $attributeRepository->findOneBy(array('connectExportMapping' => $mapping));
                if ($attribute) {
                    $category = $attribute->getCategory();
                    if (!$this->doesArticleBelongToCategory($category, $articleId)) {
                        continue;
                    }

                    $shop = $this->getShopFromCategory($category);
                    if (!$shop) {
                        continue;
                    }

                    $this->forwardToArticle($shop->getId(), $articleModel->getId(), $articleDetailModel->getId());
                    return;
                }
            }
        }

        // If we don't have a mapping, find the first category which belongs to a shop and forward to this shop
        $localCategories = $articleModel->getCategories();
        if (!empty($localCategories)) {
            foreach ($localCategories as $category) {
                $shop = $this->getShopFromCategory($category);
                if ($shop) {
                    $this->forwardToArticle($shop->getId(), $articleModel->getId(), $articleDetailModel->getId());
                    return;
                }
            }
        }

        $this->forward('index', 'index');
    }

    /**
     * Forward to a given shop
     *
     * @param $shopId
     * @param $articleId
     * @param $articleDetailId
     */
    private function forwardToArticle($shopId, $articleId, $articleDetailId = null)
    {
        /** @var Shopware\Models\Shop\Shop $shop */
        $shop = $this->getShopRepository()->getActiveById($shopId);
        if (!$shop) {
            $this->forward('index', 'index');
        }
        $shop->registerResources(Shopware()->Bootstrap());

        $this->Response()->setCookie('shop', $shopId, 0, $shop->getBasePath());

        $this->redirect(Shopware()->Front()->Router()->assemble(array(
            'sArticle' => $articleId,
            'sArticleDetail' => $articleDetailId,
            'module' => 'frontend',
            'controller' => 'detail'
        )));
    }

    /**
     * Returns the shop which the given category belongs to
     *
     * @param $category Shopware\Models\Category\Category
     * @return \Shopware\Models\Shop\Shop
     */
    public function getShopFromCategory($category)
    {
        $path = $category->getPath();
        $parts = explode('|', $path);

        $mainCategory = array_slice($parts, -2, 1);
        if ($mainCategory[0] > 0) {
            $category = $this->getCategoryRepository()->find($mainCategory[0]);
        }

        return $this->getShopRepository()->findOneBy(array('category' => $category));
    }

    /**
     * Return a single connect product for the given ID
     *
     * @param $id
     * @return Shopware\Connect\Struct\Product|null
     */
    public function getProductById($id)
    {
        $products = $this->getHelper()->getLocalProduct(array($id));
        if (empty($products)) {
            return null;
        }
        return $products[0];
    }

    /**
     * Checks if a given product belongs to a given category
     *
     * @param $category Shopware\Models\Category\Category
     * @param $id
     * @return bool
     */
    public function doesArticleBelongToCategory($category, $id)
    {
        $result = Shopware()->Db()->fetchOne(
            'SELECT id FROM s_articles_categories_ro WHERE articleID = ? and categoryID = ?',
            array(
                $id,
                $category->getId()
        ));

        return !empty($result);
    }
}