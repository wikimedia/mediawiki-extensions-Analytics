const Analytics = {

	init: function () {
		Analytics.update();
		document.querySelector( '#special-analytics-days select' ).onchange = Analytics.update;
		document.querySelector( '#special-analytics-frequency select' ).onchange = Analytics.update;
		document.querySelector( '#special-analytics-page input' ).onchange = Analytics.update;
	},

	update: function () {
		// Get the relevant params
		const days = document.querySelector( '#special-analytics-days select' ).value;
		const frequency = document.querySelector( '#special-analytics-frequency select' ).value;
		const page = document.querySelector( '#special-analytics-page input' ).value;

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
		// eslint-disable-next-line compat/compat, es-x/no-object-fromentries
		const params = Object.fromEntries( url.searchParams );
		Analytics.updateViews( params );
		Analytics.updateEdits( params );
		Analytics.updateEditors( params );
		Analytics.updateTopEditors( params );
	},

	updateViews: function ( params ) {
		new mw.Rest().get( '/analytics/views', params ).done( ( data ) => {
			const section = document.querySelector( '#special-analytics-views-section' );
			Analytics.updateDescriptionList( section, data );
			if ( Analytics.viewsChart ) {
				Analytics.viewsChart.destroy();
			}
			Analytics.viewsChart = Analytics.updateChart( section, data );
		} );
	},

	updateEdits: function ( params ) {
		new mw.Rest().get( '/analytics/edits', params ).done( ( data ) => {
			const section = document.querySelector( '#special-analytics-edits-section' );
			Analytics.updateDescriptionList( section, data );
			if ( Analytics.editsChart ) {
				Analytics.editsChart.destroy();
			}
			Analytics.editsChart = Analytics.updateChart( section, data );
		} );
	},

	updateEditors: function ( params ) {
		new mw.Rest().get( '/analytics/editors', params ).done( ( data ) => {
			const section = document.querySelector( '#special-analytics-editors-section' );
			Analytics.updateDescriptionList( section, data );
			if ( Analytics.editorsChart ) {
				Analytics.editorsChart.destroy();
			}
			Analytics.editorsChart = Analytics.updateChart( section, data );
		} );
	},

	updateTopEditors: function ( params ) {
		new mw.Rest().get( '/analytics/top-editors', params ).done( ( data ) => {
			const section = document.getElementById( 'special-analytics-top-editors-section' );
			const table = Analytics.makeTable( data );
			section.replaceChildren( table );
		} );
	},

	updateDescriptionList: function ( section, data ) {
		const dl = section.querySelector( 'dl' );

		// eslint-disable-next-line es-x/no-object-values
		const total = Object.values( data ).reduce( ( a, b ) => a + b, 0 );
		const dd1 = dl.getElementsByTagName( 'dd' )[ 0 ];
		dd1.textContent = total;

		const average = Math.round( total / Object.keys( data ).length );
		const dd2 = dl.getElementsByTagName( 'dd' )[ 1 ];
		dd2.textContent = average;
	},

	updateChart: function ( section, data ) {
		const canvas = section.querySelector( '.special-analytics-canvas' );
		return new Chart( canvas, {
			type: 'line',
			data: {
				labels: Object.keys( data ),
				datasets: [ {
					// eslint-disable-next-line es-x/no-object-values
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
		const th1 = document.createElement( 'th' );
		th1.textContent = mw.msg( 'analytics-top-editors-user' );
		const th2 = document.createElement( 'th' );
		th2.textContent = mw.msg( 'analytics-top-editors-edits' );
		const thr = document.createElement( 'tr' );
		thr.append( th1, th2 );
		const table = document.createElement( 'table' );
		table.classList.add( 'wikitable' );
		table.append( thr );
		for ( const user in data ) {
			const edits = data[ user ];
			const url = mw.util.getUrl( 'User:' + user );
			const link = document.createElement( 'a' );
			link.textContent = user;
			link.href = url;
			const td1 = document.createElement( 'td' );
			td1.append( link );
			const td2 = document.createElement( 'td' );
			td2.textContent = edits;
			const tdr = document.createElement( 'tr' );
			tdr.append( td1, td2 );
			table.append( tdr );
		}
		return table;
	}
};

// Register a ChartJS plugin to show a message when there's no data
// https://github.com/chartjs/Chart.js/issues/3745
Chart.register( {
	id: 'NoData',
	afterDraw: ( chart ) => {
		if ( chart.data.datasets.map( ( d ) => d.data.length ).reduce( ( p, a ) => p + a, 0 ) === 0 ) {
			const ctx = chart.ctx;
			const width = chart.width;
			const height = chart.height;
			const message = mw.msg( 'analytics-no-data' );
			chart.clear();
			ctx.save();
			ctx.textAlign = 'center';
			ctx.textBaseline = 'middle';
			ctx.font = '1.5rem ' + window.getComputedStyle( document.body ).fontFamily;
			ctx.fillStyle = '#aaa';
			ctx.fillText( message, width / 2, height / 2 );
			ctx.restore();
		}
	}
} );

Analytics.init();
