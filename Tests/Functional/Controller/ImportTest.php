<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Functional\Controller;

use ShopwarePlugins\Connect\Tests\DatabaseTestCaseTrait;

class ImportTest extends \Enlight_Components_Test_Plugin_TestCase
{
    use DatabaseTestCaseTrait;

    public function setUp()
    {
        parent::setUp();

        // disable auth and acl
        Shopware()->Plugins()->Backend()->Auth()->setNoAuth();
        Shopware()->Plugins()->Backend()->Auth()->setNoAcl();

        $this->manager = Shopware()->Models();
    }

    public function testGetImportedProductCategoriesTreeAction()
    {
        $this->dispatch('backend/Import/getImportedProductCategoriesTree');
        self::assertEquals(200, $this->Response()->getHttpResponseCode());
        $returnData = $this->View()->data;

        self::assertTrue($this->View()->success);
        self::assertTrue(is_array($returnData), 'Returned data must be array');

        $this->Request()
            ->setMethod('POST')
            ->setPost('categoryId', '/deutsch/boots/nike')
            ->setPost('id', 'shopId6~stream~Awesome Products~/deutsch/boots/nike');
        $this->dispatch('backend/Import/getImportedProductCategoriesTree');

        self::assertEquals(200, $this->Response()->getHttpResponseCode());
        self::assertTrue($this->View()->success);
    }

    public function testAssignRemoteToLocalCategoryAction()
    {
        $this->importFixtures(__DIR__ . '/../_fixtures/categories.sql');
        $this->dispatch('backend/Import/assignRemoteToLocalCategory');

        self::assertEquals(200, $this->Response()->getHttpResponseCode());
        self::assertFalse($this->View()->success);

        $this->Request()
            ->setMethod('POST')
            ->setPost('localCategoryId', 140809703)
            ->setPost('remoteCategoryKey', '/deutsch/television')
            ->setPost('remoteCategoryLabel', 'Television')
            ->setPost('node', 'shopId6~stream~Awesome Products~/deutsch/television');
        $this->dispatch('backend/Import/assignRemoteToLocalCategory');

        self::assertTrue($this->View()->success);
    }

    /**
     * @test
     */
    public function get_imported_product_categories_tree_when_parent_is_numeric()
    {
        $this->Request()
            ->setMethod('POST')
            ->setPost('id', 4);
        $this->dispatch('backend/Import/getImportedProductCategoriesTree');

        self::assertEquals(200, $this->Response()->getHttpResponseCode());
        self::assertTrue($this->View()->success);
        self::assertTrue(is_array($this->View()->data), 'Returned data must be array');
    }

    /**
     * @test
     */
    public function get_imported_product_categories_tree_when_parent_is_stream()
    {
        $this->Request()
            ->setMethod('POST')
            ->setPost('id', '3_stream_Awesome products');
        $this->dispatch('backend/Import/getImportedProductCategoriesTree');

        self::assertEquals(200, $this->Response()->getHttpResponseCode());
        self::assertTrue($this->View()->success);
        self::assertTrue(is_array($this->View()->data), 'Returned data must be array');
    }

    /**
     * @test
     */
    public function get_imported_product_categories_tree_when_parent_is_category()
    {
        $this->Request()
            ->setMethod('POST')
            ->setPost('id', '/bücher');
        $this->dispatch('backend/Import/getImportedProductCategoriesTree');

        self::assertEquals(200, $this->Response()->getHttpResponseCode());
        self::assertTrue($this->View()->success);
        self::assertTrue(is_array($this->View()->data), 'Returned data must be array');
    }

    /**
     * @test
     */
    public function load_articles_by_remote_category_with_empty_category()
    {
        $this->Request()
            ->setMethod('POST')
            ->setPost('shopId', '3');
        $this->dispatch('backend/Import/loadArticlesByRemoteCategory');

        self::assertEquals(200, $this->Response()->getHttpResponseCode());
        self::assertTrue($this->View()->success);
        self::assertTrue(is_array($this->View()->data), 'Returned data must be array');
    }

    /**
     * @test
     */
    public function load_articles_by_remote_category_with_stream()
    {
        $this->Request()
            ->setMethod('POST')
            ->setPost('category', '3_stream_Awesome products')
            ->setPost('shopId', '3');
        $this->dispatch('backend/Import/loadArticlesByRemoteCategory');

        self::assertEquals(200, $this->Response()->getHttpResponseCode());
        self::assertFalse($this->View()->success);
        self::assertTrue(is_string($this->View()->message), 'Returned message must a string');
    }

    /**
     * @test
     */
    public function load_articles_by_remote_category_with_empty_shop_id()
    {
        $this->Request()
            ->setMethod('POST')
            ->setPost('category', 'Awesome products');
        $this->dispatch('backend/Import/loadArticlesByRemoteCategory');

        self::assertEquals(200, $this->Response()->getHttpResponseCode());
        self::assertTrue($this->View()->success);
        self::assertTrue(is_array($this->View()->data), 'Returned data must be array');
    }

    public function testUnassignRemoteToLocalCategoryAction()
    {
        $this->Request()
            ->setMethod('POST')
            ->setPost('localCategoryId', 6);
        $this->dispatch('backend/Import/unassignRemoteToLocalCategory');

        self::assertEquals(200, $this->Response()->getHttpResponseCode());
        self::assertTrue($this->View()->success);
    }

    public function testUnassignRemoteToLocalCategoryActionWithoutCategoryId()
    {
        $this->dispatch('backend/Import/unassignRemoteToLocalCategory');

        self::assertEquals(200, $this->Response()->getHttpResponseCode());
        self::assertFalse($this->View()->success);
        self::assertEquals('Invalid local or remote category', $this->View()->error);
    }

    public function testUnassignRemoteArticlesFromLocalCategoryAction()
    {
        $this->dispatch('backend/Import/unassignRemoteArticlesFromLocalCategory');

        self::assertEquals(200, $this->Response()->getHttpResponseCode());
        self::assertTrue($this->View()->success);
    }

    public function testRecreateRemoteCategoriesAction()
    {
        $this->dispatch('backend/Import/recreateRemoteCategories');

        self::assertEquals(200, $this->Response()->getHttpResponseCode());
        self::assertTrue($this->View()->success);
    }
}
