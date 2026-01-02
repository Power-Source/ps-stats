function eefpsstatsTableToCsv( $table, filename ) {
	// Temporary delimiters unlikely to be typed by keyboard to avoid accidentally splitting the actual contents.
	const tmpColDelim = String.fromCharCode( 11 ),
		tmpRowDelim = String.fromCharCode( 0 ),
		// Actual delimiters for CSV.
		colDelim = '","',
		rowDelim = '"\r\n"',
		$rows = $table.find( 'tr' ),
		csv =
			'"' +
			$rows
				.map( function ( i, row ) {
					const $row = jQuery( row ),
						$cols = $row.find( 'td,th' );
					return $cols
						.map( function ( j, col ) {
							const $col = jQuery( col ),
								text = $col.text();
							return text.replace( /"/g, '""' ); // escape double quotes
						} )
						.get()
						.join( tmpColDelim );
				} )
				.get()
				.join( tmpRowDelim )
				.split( tmpRowDelim )
				.join( rowDelim )
				.split( tmpColDelim )
				.join( colDelim ) +
			'"',
		csvData =
			'data:application/csv;charset=utf-8,' + encodeURIComponent( csv );
	jQuery( this ).attr( {
		download: filename,
		href: csvData,
	} );
}

function eefpsstatsColumnChart( id, dataArray ) {
	const dataMap = new Map( dataArray );
	const seriesData = [];
	for ( const [ x, y ] of dataMap.entries() ) {
		seriesData.push( { meta: x, value: y } );
	}
	const data = {
		labels: Array.from( { length: dataArray.length }, ( x, i ) => i + 1 ),
		series: [ seriesData ],
	};

	const options = {
		axisX: {
			showGrid: false,
		},
		axisY: {
			onlyInteger: true,
		},
		chartPadding: {
			top: 20,
			right: 30,
			bottom: 30,
			left: 30,
		},
		height: 300,
		plugins: [
			Chartist.plugins.tooltip( {
				anchorToPoint: true,
				transformTooltipTextFnc( y ) {
					return (
						y +
						' ' +
						( parseInt( y ) === 1
							? eefpsstats_translations.view
							: eefpsstats_translations.views )
					);
				},
				appendToBody: true,
				class: 'eefpsstats-ct-tooltip',
			} ),
		],
		seriesBarDistance: 20,
	};

	const responsiveOptions = [
		[
			'screen and (max-width: 640px)',
			{
				seriesBarDistance: 5,
				axisX: {
					labelInterpolationFnc( value ) {
						return value[ 0 ];
					},
				},
			},
		],
	];

	new Chartist.Bar( id, data, options, responsiveOptions );
}

function eefpsstatsLineChart( id, dataArray, type = 'default' ) {
	const dataMap = new Map( dataArray );
	const seriesData = [];
	for ( const [ x, y ] of dataMap.entries() ) {
		seriesData.push( { meta: x, value: y } );
	}
	const data = {
		labels: Array.from( dataMap.keys() ),
		series: [ seriesData ],
	};

	const options = {
		axisX: {
			showGrid: false,
			labelInterpolationFnc( value ) {
				return type === 'daily'
					? value.substring( 0, 2 ) === '1.'
						? value.substring( 2 )
						: ''
					: value;
			},
		},
		axisY: {
			onlyInteger: true,
		},
		chartPadding: {
			top: 20,
			right: 30,
			bottom: 30,
			left: 30,
		},
		height: 300,
		plugins: [
			Chartist.plugins.tooltip( {
				anchorToPoint: true,
				transformTooltipTextFnc( y ) {
					return (
						y +
						' ' +
						( parseInt( y ) === 1
							? eefpsstats_translations.view
							: eefpsstats_translations.views )
					);
				},
				appendToBody: true,
				class: 'eefpsstats-ct-tooltip',
			} ),
		],
		showArea: true,
		showPoints: true,
	};

	new Chartist.Line( id, data, options );
}

function eefpsstatsSelectDateRange() {
	const t = new Date(),
		y = t.getFullYear(),
		m = t.getMonth(),
		d = t.getDate(),
		day = t.getDay(),
		mondayOfCurrentWeek = d - day + ( day === 0 ? -6 : 1 );
	switch ( jQuery( '#dateRange' ).val() ) {
		case 'default':
			jQuery( '#start' ).val( '' );
			jQuery( '#end' ).val( '' );
			eefpsstatsValidateDateRange();
			break;
		case 'lastYear':
			eefpsstatsSetDateRange(
				new Date( y - 1, 0, 1 ),
				new Date( y - 1, 11, 31 )
			);
			break;
		case 'lastWeek':
			eefpsstatsSetDateRange(
				new Date( y, m, mondayOfCurrentWeek - 7 ),
				new Date( y, m, mondayOfCurrentWeek - 1 )
			);
			break;
		case 'yesterday':
			eefpsstatsSetDateRange(
				new Date( y, m, d - 1 ),
				new Date( y, m, d - 1 )
			);
			break;
		case 'today':
			eefpsstatsSetDateRange( t, t );
			break;
		case 'thisWeek':
			eefpsstatsSetDateRange(
				new Date( y, m, mondayOfCurrentWeek ),
				new Date( y, m, mondayOfCurrentWeek + 6 )
			);
			break;
		case 'last28days':
			eefpsstatsSetDateRange( new Date( y, m, d - 27 ), t );
			break;
		case 'lastMonth':
			eefpsstatsSetDateRange(
				new Date( y, m - 1, 1 ),
				new Date( y, m, 0 )
			);
			break;
		case 'thisMonth':
			eefpsstatsSetDateRange(
				new Date( y, m, 1 ),
				new Date( y, m + 1, 0 )
			);
			break;
		case '1stQuarter':
			eefpsstatsSetDateRange( new Date( y, 0, 1 ), new Date( y, 2, 31 ) );
			break;
		case '2ndQuarter':
			eefpsstatsSetDateRange( new Date( y, 3, 1 ), new Date( y, 5, 30 ) );
			break;
		case '3rdQuarter':
			eefpsstatsSetDateRange( new Date( y, 6, 1 ), new Date( y, 8, 30 ) );
			break;
		case '4thQuarter':
			eefpsstatsSetDateRange(
				new Date( y, 9, 1 ),
				new Date( y, 11, 31 )
			);
			break;
		case 'thisYear':
			eefpsstatsSetDateRange(
				new Date( y, 0, 1 ),
				new Date( y, 11, 31 )
			);
			break;
	}
}

function eefpsstatsSetDateRange( start, end ) {
	jQuery( '#start' ).val( eefpsstatsDateFormat( start ) );
	jQuery( '#end' ).val( eefpsstatsDateFormat( end ) );
	eefpsstatsValidateDateRange();
}

function eefpsstatsDateFormat( date ) {
	const m = date.getMonth() + 1,
		d = date.getDate();
	return (
		date.getFullYear() +
		'-' +
		( m > 9 ? '' : '0' ) +
		m +
		'-' +
		( d > 9 ? '' : '0' ) +
		d
	);
}

function eefpsstatsDateRangeChange() {
	jQuery( '#dateRange' ).val( 'custom' );
	eefpsstatsValidateDateRange();
}

function eefpsstatsValidateDateRange() {
	const start = jQuery( '#start' ),
		end = jQuery( '#end' ),
		correct =
			start[ 0 ].validity.valid &&
			end[ 0 ].validity.valid &&
			( ( start.val() && end.val() ) ||
				( ! start.val() && ! end.val() ) );
	jQuery( 'form button' ).prop( 'disabled', ! correct );
}
