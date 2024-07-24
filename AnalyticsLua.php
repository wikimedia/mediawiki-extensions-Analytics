<?php

use MediaWiki\MediaWikiServices;

/**
 * This class queries the database for the Analytics Lua library
 */
class AnalyticsLua extends Scribunto_LuaLibraryBase {

	public static function onScribuntoExternalLibraries( string $engine, array &$extraLibraries ) {
		$extraLibraries['analytics'] = self::class;
	}

	public function register() {
		$this->getEngine()->registerInterface( __DIR__ . '/AnalyticsLua.lua', [
			'getViewsData' => [ $this, 'getViewsData' ],
			'getEditsData' => [ $this, 'getEditsData' ],
		] );
	}

	/**
	 * Get the views of a given page
	 *
	 * @param string $page Page name
	 * @return string Page views
	 */
	public function getViewsData( $page ) {
		$params = [];
		if ( $page ) {
			$params['page'] = $page;
		}
		$data = Analytics::getViewsData( $params );
		return [ self::toLuaTable( $data ) ];
	}

	/**
	 * Get the edits of a given page
	 *
	 * @param string $page Page name
	 * @return string Page views
	 */
	public function getEditsData( $page ) {
		$params = [];
		if ( $page ) {
			$params['page'] = $page;
		}
		$data = Analytics::getEditsData( $params );
		return [ self::toLuaTable( $data ) ];
	}

	/**
	 * Helper method to convert an array to a viable Lua table
	 *
	 * The resulting table has its numerical indices start with 1
	 * If $array is not an array, it is simply returned
	 *
	 * @param mixed $array
	 * @return mixed Lua object
	 * @see https://github.com/SemanticMediaWiki/SemanticScribunto/blob/master/src/ScribuntoLuaLibrary.php
	 */
	private static function toLuaTable( $array ) {
		if ( is_array( $array ) ) {
			foreach ( $array as $key => $value ) {
				$array[ $key ] = self::toLuaTable( $value );
			}
			array_unshift( $array, '' );
			unset( $array[0] );
		}
		return $array;
	}
}
