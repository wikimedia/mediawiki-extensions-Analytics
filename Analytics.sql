CREATE TABLE IF NOT EXISTS /*_*/analytics_pageviews (
	ap_page INT(5) UNSIGNED NOT NULL,
	ap_timestamp INT(5) UNSIGNED NOT NULL,
	ap_views INT(8) UNSIGNED NOT NULL,
	PRIMARY KEY ( ap_page, ap_timestamp )
);