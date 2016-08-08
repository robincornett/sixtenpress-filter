/**
 * Set up the isotope script and filters.
 * @copyright 2016 Robin Cornett
 */
(function ( document, $, undefined ) {
	'use strict';
	var SixTenFilter  = {};
	var filters = {};

	SixTenFilter.init = function () {

		if ( SixTenFilter.params.infinite ) {
			$( '.archive-pagination' ).css( 'display', 'none' );
			$( window ).on( 'resize.stp', _doInfiniteScroll ).triggerHandler( 'resize.stp' );
		}

		/**
		 * Filter using an unordered list.
		 */
		$( '.filter button' ).on( 'click.stp', function () {
			_doFilter( $( this ) );
		} );

		/**
		 * Filter using dropdown(s).
		 */
		$( '.filter' ).on( 'change.stpselect', function() {
			_doSelect( $( this ) );
		} );
	};

	/**
	 * Do infinite scroll
	 * @private
	 */
	function _doInfiniteScroll() {
		var _container   = $( '.content' ),
		    _navSelector = '.archive-pagination';
		_container.infinitescroll( {
				navSelector: _navSelector,
				nextSelector: _navSelector + ' .pagination-next a',
				itemSelector: SixTenFilter.params.selector,
				loading: {
					finishedMsg: SixTenFilter.params.finished,
					img: SixTenFilter.params.loading,
					speed: 'fast'
				}
			},
			function ( newItems ) {
				var _newItems = $( newItems ).css( { opacity: 0 } );
				_container.append( _newItems );
				_newItems.animate( { opacity: 1 } );
			}
		);
	}

	/**
	 * Filter using an unordered list (buttons)
	 * @param $select
	 * @returns {boolean}
	 * @private
	 */
	function _doFilter( $select ) {
		var selector = $select.attr( 'data-filter' );
		$( SixTenFilter.params.selector ).fadeOut().filter( selector ).fadeIn();
	}

	/**
	 * Filter using a dropdown/select
	 * @param $select
	 * @returns {boolean}
	 * @private
	 */
	function _doSelect( $select ) {
		var group        = $select.attr( 'data-filter-group' );
		filters[ group ] = $select.find( ':selected' ).attr( 'data-filter-value' );

		var _selector = _combineFilters( filters );
		$( SixTenFilter.params.selector ).fadeOut().filter( _selector ).fadeIn();

		return false;
	}

	/**
	 * Combine two select filters
	 * @param filters
	 * @returns {string}
	 * @private
	 */
	function _combineFilters( filters ) {
		var _selector = [];
		for ( var prop in filters ) {
			_selector.push( filters[ prop ] );
		}
		return _selector.join( '' );
	}

	$( document ).ready( function () {
		SixTenFilter.params = typeof SixTenPressFilter === 'undefined' ? '' : SixTenPressFilter;

		if ( typeof SixTenFilter.params !== 'undefined' ) {
			SixTenFilter.init();
		}
	} );
})( document, jQuery );
