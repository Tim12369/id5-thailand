/**
 * Editor behaviour for ID5 CMS fields: Media Library pickers and repeaters.
 *
 * Deliberately plain jQuery (already loaded by WordPress) plus jQuery UI
 * sortable (also core) so the plugin ships no bundled third-party code.
 */
( function ( $ ) {
	'use strict';

	// ─── Media Library picker ───────────────────────────────────────────────

	$( document ).on( 'click', '.id5-media__choose', function ( event ) {
		event.preventDefault();

		var $wrap = $( this ).closest( '.id5-media' );
		var kind = $wrap.data( 'kind' );
		var mime = $wrap.data( 'mime' );

		// Limit the library to what the field can actually use — images for an
		// image field, or the one file type a field declares (PDF for forms) —
		// so the wrong kind of file cannot be picked in the first place.
		var library = {};

		if ( kind === 'image' ) {
			library = { type: 'image' };
		} else if ( mime ) {
			library = { type: mime };
		}

		var frame = wp.media( {
			title: kind === 'image' ? 'เลือกรูปภาพ' : 'เลือกไฟล์',
			button: { text: 'ใช้ไฟล์นี้' },
			library: library,
			multiple: false
		} );

		frame.on( 'select', function () {
			var attachment = frame.state().get( 'selection' ).first().toJSON();

			$wrap.find( 'input[type=hidden]' ).val( attachment.id );
			$wrap.find( '.id5-media__clear' ).show();

			if ( kind === 'image' ) {
				var src = attachment.sizes && attachment.sizes.thumbnail
					? attachment.sizes.thumbnail.url
					: attachment.url;
				$wrap.find( '.id5-media__preview' ).html( $( '<img>' ).attr( 'src', src ) );
			} else {
				$wrap.find( '.id5-media__preview' ).html(
					$( '<span class="id5-media__filename">' ).text( attachment.filename || attachment.url )
				);
			}
		} );

		frame.open();
	} );

	$( document ).on( 'click', '.id5-media__clear', function ( event ) {
		event.preventDefault();

		var $wrap = $( this ).closest( '.id5-media' );

		$wrap.find( 'input[type=hidden]' ).val( '' );
		$wrap.find( '.id5-media__preview' ).empty();
		$( this ).hide();
	} );

	// ─── Repeaters ──────────────────────────────────────────────────────────

	/**
	 * Rewrite every name="id5_key[<index>][sub]" in a row so the server sees
	 * contiguous indexes after adds, removes and reordering.
	 */
	function reindex( $repeater ) {
		var key = $repeater.data( 'key' );
		var pattern = new RegExp( '^id5_' + key + '\\[[^\\]]*\\]' );

		$repeater.find( '.id5-repeater__rows > .id5-repeater__row' ).each( function ( index ) {
			$( this ).find( '[name]' ).each( function () {
				var name = $( this ).attr( 'name' );

				$( this ).attr( 'name', name.replace( pattern, 'id5_' + key + '[' + index + ']' ) );
			} );

			// Give every control in this row a unique id derived from its (now
			// reindexed) name, and point its own label at it, so clicking a
			// label focuses the field in that row rather than the first one.
			$( this ).find( '.id5-field' ).each( function ( fieldIndex ) {
				var $control = $( this ).find( 'input, textarea, select' ).not( '[type=hidden]' ).first();

				if ( ! $control.length ) {
					return;
				}

				var id = 'id5_r' + index + '_f' + fieldIndex;

				$control.attr( 'id', id );
				$( this ).find( 'label.id5-field__label' ).attr( 'for', id );
			} );
		} );
	}

	$( document ).on( 'click', '.id5-repeater__add', function ( event ) {
		event.preventDefault();

		var $repeater = $( this ).closest( '.id5-repeater' );
		var template = $repeater.find( '.id5-repeater__template' ).html();

		$repeater.find( '.id5-repeater__rows' ).append( template );
		reindex( $repeater );
	} );

	$( document ).on( 'click', '.id5-repeater__remove', function ( event ) {
		event.preventDefault();

		var $repeater = $( this ).closest( '.id5-repeater' );

		$( this ).closest( '.id5-repeater__row' ).remove();
		reindex( $repeater );
	} );

	$( function () {
		$( '.id5-repeater' ).each( function () {
			var $repeater = $( this );

			$repeater.find( '.id5-repeater__rows' ).sortable( {
				handle: '.id5-repeater__handle',
				axis: 'y',
				update: function () {
					reindex( $repeater );
				}
			} );
		} );
	} );
	// ─── Organisation chart: choosing an existing row ───────────────────────
	// Picking a row by name also brings that row's position and English name,
	// so the new person lands in the row without moving it.

	$( document ).on( 'input change', 'input[name="id5_tier_label_th"]', function () {
		var $input = $( this );
		var value = $input.val();
		var $option = $( '#' + $input.attr( 'list' ) + ' option' ).filter( function () { return this.value === value; } ).first();

		if ( ! $option.length ) {
			return;
		}

		$( 'input[name="id5_tier_order"]' ).val( $option.data( 'order' ) );

		var $english = $( 'input[name="id5_tier_label_en"]' );
		if ( $option.data( 'en' ) ) {
			$english.val( $option.data( 'en' ) );
		}
	} );
} )( jQuery );
