INSERT INTO s_plugin_connect_categories (id, category_key, label, shop_id) VALUES (1111, "/deutsch/bücher", "Bücher", 3);
INSERT INTO `s_plugin_connect_product_to_categories` (`articleID`, `connect_category_id`) VALUES (3, 1111);
INSERT INTO s_categories (id, parent, path, description) VALUES (1111, 3 ,"|3|", "Bücher");
INSERT INTO s_categories_attributes (categoryID, connect_imported_category) VALUES (1111, 1);
INSERT INTO s_plugin_connect_categories_to_local_categories (remote_category_id, local_category_id, stream) VALUES (1111, 1111, "Awesome products");

INSERT INTO `s_plugin_connect_categories` (`id`, `category_key`, `label`, `shop_id`) VALUES (2222, "/deutsch/bücher/fantasy", "Fantasy", 3);
INSERT INTO `s_plugin_connect_product_to_categories` (`articleID`, `connect_category_id`) VALUES (3, 2222);
INSERT INTO s_categories (id, parent, path, description) VALUES (2222, 1111 ,"|3|1111|", "Fantasy");
INSERT INTO s_categories_attributes (categoryID, connect_imported_category) VALUES (2222, 1);
INSERT INTO s_plugin_connect_categories_to_local_categories (remote_category_id, local_category_id, stream) VALUES (2222, 2222, "Awesome products");

INSERT INTO `s_plugin_connect_categories` (`id`, `category_key`, `label`, `shop_id`) VALUES (3333, "/deutsch/bücher/romane", "Romane", 3);
INSERT INTO `s_plugin_connect_product_to_categories` (`articleID`, `connect_category_id`) VALUES (3, 3333);
INSERT INTO s_categories (id, parent, path, description) VALUES (3333, 1111 ,"|3|1111|", "Romane");
INSERT INTO s_categories_attributes (categoryID, connect_imported_category) VALUES (3333, 1);
INSERT INTO s_plugin_connect_categories_to_local_categories (remote_category_id, local_category_id, stream) VALUES (3333, 3333, "Test stream");

INSERT INTO s_plugin_connect_categories (id, category_key, label, shop_id) VALUES (4444, "/deutsch/schuhe", "Schuhe", 3);
INSERT INTO `s_plugin_connect_product_to_categories` (`articleID`, `connect_category_id`) VALUES (3, 4444);
INSERT INTO s_categories (id, parent, path, description) VALUES (4444, 3 ,"|3|", "Schuhe");
INSERT INTO s_categories_attributes (categoryID, connect_imported_category) VALUES (4444, 1);
INSERT INTO s_plugin_connect_categories_to_local_categories (remote_category_id, local_category_id, stream) VALUES (4444, 4444, "Test stream");
