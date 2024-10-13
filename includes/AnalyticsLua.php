<?php

use MediaWiki\Extension\Scribunto\Engines\LuaCommon\LibraryBase;

/**
 * This class queries the database for the Analytics Lua library
 */
class AnalyticsLua extends LibraryBase {

	public static function onScribuntoExternalLibraries( string $engine, array &$extraLibraries ) {
		$extraLibraries['analytics'] = self::class;
	}

	public function register() {
		$this->getEngine()->registerInterface( __DIR__ . '/../AnalyticsLua.lua', [
			'getViewsData' => [ $this, 'getViewsData' ],
			'getEditsData' => [ $this, 'getEditsData' ],
		] );
	}

	/**
	 * Get the views of a given page
	 *
	 * @param array $params
	 * @return array Views data
	 */
	public function getViewsData( $params ) {
		$data = Analytics::getViewsData( $params );
		return [ $data ];
	}

	/**
	 * Get the edits of a given page
	 *
	 * @param array $params
	 * @return array Edits data
	 */
	public function getEditsData( $params ) {
		$data = Analytics::getEditsData( $params );
		return [ $data ];
	}
}
