/**
 * Red Hermanos — carousel controls (vanilla JS, no jQuery).
 * Functional-but-minimal for v1.0. // TODO v1.1: dots, autoplay, swipe.
 */
( function () {
	'use strict';

	function initCarousel( root ) {
		var track = root.querySelector( '.rh-carousel__track' );
		var prev = root.querySelector( '.rh-carousel__nav--prev' );
		var next = root.querySelector( '.rh-carousel__nav--next' );
		if ( ! track ) {
			return;
		}

		var index = 0;

		function slideCount() {
			return track.querySelectorAll( '.rh-carousel__slide' ).length;
		}

		function perView() {
			var w = window.innerWidth;
			if ( w <= 480 ) {
				return 1;
			}
			if ( w <= 768 ) {
				return 2;
			}
			return 3;
		}

		function maxIndex() {
			return Math.max( 0, slideCount() - perView() );
		}

		function update() {
			if ( index > maxIndex() ) {
				index = maxIndex();
			}
			if ( index < 0 ) {
				index = 0;
			}
			var slide = track.querySelector( '.rh-carousel__slide' );
			var step = slide ? slide.getBoundingClientRect().width + 20 : 0;
			track.style.transform = 'translateX(' + ( -index * step ) + 'px)';
		}

		if ( prev ) {
			prev.addEventListener( 'click', function () {
				index--;
				update();
			} );
		}
		if ( next ) {
			next.addEventListener( 'click', function () {
				index++;
				update();
			} );
		}
		window.addEventListener( 'resize', update );
		update();
	}

	function init() {
		document.querySelectorAll( '[data-rh-carousel]' ).forEach( initCarousel );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}

	document.addEventListener( 'rh:embed-loaded', init );
}() );
