<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Shopware_Controllers_Frontend_ConnectProductGateway extends Enlight_Controller_Action
{
    /** @var ShopwarePlugins\Connect\Components\ConnectFactory */
    private $factory;

    /** @var \ShopwarePlugins\Connect\Components\FrontendQuery\FrontendQuery */
    private $frontendQuery;

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
     * @return \ShopwarePlugins\Connect\Components\FrontendQuery\FrontendQuery
     */
    protected function getFrontendQuery()
    {
        if ($this->frontendQuery === null) {
            $this->frontendQuery = $this->get('swagconnect.frontend_query');
        }

        return $this->frontendQuery;
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

        /** @var \Shopware\Models\Article\Article $articleModel */
        $articleModel = $this->getFrontendQuery()->getArticleById($articleId);

        if (!$articleModel) {
            $this->forward('index', 'index');

            return;
        }

        // if sourceId contains detail id part get detail model
        // if not use main detail
        if ($detailId > 0) {
            /** @var \Shopware\Models\Article\Detail $articleDetailModel */
            $articleDetailModel = $this->getFrontendQuery()->getArticleDetailById($detailId);

            if (!$articleDetailModel) {
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

        $shopId = (int) $this->Request()->getParam('shId');
        if ($shopId > 0) {
            /** @var \Shopware\Models\Shop\Shop $shop */
            $shop = $this->getFrontendQuery()->getShopById($shopId);

            if ($shop instanceof \Shopware\Models\Shop\Shop) {
                $this->forwardToArticle($shop->getId(), $articleModel->getId(), $articleDetailModel->getId());

                return;
            }
        }

        // find the first category which belongs to a shop and forward to this shop
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
        /** @var \Shopware\Models\Shop\Shop $shop */
        $shop = $this->getFrontendQuery()->getShopById($shopId, true);

        if (!$shop) {
            $this->forward('index', 'index');
        }
        $shop->registerResources(Shopware()->Container()->get('bootstrap'));

        $this->Response()->setCookie('shop', $shopId, 0, $shop->getBasePath());

        $this->redirect(Shopware()->Front()->Router()->assemble([
            'sArticle' => $articleId,
            'sArticleDetail' => $articleDetailId,
            'module' => 'frontend',
            'controller' => 'detail'
        ]));
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
            /** @var Shopware\Models\Category\Category $category */
            $category = $this->getFrontendQuery()->getCategoryById($mainCategory[0]);
        }

        /** @var \Shopware\Models\Shop\Shop $shop */
        $shop = $this->getFrontendQuery()->getShopByCategoryId($category->getId());

        return $shop;
    }

    /**
     * Return a single connect product for the given ID
     *
     * @param $id
     * @return Shopware\Connect\Struct\Product|null
     */
    public function getProductById($id)
    {
        $products = $this->getHelper()->getLocalProduct([$id]);
        if (empty($products)) {
            return null;
        }

        return $products[0];
    }
}
