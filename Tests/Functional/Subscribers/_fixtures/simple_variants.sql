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

INSERT INTO s_articles (`id`, `name`, `main_detail_id`) VALUES (32871, 'E-book reader', 2404542);
INSERT INTO s_articles_details (id, `articleID`, `ordernumber`, `kind`) VALUES (2404542, 32871, 'sw32871', 1);
INSERT INTO s_plugin_connect_items (article_id, article_detail_id, source_id, purchase_price_hash, offer_valid_until, stream, export_status, exported)
VALUES (32871, 2404542, '32871-2404542', "hash", 123, 1, '', 0);

INSERT INTO s_articles_details (id, `articleID`, `ordernumber`, `kind`) VALUES (2404543, 32871, 'sw32871.2', 2);
INSERT INTO s_plugin_connect_items (article_id, article_detail_id, source_id, purchase_price_hash, offer_valid_until, stream, export_status, exported)
VALUES (32871, 2404543, '32871-2404543', "hash", 123, 1, '', 0);

INSERT INTO s_articles_details (id, `articleID`, `ordernumber`, `kind`) VALUES (2404544, 32871, 'sw32871.3', 2);
INSERT INTO s_plugin_connect_items (article_id, article_detail_id, source_id, purchase_price_hash, offer_valid_until, stream, export_status, exported)
VALUES (32871, 2404544, '32871-2404544', "hash", 123, 1, '', 0);