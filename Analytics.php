<?php

use MediaWiki\MediaWikiServices;

class Analytics {

	public static function onLoadExtensionSchemaUpdates( DatabaseUpdater $updater ) {
		$updater->addExtensionTable( 'analytics_pageviews', __DIR__ . '/Analytics.sql' );
	}

	public static function onPageViewUpdates( WikiPage $wikipage, User $user ) {
		$config = MediaWikiServices::getInstance()->getMainConfig();
		if ( !$config->get( 'AnalyticsCountPageViews' ) ) {
			return;
		}
		if ( $user->isAllowed( 'bot' ) ) {
			return; // Don't count bots
		}
		if ( $wikipage->exists() ) {
			$pageId = $wikipage->getId();
			$update = new AnalyticsUpdate( $pageId );
			DeferredUpdates::addUpdate( $update );
		}
	}

	public static function getViewsData( $params ) {
		return self::getChartData( 'views', $params );
	}

	public static function getEditsData( $params ) {
		return self::getChartData( 'edits', $params );
	}

	public static function getEditorsData( $params ) {
		return self::getChartData( 'editors', $params );
	}

	public static function getTopEditorsData( $params ) {
		return self::getTableData( $params );
	}

	private static function getChartData( $chart, $params ) {
		// Set the relevant params
		// @todo Make more readable and robust
		$page = empty( $params['page'] ) ? null : $params['page'];
		if ( $page ) {
			$title = Title::newFromText( $page );
			$pageID = $title->getArticleID();
		}
		$days = empty( $params['days'] ) ? 9999 : intval( $params['days'] );
		$frequency = empty( $params['frequency'] ) ? null : $params['frequency'];

		// Figure out the best frequency when it's not given
		if ( !$frequency ) {
			switch ( $days ) {
				case $days > 1100:
					$frequency = 'years';
					break;
				case $days > 90:
					$frequency = 'months';
					break;
				case $days < 2:
					$frequency = 'hours';
					break;
				default:
					$frequency = 'days';
					break;
			}
		}

		// Figure out some variables
		switch ( $frequency ) {
			case 'years':
				$timestampFormat = 'Y';
				$timestampLength = 4; // YYYY
				$dataPoints = ceil( $days / 365 );
				break;
			case 'months':
				$timestampFormat = 'Ym';
				$timestampLength = 6; // YYYYMM
				$dataPoints = ceil( $days / 30 );
				break;
			case 'hours':
				$timestampFormat = 'Ymdh';
				$timestampLength = 10; // YYYYMMDDHH
				$dataPoints = ceil( $days * 24 );
				break;
			case 'days':
				$timestampFormat = 'Ymd';
				$timestampLength = 8; // YYYYMMDD
				$dataPoints = $days;
				break;
		}

		// Build the database query
		$lb = MediaWikiServices::getInstance()->getDBLoadBalancer();
		$dbr = $lb->getConnectionRef( DB_REPLICA );
		$query = $dbr->newSelectQueryBuilder();
		switch ( $chart ) {
			case 'views':
				// @todo No hourly data
				$query->select( [ "LEFT( ap_timestamp, $timestampLength ) AS timestamp", 'SUM( ap_views ) AS value' ] )
					->from( 'analytics_pageviews' )
					->groupBy( "LEFT( ap_timestamp, $timestampLength )" );
				if ( $pageID ) {
					$query->where( [ 'ap_page' => $pageID ] );
				}
				break;
			case 'edits':
				$query->select( [ "LEFT( rev_timestamp, $timestampLength ) AS timestamp", 'COUNT(*) AS value' ] )
					->from( 'revision' )
					->groupBy( "LEFT( rev_timestamp, $timestampLength )" );
				if ( $pageID ) {
					$query->where( [ 'rev_page' => $pageID ] );
				}
				break;
			case 'editors':
				$query->select( [ "LEFT( rev_timestamp, $timestampLength ) AS timestamp", 'COUNT( DISTINCT rev_actor ) AS value' ] )
					->from( 'revision' )
					->groupBy( "LEFT( rev_timestamp, $timestampLength )" );
					if ( $pageID ) {
						$query->where( [ 'rev_page' => $pageID ] );
					}
				break;
		}
		$query->orderBy( 'timestamp DESC' );
		$query->limit( $dataPoints );

		// Fetch the results
		$results = [];
		$resultSet = $query->fetchResultSet();
		foreach ( $resultSet as $result ) {
			$timestamp = $result->timestamp;
			$value = $result->value;
			$results[ $timestamp ] = $value;
		}

		// Fill the empty values
		$data = [];
		for ( $dataPoint = 0; $dataPoint < $dataPoints; $dataPoint++ ) {
			$timestamp = date( $timestampFormat, strtotime( "-$dataPoint $frequency" ) );
			if ( array_key_exists( $timestamp, $results ) ) {
				$data[ $timestamp ] = $results[ $timestamp ];
			} else {
				$data[ $timestamp ] = 0;
			}
		}

		// Trim the empty values at the start
		$data = array_reverse( $data, true );
		foreach ( $data as $timestamp => $value ) {
			if ( $value === 0 ) {
				unset( $data[ $timestamp ] );
			} else {
				break;
			}
		}

		return $data;
	}

	private static function getTableData( $params ) {
		// Set the relevant params
		$page = empty( $params['page'] ) ? null : $params['page'];
		if ( $page ) {
			$title = Title::newFromText( $page );
			$pageID = $title->getArticleID();
		}

		// Query the database
		$lb = MediaWikiServices::getInstance()->getDBLoadBalancer();
		$dbr = $lb->getConnectionRef( DB_REPLICA );
		$query = $dbr->newSelectQueryBuilder()
			->select( [ 'actor_name AS actor', 'COUNT( rev_id ) as edits' ] )
			->from( 'revision' )
			->join( 'actor', null, 'rev_actor = actor_id' )
			->groupBy( 'rev_actor' )
			->orderBy( 'edits DESC' )
			->limit( 10 );
		if ( $pageID ) {
			$query->where( [ 'rev_page' => $pageID ] );
		}

		// Extract the data
		$data = [];
		$resultSet = $query->fetchResultSet();
		foreach ( $resultSet as $result ) {
			$actor = $result->actor;
			$edits = $result->edits;
			$data[ $actor ] = $edits;
		}
		return $data;
	}
}