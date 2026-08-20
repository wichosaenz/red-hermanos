/**
 * Red Hermanos — card click enhancement (vanilla JS, no jQuery).
 *
 * R-LINK-7: the whole card is made clickable here in JS, using the card's
 * data-rh-url. Crawlers only ever see the single <a> around the title in the
 * HTML — this is a pure UX affordance, same-tab navigation (never _blank).
 *
 * Clicks that land on the real <a> (or on a text selection) are left alone so
 * normal link behavior and copy/paste keep working.
 */
( function () {
	'use strict';

	function onClick( ev ) {
		var card = ev.target.closest ? ev.target.closest( '.rh-card' ) : null;
		if ( ! card ) {
			return;
		}

		// Let genuine clicks on the anchor behave normally.
		if ( ev.target.closest( 'a' ) ) {
			return;
		}

		// Ignore if the user is selecting text.
		var sel = window.getSelection && window.getSelection();
		if ( sel && sel.toString().length > 0 ) {
			return;
		}

		var url = card.getAttribute( 'data-rh-url' );
		if ( url ) {
			window.location.href = url; // Same tab — never target="_blank".
		}
	}

	function init() {
		var containers = document.querySelectorAll( '.rh-related-posts' );
		containers.forEach( function ( c ) {
			c.addEventListener( 'click', onClick );
			// Cursor affordance.
			c.querySelectorAll( '.rh-card[data-rh-url]' ).forEach( function ( card ) {
				card.style.cursor = 'pointer';
			} );
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}

	document.addEventListener( 'rh:embed-loaded', init );
}() );
