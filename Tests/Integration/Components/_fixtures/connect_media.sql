INSERT INTO s_articles (`id`, `name`) VALUES (14467, 'Baby-Träger');

INSERT INTO s_articles_img (id, articleID, img, main, parent_id, media_id) VALUES (1234, 14467, "test_img", 1, NULL, 1234);
INSERT INTO s_media (`id`, `name`) VALUES (1234, "test_img");
INSERT INTO s_media_attributes (mediaID, connect_hash) VALUES (1234, "main_img");

INSERT INTO s_articles_img (id, articleID, img, main, parent_id, media_id) VALUES (1235, 14467, "test_img", 0, NULL, 1235);
INSERT INTO s_media (`id`, `name`) VALUES (1235, "test_img");
INSERT INTO s_media_attributes (mediaID, connect_hash) VALUES (1235, "no_main_img");