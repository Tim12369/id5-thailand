/**
 * The block editor for "ส่วนเนื้อหาเพิ่มเติม" and "หน้าเว็บเพิ่มเติม".
 *
 * - Shows the type's instructions above the canvas.
 * - Inside the site editor's overlay, reports when a save starts and when it
 *   has finished (including the boxes below the editor), the way the classic
 *   forms do by loading a new page, so the website behind it is refreshed.
 */
( function ( wp, cfg ) {
	if ( ! wp || ! wp.data ) {
		return;
	}

	wp.domReady( function () {
		if ( cfg && cfg.notice ) {
			wp.data.dispatch( 'core/notices' ).createNotice( 'info', cfg.notice, { id: 'id5-block-help', isDismissible: true } );
		}
	} );

	if ( window.parent === window ) {
		return;
	}

	var tell = function ( type ) {
		try {
			window.parent.postMessage( { source: 'id5-cms-form', type: type }, window.location.origin );
		} catch ( e ) {}
	};

	var busy = false;

	wp.data.subscribe( function () {
		var editor = wp.data.select( 'core/editor' );
		var post = wp.data.select( 'core/edit-post' );

		if ( ! editor ) {
			return;
		}

		var saving = ( editor.isSavingPost() && ! editor.isAutosavingPost() ) || ( post && post.isSavingMetaBoxes && post.isSavingMetaBoxes() );

		if ( saving && ! busy ) {
			busy = true;
			tell( 'busy' );
		} else if ( ! saving && busy ) {
			busy = false;
			tell( 'ready' );
		}
	} );
} )( window.wp, window.ID5BlockEditor );
