INSERT INTO s_articles (`id`, `name`) VALUES (14467, 'Baby-Träger');
INSERT INTO s_articles_details (id, `articleID`, `ordernumber`, `kind`) VALUES (7091846, 14467, 'sw1004', 1);
INSERT INTO s_plugin_connect_items (article_id, article_detail_id, source_id, purchase_price_hash, offer_valid_until, stream)
VALUES (14467, 7091846, 14467, "hash", 123, 1);

INSERT INTO s_articles (`id`, `name`) VALUES (14468, 'Boba Wrap');
INSERT INTO s_articles_details (id, `articleID`, `ordernumber`, `kind`) VALUES (7091847, 14468, 'sw1005', 1);
INSERT INTO s_plugin_connect_items (article_id, article_detail_id, source_id, purchase_price_hash, offer_valid_until, stream)
VALUES (14468, 7091847, 14468, "hash", 123, 1);

INSERT INTO s_articles (`id`, `name`) VALUES (14469, 'SECHS Positionen');
INSERT INTO s_articles_details (id, `articleID`, `ordernumber`, `kind`) VALUES (7091848, 14469, 'sw1006', 1);
INSERT INTO s_plugin_connect_items (article_id, article_detail_id, source_id, purchase_price_hash, offer_valid_until, stream)
VALUES (14469, 7091848, 14469, "hash", 123, 1);
INSERT INTO s_articles_details (id, `articleID`, `ordernumber`, `kind`) VALUES (7091849, 14469, 'sw1006.2', 2);
INSERT INTO s_plugin_connect_items (article_id, article_detail_id, source_id, purchase_price_hash, offer_valid_until, stream)
VALUES (14469, 7091849, '14469-7091849', "hash", 123, 1);

INSERT INTO s_articles (`id`, `name`) VALUES (14470, 'Tom Joule Wellibob');
INSERT INTO s_articles_details (id, `articleID`, `ordernumber`, `kind`) VALUES (7091850, 14470, 'sw1007', 1);
INSERT INTO s_plugin_connect_items (article_id, article_detail_id, source_id, purchase_price_hash, offer_valid_until, stream)
VALUES (14470, 7091850, 14470, "hash", 123, 1);
INSERT INTO s_articles_details (id, `articleID`, `ordernumber`, `kind`) VALUES (7091851, 14470, 'sw1007.2', 2);
INSERT INTO s_plugin_connect_items (article_id, article_detail_id, source_id, purchase_price_hash, offer_valid_until, stream)
VALUES (14470, 7091851, '14470-7091851', "hash", 123, 1);
INSERT INTO s_articles_details (id, `articleID`, `ordernumber`, `kind`) VALUES (7091852, 14470, 'sw1007.3', 2);
INSERT INTO s_plugin_connect_items (article_id, article_detail_id, source_id, purchase_price_hash, offer_valid_until, stream)
VALUES (14470, 7091852, '14470-7091852', "hash", 123, 1);

INSERT INTO s_articles (`id`, `name`) VALUES (14471, 'CI Module');
INSERT INTO s_articles_details (id, `articleID`, `ordernumber`, `kind`) VALUES (7091853, 14471, 'sw1008', 1);
INSERT INTO s_plugin_connect_items (article_id, article_detail_id, source_id, purchase_price_hash, offer_valid_until, stream, shopId)
VALUES (14471, 7091853, 14471, "hash", 123, 'Best sellers', 42);