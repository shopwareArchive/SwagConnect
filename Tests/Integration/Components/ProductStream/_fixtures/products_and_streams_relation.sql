INSERT INTO s_articles (`name`) VALUES ('Glasbecher Stela');
INSERT INTO s_articles_details (`articleID`, `ordernumber`) VALUES ((SELECT id FROM s_articles WHERE `name` = 'Glasbecher Stela'), 'sw1001');
INSERT INTO s_articles_details (`articleID`, `ordernumber`) VALUES ((SELECT id FROM s_articles WHERE `name` = 'Glasbecher Stela'), 'sw1001.2');
INSERT INTO s_articles_details (`articleID`, `ordernumber`) VALUES ((SELECT id FROM s_articles WHERE `name` = 'Glasbecher Stela'), 'sw1001.3');

INSERT INTO s_product_streams (`name`) VALUES ('Küche');
INSERT INTO s_product_streams_selection (`article_id`, `stream_id`) VALUES ((SELECT id FROM s_articles WHERE `name` = 'Glasbecher Stela'), (SELECT id FROM s_product_streams WHERE `name` = 'Küche'));

INSERT INTO s_articles (`name`) VALUES ('Sofa Da Vinci');
INSERT INTO s_articles_details (`articleID`, `ordernumber`) VALUES ((SELECT id FROM s_articles WHERE `name` = 'Sofa Da Vinci'), 'sw1002');
INSERT INTO s_articles_details (`articleID`, `ordernumber`) VALUES ((SELECT id FROM s_articles WHERE `name` = 'Sofa Da Vinci'), 'sw1002.2');
INSERT INTO s_articles_details (`articleID`, `ordernumber`) VALUES ((SELECT id FROM s_articles WHERE `name` = 'Sofa Da Vinci'), 'sw1002.3');

INSERT INTO s_articles (`name`) VALUES ('Tisch Industrie Stil Massiv Modern');
INSERT INTO s_articles_details (`articleID`, `ordernumber`) VALUES ((SELECT id FROM s_articles WHERE `name` = 'Tisch Industrie Stil Massiv Modern'), 'sw1003');

INSERT INTO s_product_streams (`name`) VALUES ('Wohnzimmer');
INSERT INTO s_product_streams_selection (`article_id`, `stream_id`) VALUES ((SELECT id FROM s_articles WHERE `name` = 'Sofa Da Vinci'), (SELECT id FROM s_product_streams WHERE `name` = 'Wohnzimmer'));
INSERT INTO s_product_streams_selection (`article_id`, `stream_id`) VALUES ((SELECT id FROM s_articles WHERE `name` = 'Tisch Industrie Stil Massiv Modern'), (SELECT id FROM s_product_streams WHERE `name` = 'Wohnzimmer'));