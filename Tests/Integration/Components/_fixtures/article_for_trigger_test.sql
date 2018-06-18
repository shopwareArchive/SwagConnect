INSERT INTO IGNORE s_core_tax (id, tax, description) VALUES (111, 7.5, "default");

INSERT INTO IGNORE s_articles_supplier (id, `name`, img, link) VALUES (111, "testSupplier", "", "");

UPDATE s_articles SET taxID = 111, supplierID = 111 WHERE id = 3;

INSERT INTO IGNORE s_plugin_connect_items (article_id, article_detail_id, source_id, purchase_price_hash, offer_valid_until, stream, shop_id, cron_update)
VALUES (3, 3, "1234-1", "hash", 123, "Awesome products", 1234, NULL);

INSERT INTO IGNORE s_articles_attributes (articleID, articledetailsID) VALUES (3, 3);

INSERT INTO IGNORE s_articles_img (articleID, main, description, position, width, height, relations, extension) VALUES (3, 1, "", 1, 1, 1, "", "jpeg");

INSERT INTO IGNORE s_categories (id, parent, description, `left`, `right`, `level`, added, changed, active, blog, hidefilter, hidetop)
VALUES (2222, 1, "Deutsch", 1, 1, 1, NOW(), NOW(), 1, 1, 0, 0);

INSERT INTO IGNORE s_articles_categories (articleID, categoryID) VALUES (3, 2222);

INSERT INTO IGNORE s_articles_prices (articleID, articledetailsID, price, pricegroup, `from`, `to`) VALUES (3, 3, 7.9, 1, 1, "beliebig");

INSERT INTO IGNORE s_articles_translations (articleID, languageID, `name`, keywords, description, description_long, description_clear, attr1, attr2, attr3, attr4, attr5) VALUES (3, 1, "testTranslation", "", "", "", "", "", "", "", "", "");