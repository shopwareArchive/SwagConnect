<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Integration\Components;

use ShopwarePlugins\Connect\Components\TriggerService;
use ShopwarePlugins\Connect\Tests\DatabaseTestCaseTrait;

class TriggerServiceTest extends \PHPUnit_Framework_TestCase
{
    use DatabaseTestCaseTrait;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $connection;

    // Triggers and our transaction handling for testcases don't work together so we do it manually
    public function setUp()
    {
        $this->connection = Shopware()->Container()->get('dbal_connection');
        $this->importFixtures(__DIR__ . '/_fixtures/article_for_trigger_test.sql');
        $triggerService = new TriggerService($this->connection);
        $triggerService->activateTriggers();
    }

    public function tearDown()
    {
        $triggerService = new TriggerService($this->connection);
        $triggerService->deactivateTriggers();
        $this->importFixtures(__DIR__ . '/_fixtures/delete_article_for_trigger_test.sql');
    }

    public function testActivateArticleTriggers()
    {
        $this->assertArticleIsNotMarkedForUpdate();

        $this->connection->executeQuery('UPDATE s_articles SET `name` = "newName" WHERE id = 1234');

        $this->assertArticleIsMarkedForUpdate();
    }

    public function testDeactivateArticleTriggers()
    {
        $triggerService = new TriggerService($this->connection);
        $triggerService->deactivateTriggers();

        $this->assertArticleIsNotMarkedForUpdate();

        $triggers = $this->connection->fetchAll('SHOW TRIGGERS');
        $this->assertEquals([], $triggers);

        $this->connection->executeQuery('UPDATE s_articles SET `name` = "newName" WHERE id = 1234');

        $cronUpdate = $this->connection->fetchColumn('SELECT cron_update FROM s_plugin_connect_items WHERE article_id = 1234');
        $this->assertEquals(0, $cronUpdate);
    }

    public function testActivateArticleDetailsUpdateTriggers()
    {
        $this->assertArticleIsNotMarkedForUpdate();

        $this->connection->executeQuery('UPDATE s_articles_details SET ordernumber = "nr1234" WHERE articleID = 1234');

        $this->assertArticleIsMarkedForUpdate();
    }

    public function testActivateArticleDetailsInsertTriggers()
    {
        $this->assertArticleIsNotMarkedForUpdate();

        $this->connection->executeQuery('INSERT INTO s_articles_details (id, `articleID`, `ordernumber`, `kind`, `position`) VALUES (7091847, 1234, "sw1004-2", 2, 3);');

        $this->assertArticleIsMarkedForUpdate();
    }

    public function testActivateArticleDetailsDeleteTriggers()
    {
        $this->assertArticleIsNotMarkedForUpdate();

        $this->connection->executeQuery('DELETE FROM s_articles_details WHERE articleID = 1234');

        $this->assertArticleIsMarkedForUpdate();
    }

    public function testActivateArticleAttributesUpdateTriggers()
    {
        $this->assertArticleIsNotMarkedForUpdate();

        $this->connection->executeQuery('UPDATE s_articles_attributes SET attr1 = "super important Attribute" WHERE articleID = 1234');

        $this->assertArticleIsMarkedForUpdate();
    }

    public function testActivateArticleAttributesDeleteTriggers()
    {
        $this->assertArticleIsNotMarkedForUpdate();

        $this->connection->executeQuery('DELETE FROM s_articles_attributes WHERE articleID = 1234');

        $this->assertArticleIsMarkedForUpdate();
    }

    public function testActivateArticleImagesUpdateTriggers()
    {
        $this->assertArticleIsNotMarkedForUpdate();

        $this->connection->executeQuery('UPDATE s_articles_img SET description = "someImage" WHERE articleID = 1234');

        $this->assertArticleIsMarkedForUpdate();
    }

    public function testActivateArticleImagesInsertTriggers()
    {
        $this->assertArticleIsNotMarkedForUpdate();

        $this->connection->executeQuery('INSERT INTO s_articles_img (articleID, main, description, position, width, height, relations, extension) VALUES (1234, 0, "", 1, 1, 1, "", "jpeg");');

        $this->assertArticleIsMarkedForUpdate();
    }

    public function testActivateArticleImagesDeleteTriggers()
    {
        $this->assertArticleIsNotMarkedForUpdate();

        $this->connection->executeQuery('DELETE FROM s_articles_img WHERE articleID = 1234');

        $this->assertArticleIsMarkedForUpdate();
    }

    public function testActivateArticleCategoriesUpdateTriggers()
    {
        $this->assertArticleIsNotMarkedForUpdate();

        $this->connection->executeQuery('UPDATE s_articles_categories SET categoryID = 3 WHERE articleID = 1234');

        $this->assertArticleIsMarkedForUpdate();
    }

    public function testActivateArticleCategoriesInsertTriggers()
    {
        $this->assertArticleIsNotMarkedForUpdate();

        $this->connection->executeQuery('INSERT INTO s_articles_categories (articleID, categoryID) VALUES (1234, 3);');

        $this->assertArticleIsMarkedForUpdate();
    }

