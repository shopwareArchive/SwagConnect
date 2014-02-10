CREATE TABLE IF NOT EXISTS `s_plugin_bepado_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `isError` int(1) NOT NULL,
  `service` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `command` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `request` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `response` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `time` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;