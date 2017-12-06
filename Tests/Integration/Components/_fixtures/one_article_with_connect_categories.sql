INSERT INTO s_articles (`id`, `name`, main_detail_id, changetime, pricegroupActive, laststock, crossbundlelook, notification, template, mode)
VALUES (1234, 'Baby-Träger', 7091846, NOW(), 1, 1, 0, 0, 1, 0);
INSERT INTO s_articles_details (id, `articleID`, `ordernumber`, `kind`, `position`) VALUES (7091846, 1234, 'sw1004', 1, 0);
INSERT INTO s_plugin_connect_items (article_id, article_detail_id, source_id, purchase_price_hash, offer_valid_until, stream, shop_id)
VALUES (1234, 7091846, "1234-1", "hash", 123, "Awesome products", 1234);

INSERT INTO s_plugin_connect_categories (id, category_key, label, shop_id) VALUES (1111, "/deutsch/bücher", "Bücher", 1234);
INSERT INTO `s_plugin_connect_product_to_categories` (`articleID`, `connect_category_id`) VALUES (1234, 1111);

INSERT INTO `s_plugin_connect_categories` (`id`, `category_key`, `label`, `shop_id`) VALUES (2222, "/deutsch/bücher/fantasy", "Fantasy", 1234);
INSERT INTO `s_plugin_connect_product_to_categories` (`articleID`, `connect_category_id`) VALUES (1234, 2222)