( function () {
	'use strict';
	document.addEventListener( 'DOMContentLoaded', function () {
		if ( ! window.visiseAdmin || ! visiseAdmin.sseUrl ) {
			return;
		}
		var tbody    = document.getElementById( 'visise-visitors-tbody' );
		var tableWrap= document.getElementById( 'visise-visitors-table-wrap' );
		var emptyNotice = document.getElementById( 'visise-visitors-empty' );
		var onlineEl = document.getElementById( 'pv-online-now-value' );

		function connect() {
			var es = new EventSource( visiseAdmin.sseUrl );
			es.onmessage = function ( e ) {
				var d;
				try { d = JSON.parse( e.data ); } catch ( err ) { return; }
				if ( onlineEl && d.onlineText ) {
					onlineEl.textContent = d.onlineText;
				}
				if ( tbody && d.visitorsHtml !== undefined ) {
					tbody.innerHTML = d.visitorsHtml;
				}
				if ( tableWrap ) {
					tableWrap.style.display = d.visitorsEmpty ? 'none' : '';
				}
				if ( emptyNotice ) {
					emptyNotice.style.display = d.visitorsEmpty ? '' : 'none';
				}
			};
			es.onerror = function () {
				es.close();
				setTimeout( connect, 5000 );
			};
		}
		connect();
	} );
} )();