    public function testActivateArticleCategoriesDeleteTriggers()
    {
        $this->assertArticleIsNotMarkedForUpdate();

        $this->connection->executeQuery('DELETE FROM s_articles_categories WHERE articleID = 1234');

        $this->assertArticleIsMarkedForUpdate();
    }

    public function testActivateArticlePricesUpdateTriggers()
    {
        $this->assertArticleIsNotMarkedForUpdate();

        $this->connection->executeQuery('UPDATE s_articles_prices SET price = 3 WHERE articleID = 1234');

        $this->assertArticleIsMarkedForUpdate();
    }

    public function testActivateArticlePricesInsertTriggers()
    {
        $this->assertArticleIsNotMarkedForUpdate();

        $this->connection->executeQuery('INSERT INTO s_articles_prices (articleID, articledetailsID, price, pricegroup, `from`, `to`) VALUES (1234, 7091846, 7.9, 1, 7, "beliebig");');

        $this->assertArticleIsMarkedForUpdate();
    }

    public function testActivateArticlePricesDeleteTriggers()
    {
        $this->assertArticleIsNotMarkedForUpdate();

        $this->connection->executeQuery('DELETE FROM s_articles_prices WHERE articleID = 1234');

        $this->assertArticleIsMarkedForUpdate();
    }

    public function testActivateArticleTranslationsUpdateTriggers()
    {
        $this->assertArticleIsNotMarkedForUpdate();

        $this->connection->executeQuery('UPDATE s_articles_translations SET `name` = "awesome Translation" WHERE articleID = 1234');

        $this->assertArticleIsMarkedForUpdate();
    }

    public function testActivateArticleTranslationsInsertTriggers()
    {
        $this->assertArticleIsNotMarkedForUpdate();

        $this->connection->executeQuery('INSERT INTO s_articles_translations (articleID, languageID, `name`, keywords, description, description_long, description_clear, attr1, attr2, attr3, attr4, attr5) VALUES (1234, 2, "testTranslation is Awesome", "", "", "", "", "", "", "", "", "");');

        $this->assertArticleIsMarkedForUpdate();
    }

    public function testActivateArticleTranslationsDeleteTriggers()
    {
        $this->assertArticleIsNotMarkedForUpdate();

        $this->connection->executeQuery('DELETE FROM s_articles_translations WHERE articleID = 1234');

        $this->assertArticleIsMarkedForUpdate();
    }

    public function testActivateSupplierTriggers()
    {
        $this->assertArticleIsNotMarkedForUpdate();

        $this->connection->executeQuery('UPDATE s_articles_supplier SET `name` = "awesome Supplier" WHERE id = 111');

        $this->assertArticleIsMarkedForUpdate();
    }

    public function testActivateTaxTriggersTaxChange()
    {
        $this->assertArticleIsNotMarkedForUpdate();

        $this->connection->executeQuery('UPDATE s_core_tax SET tax = 10 WHERE id = 111');

        $this->assertArticleIsMarkedForUpdate();
    }

    public function testActivateTaxDontTriggersDexcriptionChange()
    {
        $this->assertArticleIsNotMarkedForUpdate();

        $this->connection->executeQuery('UPDATE s_core_tax SET description = "awesome Tax" WHERE id = 111');

        $this->assertArticleIsNotMarkedForUpdate();
    }

    public function testActivateCategoriesTriggersDescriptionChange()
    {
        $this->assertArticleIsNotMarkedForUpdate();

        $this->connection->executeQuery('UPDATE s_categories SET description = "awesome Category" WHERE id = 2222');

        $this->assertArticleIsMarkedForUpdate();
    }

    public function testActivateCategoriesTriggersParentChange()
    {
        $this->assertArticleIsNotMarkedForUpdate();

        $this->connection->executeQuery('UPDATE s_categories SET parent = 3 WHERE id = 2222');

        $this->assertArticleIsMarkedForUpdate();
    }

    public function testActivateCategoriesDontTriggersPositionChange()
    {
        $this->assertArticleIsNotMarkedForUpdate();

        $this->connection->executeQuery('UPDATE s_categories SET position = 3 WHERE id = 2222');

        $this->assertArticleIsNotMarkedForUpdate();
    }

    private function assertArticleIsNotMarkedForUpdate()
    {
        $cronUpdate = $this->connection->fetchColumn('SELECT cron_update FROM s_plugin_connect_items WHERE article_id = 1234');
        $this->assertNull($cronUpdate);
    }

    private function assertArticleIsMarkedForUpdate()
    {
        $triggers = $this->connection->fetchAll('SHOW TRIGGERS');
        $this->assertTrue(count($triggers) > 0);

        $cronUpdate = $this->connection->fetchColumn('SELECT cron_update FROM s_plugin_connect_items WHERE article_id = 1234');
        $this->assertEquals(1, $cronUpdate);
    }
}
