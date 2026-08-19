/**
 * Red Hermanos — embed loader (vanilla JS, no jQuery).
 *
 * Finds [data-rh-embed] containers, builds the /render endpoint URL from their
 * data-* attributes, fetches, and injects resp.html. Errors are swallowed so a
 * failed embed leaves an empty container without breaking the page. data-rh-
 * loaded guards against double-loading.
 */
( function () {
	'use strict';

	var endpoint = ( window.RH_EMBED && window.RH_EMBED.endpoint ) ? window.RH_EMBED.endpoint : '';

	function buildUrl( el ) {
		var url = endpoint;
		if ( ! url ) {
			return '';
		}
		var params = [];
		var map = {
			format: 'format',
			count: 'count',
			thumbs: 'thumbs',
			vertical: 'vertical',
			heading: 'heading'
		};
		Object.keys( map ).forEach( function ( key ) {
			var val = el.getAttribute( 'data-' + key );
			if ( null !== val && '' !== val ) {
				params.push( encodeURIComponent( map[ key ] ) + '=' + encodeURIComponent( val ) );
			}
		} );
		return url + ( url.indexOf( '?' ) === -1 ? '?' : '&' ) + params.join( '&' );
	}

	function loadOne( el ) {
		if ( el.getAttribute( 'data-rh-loaded' ) ) {
			return;
		}
		el.setAttribute( 'data-rh-loaded', '1' );

		var url = buildUrl( el );
		if ( ! url ) {
			return;
		}

		fetch( url, { credentials: 'same-origin' } )
			.then( function ( r ) {
				return r.ok ? r.json() : null;
			} )
			.then( function ( data ) {
				if ( data && 'string' === typeof data.html ) {
					el.innerHTML = data.html;
					// Let rh-images.js re-scan any injected thumbnails.
					document.dispatchEvent( new CustomEvent( 'rh:embed-loaded' ) );
				}
			} )
			.catch( function () {
				// Silent — leave the container empty.
			} );
	}

	function init() {
		var nodes = document.querySelectorAll( '[data-rh-embed]' );
		nodes.forEach( loadOne );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
