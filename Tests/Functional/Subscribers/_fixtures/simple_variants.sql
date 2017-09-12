INSERT INTO s_articles (`id`, `name`, `main_detail_id`) VALUES (32870, 'All-New Fire HD 8 Tablet', 2404535);
INSERT INTO s_articles_details (id, `articleID`, `ordernumber`, `kind`) VALUES (2404535, 32870, 'sw32870', 1);
INSERT INTO s_plugin_connect_items (article_id, article_detail_id, source_id, purchase_price_hash, offer_valid_until, stream, export_status)
VALUES (32870, 2404535, 32870, "hash", 123, 1, 'synced');
INSERT INTO s_articles_details (id, `articleID`, `ordernumber`, `kind`) VALUES (2404536, 32870, 'sw32870.2', 2);
INSERT INTO s_plugin_connect_items (article_id, article_detail_id, source_id, purchase_price_hash, offer_valid_until, stream, export_status)
VALUES (32870, 2404536, '32870-2404536', "hash", 123, 1, 'synced');
INSERT INTO s_articles_details (id, `articleID`, `ordernumber`, `kind`) VALUES (2404537, 32870, 'sw32870.3', 2);
INSERT INTO s_plugin_connect_items (article_id, article_detail_id, source_id, purchase_price_hash, offer_valid_until, stream, export_status)
VALUES (32870, 2404537, '32870-2404537', "hash", 123, 1, 'synced');