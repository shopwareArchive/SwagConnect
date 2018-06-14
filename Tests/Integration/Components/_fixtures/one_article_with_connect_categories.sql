INSERT INTO s_plugin_connect_items (article_id, article_detail_id, source_id, purchase_price_hash, offer_valid_until, stream, shop_id)
VALUES (3, 3, "1234-1", "hash", 123, "Awesome products", 1234);

INSERT INTO s_plugin_connect_categories (id, category_key, label, shop_id) VALUES (1111, "/deutsch/bücher", "Bücher", 1234);
INSERT INTO `s_plugin_connect_product_to_categories` (`articleID`, `connect_category_id`) VALUES (3, 1111);

INSERT INTO `s_plugin_connect_categories` (`id`, `category_key`, `label`, `shop_id`) VALUES (2222, "/deutsch/bücher/fantasy", "Fantasy", 1234);
INSERT INTO `s_plugin_connect_product_to_categories` (`articleID`, `connect_category_id`) VALUES (3, 2222)