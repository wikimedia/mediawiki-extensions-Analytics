CREATE TABLE IF NOT EXISTS /*_*/analytics_pageviews (
	ap_page INTEGER NOT NULL,
	ap_timestamp INTEGER NOT NULL,
	ap_views INTEGER NOT NULL,
	PRIMARY KEY ( ap_page, ap_timestamp )
);