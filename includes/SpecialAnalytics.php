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
		$output->addModules( 'ext.Analytics' );

		$request = $this->getRequest();

		$html = Html::openElement( 'div', [ 'id' => 'special-analytics' ] );

		$days = $request->getRawVal( 'days' );
		$days = $days ? intval( $days ) : '';
		$daysDropdown = new OOUI\DropdownInputWidget( [
			'id' => 'special-analytics-days',
			'value' => $days,
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

		$frequency = $request->getRawVal( 'frequency' );
		$frequencyDropdown = new OOUI\DropdownInputWidget( [
			'id' => 'special-analytics-frequency',
			'value' => $frequency,
			'options' => [
				[ 'data' => 'hourly', 'label' => 'Hourly' ],
				[ 'data' => 'daily', 'label' => 'Daily' ],
				[ 'data' => 'monthly', 'label' => 'Monthly' ],
				[ 'data' => 'yearly', 'label' => 'Yearly' ],
				[ 'data' => '', 'label' => 'Auto frequency' ],
			]
		] );

		$pageInput = new OOUI\TextInputWidget( [
			'id' => 'special-analytics-page',
			'placeholder' => 'Filter by page...',
			'value' => $subpage ? str_replace( '_', ' ', $subpage ) : null
		] );

		$html .= new OOUI\HorizontalLayout( [
			'id' => 'special-analytics-filters',
			'items' => [ $daysDropdown, $frequencyDropdown, $pageInput ]
		] );

		$html .= Html::closeElement( 'div' );

		$html .= Html::element( 'h2', [ 'id' => 'Views' ], 'Views' );
		$html .= Html::element( 'canvas', [ 'id' => 'special-analytics-views', 'class' => 'special-analytics-canvas', 'width' => 1000, 'height' => 200 ] );

		$html .= Html::element( 'h2', [ 'id' => 'Edits' ], 'Edits' );
		$html .= Html::element( 'canvas', [ 'id' => 'special-analytics-edits', 'class' => 'special-analytics-canvas', 'width' => 1000, 'height' => 200 ] );

		$html .= Html::element( 'h2', [ 'id' => 'Editors' ], 'Editors' );
		$html .= Html::element( 'canvas', [ 'id' => 'special-analytics-editors', 'class' => 'special-analytics-canvas', 'width' => 1000, 'height' => 200 ] );

		$html .= Html::element( 'h2', [ 'id' => 'TopEditors' ], 'Top editors' );
		$html .= Html::element( 'div', [ 'id' => 'special-analytics-top-editors' ] );

		$html .= Html::closeElement( 'div' );
		$output->addHTML( $html );
	}
}