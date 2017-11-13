INSERT INTO s_filter (`id`, `name`, `position`, `comparable`, `sortmode`) VALUES (1234, "TestFilter", 0, 1, 0);

INSERT INTO s_articles (`id`, `name`, `main_detail_id`, filtergroupID, taxID) VALUES (32870, 'All-New Fire HD 8 Tablet', 2404535, 1234, 1);
INSERT INTO s_articles_details (id, `articleID`, `ordernumber`, `kind`) VALUES (2404535, 32870, 'sw32870', 1);
INSERT INTO s_articles_attributes (`articleID`, `articledetailsID`) VALUES (32870, 2404535);
INSERT INTO s_plugin_connect_items (article_id, article_detail_id, source_id, exported, export_status)
VALUES (32870, 2404535, 32870, 1, 'synced');

INSERT INTO s_articles_details (id, `articleID`, `ordernumber`, `kind`) VALUES (2404536, 32870, 'sw32870.2', 2);
INSERT INTO s_articles_attributes (`articleID`, `articledetailsID`) VALUES (32870, 2404536);
INSERT INTO s_plugin_connect_items (article_id, article_detail_id, source_id, exported, export_status)
VALUES (32870, 2404536, 32870, 1, 'synced');

INSERT INTO s_articles_details (id, `articleID`, `ordernumber`, `kind`) VALUES (2404537, 32870, 'sw32870.3', 2);
INSERT INTO s_articles_attributes (`articleID`, `articledetailsID`) VALUES (32870, 2404537);
INSERT INTO s_plugin_connect_items (article_id, article_detail_id, source_id, exported, export_status)
VALUES (32870, 2404537, 32870, 1, 'synced');

