let Analytics = {

	init: function () {
		Analytics.update();
		$( '#special-analytics-days select' ).on( 'change', Analytics.update );
		$( '#special-analytics-frequency select' ).on( 'change', Analytics.update );
		$( '#special-analytics-page input' ).on( 'change', Analytics.update );
	},

	update: function () {
		// Get the relevant params
		const days = $( '#special-analytics-days select' ).val();
		const frequency = $( '#special-analytics-frequency select' ).val();
		const page = $( '#special-analytics-page input' ).val();

		// Update the URL
		const url = new URL( window.location.href );
		if ( days ) {
			url.searchParams.set( 'days', days );
		} else {
			url.searchParams.delete( 'days' );
		}
		if ( frequency ) {
			url.searchParams.set( 'frequency', frequency );
		} else {
			url.searchParams.delete( 'frequency' );
		}
		if ( page ) {
			url.searchParams.set( 'page', page );
		} else {
			url.searchParams.delete( 'page' );
		}
		window.history.pushState( {}, '', url.href );

		// Update the charts and tables
		const params = Object.fromEntries( url.searchParams );
		Analytics.updateViews( params );
		Analytics.updateEdits( params );
		Analytics.updateEditors( params );
		Analytics.updateTopEditors( params );
	},

	updateViews: function ( params ) {
		new mw.Rest().get( '/analytics/views', params ).done( function ( data ) {
			if ( Analytics.viewsChart ) {
				Analytics.viewsChart.destroy();
			}
			const canvas = document.getElementById( 'special-analytics-views' );
			Analytics.viewsChart = Analytics.makeChart( canvas, data );
		} );
	},

	updateEdits: function ( params ) {
		new mw.Rest().get( '/analytics/edits', params ).done( function ( data ) {
			if ( Analytics.editsChart ) {
				Analytics.editsChart.destroy();
			}
			const canvas = document.getElementById( 'special-analytics-edits' );
			Analytics.editsChart = Analytics.makeChart( canvas, data );
		} );
	},

	updateEditors: function ( params ) {
		new mw.Rest().get( '/analytics/editors', params ).done( function ( data ) {
			if ( Analytics.editorsChart ) {
				Analytics.editorsChart.destroy();
			}
			const canvas = document.getElementById( 'special-analytics-editors' );
			Analytics.editorsChart = Analytics.makeChart( canvas, data );
		} );
	},

	updateTopEditors: function ( params ) {
		new mw.Rest().get( '/analytics/top-editors', params ).done( function ( data ) {
			const $table = Analytics.makeTable( data );
			const div = document.getElementById( 'special-analytics-top-editors' );
			$( div ).html( $table );
		} );
	},

	makeChart: function ( canvas, data ) {
		return new Chart( canvas, {
			type: 'line',
			data: {
				labels: Object.keys( data ),
				datasets: [ {
					data: Object.values( data ),
					borderWidth: 1
				} ]
			},
			options: {
				responsive: false,
				maintainAspectRatio: false,
				plugins: {
					legend: {
						display: false
					}
				},
				scales: {
					y: {
						beginAtZero: true
					}
				}
			}
		} );
	},

	makeTable: function ( data ) {
		const $th1 = $( '<th>User</th>' );
		const $th2 = $( '<th>Edits</th>' );
		const $thr = $( '<tr></tr>' ).append( $th1, $th2 );
		const $table = $( '<table class="wikitable"></table>' ).append( $thr );
		for ( const [ user, edits ] of Object.entries( data ) ) {
			const url = mw.util.getUrl( 'User:' + user );
			const link = $( '<a href="' + url + '">' + user + '</a>' );
			const $td1 = $( '<td></td>' ).html( link );
			const $td2 = $( '<td></td>' ).text( edits );
			const $tdr = $( '<tr></tr>' ).append( $td1, $td2 );
			$table.append( $tdr );
		}
		return $table;
	}
};

// Register a ChartJS plugin to show a message when there's no data
// https://github.com/chartjs/Chart.js/issues/3745
Chart.register( {
	id: 'NoData',
	afterDraw: function ( chart ) {
		if ( chart.data.datasets .map( ( d ) => d.data.length ).reduce( ( p, a ) => p + a, 0 ) === 0 ) {
			const ctx = chart.ctx;
			const width = chart.width;
			const height = chart.height;
			chart.clear();
			ctx.save();
			ctx.textAlign = 'center';
			ctx.textBaseline = 'middle';
			ctx.font = '1.5rem ' + window.getComputedStyle( document.body ).fontFamily;
			ctx.fillStyle = '#aaa';
			ctx.fillText( 'No data for this time period', width / 2, height / 2 );
			ctx.restore();
		}
	},
} );

mw.loader.using( [
	'mediawiki.api',
	'mediawiki.util'
], Analytics.init );