( function() {
	var psstatsReq;
	try {
		psstatsReq = new XMLHttpRequest();
		psstatsReq.open( 'POST', psstats_ajax.url, true );
		psstatsReq.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded;' );
		psstatsReq.send(
			'_ajax_nonce=' + psstats_ajax.nonce +
			'&action=psstats_track' +
			'&psstats_referrer=' + encodeURIComponent( document.referrer ) +
			'&psstats_target=' + encodeURIComponent( location.pathname + location.search )
		);
	} catch ( e ) {
	}
}() );
