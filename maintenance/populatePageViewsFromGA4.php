<?php

require __DIR__ . '/../../../maintenance/Maintenance.php';
require __DIR__ . '/../vendor/autoload.php';

use Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\RunReportRequest;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\FilterExpression;
use Google\Analytics\Data\V1beta\Filter;
use Google\Analytics\Data\V1beta\StringFilter;

use MediaWiki\MediaWikiServices;

class AnalyticsPageViewsScript extends Maintenance {

	/**
	 * Define the script options
	 */
	public function __construct() {
		parent::__construct();
		$this->addOption( 'credentials', 'Path to the Google Cloud credentials file', false, true, 'c' );
		$this->addOption( 'property', 'Property ID', false, true, 'p' );
		$this->addOption( 'days', 'How many days to fetch', false, true, 'd' );
		$this->addOption( 'offset', 'How many days to skip', false, true, 'o' );
	}

	/**
	 * Main script
	 */
	public function execute() {
		$credentials = $this->getOption( 'credentials' );
		$property = $this->getOption( 'property' );
		$days = $this->getOption( 'days' );
		$offset = $this->getOption( 'offset' );

		// Connect to the database
		$services = MediaWikiServices::getInstance();
		$lb = $services->getDBLoadBalancer();
		$dbw = $lb->getConnectionRef( DB_MASTER );

		// Connect to Google Cloud
		$client = new BetaAnalyticsDataClient( [
			'credentials' => json_decode( file_get_contents( $credentials ), true )
		] );

		// Prepare the API call
		// https://developers.google.com/analytics/devguides/reporting/data/v1/api-schema
		// https://github.com/googleapis/php-analytics-data
		$request = new RunReportRequest();
		$request->setProperty( 'properties/' . $property );
		$request->setDimensions( [ new Dimension( [ 'name' => 'pagePath' ] ) ] );
		$request->setMetrics( [ new Metric( [ 'name' => 'screenPageViews' ] ) ] );

		// Make one call per day
		for ( $day = 1; $day <= $days; $day++ ) {
			if ( $offset && $day <= $offset ) {
				continue;
			}

			$timestamp = date( 'Ymd', strtotime( "-$day days" ) );
			print "$timestamp ($day/$days)" . PHP_EOL;

			$request->setDateRanges( [
				new DateRange( [
					'start_date' => $day . 'daysAgo',
					'end_date' => $day . 'daysAgo',
				] ),
			] );
			$response = $client->runReport( $request );

			// Insert into database
			$rows = $response->getRows();
			foreach ( $rows as $row ) {
				$views = $row->getMetricValues()[0]->getValue();
				$path = $row->getDimensionValues()[0]->getValue();
				$page = substr( $path, 1 ); // Remove the leading dash
				$title = Title::newFromDBkey( $page );
				if ( !$title ) {
					continue;
				}
				$id = $title->getArticleID();
				if ( !$id ) {
					continue;
				}
				$dbw->upsert( 'analytics_pageviews',
					[ 'ap_page' => $id, 'ap_timestamp' => $timestamp, 'ap_views' => $views ],
					[ [ 'ap_page', 'ap_timestamp' ] ],
					[ 'ap_views' => $views ]
				);
			}

			// Don't exceed Google's API request limits
			usleep( 100000 );
		}
	}
}

$maintClass = AnalyticsPageViewsScript::class;
require_once RUN_MAINTENANCE_IF_MAIN;