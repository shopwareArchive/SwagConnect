<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Components;

use Doctrine\DBAL\Connection;

class TriggerService
{
    /**
     * @var Connection;
     */
    private $connection;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection= $connection;
    }

    public function activateTriggers()
    {
        $this->createArticleTrigger();
        $this->createArticleDetailsTrigger();
        $this->createArticleAttributesTrigger();
        $this->createArticleImagesTrigger();
        $this->createArticleCategoriesTrigger();
        $this->createArticlePricesTrigger();
        $this->createArticleTranslationsTrigger();
        $this->createSupplierTrigger();
        $this->createTaxTrigger();
        $this->createCategoriesTrigger();
    }

    public function deactivateTriggers()
    {
        $this->connection->executeQuery('
            DROP TRIGGER IF EXISTS connect_article_trigger;
            DROP TRIGGER IF EXISTS connect_article_details_update_trigger;
            DROP TRIGGER IF EXISTS connect_article_details_insert_trigger;
            DROP TRIGGER IF EXISTS connect_article_details_delete_trigger;
            DROP TRIGGER IF EXISTS connect_article_attributes_update_trigger;
            DROP TRIGGER IF EXISTS connect_article_attributes_insert_trigger;
            DROP TRIGGER IF EXISTS connect_article_attributes_delete_trigger;
            DROP TRIGGER IF EXISTS connect_article_images_update_trigger;
            DROP TRIGGER IF EXISTS connect_article_images_insert_trigger;
            DROP TRIGGER IF EXISTS connect_article_images_delete_trigger;
            DROP TRIGGER IF EXISTS connect_article_categories_update_trigger;
            DROP TRIGGER IF EXISTS connect_article_categories_insert_trigger;
            DROP TRIGGER IF EXISTS connect_article_categories_delete_trigger;
            DROP TRIGGER IF EXISTS connect_article_prices_update_trigger;      
            DROP TRIGGER IF EXISTS connect_article_prices_insert_trigger;
            DROP TRIGGER IF EXISTS connect_article_prices_delete_trigger;
            DROP TRIGGER IF EXISTS connect_article_translations_update_trigger;
            DROP TRIGGER IF EXISTS connect_article_translations_insert_trigger;
            DROP TRIGGER IF EXISTS connect_article_translations_delete_trigger;
            DROP TRIGGER IF EXISTS connect_supplier_trigger;
            DROP TRIGGER IF EXISTS connect_tax_trigger;  
            DROP TRIGGER IF EXISTS connect_categories_trigger;  
        ');
    }

    private function createArticleTrigger()
    {
        $this->connection->executeQuery('
            CREATE TRIGGER connect_article_trigger
            AFTER UPDATE
            ON s_articles
            FOR EACH ROW
                UPDATE s_plugin_connect_items SET cron_update = 1 WHERE article_id = NEW.id
        ');
    }

    private function createArticleDetailsTrigger()
    {
        $this->connection->executeQuery('
            CREATE TRIGGER connect_article_details_update_trigger
            AFTER UPDATE
            ON s_articles_details
            FOR EACH ROW
                UPDATE s_plugin_connect_items SET cron_update = 1 WHERE article_id = NEW.articleID
        ');
        $this->connection->executeQuery('
            CREATE TRIGGER connect_article_details_insert_trigger
            AFTER INSERT
            ON s_articles_details
            FOR EACH ROW
                UPDATE s_plugin_connect_items SET cron_update = 1 WHERE article_id = NEW.articleID
        ');
        $this->connection->executeQuery('
            CREATE TRIGGER connect_article_details_delete_trigger
            AFTER DELETE
            ON s_articles_details
            FOR EACH ROW
                UPDATE s_plugin_connect_items SET cron_update = 1 WHERE article_id = OLD.articleID
        ');
    }

    private function createArticleAttributesTrigger()
    {
        $this->connection->executeQuery('
            CREATE TRIGGER connect_article_attributes_update_trigger
            AFTER UPDATE
            ON s_articles_attributes
            FOR EACH ROW
                UPDATE s_plugin_connect_items SET cron_update = 1 WHERE article_id = NEW.articleID
        ');
        $this->connection->executeQuery('
            CREATE TRIGGER connect_article_attributes_insert_trigger
            AFTER INSERT
            ON s_articles_attributes
            FOR EACH ROW
                UPDATE s_plugin_connect_items SET cron_update = 1 WHERE article_id = NEW.articleID
        ');
        $this->connection->executeQuery('
            CREATE TRIGGER connect_article_attributes_delete_trigger
            AFTER DELETE
            ON s_articles_attributes
            FOR EACH ROW
                UPDATE s_plugin_connect_items SET cron_update = 1 WHERE article_id = OLD.articleID
        ');
    }

    private function createArticleImagesTrigger()
    {
        $this->connection->executeQuery('
            CREATE TRIGGER connect_article_images_update_trigger
            AFTER UPDATE
            ON s_articles_img
            FOR EACH ROW
                UPDATE s_plugin_connect_items SET cron_update = 1 WHERE article_id = NEW.articleID
        ');
        $this->connection->executeQuery('
            CREATE TRIGGER connect_article_images_insert_trigger
            AFTER INSERT
            ON s_articles_img
            FOR EACH ROW
                UPDATE s_plugin_connect_items SET cron_update = 1 WHERE article_id = NEW.articleID
        ');
        $this->connection->executeQuery('
            CREATE TRIGGER connect_article_images_delete_trigger
            AFTER DELETE
            ON s_articles_img
            FOR EACH ROW
                UPDATE s_plugin_connect_items SET cron_update = 1 WHERE article_id = OLD.articleID
        ');
    }

    private function createArticleCategoriesTrigger()
    {
        $this->connection->executeQuery('
            CREATE TRIGGER connect_article_categories_update_trigger
            AFTER UPDATE
            ON s_articles_categories
            FOR EACH ROW
                UPDATE s_plugin_connect_items SET cron_update = 1 WHERE article_id = NEW.articleID
        ');
        $this->connection->executeQuery('
            CREATE TRIGGER connect_article_categories_insert_trigger
            AFTER INSERT
            ON s_articles_categories
            FOR EACH ROW
                UPDATE s_plugin_connect_items SET cron_update = 1 WHERE article_id = NEW.articleID
        ');
        $this->connection->executeQuery('
            CREATE TRIGGER connect_article_categories_delete_trigger
            AFTER DELETE
            ON s_articles_categories
            FOR EACH ROW
                UPDATE s_plugin_connect_items SET cron_update = 1 WHERE article_id = OLD.articleID
        ');
    }

    private function createArticlePricesTrigger()
    {
        $this->connection->executeQuery('
            CREATE TRIGGER connect_article_prices_update_trigger
            AFTER UPDATE
            ON s_articles_prices
            FOR EACH ROW
                UPDATE s_plugin_connect_items SET cron_update = 1 WHERE article_id = NEW.articleID
        ');
        $this->connection->executeQuery('
            CREATE TRIGGER connect_article_prices_insert_trigger
            AFTER INSERT
            ON s_articles_prices
            FOR EACH ROW
                UPDATE s_plugin_connect_items SET cron_update = 1 WHERE article_id = NEW.articleID
        ');
        $this->connection->executeQuery('
            CREATE TRIGGER connect_article_prices_delete_trigger
            AFTER DELETE
            ON s_articles_prices
            FOR EACH ROW
                UPDATE s_plugin_connect_items SET cron_update = 1 WHERE article_id = OLD.articleID
        ');
    }

    private function createArticleTranslationsTrigger()
    {
        $this->connection->executeQuery('
            CREATE TRIGGER connect_article_translations_update_trigger
            AFTER UPDATE
            ON s_articles_translations
            FOR EACH ROW
                UPDATE s_plugin_connect_items SET cron_update = 1 WHERE article_id = NEW.articleID
        ');
        $this->connection->executeQuery('
            CREATE TRIGGER connect_article_translations_insert_trigger
            AFTER INSERT
            ON s_articles_translations
            FOR EACH ROW
                UPDATE s_plugin_connect_items SET cron_update = 1 WHERE article_id = NEW.articleID
        ');
        $this->connection->executeQuery('
            CREATE TRIGGER connect_article_translations_delete_trigger
            AFTER DELETE
            ON s_articles_translations
            FOR EACH ROW
                UPDATE s_plugin_connect_items SET cron_update = 1 WHERE article_id = OLD.articleID
        ');
    }

    private function createSupplierTrigger()
    {
        $this->connection->executeQuery('
            CREATE TRIGGER connect_supplier_trigger
            AFTER UPDATE
            ON s_articles_supplier
            FOR EACH ROW
                UPDATE s_plugin_connect_items SET cron_update = 1 WHERE article_id IN 
                    (
                        SELECT id FROM s_articles WHERE supplierID = NEW.id
                    );
        ');
    }

    private function createTaxTrigger()
    {
        $this->connection->executeQuery('
            CREATE TRIGGER connect_tax_trigger
            AFTER UPDATE
            ON s_core_tax
            FOR EACH ROW
                IF (NEW.tax <> OLD.tax) THEN
                UPDATE s_plugin_connect_items SET cron_update = 1 WHERE article_id IN 
                    (
                        SELECT id FROM s_articles WHERE taxID = NEW.id
                    );
                END IF;
        ');
    }

    private function createCategoriesTrigger()
    {
        $this->connection->executeQuery('
            CREATE TRIGGER connect_categories_trigger
            AFTER UPDATE
            ON s_categories
            FOR EACH ROW
                IF (NEW.description <> OLD.description) OR (NEW.parent <> OLD.parent) THEN
                UPDATE s_plugin_connect_items SET cron_update = 1 WHERE article_id IN 
                    (
                        SELECT articleID FROM s_articles_categories WHERE categoryID = NEW.id
                    );
                END IF;
        ');
    }
}
