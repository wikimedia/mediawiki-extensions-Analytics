<?php

use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\SimpleHandler;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * Class to get analytics for a page or the entire site
 * GET /analytics/{endpoint}
 */
class AnalyticsAPI extends SimpleHandler {

	private const VALID_ENDPOINTS = [ 'views', 'edits', 'editors', 'top-editors' ];

	private const VALID_FREQUENCIES = [ 'monthly', 'daily' ];

	public function run( $endpoint ) {
		$request = $this->getRequest();
		$params = $request->getQueryParams();
		switch ( $endpoint ) {
			case 'views':
				$data = Analytics::getViewsData( $params );
				break;
			case 'edits':
				$data = Analytics::getEditsData( $params );
				break;
			case 'editors':
				$data = Analytics::getEditorsData( $params );
				break;
			case 'top-editors':
				$data = Analytics::getTopEditorsData( $params );
				break;
		}
		return $data;
	}

	/** @inheritDoc */
	public function needsWriteAccess() {
		return false;
	}

	/** @inheritDoc */
	public function getParamSettings() {
		return [
			'endpoint' => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => self::VALID_ENDPOINTS,
				ParamValidator::PARAM_REQUIRED => true
			],
			'days' => [
				self::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_TYPE => 'string'
			],
			'frequency' => [
				self::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_TYPE => self::VALID_FREQUENCIES,
			],
			'page' => [
				self::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_TYPE => 'string'
			]
		];
	}
}
