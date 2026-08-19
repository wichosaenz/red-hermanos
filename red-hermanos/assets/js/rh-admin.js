/**
 * Red Hermanos — admin GUI (vanilla JS, no jQuery except wp-color-picker init).
 *
 * Responsibilities: tabs are server-rendered (?tab=), so JS handles: color
 * picker init, media (fallback image) picker, live preview via /render, copy
 * snippet buttons, /status test and cache purge. No localStorage — state lives
 * in the DOM.
 */
( function () {
	'use strict';

	var CFG = window.RH_ADMIN || {};
	var I18N = CFG.i18n || {};

	/* --- Color picker (wp-color-picker uses jQuery under the hood). --- */
	function initColorPicker() {
		if ( window.jQuery && window.jQuery.fn && window.jQuery.fn.wpColorPicker ) {
			window.jQuery( '.rh-color-picker' ).wpColorPicker();
		}
	}

	/* --- Media (fallback image) picker. --- */
	function initMediaPicker() {
		document.querySelectorAll( '.rh-media-pick' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				if ( ! window.wp || ! window.wp.media ) {
					return;
				}
				var frame = window.wp.media( {
					title: 'Select image',
					button: { text: 'Use image' },
					multiple: false
				} );
				frame.on( 'select', function () {
					var att = frame.state().get( 'selection' ).first().toJSON();
					var input = btn.parentNode.querySelector( '.rh-media-url' );
					if ( input && att && att.url ) {
						input.value = att.url;
					}
				} );
				frame.open();
			} );
		} );
	}

	/* --- Live preview via /render. --- */
	function renderUrl( params ) {
		var base = CFG.renderEndpoint || '';
		if ( ! base ) {
			return '';
		}
		var q = [];
		Object.keys( params ).forEach( function ( k ) {
			if ( null !== params[ k ] && '' !== params[ k ] ) {
				q.push( encodeURIComponent( k ) + '=' + encodeURIComponent( params[ k ] ) );
			}
		} );
		return base + ( base.indexOf( '?' ) === -1 ? '?' : '&' ) + q.join( '&' );
	}

	function loadPreview( target, params ) {
		if ( ! target ) {
			return;
		}
		target.innerHTML = '<em>' + ( I18N.loading || 'Loading…' ) + '</em>';
		var url = renderUrl( params );
		if ( ! url ) {
			return;
		}
		fetch( url, { credentials: 'same-origin' } )
			.then( function ( r ) {
				return r.ok ? r.json() : null;
			} )
			.then( function ( data ) {
				if ( data && 'string' === typeof data.html && data.html.length ) {
					target.innerHTML = data.html;
				} else {
					target.innerHTML = '<em>' + ( I18N.noData || 'No data.' ) + '</em>';
				}
			} )
			.catch( function () {
				target.innerHTML = '<em>' + ( I18N.noData || 'No data.' ) + '</em>';
			} );
	}

	function initPreviewButtons() {
		document.querySelectorAll( '.rh-preview-btn' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				var target = document.querySelector( btn.getAttribute( 'data-target' ) );
				loadPreview( target, {
					format: btn.getAttribute( 'data-format' ) || 'cards_grid',
					count: btn.getAttribute( 'data-count' ) || '3',
					thumbs: btn.getAttribute( 'data-thumbs' ),
					vertical: btn.getAttribute( 'data-vertical' )
				} );
			} );
		} );

		// Tools tab: render all format tiles at once.
		document.querySelectorAll( '.rh-preview-all' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				document.querySelectorAll( '.rh-format-gallery .rh-preview-box' ).forEach( function ( box ) {
					loadPreview( box, {
						format: box.getAttribute( 'data-format' ) || 'cards_grid',
						count: box.getAttribute( 'data-count' ) || '6'
					} );
				} );
			} );
		} );
	}

	/* --- Copy snippet buttons. --- */
	function copyText( text ) {
		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			return navigator.clipboard.writeText( text );
		}
		// Fallback.
		var ta = document.createElement( 'textarea' );
		ta.value = text;
		ta.style.position = 'fixed';
		ta.style.opacity = '0';
		document.body.appendChild( ta );
		ta.select();
		try {
			document.execCommand( 'copy' );
		} catch ( e ) {}
		document.body.removeChild( ta );
		return Promise.resolve();
	}

	function initCopyButtons() {
		document.querySelectorAll( '.rh-copy-btn' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				copyText( btn.getAttribute( 'data-copy' ) || '' ).then( function () {
					var original = btn.textContent;
					btn.textContent = I18N.copied || 'Copied!';
					btn.classList.add( 'rh-copied' );
					setTimeout( function () {
						btn.textContent = original;
						btn.classList.remove( 'rh-copied' );
					}, 1500 );
				} );
			} );
		} );
	}

	/* --- Status test + cache purge. --- */
	function initStatusTools() {
		var out = document.getElementById( 'rh-status-out' );

		document.querySelectorAll( '.rh-status-btn' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				if ( ! out || ! CFG.statusEndpoint ) {
					return;
				}
				out.textContent = I18N.loading || 'Loading…';
				fetch( CFG.statusEndpoint, {
					credentials: 'same-origin',
					headers: { 'X-WP-Nonce': CFG.restNonce || '' }
				} )
					.then( function ( r ) {
						return r.json();
					} )
					.then( function ( data ) {
						out.textContent = JSON.stringify( data, null, 2 );
					} )
					.catch( function ( err ) {
						out.textContent = String( err );
					} );
			} );
		} );

		document.querySelectorAll( '.rh-purge-btn' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				if ( ! out || ! CFG.ajaxUrl ) {
					return;
				}
				var body = new URLSearchParams();
				body.append( 'action', 'rh_purge_cache' );
				body.append( 'nonce', CFG.purgeNonce || '' );
				fetch( CFG.ajaxUrl, {
					method: 'POST',
					credentials: 'same-origin',
					body: body
				} )
					.then( function ( r ) {
						return r.json();
					} )
					.then( function ( data ) {
						out.textContent = ( data && data.data && data.data.message ) ? data.data.message : ( I18N.purgeFail || 'Done.' );
					} )
					.catch( function () {
						out.textContent = I18N.purgeFail || 'Purge failed.';
					} );
			} );
		} );
	}

	function init() {
		initColorPicker();
		initMediaPicker();
		initPreviewButtons();
		initCopyButtons();
		initStatusTools();
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
