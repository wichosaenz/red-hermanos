/**
 * Red Hermanos — runtime broken-image handling (vanilla JS, no jQuery).
 *
 * For every <img data-rh-fallback> inside .rh-related-posts, react to onerror
 * per data-rh-mode so a broken/incorrect thumbnail never shows:
 *   - fallback   : swap src to the fallback once (guarded); if that also fails,
 *                  degrade to hide_image.
 *   - hide_image : hide the image, mark the card as text-only (.rh-card--noimg).
 *   - hide_card  : hide the whole card; if the section empties, hide it too.
 */
( function () {
	'use strict';

	function hideImage( img ) {
		var thumb = img.closest( '.rh-card__thumb' ) || img;
		if ( thumb ) {
			thumb.style.display = 'none';
		}
		var card = img.closest( '.rh-card' );
		if ( card ) {
			card.classList.add( 'rh-card--noimg' );
		}
	}

	function hideCard( img ) {
		var card = img.closest( '.rh-card' );
		if ( ! card ) {
			hideImage( img );
			return;
		}
		card.style.display = 'none';

		var section = card.closest( '.rh-related-posts' );
		if ( section ) {
			var visible = section.querySelectorAll( '.rh-card' );
			var anyVisible = false;
			visible.forEach( function ( c ) {
				if ( 'none' !== c.style.display ) {
					anyVisible = true;
				}
			} );
			if ( ! anyVisible ) {
				section.style.display = 'none';
			}
		}
	}

	function onError( ev ) {
		var img = ev.target;
		if ( ! img || 'IMG' !== img.tagName ) {
			return;
		}

		var mode = img.getAttribute( 'data-rh-mode' ) || 'fallback';
		var fallback = img.getAttribute( 'data-rh-fallback' ) || '';

		if ( 'fallback' === mode ) {
			// Try the fallback exactly once.
			if ( fallback && ! img.getAttribute( 'data-rh-tried' ) ) {
				img.setAttribute( 'data-rh-tried', '1' );
				img.src = fallback;
				return;
			}
			// Fallback missing or already failed -> degrade to hide_image.
			hideImage( img );
			return;
		}

		if ( 'hide_card' === mode ) {
			hideCard( img );
			return;
		}

		// Default / hide_image.
		hideImage( img );
	}

	function init() {
		var imgs = document.querySelectorAll( '.rh-related-posts img[data-rh-fallback], .rh-related-posts img[data-rh-mode]' );
		imgs.forEach( function ( img ) {
			img.addEventListener( 'error', onError );
			// Catch images that failed before the listener attached.
			if ( img.complete && img.naturalWidth === 0 ) {
				onError( { target: img } );
			}
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}

	// Re-scan when embeds inject content.
	document.addEventListener( 'rh:embed-loaded', init );
}() );
