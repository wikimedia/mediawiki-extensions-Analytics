<?php

use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\SimpleHandler;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * Class to get analytics for a page or the entire site
 * GET /analytics/{dataset}
 */
class AnalyticsAPI extends SimpleHandler {

	public function run( $dataset ) {
		$request = $this->getRequest();
		$params = $request->getQueryParams();
		switch ( $dataset ) {
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
			'dataset' => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true
			]
		];
	}
}
