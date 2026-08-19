/**
 * Red Hermanos — ticker behavior (vanilla JS, no jQuery).
 * Functional-but-minimal for v1.0: pause horizontal auto-advance on hover.
 * // TODO v1.1: seamless infinite loop with duplicated track.
 */
( function () {
	'use strict';

	function initTicker( root ) {
		var track = root.querySelector( '.rh-ticker__track' );
		if ( ! track ) {
			return;
		}

		var paused = false;
		var speed = 0.5; // px per frame.

		root.addEventListener( 'mouseenter', function () {
			paused = true;
		} );
		root.addEventListener( 'mouseleave', function () {
			paused = false;
		} );

		function step() {
			if ( ! paused && track.scrollWidth > track.clientWidth ) {
				track.scrollLeft += speed;
				// Loop back to start.
				if ( track.scrollLeft + track.clientWidth >= track.scrollWidth - 1 ) {
					track.scrollLeft = 0;
				}
			}
			window.requestAnimationFrame( step );
		}

		window.requestAnimationFrame( step );
	}

	function init() {
		document.querySelectorAll( '[data-rh-ticker]' ).forEach( initTicker );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}

	document.addEventListener( 'rh:embed-loaded', init );
}() );
