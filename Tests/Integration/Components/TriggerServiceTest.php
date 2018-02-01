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

    // Triggers and our transaction handling for testcases don't work together so we do it manually
    public function setUp()
    {
        $this->importFixtures(__DIR__ . '/_fixtures/article_for_trigger_test.sql');
        $triggerService = new TriggerService(Shopware()->Container()->get('dbal_connection'));
        $triggerService->activateTriggers();
    }

    public function tearDown()
    {
        $triggerService = new TriggerService(Shopware()->Container()->get('dbal_connection'));
        $triggerService->deactivateTriggers();
        $this->importFixtures(__DIR__ . '/_fixtures/delete_article_for_trigger_test.sql');
    }

    public function testActivateArticleTriggers()
    {
        $connection = Shopware()->Container()->get('dbal_connection');
        $this->assertArticleIsNotMarkedForUpdate($connection);

        $connection->executeQuery('UPDATE s_articles SET `name` = "newName" WHERE id = 1234');

        $this->assertArticleIsMarkedForUpdate($connection);
    }

    public function testDeactivateArticleTriggers()
    {
        $triggerService = new TriggerService(Shopware()->Models()->getConnection());
        $triggerService->deactivateTriggers();

        $connection = Shopware()->Container()->get('dbal_connection');
        $this->assertArticleIsNotMarkedForUpdate($connection);

        $triggers = $connection->fetchAll('SHOW TRIGGERS');
        $this->assertEquals([], $triggers);

        $connection->executeQuery('UPDATE s_articles SET `name` = "newName" WHERE id = 1234');

        $cronUpdate = $connection->fetchColumn('SELECT cron_update FROM s_plugin_connect_items WHERE article_id = 1234');
        $this->assertEquals(0, $cronUpdate);
    }

    public function testActivateArticleDetailsUpdateTriggers()
    {
        $connection = Shopware()->Container()->get('dbal_connection');
        $this->assertArticleIsNotMarkedForUpdate($connection);

        $connection->executeQuery('UPDATE s_articles_details SET ordernumber = "nr1234" WHERE articleID = 1234');

        $this->assertArticleIsMarkedForUpdate($connection);
    }

    public function testActivateArticleDetailsInsertTriggers()
    {
        $connection = Shopware()->Container()->get('dbal_connection');
        $this->assertArticleIsNotMarkedForUpdate($connection);

        $connection->executeQuery('INSERT INTO s_articles_details (id, `articleID`, `ordernumber`, `kind`, `position`) VALUES (7091847, 1234, "sw1004-2", 2, 3);');

        $this->assertArticleIsMarkedForUpdate($connection);
    }

    public function testActivateArticleDetailsDeleteTriggers()
    {
        $connection = Shopware()->Container()->get('dbal_connection');
        $this->assertArticleIsNotMarkedForUpdate($connection);

        $connection->executeQuery('DELETE FROM s_articles_details WHERE articleID = 1234');

        $this->assertArticleIsMarkedForUpdate($connection);
    }

    public function testActivateArticleAttributesUpdateTriggers()
    {
        $connection = Shopware()->Container()->get('dbal_connection');
        $this->assertArticleIsNotMarkedForUpdate($connection);

        $connection->executeQuery('UPDATE s_articles_attributes SET attr1 = "super important Attribute" WHERE articleID = 1234');

        $this->assertArticleIsMarkedForUpdate($connection);
    }

    public function testActivateArticleAttributesDeleteTriggers()
    {
        $connection = Shopware()->Container()->get('dbal_connection');
        $this->assertArticleIsNotMarkedForUpdate($connection);

        $connection->executeQuery('DELETE FROM s_articles_attributes WHERE articleID = 1234');

        $this->assertArticleIsMarkedForUpdate($connection);
    }

    public function testActivateArticleImagesUpdateTriggers()
    {
        $connection = Shopware()->Container()->get('dbal_connection');
        $this->assertArticleIsNotMarkedForUpdate($connection);

        $connection->executeQuery('UPDATE s_articles_img SET description = "someImage" WHERE articleID = 1234');

        $this->assertArticleIsMarkedForUpdate($connection);
    }

    public function testActivateArticleImagesInsertTriggers()
    {
        $connection = Shopware()->Container()->get('dbal_connection');
        $this->assertArticleIsNotMarkedForUpdate($connection);

        $connection->executeQuery('INSERT INTO s_articles_img (articleID, main, description, position, width, height, relations, extension) VALUES (1234, 0, "", 1, 1, 1, "", "jpeg");');

        $this->assertArticleIsMarkedForUpdate($connection);
    }

    public function testActivateArticleImagesDeleteTriggers()
    {
        $connection = Shopware()->Container()->get('dbal_connection');
        $this->assertArticleIsNotMarkedForUpdate($connection);

        $connection->executeQuery('DELETE FROM s_articles_img WHERE articleID = 1234');

        $this->assertArticleIsMarkedForUpdate($connection);
    }

    public function testActivateArticleCategoriesUpdateTriggers()
    {
        $connection = Shopware()->Container()->get('dbal_connection');
        $this->assertArticleIsNotMarkedForUpdate($connection);

        $connection->executeQuery('UPDATE s_articles_categories SET categoryID = 3 WHERE articleID = 1234');

        $this->assertArticleIsMarkedForUpdate($connection);
    }

    public function testActivateArticleCategoriesInsertTriggers()
    {
        $connection = Shopware()->Container()->get('dbal_connection');
        $this->assertArticleIsNotMarkedForUpdate($connection);

        $connection->executeQuery('INSERT INTO s_articles_categories (articleID, categoryID) VALUES (1234, 3);');

        $this->assertArticleIsMarkedForUpdate($connection);
    }

    public function testActivateArticleCategoriesDeleteTriggers()
    {
        $connection = Shopware()->Container()->get('dbal_connection');
        $this->assertArticleIsNotMarkedForUpdate($connection);

        $connection->executeQuery('DELETE FROM s_articles_categories WHERE articleID = 1234');

        $this->assertArticleIsMarkedForUpdate($connection);
    }

    public function testActivateArticlePricesUpdateTriggers()
    {
        $connection = Shopware()->Container()->get('dbal_connection');
        $this->assertArticleIsNotMarkedForUpdate($connection);

        $connection->executeQuery('UPDATE s_articles_prices SET price = 3 WHERE articleID = 1234');

        $this->assertArticleIsMarkedForUpdate($connection);
    }

    public function testActivateArticlePricesInsertTriggers()
    {
        $connection = Shopware()->Container()->get('dbal_connection');
        $this->assertArticleIsNotMarkedForUpdate($connection);

        $connection->executeQuery('INSERT INTO s_articles_prices (articleID, articledetailsID, price, pricegroup, `from`, `to`) VALUES (1234, 7091846, 7.9, 1, 7, "beliebig");');

        $this->assertArticleIsMarkedForUpdate($connection);
    }

    public function testActivateArticlePricesDeleteTriggers()
    {
        $connection = Shopware()->Container()->get('dbal_connection');
        $this->assertArticleIsNotMarkedForUpdate($connection);

        $connection->executeQuery('DELETE FROM s_articles_prices WHERE articleID = 1234');

        $this->assertArticleIsMarkedForUpdate($connection);
    }

    public function testActivateArticleTranslationsUpdateTriggers()
    {
        $connection = Shopware()->Container()->get('dbal_connection');
        $this->assertArticleIsNotMarkedForUpdate($connection);

        $connection->executeQuery('UPDATE s_articles_translations SET `name` = "awesome Translation" WHERE articleID = 1234');

        $this->assertArticleIsMarkedForUpdate($connection);
    }

    public function testActivateArticleTranslationsInsertTriggers()
    {
        $connection = Shopware()->Container()->get('dbal_connection');
        $this->assertArticleIsNotMarkedForUpdate($connection);

        $connection->executeQuery('INSERT INTO s_articles_translations (articleID, languageID, `name`, keywords, description, description_long, description_clear, attr1, attr2, attr3, attr4, attr5) VALUES (1234, 2, "testTranslation is Awesome", "", "", "", "", "", "", "", "", "");');

        $this->assertArticleIsMarkedForUpdate($connection);
    }

    public function testActivateArticleTranslationsDeleteTriggers()
    {
        $connection = Shopware()->Container()->get('dbal_connection');
        $this->assertArticleIsNotMarkedForUpdate($connection);

        $connection->executeQuery('DELETE FROM s_articles_translations WHERE articleID = 1234');

        $this->assertArticleIsMarkedForUpdate($connection);
    }

    public function testActivateSupplierTriggers()
    {
        $connection = Shopware()->Container()->get('dbal_connection');
        $this->assertArticleIsNotMarkedForUpdate($connection);

        $connection->executeQuery('UPDATE s_articles_supplier SET `name` = "awesome Supplier" WHERE id = 111');

        $this->assertArticleIsMarkedForUpdate($connection);
    }

    public function testActivateTaxTriggersTaxChange()
    {
        $connection = Shopware()->Container()->get('dbal_connection');
        $this->assertArticleIsNotMarkedForUpdate($connection);

        $connection->executeQuery('UPDATE s_core_tax SET tax = 10 WHERE id = 111');

        $this->assertArticleIsMarkedForUpdate($connection);
    }

    public function testActivateTaxDontTriggersDexcriptionChange()
    {
        $connection = Shopware()->Container()->get('dbal_connection');
        $this->assertArticleIsNotMarkedForUpdate($connection);

        $connection->executeQuery('UPDATE s_core_tax SET description = "awesome Tax" WHERE id = 111');

        $this->assertArticleIsNotMarkedForUpdate($connection);
    }

    public function testActivateCategoriesTriggersDescriptionChange()
    {
        $connection = Shopware()->Container()->get('dbal_connection');
        $this->assertArticleIsNotMarkedForUpdate($connection);

        $connection->executeQuery('UPDATE s_categories SET description = "awesome Category" WHERE id = 2222');

        $this->assertArticleIsMarkedForUpdate($connection);
    }

    public function testActivateCategoriesTriggersParentChange()
    {
        $connection = Shopware()->Container()->get('dbal_connection');
        $this->assertArticleIsNotMarkedForUpdate($connection);

        $connection->executeQuery('UPDATE s_categories SET parent = 3 WHERE id = 2222');

        $this->assertArticleIsMarkedForUpdate($connection);
    }

    public function testActivateCategoriesDontTriggersPositionChange()
    {
        $connection = Shopware()->Container()->get('dbal_connection');
        $this->assertArticleIsNotMarkedForUpdate($connection);

        $connection->executeQuery('UPDATE s_categories SET position = 3 WHERE id = 2222');

        $this->assertArticleIsNotMarkedForUpdate($connection);
    }

    /**
     * @param $connection
     */
    private function assertArticleIsNotMarkedForUpdate($connection)
    {
        $cronUpdate = $connection->fetchColumn('SELECT cron_update FROM s_plugin_connect_items WHERE article_id = 1234');
        $this->assertNull($cronUpdate);
    }

    /**
     * @param $connection
     */
    private function assertArticleIsMarkedForUpdate($connection)
    {
        $cronUpdate = $connection->fetchColumn('SELECT cron_update FROM s_plugin_connect_items WHERE article_id = 1234');
        $this->assertEquals(1, $cronUpdate);
    }
}
