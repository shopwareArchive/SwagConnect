DELETE FROM `s_articles_categories`;
DELETE FROM `s_plugin_connect_items`;

INSERT INTO `s_categories` (`id`, `parent`, `path`, `description`) VALUES (1884, 3, "|3|", "Test");
INSERT INTO `s_categories` (`id`, `parent`, `path`, `description`) VALUES (1885, 1884, "|3|1884|", "Test1");
INSERT INTO `s_categories` (`id`, `parent`, `path`, `description`) VALUES (1886, 1884, "|3|1884|", "Test2");

INSERT INTO `s_plugin_connect_items` (`article_id`, `shop_id`, `category`) VALUES (3, 1111, "{\"\/deutsch\/test\/test1\":\"Test1\",\"\/deutsch\/test\":\"Test\",\"\/deutsch\":\"Deutsch\"}");
INSERT INTO `s_plugin_connect_items` (`article_id`, `shop_id`, `category`) VALUES (4, 1111, "{\"\/deutsch\/test\/test2\":\"Test2\",\"\/deutsch\/test\":\"Test\",\"\/deutsch\":\"Deutsch\"}");
INSERT INTO `s_plugin_connect_items` (`article_id`, `shop_id`, `category`) VALUES (5, 1222, "{\"\/deutsch\/test\/test2\":\"Test2\",\"\/deutsch\/test\":\"Test\",\"\/deutsch\":\"Deutsch\"}");
INSERT INTO `s_plugin_connect_items` (`article_id`, `shop_id`, `category`) VALUES (6, 1222, "{\"\/deutsch\/test\/test1\":\"Test1\",\"\/deutsch\/test\":\"Test\",\"\/deutsch\":\"Deutsch\"}");

INSERT INTO `s_plugin_connect_categories` (`id`, `category_key`, `label`) VALUES (1, "/deutsch", "Deutsch");
INSERT INTO `s_plugin_connect_categories` (`id`, `category_key`, `label`) VALUES (2, "/deutsch/test", "Test");
INSERT INTO `s_plugin_connect_categories` (`id`, `category_key`, `label`) VALUES (3, "/deutsch/test/test1", "Test1");
INSERT INTO `s_plugin_connect_categories` (`id`, `category_key`, `label`) VALUES (4, "/deutsch/test/test2", "Test2");

INSERT INTO `s_plugin_connect_product_to_categories` (connect_category_id, articleID) VALUES (1, 3);
INSERT INTO `s_plugin_connect_product_to_categories` (connect_category_id, articleID) VALUES (1, 4);
INSERT INTO `s_plugin_connect_product_to_categories` (connect_category_id, articleID) VALUES (1, 5);
INSERT INTO `s_plugin_connect_product_to_categories` (connect_category_id, articleID) VALUES (1, 6);
INSERT INTO `s_plugin_connect_product_to_categories` (connect_category_id, articleID) VALUES (2, 3);
INSERT INTO `s_plugin_connect_product_to_categories` (connect_category_id, articleID) VALUES (2, 4);
INSERT INTO `s_plugin_connect_product_to_categories` (connect_category_id, articleID) VALUES (2, 5);
INSERT INTO `s_plugin_connect_product_to_categories` (connect_category_id, articleID) VALUES (2, 6);
INSERT INTO `s_plugin_connect_product_to_categories` (connect_category_id, articleID) VALUES (3, 3);
INSERT INTO `s_plugin_connect_product_to_categories` (connect_category_id, articleID) VALUES (4, 4);
INSERT INTO `s_plugin_connect_product_to_categories` (connect_category_id, articleID) VALUES (4, 5);
INSERT INTO `s_plugin_connect_product_to_categories` (connect_category_id, articleID) VALUES (3, 6);

INSERT INTO `s_articles_categories` (`articleID`, `categoryID`) VALUES (3, 1885);
INSERT INTO `s_articles_categories` (`articleID`, `categoryID`) VALUES (4, 1886);
INSERT INTO `s_articles_categories` (`articleID`, `categoryID`) VALUES (5, 1886);
INSERT INTO `s_articles_categories` (`articleID`, `categoryID`) VALUES (6, 1885);

INSERT INTO `s_plugin_connect_categories_to_local_categories` (remote_category_id, local_category_id) VALUES (3, 1885);
INSERT INTO `s_plugin_connect_categories_to_local_categories` (remote_category_id, local_category_id) VALUES (4, 1886);