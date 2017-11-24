INSERT INTO s_categories (`id`, `parent`, `path`, `description`) VALUES (140809703, 3, '|3|', 'TV');
INSERT INTO s_plugin_connect_categories (category_key, label, shop_id) VALUES ('/deutsch/television', 'Television', 1234);

INSERT INTO s_articles_categories (articleID, categoryID) VALUES (2,140809703);
INSERT INTO s_categories_attributes (categoryID, connect_imported_category) VALUES (140809703, 1);
INSERT INTO s_articles_categories_ro (articleID, categoryID, parentCategoryID) VALUES (2 ,140809703, 3);
UPDATE s_articles_attributes SET connect_mapped_category = 1 WHERE articleID = 2;