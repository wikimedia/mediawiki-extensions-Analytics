<?php

use MediaWiki\MediaWikiServices;

class SpecialAnalytics extends SpecialPage {

	public function __construct( $name = '', $restriction = '', $listed = true ) {
		parent::__construct( 'Analytics' );
	}

	function execute( $subpage ) {
		$this->setHeaders();
		$output = $this->getOutput();
		$output->enableOOUI();

		$output->addModuleStyles( 'ext.Analytics.styles' );
		$output->addScript( '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>' );
		$output->addModules( 'ext.Analytics' );

		$html = Html::openElement( 'div', [ 'id' => 'analytics' ] );

		$days = new OOUI\DropdownInputWidget( [
			'id' => 'analytics-days',
			'options' => [
				[ 'data' => 1, 'label' => 'Last 24 hours' ],
				[ 'data' => 3, 'label' => 'Last 3 days' ],
				[ 'data' => 7, 'label' => 'Last 7 days' ],
				[ 'data' => 30, 'label' => 'Last 30 days' ],
				[ 'data' => 90, 'label' => 'Last 90 days' ],
				[ 'data' => 365, 'label' => 'Last 365 days' ],
				[ 'data' => '', 'label' => 'All time' ],
			]
		] );

		$page = new OOUI\TextInputWidget( [
			'id' => 'analytics-page',
			'placeholder' => 'Filter by page...',
			'value' => $subpage ? str_replace( '_', ' ', $subpage ) : null
		] );

		$html .= new OOUI\HorizontalLayout( [
			'id' => 'analytics-filters',
			'items' => [ $days, $page ]
		] );

		$html .= Html::closeElement( 'div' );

		$html .= Html::element( 'h2', [ 'id' => 'Views' ], 'Views' );
		$html .= Html::element( 'canvas', [ 'id' => 'analytics-views', 'class' => 'analytics-canvas', 'width' => 1000, 'height' => 200 ] );

		$html .= Html::element( 'h2', [ 'id' => 'Edits' ], 'Edits' );
		$html .= Html::element( 'canvas', [ 'id' => 'analytics-edits', 'class' => 'analytics-canvas', 'width' => 1000, 'height' => 200 ] );

		$html .= Html::element( 'h2', [ 'id' => 'Editors' ], 'Editors' );
		$html .= Html::element( 'canvas', [ 'id' => 'analytics-editors', 'class' => 'analytics-canvas', 'width' => 1000, 'height' => 200 ] );

		$html .= Html::element( 'h2', [ 'id' => 'TopEditors' ], 'Top editors' );
		$html .= Html::element( 'div', [ 'id' => 'analytics-top-editors' ] );

		$html .= Html::closeElement( 'div' );
		$output->addHTML( $html );
	}
}