<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Functional\Subscribers;

use ShopwarePlugins\Connect\Tests\DatabaseTestCaseTrait;
use Shopware\Components\Model\ModelManager;

class LifecycleTest extends \Enlight_Components_Test_Plugin_TestCase
{
    use DatabaseTestCaseTrait;

    /**
     * @var ModelManager
     */
    private $manager;

    /**
     * @before
     */
    public function prepare()
    {
        $this->manager = Shopware()->Models();
        // disable auth and acl
        Shopware()->Plugins()->Backend()->Auth()->setNoAuth();
        Shopware()->Plugins()->Backend()->Auth()->setNoAcl();
    }

    public function test_generate_delete_change_for_variants()
    {
        $this->importFixtures(__DIR__ . '/_fixtures/simple_variants.sql');

        $this->Request()
            ->setMethod('POST')
            ->setPost('articleId', 32870)
            ->setPost('id', 2404537);

        $this->dispatch('backend/Article/deleteDetail');

        $this->assertEquals(200, $this->Response()->getHttpResponseCode());
        $this->assertTrue($this->View()->success);

        $changes = $this->manager->getConnection()->fetchAll(
            'SELECT c_entity_id, c_operation, c_revision, c_payload FROM sw_connect_change WHERE c_entity_id = ?',
            ['32870-2404537']
        );

        $this->assertCount(1, $changes);
        $this->assertEquals('delete', $changes[0]['c_operation']);
    }

    public function test_generate_delete_change_only_for_exported()
    {
        $this->importFixtures(__DIR__ . '/_fixtures/simple_variants.sql');

        $detailExist = $this->manager->getConnection()->fetchColumn(
            'SELECT COUNT(id) FROM s_articles_details WHERE id = ? AND articleID = ?',
            [2404544, 32871]
        );
        $this->assertEquals(1, $detailExist, 'Article detail does not exist!');

        $this->Request()
            ->setMethod('POST')
            ->setPost('articleId', 32871)
            ->setPost('id', 2404544);

        $this->dispatch('backend/Article/deleteDetail');

        $this->assertEquals(200, $this->Response()->getHttpResponseCode());
        $this->assertTrue($this->View()->success);

        $changes = $this->manager->getConnection()->fetchAll(
            'SELECT c_entity_id, c_operation, c_revision, c_payload FROM sw_connect_change WHERE c_entity_id = ?',
            ['32871-2404544']
        );

        $this->assertEmpty($changes);
    }
}
