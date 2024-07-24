<?php

use MediaWiki\MediaWikiServices;

class AnalyticsUpdate implements DeferrableUpdate {

	/** @var int Page ID to increment the view count */
	protected $pageId;

	/**
	 * Constructor
	 *
	 * @param int $page Page ID to increment the view count
	 */
	public function __construct( $pageId ) {
		$this->pageId = intval( $pageId );
	}

	/**
	 * Run the update
	 */
	public function doUpdate() {
		$services = MediaWikiServices::getInstance();
		$lb = $services->getDBLoadBalancer();
		$dbw = $lb->getConnection( DB_PRIMARY );
		$pageId = $this->pageId;
		$timestamp = date( 'Ymd' );
		$dbw->upsert( 'analytics_pageviews',
			[ 'ap_page' => $pageId, 'ap_timestamp' => $timestamp, 'ap_views' => 1 ], // Perform this INSERT if page ID not found
			[ [ 'ap_page', 'ap_timestamp' ] ],
			[ 'ap_views = ap_views + 1' ] // Perform this SET if page ID found
		);
	}
}