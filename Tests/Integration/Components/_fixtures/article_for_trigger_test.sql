INSERT INTO s_core_tax (id, tax, description) VALUES (111, 7.5, "default");

INSERT INTO s_articles_supplier (id, `name`, img, link) VALUES (111, "testSupplier", "", "");

INSERT INTO s_articles (`id`, `name`, main_detail_id, changetime, pricegroupActive, laststock, crossbundlelook, notification, template, mode, taxID, supplierID)
VALUES (1234, 'Baby-Träger', 7091846, NOW(), 1, 1, 0, 0, 1, 0, 111, 111);

INSERT INTO s_articles_details (id, `articleID`, `ordernumber`, `kind`, `position`) VALUES (7091846, 1234, 'sw1004', 1, 0);

INSERT INTO s_plugin_connect_items (article_id, article_detail_id, source_id, purchase_price_hash, offer_valid_until, stream, shop_id, cron_update)
VALUES (1234, 7091846, "1234-1", "hash", 123, "Awesome products", 1234, NULL);

INSERT INTO s_articles_attributes (articleID, articledetailsID) VALUES (1234, 7091846);

INSERT INTO s_articles_img (articleID, main, description, position, width, height, relations, extension) VALUES (1234, 1, "", 1, 1, 1, "", "jpeg");

INSERT INTO s_categories (id, parent, description, `left`, `right`, `level`, added, changed, active, blog, hidefilter, hidetop)
VALUES (2222, 1, "Deutsch", 1, 1, 1, NOW(), NOW(), 1, 1, 0, 0);

INSERT INTO s_articles_categories (articleID, categoryID) VALUES (1234, 2222);

INSERT INTO s_articles_prices (articleID, articledetailsID, price, pricegroup, `from`, `to`) VALUES (1234, 7091846, 7.9, 1, 1, "beliebig");

INSERT INTO s_articles_translations (articleID, languageID, `name`, keywords, description, description_long, description_clear, attr1, attr2, attr3, attr4, attr5) VALUES (1234, 1, "testTranslation", "", "", "", "", "", "", "", "", "");