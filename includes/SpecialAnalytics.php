<?php

use MediaWiki\Html\Html;

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
		$daysDropdown = new OOUI\DropdownInputWidget( [
			'id' => 'special-analytics-days',
			'classes' => [ 'special-analytics-filter' ],
			'value' => $days,
			'options' => [
				[ 'data' => 30, 'label' => $this->msg( 'analytics-last-30-days' ) ],
				[ 'data' => 90, 'label' => $this->msg( 'analytics-last-90-days' ) ],
				[ 'data' => 365, 'label' => $this->msg( 'analytics-last-365-days' ) ],
				[ 'data' => '', 'label' => $this->msg( 'analytics-all-time' ) ]
			]
		] );

		$frequency = $request->getRawVal( 'frequency' );
		$frequencyDropdown = new OOUI\DropdownInputWidget( [
			'id' => 'special-analytics-frequency',
			'classes' => [ 'special-analytics-filter' ],
			'value' => $frequency,
			'options' => [
				[ 'data' => 'daily', 'label' => $this->msg( 'analytics-daily' ) ],
				[ 'data' => '', 'label' => $this->msg( 'analytics-monthly' ) ]
			]
		] );

		$pageInput = new OOUI\TextInputWidget( [
			'id' => 'special-analytics-page',
			'classes' => [ 'special-analytics-filter' ],
			'placeholder' => $this->msg( 'analytics-filter' ),
			'value' => $subpage ? str_replace( '_', ' ', $subpage ) : null
		] );

		$html .= new OOUI\HorizontalLayout( [
			'id' => 'special-analytics-filters',
			'items' => [ $daysDropdown, $frequencyDropdown, $pageInput ]
		] );

		$html .= Html::closeElement( 'div' );

		$html .= Html::element( 'h2', [], $this->msg( 'analytics-views' ) );
		$html .= Html::openElement( 'section', [ 'id' => 'special-analytics-views-section' ] );
		$html .= Html::openElement( 'dl', [ 'class' => 'special-analytics-description-list' ] );
		$html .= Html::element( 'dt', [], $this->msg( 'analytics-total' ) );
		$html .= Html::element( 'dd', [] );
		$html .= Html::element( 'dt', [], $this->msg( 'analytics-average' ) );
		$html .= Html::element( 'dd', [] );
		$html .= Html::closeElement( 'dl' );
		$html .= Html::element( 'canvas', [ 'class' => 'special-analytics-canvas', 'width' => 1000, 'height' => 200 ] );
		$html .= Html::closeElement( 'section' );

		$html .= Html::element( 'h2', [], $this->msg( 'analytics-edits' ) );
		$html .= Html::openElement( 'section', [ 'id' => 'special-analytics-edits-section' ] );
		$html .= Html::openElement( 'dl', [ 'class' => 'special-analytics-description-list' ] );
		$html .= Html::element( 'dt', [], $this->msg( 'analytics-total' ) );
		$html .= Html::element( 'dd', [] );
		$html .= Html::element( 'dt', [], $this->msg( 'analytics-average' ) );
		$html .= Html::element( 'dd', [] );
		$html .= Html::closeElement( 'dl' );
		$html .= Html::element( 'canvas', [ 'class' => 'special-analytics-canvas', 'width' => 1000, 'height' => 200 ] );
		$html .= Html::closeElement( 'section' );

		$html .= Html::element( 'h2', [], $this->msg( 'analytics-editors' ) );
		$html .= Html::openElement( 'section', [ 'id' => 'special-analytics-editors-section' ] );
		$html .= Html::openElement( 'dl', [ 'class' => 'special-analytics-description-list' ] );
		$html .= Html::element( 'dt', [], $this->msg( 'analytics-total' ) );
		$html .= Html::element( 'dd', [] );
		$html .= Html::element( 'dt', [], $this->msg( 'analytics-average' ) );
		$html .= Html::element( 'dd', [] );
		$html .= Html::closeElement( 'dl' );
		$html .= Html::element( 'canvas', [ 'class' => 'special-analytics-canvas', 'width' => 1000, 'height' => 200 ] );
		$html .= Html::closeElement( 'section' );

		$html .= Html::element( 'h3', [], $this->msg( 'analytics-top-editors' ) );
		$html .= Html::element( 'section', [ 'id' => 'special-analytics-top-editors-section' ] );

		$html .= Html::closeElement( 'div' );
		$output->addHTML( $html );
	}
}
