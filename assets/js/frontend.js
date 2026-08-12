( function () {
	'use strict';
	document.addEventListener( 'DOMContentLoaded', function () {
		if ( 'undefined' === typeof visiseFrontend ) {
			return;
		}
		if ( visiseFrontend.fpEnabled ) {
			try {
				var fpParts = [
					navigator.userAgent || '',
					navigator.language || '',
					String( screen.width ) + 'x' + String( screen.height ) + 'x' + String( screen.colorDepth ),
					String( new Date().getTimezoneOffset() ),
					String( navigator.hardwareConcurrency || '' ),
					String( navigator.maxTouchPoints || '' )
				];
				try {
					var canvas = document.createElement( 'canvas' );
					var ctx    = canvas.getContext( '2d' );
					if ( ctx ) {
						ctx.textBaseline = 'top';
						ctx.font         = '14px Arial';
						ctx.fillText( 'visise-fp', 2, 2 );
						fpParts.push( canvas.toDataURL() );
					}
				} catch ( e ) {}
				var raw   = fpParts.join( '||' ) + '|' + ( visiseFrontend.nonce || '' );
				var hash1 = 0, hash2 = 0;
				for ( var i = 0; i < raw.length; i++ ) {
					var code = raw.charCodeAt( i );
					hash1 = ( ( hash1 << 5 ) - hash1 + code ) | 0;
					hash2 = ( ( hash2 << 7 ) - hash2 + code * 31 ) | 0;
				}
				var fingerprint = ( hash1 >>> 0 ).toString( 16 ) + ( hash2 >>> 0 ).toString( 16 );
				var expires = new Date();
				expires.setTime( expires.getTime() + 10 * 365 * 24 * 60 * 60 * 1000 );
				document.cookie = 'visise_fp=' + fingerprint + '; expires=' + expires.toUTCString() + '; path=/; SameSite=Lax' + ( 'https:' === window.location.protocol ? '; Secure' : '' );
			} catch ( e ) {}
		}

		if ( visiseFrontend.trackAction && visiseFrontend.trackNonce ) {
			var trackPage = function () {
				var formData = new FormData();
				formData.append( 'action', visiseFrontend.trackAction );
				formData.append( 'nonce', visiseFrontend.trackNonce );
				formData.append( 'path', window.location.pathname + window.location.search );
				fetch( visiseFrontend.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: formData } ).catch( function () {} );
			};
			trackPage();
			window.setInterval( trackPage, visiseFrontend.intervalMs || 20000 );
		}

		if ( visiseFrontend.offlineAction && visiseFrontend.offlineNonce && navigator.sendBeacon ) {
			window.addEventListener( 'pagehide', function () {
				var formData = new FormData();
				formData.append( 'action', visiseFrontend.offlineAction );
				formData.append( 'nonce', visiseFrontend.offlineNonce );
				navigator.sendBeacon( visiseFrontend.ajaxUrl, formData );
			} );
		}

		if ( ! visiseFrontend.showBadge ) {
			return;
		}
		var badge     = document.querySelector( '.pv-visitor-badge' );
		var onlineEl  = document.querySelector( '[data-pv-online-value]' );
		var tooltipEl = document.querySelector( '[data-pv-tooltip-text]' );
		if ( ! badge || ! onlineEl ) {
			return;
		}
		badge.addEventListener( 'click', function () {
			badge.classList.toggle( 'pv-visitor-badge--open' );
		} );
		badge.addEventListener( 'focus', function () {
			badge.classList.add( 'pv-visitor-badge--open' );
		} );
		badge.addEventListener( 'blur', function () {
			badge.classList.remove( 'pv-visitor-badge--open' );
		} );

		function refreshStats() {
			var formData = new FormData();
			formData.append( 'action', visiseFrontend.action );
			formData.append( 'nonce', visiseFrontend.nonce );
			fetch( visiseFrontend.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: formData } )
				.then( function ( response ) { return response.json(); } )
				.then( function ( json ) {
					if ( ! json || ! json.success || ! json.data ) {
						return;
					}
					onlineEl.textContent = json.data.online;
					if ( tooltipEl ) {
						tooltipEl.textContent = visiseFrontend.todayText
							.replace( '%s', json.data.today )
							.replace( '%s', json.data.week );
					}
					badge.classList.add( 'pv-visitor-badge--pulse' );
					window.setTimeout( function () {
						badge.classList.remove( 'pv-visitor-badge--pulse' );
					}, 600 );
				} )
				.catch( function () {} );
		}
		window.setInterval( refreshStats, visiseFrontend.intervalMs || 20000 );
	} );
} )();
