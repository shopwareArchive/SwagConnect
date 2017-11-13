DELETE FROM `s_articles_categories`;
DELETE FROM `s_plugin_connect_items`;

INSERT INTO `s_categories` (`id`, `parent`, `path`, `description`) VALUES (1884, 3, "|3|", "Test123");
INSERT INTO `s_categories` (`id`, `parent`, `path`, `description`) VALUES (1885, 1884, "|3|1884|", "Test123456");
INSERT INTO `s_categories` (`id`, `parent`, `path`, `description`) VALUES (1886, 1884, "|3|1884|", "Test123456");

INSERT INTO `s_plugin_connect_items` (`article_id`, `exported`, `cron_update`) VALUES (3, 1, NULL);
INSERT INTO `s_plugin_connect_items` (`article_id`, `exported`, `cron_update`) VALUES (4, 1, NULL);
INSERT INTO `s_plugin_connect_items` (`article_id`, `exported`, `cron_update`) VALUES (5, 1, NULL);

INSERT INTO `s_articles_categories` (`articleID`, `categoryID`) VALUES (3, 1885);
INSERT INTO `s_articles_categories` (`articleID`, `categoryID`) VALUES (4, 1886);
INSERT INTO `s_articles_categories` (`articleID`, `categoryID`) VALUES (5, 1884);
