INSERT INTO s_product_streams (name, conditions, type, sorting, description) VALUES ('AwStream', null, 2, '"{\\"Shopware\\\\\\\\Bundle\\\\\\\\SearchBundle\\\\\\\\Sorting\\\\\\\\PriceSorting\\":{\\"direction\\":\\"DESC\\"}}"', 'lorem ipsum');

DELETE FROM s_product_streams_selection;
INSERT INTO s_product_streams_selection (stream_id, article_id) SELECT id, 2 FROM s_product_streams WHERE name = 'AwStream';
INSERT INTO s_product_streams_selection (stream_id, article_id) SELECT id, 117 FROM s_product_streams WHERE name = 'AwStream';