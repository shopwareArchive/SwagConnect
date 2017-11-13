<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Functional\Subscriber;

use ShopwarePlugins\Connect\Tests\DatabaseTestCaseTrait;

class ArticleTest extends \Enlight_Components_Test_Plugin_TestCase
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

    public function testExtendBackendArticlePropertyGroup()
    {
        $this->importFixtures(__DIR__ . '/_fixtures/articleWithPriceGroup.sql');

        $this->Request()
            ->setMethod('POST')
            ->setPost('articleId', '32870');
        $this->dispatch('backend/Article/setPropertyList');

        self::assertEquals(200, $this->Response()->getHttpResponseCode());
        self::assertTrue($this->View()->success);

        $statuses = $this->manager->getConnection()->executeQuery(
            'SELECT export_status FROM s_plugin_connect_items WHERE article_id = ?',
            [32870]
        )->fetchAll(\PDO::FETCH_COLUMN);

        foreach ($statuses as $status) {
            //doesn't matter that theres an error
            //but this verifys that the export of the articles has run
            //we had an bug there: https://jira.shopware.com/browse/CON-4955
            self::assertEquals('error', $status);
        }
    }
}
