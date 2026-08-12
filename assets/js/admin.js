( function () {
	'use strict';
	document.addEventListener( 'DOMContentLoaded', function () {
		var unbanForms = document.querySelectorAll( '.pv-js-confirm-unban' );
		unbanForms.forEach( function ( button ) {
			button.addEventListener( 'click', function ( event ) {
				var message = ( window.visiseAdmin && visiseAdmin.confirmUnban ) ? visiseAdmin.confirmUnban : 'Are you sure?';
				if ( ! window.confirm( message ) ) {
					event.preventDefault();
				}
			} );
		} );

		var typeSelect = document.querySelector( '.pv-ban-type-select' );
		if ( typeSelect ) {
			var toggleTempFields = function () {
				var tempFields = document.querySelectorAll( '.pv-temp-only' );
				var isPermanent = 'permanent' === typeSelect.value;
				tempFields.forEach( function ( field ) {
					field.style.display = isPermanent ? 'none' : '';
				} );
			};
			typeSelect.addEventListener( 'change', toggleTempFields );
			toggleTempFields();
		}

		var searchInput = document.getElementById( 'pv-ban-search' );
		var banTable = document.getElementById( 'pv-ban-table' );
		if ( searchInput && banTable ) {
			searchInput.addEventListener( 'input', function () {
				var term = searchInput.value.toLowerCase();
				var rows = banTable.querySelectorAll( 'tbody tr' );
				rows.forEach( function ( row ) {
					var text = row.textContent.toLowerCase();
					row.style.display = text.indexOf( term ) === -1 ? 'none' : '';
				} );
			} );
		}

		var selectAll = document.getElementById( 'pv-select-all-bans' );
		if ( selectAll ) {
			selectAll.addEventListener( 'change', function () {
				var boxes = document.querySelectorAll( 'input[name="ips[]"]' );
				boxes.forEach( function ( box ) {
					box.checked = selectAll.checked;
				} );
			} );
		}

		var onlineValueEl = document.getElementById( 'pv-online-now-value' );
		if ( onlineValueEl && window.visiseAdmin && visiseAdmin.ajaxUrl ) {
			var refreshOnlineCount = function () {
				var formData = new FormData();
				formData.append( 'action', visiseAdmin.onlineAction );
				formData.append( 'nonce', visiseAdmin.onlineNonce );
				fetch( visiseAdmin.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: formData } )
					.then( function ( response ) { return response.json(); } )
					.then( function ( json ) {
						if ( json && json.success && json.data ) {
							onlineValueEl.textContent = json.data.online;
						}
					} )
					.catch( function () {} );
			};
			window.setInterval( refreshOnlineCount, visiseAdmin.onlineInterval || 15000 );
		}
	} );
} )();
