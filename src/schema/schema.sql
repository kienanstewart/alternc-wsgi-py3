CREATE TABLE IF NOT EXISTS `subdomain_wsgi` (
 `id` bigint(20) unsigned NOT NULL,
 `venv` varchar(255) default '',
 `app_subdir` varchar(255) default '',
 PRIMARY KEY (`id`)
) COMMENT = 'WSGI / Python settings for each subdomain'
