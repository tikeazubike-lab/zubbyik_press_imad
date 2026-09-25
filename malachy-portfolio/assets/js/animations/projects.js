/**
 * HO-051 — 3D looping card showcase (#work).
 *
 * Desktop (>=1200px): automated 5s cycle — deck recedes (front → 2nd → 3rd →
 * dark), a new front card slips in; left copy stage plays spotlight title +
 * layered parallax description. Dot timeline jumps to a card and resumes 3s
 * later from that card forward with no skips.
 * Tablet (768–1199px): same composition, swipe-driven (discrete step per
 * swipe, light parallax scrub during drag). No autoplay, no dots.
 * Mobile (<=767px): native scroll-snap track only — no 3D, no text anims.
 *
 * Vanilla JS + WAAPI — GSAP deliberately unused (SplitText licensing; GSAP is
 * not enqueued below the desktop gate). See HO-051.
 */
( function () {
	'use strict';

	var root = document.querySelector( '[data-jc]' );
	if ( ! root ) {
		return;
	}

	var cards = Array.prototype.slice.call( root.querySelectorAll( '[data-jc-card]' ) );
	var total = cards.length;
	if ( ! total ) {
		return;
	}

	var track = root.querySelector( '[data-jc-track]' );
	var stage = root.querySelector( '[data-jc-stage]' );
	var catEl = root.querySelector( '[data-jc-cat]' );
	var jobEl = root.querySelector( '[data-jc-job]' );
	var backEl = root.querySelector( '[data-jc-desc-back]' );
	var midEl = root.querySelector( '[data-jc-desc-mid]' );
	var frontEl = root.querySelector( '[data-jc-desc-front]' );
	var dotsWrap = root.querySelector( '[data-jc-dots]' );
	var dots = dotsWrap ? Array.prototype.slice.call( dotsWrap.querySelectorAll( '[data-jc-dot]' ) ) : [];
	var counterEl = document.querySelector( '[data-jc-counter]' );

	var mqMobile = window.matchMedia( '(max-width: 767px)' );
	var mqTablet = window.matchMedia( '(min-width: 768px) and (max-width: 1199px)' );
	var mqDesktop = window.matchMedia( '(min-width: 1200px)' );
	var mqReduce = window.matchMedia( '(prefers-reduced-motion: reduce)' );

	var active = 0;
	var currentCategory = cards[ 0 ].getAttribute( 'data-category' ) || '';
	var autoplayTimer = null;
	var resumeTimer = null;
	var lastInteraction = 0;
	var busy = false;

	var STEP_MS = 5000;
	var RESUME_MS = 3000;

	/* ---------------------------------------------------------------------
	 * Spotlight reveal — char split + staggered blur/scale/opacity from center.
	 * Equivalent of the GSAP SplitText snippet in the spec without the plugin.
	 * ------------------------------------------------------------------- */

	function splitChars( el ) {
		var text = el.textContent;
		el.textContent = '';
		var frag = document.createDocumentFragment();
		for ( var i = 0; i < text.length; i++ ) {
			var span = document.createElement( 'span' );
			span.className = 'jc-char';
			span.textContent = text.charAt( i ) === ' ' ? '\u00A0' : text.charAt( i );
			frag.appendChild( span );
		}
		el.appendChild( frag );
		return Array.prototype.slice.call( el.querySelectorAll( '.jc-char' ) );
	}

	function spotlight( el ) {
		if ( ! el ) {
			return;
		}
		if ( mqReduce.matches || typeof el.animate !== 'function' ) {
			return;
		}
		var chars = splitChars( el );
		var mid = ( chars.length - 1 ) / 2;
		chars.forEach( function ( char, i ) {
			char.animate(
				[
					{ opacity: 0.1, transform: 'scale(0.8)', filter: 'blur(4px)' },
					{ opacity: 1, transform: 'scale(1)', filter: 'blur(0px)' },
				],
				{
					duration: 400,
					delay: Math.abs( i - mid ) * 60,
					easing: 'cubic-bezier(0.16, 1, 0.3, 1)',
					fill: 'backwards',
				}
			);
		} );
	}

	function dissolve( el ) {
		if ( ! el || mqReduce.matches || typeof el.animate !== 'function' ) {
			return;
		}
		el.animate(
			[
				{ opacity: 1, filter: 'blur(0px)' },
				{ opacity: 0, filter: 'blur(3px)' },
			],
			{ duration: 280, easing: 'ease-in' }
		);
	}

	/* ---------------------------------------------------------------------
	 * Parallax description layers — different rates per layer (the spec's
	 * scroll-scrub parallax adapted to step transitions; tablet drag scrubs
	 * the same offsets via --drag).
	 * ------------------------------------------------------------------- */

	function parallaxIn() {
		if ( mqReduce.matches || typeof midEl.animate !== 'function' ) {
			return;
		}
		[
			[ backEl, -80, 700, 0 ],
			[ midEl, -40, 550, 60 ],
			[ frontEl, -20, 450, 110 ],
		].forEach( function ( layer ) {
			if ( ! layer[ 0 ] ) {
				return;
			}
			layer[ 0 ].animate(
				[
					{ opacity: 0, transform: 'translateY(' + Math.abs( layer[ 1 ] ) / 2 + 'px)' },
					{ opacity: parseFloat( getComputedStyle( layer[ 0 ] ).opacity ) || 1, transform: 'translateY(0px)' },
				],
				{
					duration: layer[ 2 ],
					delay: layer[ 3 ],
					easing: 'cubic-bezier(0.16, 1, 0.3, 1)',
					fill: 'backwards',
				}
			);
		} );
	}

	/* ---------------------------------------------------------------------
	 * Deck slots. Slot k (0 = front) holds index (active - k) mod n: the two
	 * most recent jobs peek 20%-apart to the left, oldest recedes to dark.
	 * Advance: 3rd fades into dark, 2nd → 3rd, front → 2nd, new front slips in.
	 * ------------------------------------------------------------------- */

	function slotFor( index ) {
		var rel = ( active - index + total ) % total;
		return rel < 3 ? rel : 'out';
	}

	var SLOT_STYLES = {
		0: { x: 0, y: 0, z: 0, rot: 0, scale: 1, opacity: 1, bright: 1, zIdx: 3 },
		1: { x: -20, y: 2, z: -90, rot: 7, scale: 0.94, opacity: 0.78, bright: 0.8, zIdx: 2 },
		2: { x: -40, y: 4, z: -180, rot: 11, scale: 0.88, opacity: 0.42, bright: 0.55, zIdx: 1 },
		out: { x: -42, y: 5, z: -240, rot: 12, scale: 0.86, opacity: 0, bright: 0.4, zIdx: 0 },
	};

	function slotTransform( style ) {
		return (
			'translate3d(' + style.x + '%, ' + style.y + '%, ' + style.z + 'px) ' +
			'rotateY(' + style.rot + 'deg) scale(' + style.scale + ')'
		);
	}

	function applySlot( card, slot, animate, entering ) {
		var style = SLOT_STYLES[ slot ];
		card.style.zIndex = String( style.zIdx );
		card.setAttribute( 'data-slot', String( slot ) );

		if ( ! animate || mqReduce.matches || typeof card.animate !== 'function' ) {
			card.style.transform = slotTransform( style );
			card.style.opacity = String( style.opacity );
			card.style.filter = 'brightness(' + style.bright + ')';
			return;
		}

		var to = {
			transform: slotTransform( style ),
			opacity: style.opacity,
			filter: 'brightness(' + style.bright + ')',
		};

		if ( entering ) {
			// New front card: appears and slips into the start position.
			card.animate(
				[
					{ transform: 'translate3d(55%, 3%, 40px) rotateY(-6deg) scale(0.96)', opacity: 0, filter: 'brightness(1)' },
					to,
				],
				{ duration: 1000, easing: 'cubic-bezier(0.16, 1, 0.3, 1)', fill: 'both' }
			);
		} else if ( slot === 'out' ) {
			// Deepest card disappears into the dark background.
			card.animate( [ { opacity: card.style.opacity || 1 }, to ], {
				duration: 850,
				easing: 'ease-in',
				fill: 'both',
			} );
		} else {
			// Implicit from-keyframe = current computed position (slot shift).
			card.animate( [ to ], { duration: 950, easing: 'cubic-bezier(0.16, 1, 0.3, 1)', fill: 'both' } );
		}

		// Keep terminal styles authoritative after animations land.
		card.style.transform = to.transform;
		card.style.opacity = String( to.opacity );
		card.style.filter = to.filter;
	}

	function layout( animate ) {
		cards.forEach( function ( card ) {
			var slot = slotFor( Number( card.getAttribute( 'data-index' ) ) );
			var wasOut = card.getAttribute( 'data-slot' ) === 'out';
			applySlot( card, slot, animate, animate && wasOut && slot === 0 );
		} );
	}

	/* ---------------------------------------------------------------------
	 * Left copy stage
	 * ------------------------------------------------------------------- */

	function updateCopy( animate ) {
		var card = cards[ active ];
		var category = card.getAttribute( 'data-category' ) || '';
		var job = card.getAttribute( 'data-job' ) || '';
		var desc = card.getAttribute( 'data-desc' ) || '';
		var tldr = card.getAttribute( 'data-tldr' ) || '';

		var categoryChanged = category !== currentCategory;

		if ( animate && categoryChanged ) {
			dissolve( catEl );
			window.setTimeout( function () {
				// Cancel the dissolve so its final state can't hold opacity at 0.
				if ( typeof catEl.getAnimations === 'function' ) {
					catEl.getAnimations().forEach( function ( a ) {
						a.cancel();
					} );
				}
				catEl.textContent = category;
				spotlight( catEl );
			}, 260 );
		} else {
			catEl.textContent = category;
			if ( animate ) {
				spotlight( catEl );
			}
		}
		currentCategory = category;

		jobEl.textContent = job;
		if ( animate ) {
			spotlight( jobEl );
		}

		backEl.textContent = category;
		midEl.textContent = desc;
		frontEl.textContent = tldr;
		if ( animate ) {
			parallaxIn();
		}
	}

	function updateChrome() {
		dots.forEach( function ( dot, i ) {
			dot.setAttribute( 'aria-current', i === active ? 'true' : 'false' );
		} );
		if ( counterEl ) {
			counterEl.textContent =
				String( active + 1 ).padStart( 2, '0' ) + ' / ' + String( total ).padStart( 2, '0' );
		}
	}

	/* ---------------------------------------------------------------------
	 * Navigation
	 * ------------------------------------------------------------------- */

	function goTo( index, animate ) {
		var next = ( ( index % total ) + total ) % total;
		if ( next === active && animate ) {
			return;
		}
		if ( busy ) {
			return;
		}
		busy = true;
		window.setTimeout( function () {
			busy = false;
		}, 650 );

		active = next;
		layout( animate !== false );
		updateCopy( animate !== false );
		updateChrome();
	}

	function next() {
		goTo( active + 1 );
	}

	function prev() {
		goTo( active - 1 );
	}

	/* ---------------------------------------------------------------------
	 * Desktop autoplay — 5s cycle, pauses on hover/focus, dot jumps pause
	 * and resume 3s after the last interaction, continuing from the clicked
	 * card forward with no skips.
	 * ------------------------------------------------------------------- */

	function stopAutoplay() {
		if ( autoplayTimer ) {
			window.clearInterval( autoplayTimer );
			autoplayTimer = null;
		}
		if ( resumeTimer ) {
			window.clearTimeout( resumeTimer );
			resumeTimer = null;
		}
	}

	function startAutoplay() {
		stopAutoplay();
		if ( ! mqDesktop.matches || mqReduce.matches ) {
			return;
		}
		autoplayTimer = window.setInterval( next, STEP_MS );
	}

	function pauseForInteraction() {
		lastInteraction = Date.now();
		stopAutoplay();
		if ( ! mqDesktop.matches || mqReduce.matches ) {
			return;
		}
		resumeTimer = window.setTimeout( function start() {
			startAutoplay();
		}, RESUME_MS );
	}

	if ( dots.length ) {
		dots.forEach( function ( dot, i ) {
			dot.addEventListener( 'click', function () {
				// Mobile uses the native scroll-snap track — page it to the
				// card instead of running the deck layout (HO-053).
				if ( mqMobile.matches ) {
					if ( track ) {
						track.scrollTo( {
							left: i * ( track.clientWidth || 1 ),
							behavior: 'smooth',
						} );
					}
					return;
				}
				pauseForInteraction();
				goTo( i );
			} );
		} );
	}

	root.addEventListener( 'mouseenter', function () {
		if ( autoplayTimer ) {
			stopAutoplay();
			root.setAttribute( 'data-paused', 'hover' );
		}
	} );
	root.addEventListener( 'mouseleave', function () {
		if ( root.getAttribute( 'data-paused' ) === 'hover' ) {
			root.removeAttribute( 'data-paused' );
			startAutoplay();
		}
	} );
	root.addEventListener( 'focusin', function () {
		stopAutoplay();
	} );
	root.addEventListener( 'focusout', function ( e ) {
		if ( ! root.contains( e.relatedTarget ) ) {
			pauseForInteraction();
		}
	} );

	/* ---------------------------------------------------------------------
	 * Tablet swipe — discrete step per swipe (left = next, right = prev) with
	 * a light parallax scrub on the copy layers during drag. Mobile uses the
	 * native scroll-snap track instead.
	 * ------------------------------------------------------------------- */

	var dragStartX = null;
	var dragActive = false;

	function dragParallax( dx ) {
		var clamped = Math.max( -1, Math.min( 1, dx / 160 ) );
		root.style.setProperty( '--drag', clamped.toFixed( 3 ) );
	}

	if ( stage ) {
		stage.addEventListener( 'pointerdown', function ( e ) {
			if ( ! mqTablet.matches || mqMobile.matches ) {
				return;
			}
			dragStartX = e.clientX;
			dragActive = true;
			stage.setPointerCapture( e.pointerId );
		} );
		stage.addEventListener( 'pointermove', function ( e ) {
			if ( ! dragActive || dragStartX === null ) {
				return;
			}
			dragParallax( e.clientX - dragStartX );
		} );
		stage.addEventListener( 'pointerup', function ( e ) {
			if ( ! dragActive || dragStartX === null ) {
				return;
			}
			var dx = e.clientX - dragStartX;
			dragActive = false;
			dragStartX = null;
			root.style.setProperty( '--drag', '0' );
			if ( Math.abs( dx ) > 48 ) {
				if ( dx < 0 ) {
					next();
				} else {
					prev();
				}
			}
		} );
		stage.addEventListener( 'pointercancel', function () {
			dragActive = false;
			dragStartX = null;
			root.style.setProperty( '--drag', '0' );
		} );
	}

	/* ---------------------------------------------------------------------
	 * Mobile — native scroll-snap; chrome follows scroll position.
	 * ------------------------------------------------------------------- */

	var scrollRaf = null;
	function syncFromScroll() {
		if ( ! track ) {
			return;
		}
		var width = track.clientWidth || 1;
		var index = Math.round( track.scrollLeft / width );
		index = Math.max( 0, Math.min( total - 1, index ) );
		if ( index !== active ) {
			active = index;
			updateChrome();
		}
	}

	if ( track ) {
		track.addEventListener( 'scroll', function () {
			if ( ! mqMobile.matches ) {
				return;
			}
			if ( scrollRaf ) {
				window.cancelAnimationFrame( scrollRaf );
			}
			scrollRaf = window.requestAnimationFrame( syncFromScroll );
		}, { passive: true } );
	}

	/* ---------------------------------------------------------------------
	 * Keyboard (desktop/tablet)
	 * ------------------------------------------------------------------- */

	root.addEventListener( 'keydown', function ( e ) {
		if ( mqMobile.matches ) {
			return;
		}
		if ( e.key === 'ArrowRight' || e.key === 'ArrowDown' ) {
			e.preventDefault();
			pauseForInteraction();
			next();
		} else if ( e.key === 'ArrowLeft' || e.key === 'ArrowUp' ) {
			e.preventDefault();
			pauseForInteraction();
			prev();
		}
	} );

	/* ---------------------------------------------------------------------
	 * Mode switching
	 * ------------------------------------------------------------------- */

	function applyMode() {
		stopAutoplay();
		root.setAttribute( 'data-mode', mqMobile.matches ? 'mobile' : ( mqTablet.matches ? 'tablet' : 'desktop' ) );
		if ( mqMobile.matches ) {
			// Native snap track: reset any deck transforms from other modes.
			cards.forEach( function ( card ) {
				card.style.transform = '';
				card.style.opacity = '';
				card.style.filter = '';
				card.style.zIndex = '';
				card.removeAttribute( 'data-slot' );
			} );
			syncFromScroll();
			return;
		}
		layout( false );
		updateCopy( false );
		updateChrome();
		startAutoplay();
	}

	function onModeChange() {
		applyMode();
	}

	[ mqMobile, mqDesktop, mqTablet ].forEach( function ( mq ) {
		if ( typeof mq.addEventListener === 'function' ) {
			mq.addEventListener( 'change', onModeChange );
		} else if ( typeof mq.addListener === 'function' ) {
			mq.addListener( onModeChange );
		}
	} );

	mqReduce.addEventListener && mqReduce.addEventListener( 'change', onModeChange );

	// Initial paint.
	applyMode();
} )();
