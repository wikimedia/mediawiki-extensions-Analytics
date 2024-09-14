<?php

use MediaWiki\MediaWikiServices;

class Analytics {

	public static function onLoadExtensionSchemaUpdates( DatabaseUpdater $updater ) {
		$updater->addExtensionTable( 'analytics_pageviews', __DIR__ . '/../sql/Analytics.sql' );
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
		$page = empty( $params['page'] ) ? null : $params['page'];
		$days = empty( $params['days'] ) ? 9999 : intval( $params['days'] );
		$frequency = empty( $params['frequency'] ) ? 'monthly' : $params['frequency'];

		// Set some variables
		if ( $frequency === 'daily' ) {
			$period = 'days';
			$timestampFormat = 'Ymd';
			$timestampLength = 8; // YYYYMMDD
			$dataPoints = $days;
		} else {
			$period = 'months';
			$timestampFormat = 'Ym';
			$timestampLength = 6; // YYYYMM
			$dataPoints = ceil( $days / 30 );
		}

		// Connect to the database
		$services = MediaWikiServices::getInstance();
		$lb = $services->getDBLoadBalancer();
		$dbr = $lb->getConnectionRef( DB_REPLICA );

		// Build the database query
		$query = $dbr->newSelectQueryBuilder();
		switch ( $chart ) {
			case 'views':
				$query->select( [ "LEFT( ap_timestamp, $timestampLength ) AS timestamp", 'SUM( ap_views ) AS value' ] )
					->from( 'analytics_pageviews' );
				$pageField = 'ap_page';
				break;
			case 'edits':
				$query->select( [ "LEFT( rev_timestamp, $timestampLength ) AS timestamp", 'COUNT(*) AS value' ] )
					->from( 'revision' );
				$pageField = 'rev_page';
				break;
			case 'pages':
				$query->select( [ "LEFT( rev_timestamp, $timestampLength ) AS timestamp", 'MIN( rev_timestamp ) AS value' ] )
					->from( 'revision' );
				$pageField = 'rev_page';
				break;
			case 'editors':
				$query->select( [ "LEFT( rev_timestamp, $timestampLength ) AS timestamp", 'COUNT( DISTINCT rev_actor ) AS value' ] )
					->from( 'revision' );
				$pageField = 'rev_page';
				break;
		}
		if ( $page ) {
			$title = Title::newFromText( $page );
			if ( $title->getNamespace() === NS_CATEGORY ) {
				$tablePrefix = $dbr->tablePrefix();
				$pageKey = $title->getDBkey();
				$query->where( $pageField . ' IN ( SELECT cl_from FROM ' . $tablePrefix . 'categorylinks WHERE cl_to = "' . $pageKey . '" )' );
			} else {
				$pageId = $title->getArticleID();
				$query->where( [ $pageField => $pageId ] );
			}
		}
		$query->groupBy( "LEFT( timestamp, $timestampLength )" )
			->orderBy( 'timestamp DESC' )
			->limit( $dataPoints );

		// Fetch the results
		$results = [];
		$resultSet = $query->fetchResultSet();
		foreach ( $resultSet as $result ) {
			$timestamp = strval( $result->timestamp );
			$value = $result->value;
			$results[ $timestamp ] = $value;
		}

		// Fill the empty values
		$data = [];
		for ( $dataPoint = 0; $dataPoint < $dataPoints; $dataPoint++ ) {
			$timestamp = date( $timestampFormat, strtotime( "-$dataPoint $period" ) );
			if ( array_key_exists( $timestamp, $results ) ) {
				$value = intval( $results[ $timestamp ] );
				$data[ $timestamp ] = $value;
			} else {
				$data[ $timestamp ] = 0;
			}
		}

		// Trim the empty values at the start
		$data = array_reverse( $data, true ); // This shouldn't be necessary
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

		// Connect to the database
		$services = MediaWikiServices::getInstance();
		$lb = $services->getDBLoadBalancer();
		$dbr = $lb->getConnectionRef( DB_REPLICA );

		// Build the query
		$query = $dbr->newSelectQueryBuilder()
			->select( [ 'actor_name AS actor', 'COUNT( rev_id ) as edits' ] )
			->from( 'revision' )
			->join( 'actor', null, 'rev_actor = actor_id' )
			->groupBy( 'rev_actor' )
			->orderBy( 'edits DESC' )
			->limit( 10 );
		if ( $page ) {
			$title = Title::newFromText( $page );
			if ( $title->getNamespace() === NS_CATEGORY ) {
				$tablePrefix = $dbr->tablePrefix();
				$pageKey = $title->getDBkey();
				$query->where( 'rev_page IN ( SELECT cl_from FROM ' . $tablePrefix . 'categorylinks WHERE cl_to = "' . $pageKey . '" )' );
			} else {
				$pageId = $title->getArticleID();
				$query->where( [ 'rev_page' => $pageId ] );
			}
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
