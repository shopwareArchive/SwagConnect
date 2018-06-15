DELETE FROM s_categories;
DELETE FROM s_articles_categories;
DELETE FROM s_plugin_connect_categories;

INSERT INTO s_plugin_connect_items (article_id, article_detail_id, source_id, purchase_price_hash, offer_valid_until, stream, shop_id)
VALUES (3, 3, 3, "hash", 123, "Awesome products", 1234);

INSERT INTO s_categories (id, parent, description, `left`, `right`, `level`, added, changed, active, blog, hidefilter, hidetop)
VALUES (2222, 1, "Deutsch", 1, 1, 1, NOW(), NOW(), 1, 1, 0, 0);
INSERT INTO s_categories (id, parent, description, `left`, `right`, `level`, added, changed, active, blog, hidefilter, hidetop)
VALUES (3333, 2222, "Deutsch", 1, 1, 1, NOW(), NOW(), 1, 1, 0, 0);
INSERT INTO s_categories (id, parent, description, `left`, `right`, `level`, added, changed, active, blog, hidefilter, hidetop)
VALUES (4444, 2222, "Deutsch", 1, 1, 1, NOW(), NOW(), 1, 1, 0, 0);

INSERT INTO s_articles_categories (articleID, categoryID) VALUES (3, 3333);
INSERT INTO s_articles_categories (articleID, categoryID) VALUES (3, 4444);

INSERT INTO s_plugin_connect_categories (id, category_key, label, shop_id) VALUES (2222, "/deutsch", "Deutsch", 3);
INSERT INTO s_plugin_connect_categories (id, category_key, label, shop_id) VALUES (3333, "/deutsch/test1", "Test 1", 3);
INSERT INTO s_plugin_connect_categories (id, category_key, label, shop_id) VALUES (4444, "/deutsch/test2", "Test 2", 3);

INSERT INTO s_plugin_connect_product_to_categories (connect_category_id, articleID) VALUES (2222, 3);
INSERT INTO s_plugin_connect_product_to_categories (connect_category_id, articleID) VALUES (3333, 3);
INSERT INTO s_plugin_connect_product_to_categories (connect_category_id, articleID) VALUES (4444, 3);

INSERT INTO s_plugin_connect_categories_to_local_categories (remote_category_id, local_category_id) VALUES (2222, 2222);
INSERT INTO s_plugin_connect_categories_to_local_categories (remote_category_id, local_category_id) VALUES (3333, 3333);
INSERT INTO s_plugin_connect_categories_to_local_categories (remote_category_id, local_category_id) VALUES (4444, 4444);

INSERT INTO s_categories_attributes (categoryID, connect_imported_category) VALUES (2222, 1);
INSERT INTO s_categories_attributes (categoryID, connect_imported_category) VALUES (3333, 1);
INSERT INTO s_categories_attributes (categoryID, connect_imported_category) VALUES (4444, 1);