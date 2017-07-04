<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\ShopwarePlugins\Connect;

class ImportTest extends \Enlight_Components_Test_Controller_TestCase
{
    public function setUp()
    {
        parent::setUp();

        // disable auth and acl
        Shopware()->Plugins()->Backend()->Auth()->setNoAuth();
        Shopware()->Plugins()->Backend()->Auth()->setNoAcl();
    }

    public function testGetImportedProductCategoriesTreeAction()
    {
        $this->dispatch('backend/Import/getImportedProductCategoriesTree');
        $this->assertEquals(200, $this->Response()->getHttpResponseCode());
        $returnData = $this->View()->data;

        $this->assertTrue($this->View()->success);
        $this->assertTrue(is_array($returnData), 'Returned data must be array');
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

        $this->assertEquals(200, $this->Response()->getHttpResponseCode());
        $this->assertTrue($this->View()->success);
        $this->assertTrue(is_array($this->View()->data), 'Returned data must be array');
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

        $this->assertEquals(200, $this->Response()->getHttpResponseCode());
        $this->assertTrue($this->View()->success);
        $this->assertTrue(is_array($this->View()->data), 'Returned data must be array');
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

        $this->assertEquals(200, $this->Response()->getHttpResponseCode());
        $this->assertTrue($this->View()->success);
        $this->assertTrue(is_array($this->View()->data), 'Returned data must be array');
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

        $this->assertEquals(200, $this->Response()->getHttpResponseCode());
        $this->assertTrue($this->View()->success);
        $this->assertTrue(is_array($this->View()->data), 'Returned data must be array');
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

        $this->assertEquals(200, $this->Response()->getHttpResponseCode());
        $this->assertTrue($this->View()->success);
        $this->assertTrue(is_array($this->View()->data), 'Returned data must be array');
    }

    /**
     * @test
     */
    public function load_articles_by_remote_category_with_empty_shop_id()
    {
        $this->Request()
            ->setMethod('POST')
            ->setPost('category', '3_stream_Awesome products');
        $this->dispatch('backend/Import/loadArticlesByRemoteCategory');

        $this->assertEquals(200, $this->Response()->getHttpResponseCode());
        $this->assertTrue($this->View()->success);
        $this->assertTrue(is_array($this->View()->data), 'Returned data must be array');
    }

    public function testUnassignRemoteToLocalCategoryAction()
    {
        $this->Request()
            ->setMethod('POST')
            ->setPost('localCategoryId', 6);
        $this->dispatch('backend/Import/unassignRemoteToLocalCategory');

        $this->assertEquals(200, $this->Response()->getHttpResponseCode());
        $this->assertTrue($this->View()->success);
    }

    public function testUnassignRemoteToLocalCategoryActionWithoutCategoryId()
    {
        $this->dispatch('backend/Import/unassignRemoteToLocalCategory');

        $this->assertEquals(200, $this->Response()->getHttpResponseCode());
        $this->assertFalse($this->View()->success);
        $this->assertEquals('Invalid local or remote category', $this->View()->error);
    }

    public function testUnassignRemoteArticlesFromLocalCategoryAction()
    {
        $this->dispatch('backend/Import/unassignRemoteArticlesFromLocalCategory');

        $this->assertEquals(200, $this->Response()->getHttpResponseCode());
        $this->assertTrue($this->View()->success);
    }

    public function testRecreateRemoteCategoriesAction()
    {
        $this->dispatch('backend/Import/recreateRemoteCategories');

        $this->assertEquals(200, $this->Response()->getHttpResponseCode());
        $this->assertTrue($this->View()->success);
    }
}
