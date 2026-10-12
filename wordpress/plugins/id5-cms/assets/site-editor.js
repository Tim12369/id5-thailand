/**
 * The site editor screen: live website on the right, editing panel on the left.
 *
 * The website (a different application, on a different origin) is shown in a
 * frame in "edit mode". It tells this script what is on the page and when a
 * pencil is clicked; this script answers by sliding the matching WordPress edit
 * screen over the preview, and reloads the preview after a save.
 *
 * Plain JavaScript, no build step and no libraries beyond wp.media (core).
 */
( function () {
	'use strict';

	var CFG = window.ID5SiteEditor;
	var root = document.getElementById( 'id5-se' );

	if ( ! CFG || ! root ) {
		return;
	}

	var preview = document.getElementById( 'id5-se-preview' );
	var overlay = document.getElementById( 'id5-se-overlay' );
	var formFrame = document.getElementById( 'id5-se-form' );
	var overlayTitle = document.getElementById( 'id5-se-overlay-title' );
	var itemsBox = document.getElementById( 'id5-se-items' );
	var pageLabel = document.getElementById( 'id5-se-page' );
	var stateLabel = document.getElementById( 'id5-se-state' );
	var stage = root.querySelector( '.id5-se__stage' );

	var currentPath = CFG.path || '/';
	var pageItems = [];
	var pendingOpen = CFG.open || '';
	var pendingFocus = null;
	var formLoads = 0;
	var dirty = false;
	var formBusy = false;
	var finishing = false;
	var reported = false;
	var reloadTimer = null;

	// How long the website needs to receive a change: WordPress notifies it,
	// it pulls the content, then the page can be reloaded.
	var SYNC_DELAY = 2500;

	// ── Preview ─────────────────────────────────────────────────────────

	function previewUrl( path ) {
		var url = new URL( CFG.site + path );
		url.searchParams.set( CFG.param, CFG.token );
		return url.toString();
	}

	function loadPreview() {
		preview.src = previewUrl( currentPath );
	}

	function reloadPreviewSoon( delay ) {
		clearTimeout( reloadTimer );
		setState( 'กำลังอัปเดตเว็บไซต์…', true );
		reloadTimer = setTimeout( function () {
			loadPreview();
			setState( 'เผยแพร่แล้ว', false );
		}, delay );
	}

	function tellPreview( message ) {
		if ( ! preview.contentWindow ) {
			return;
		}
		message.source = 'id5-cms';
		preview.contentWindow.postMessage( message, CFG.siteOrigin );
	}

	// What the wording panel (site-editor-texts.js) needs from this script.
	window.ID5SE = {
		reloadPreviewSoon: function () { reloadPreviewSoon( SYNC_DELAY ); },
		setState: function ( text, busy ) { setState( text, busy ); },
		tellPreview: function ( message ) { tellPreview( message ); },
		onPage: null,
		openText: null
	};

	function setState( text, busy ) {
		stateLabel.textContent = text;
		stateLabel.classList.toggle( 'is-busy', !! busy );
	}

	// ── Overlay: a WordPress edit screen over the preview ───────────────

	// ── Deleting the item that is open ──────────────────────────────────
	// The WordPress form has its own "move to trash" link, but it is small and
	// easy to miss; this puts the same action in plain sight.

	var deleteButton = document.getElementById( 'id5-se-delete' );
	var deleteLabel = document.getElementById( 'id5-se-delete-label' );
	var deleteNote = document.getElementById( 'id5-se-delete-note' );
	var openItem = null;

	function ajax( action, fields ) {
		var body = new FormData();
		body.append( 'action', action );
		body.append( 'nonce', CFG.nonce );
		Object.keys( fields ).forEach( function ( name ) { body.append( name, fields[ name ] ); } );

		return fetch( CFG.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body } )
			.then( function ( response ) { return response.json(); } )
			.catch( function () { return { success: false, data: { message: 'เชื่อมต่อไม่ได้ กรุณาลองใหม่' } }; } );
	}

	function offerDelete( target ) {
		openItem = null;
		deleteButton.hidden = true;
		deleteNote.hidden = true;

		if ( ! target ) {
			return;
		}

		ajax( 'id5_item_info', { target: target } ).then( function ( result ) {
			// The editor may have moved on to something else in the meantime.
			if ( ! result.success || overlay.hidden || pendingDelete !== target ) {
				return;
			}

			openItem = result.data;
			openItem.target = target;

			if ( openItem.deletable ) {
				deleteLabel.textContent = openItem.permanent ? 'ลบหมวดหมู่นี้' : 'ลบรายการนี้';
				deleteButton.hidden = false;
			} else if ( openItem.reason ) {
				deleteNote.textContent = 'ลบไม่ได้: ' + openItem.reason;
				deleteNote.hidden = false;
			}
		} );
	}

	var pendingDelete = '';

	deleteButton.addEventListener( 'click', function () {
		if ( ! openItem ) {
			return;
		}

		var question = openItem.permanent
			? 'ลบหมวดหมู่ "' + openItem.name + '" ออกจากเว็บไซต์?\n\nการลบหมวดหมู่เป็นการลบถาวร ไม่สามารถกู้คืนได้'
			: 'ลบ "' + openItem.name + '" ออกจากเว็บไซต์?\n\nรายการจะถูกย้ายไปถังขยะ และกู้คืนได้ภายใน 30 วัน';

		if ( ! window.confirm( question ) ) {
			return;
		}

		deleteButton.disabled = true;
		setState( 'กำลังลบ…', true );

		ajax( 'id5_item_delete', { target: openItem.target } ).then( function ( result ) {
			deleteButton.disabled = false;

			if ( ! result.success ) {
				setState( 'ลบไม่สำเร็จ', false );
				window.alert( ( result.data && result.data.message ) || 'ลบไม่สำเร็จ' );
				return;
			}

			// Nothing left to edit: close the form and show the website
			// without the item.
			pendingFocus = null;
			formBusy = false;
			dirty = true;
			closeOverlay();
		} );
	} );

	function openOverlay( url, title, target ) {
		pendingDelete = target || '';
		offerDelete( pendingDelete );
		finishing = false;
		formBusy = false;
		formLoads = 0;
		overlayTitle.textContent = title || '';
		overlay.hidden = false;
		formFrame.classList.add( 'is-loading' );
		formFrame.src = url;
	}

	function closeOverlay() {
		overlay.hidden = true;

		// A save is still on its way to the server. Dropping the form now could
		// cut that request short, and reloading the preview now would show the
		// old content — so let it land first, then finish closing.
		if ( formBusy ) {
			finishing = true;
			setState( 'กำลังบันทึก…', true );
			setTimeout( finishClose, 15000 ); // never wait forever
			return;
		}

		finishClose();
	}

	function finishClose() {
		if ( ! overlay.hidden ) {
			return; // another form was opened in the meantime
		}

		finishing = false;
		formBusy = false;
		formFrame.src = 'about:blank';

		if ( dirty ) {
			dirty = false;
			reloadPreviewSoon( SYNC_DELAY );
		} else {
			setState( 'เผยแพร่แล้ว', false );
		}
	}

	formFrame.addEventListener( 'load', function () {
		formFrame.classList.remove( 'is-loading' );

		if ( overlay.hidden ) {
			// The save that was in flight when the form was closed has landed.
			if ( finishing ) {
				dirty = true;
				finishClose();
			}
			return;
		}

		// Fallback for browsers that do not send Sec-Fetch-Dest: hide the
		// WordPress sidebar and toolbar inside the overlay from here.
		try {
			var doc = formFrame.contentDocument;
			if ( doc && doc.head && ! doc.getElementById( 'id5-framed-css' ) ) {
				var style = doc.createElement( 'style' );
				style.id = 'id5-framed-css';
				style.textContent = '#adminmenumain,#wpadminbar,#wpfooter,#screen-meta-links{display:none!important}' +
					'#wpcontent,#wpfooter{margin-left:0!important}html.wp-toolbar{padding-top:0!important}';
				doc.head.appendChild( style );
			}
		} catch ( e ) { /* cross-origin page inside the overlay: nothing to adjust */ }

		// Fallback for when the framed page could not report for itself (see
		// the message handler below): the first load is the form opening, and
		// any later load means it was submitted, so the website has changed.
		formLoads++;
		if ( formLoads > 1 && ! reported ) {
			dirty = true;
			reloadPreviewSoon( SYNC_DELAY );
		}
	} );

	// The edit form reports its own saves. Each WordPress screen shown in the
	// overlay says "busy" the instant a form is submitted or the page starts to
	// leave, and "ready" when the next page has arrived. Listening for that —
	// rather than waiting for the frame to finish loading — means a save is
	// noticed even if the editor presses Update, or "back", very quickly.
	window.addEventListener( 'message', function ( event ) {
		var data = event.data;

		if ( event.origin !== window.location.origin || ! data || 'id5-cms-form' !== data.source ) {
			return;
		}

		reported = true;

		if ( 'busy' === data.type ) {
			if ( ! overlay.hidden ) {
				formBusy = true;
				dirty = true;
			}
		} else if ( 'ready' === data.type && formBusy ) {
			formBusy = false;

			if ( overlay.hidden ) {
				if ( finishing ) {
					finishClose();
				}
			} else {
				// Saved while the form is still open: refresh the website behind it.
				reloadPreviewSoon( SYNC_DELAY );
			}
		}
	} );

	document.getElementById( 'id5-se-back' ).addEventListener( 'click', closeOverlay );

	document.addEventListener( 'keydown', function ( event ) {
		if ( 'Escape' === event.key && ! overlay.hidden ) {
			closeOverlay();
		}
	} );

	// ── Opening things ──────────────────────────────────────────────────

	function typeInfo( postType ) {
		return CFG.types.filter( function ( t ) { return t.type === postType; } )[ 0 ];
	}

	/**
	 * Open whatever a target names:
	 *   post:123         the edit screen of one item
	 *   new:id5_faq      a blank item of that type
	 *   list:id5_office  the list of that type
	 *   setting:hero     a group of site-wide settings, in the panel
	 */
	function openTarget( target, title ) {
		var parts = String( target ).split( ':' );
		var kind = parts[ 0 ];
		var value = parts.slice( 1 ).join( ':' );
		var info;

		if ( 'post' === kind && /^\d+$/.test( value ) ) {
			pendingFocus = target;
			openOverlay( CFG.adminUrl + 'post.php?post=' + value + '&action=edit', title || 'แก้ไข', target );
		} else if ( 'new' === kind && ( info = typeInfo( value.split( '?' )[ 0 ] ) ) && info.canCreate ) {
			// "new:id5_person?id5_section=…" opens the form with some of it
			// already filled in. Only the plugin's own fields are passed on.
			var prefill = value.indexOf( '?' ) < 0 ? '' : value.slice( value.indexOf( '?' ) + 1 ).split( '&' ).filter( function ( pair ) {
				return /^id5_[a-z0-9_]+=/.test( pair );
			} ).join( '&' );

			openOverlay( info.newUrl + ( prefill ? ( info.newUrl.indexOf( '?' ) < 0 ? '?' : '&' ) + prefill : '' ), title && prefill ? title : 'เพิ่ม' + info.label );
		} else if ( 'list' === kind && ( info = typeInfo( value ) ) ) {
			openOverlay( info.listUrl, info.label );
		} else if ( 'setting' === kind ) {
			openSettingGroup( value );
		} else if ( 'text' === kind && window.ID5SE.openText ) {
			// A piece of fixed wording: its form is in the panel, not a screen.
			window.ID5SE.openText( value.split( ',' ) );
		} else if ( 'term' === kind && /^\d+$/.test( value ) ) {
			pendingFocus = target;
			openOverlay( CFG.termUrl + value, title || 'แก้ไขหมวดหมู่', target );
		} else if ( 'terms' === kind && /^[a-z0-9_]+$/.test( value ) ) {
			openOverlay( CFG.termsUrl + value, title || 'หมวดหมู่' );
		} else if ( 'menus' === kind ) {
			openOverlay( CFG.adminUrl + 'nav-menus.php', 'เมนูนำทาง' );
		}
	}

	function openSettingGroup( id ) {
		var section = root.querySelector( '[data-setting-group="' + id + '"]' );

		if ( ! section ) {
			return;
		}

		root.querySelectorAll( '[data-setting-group]' ).forEach( function ( other ) {
			other.open = other === section;
		} );
		section.scrollIntoView( { behavior: 'smooth', block: 'start' } );

		var first = section.querySelector( 'input[type=text], textarea' );
		if ( first ) {
			setTimeout( function () { first.focus(); }, 300 );
		}
	}

	// ── Panel: what is on the page being previewed ──────────────────────

	function renderItems() {
		itemsBox.textContent = '';

		if ( ! pageItems.length ) {
			var empty = document.createElement( 'p' );
			empty.className = 'id5-se__empty';
			empty.textContent = 'หน้านี้ไม่มีส่วนที่แก้ไขได้โดยตรง ลองเลือกจาก "เนื้อหาทั้งหมด" ด้านล่าง';
			itemsBox.appendChild( empty );
			return;
		}

		// Group by kind, keeping the order things appear on the page.
		var groups = [];
		var byKind = {};
		var seen = {};

		pageItems.forEach( function ( item ) {
			var key = item.target;
			seen[ key ] = ( seen[ key ] || 0 );
			var entry = { target: item.target, title: item.title, kind: item.kind, index: seen[ key ]++ };

			if ( entry.index > 0 ) {
				return; // the same item shown twice on a page is listed once
			}
			if ( ! byKind[ item.kind ] ) {
				byKind[ item.kind ] = { kind: item.kind, items: [] };
				groups.push( byKind[ item.kind ] );
			}
			byKind[ item.kind ].items.push( entry );
		} );

		groups.forEach( function ( group ) {
			var details = document.createElement( 'details' );
			details.className = 'id5-se__kind';
			details.open = group.items.length <= 12;

			var summary = document.createElement( 'summary' );
			summary.textContent = group.kind || 'เนื้อหา';
			var count = document.createElement( 'span' );
			count.className = 'id5-se__count';
			count.textContent = group.items.length;
			summary.appendChild( count );
			details.appendChild( summary );

			group.items.forEach( function ( item ) {
				var row = document.createElement( 'button' );
				row.type = 'button';
				row.className = 'id5-se__row';
				row.setAttribute( 'data-target', item.target );

				var text = document.createElement( 'span' );
				text.textContent = item.title || item.kind;
				var icon = document.createElement( 'span' );
				icon.className = 'dashicons dashicons-edit';

				row.appendChild( text );
				row.appendChild( icon );

				row.addEventListener( 'mouseenter', function () { tellPreview( { type: 'hover', target: item.target } ); } );
				row.addEventListener( 'mouseleave', function () { tellPreview( { type: 'unhover', target: item.target } ); } );
				row.addEventListener( 'click', function () {
					tellPreview( { type: 'focus', target: item.target } );
					openTarget( item.target, ( item.kind ? item.kind + ': ' : '' ) + item.title );
				} );

				details.appendChild( row );
			} );

			itemsBox.appendChild( details );
		} );
	}

	// ── Messages from the website in the frame ──────────────────────────

	window.addEventListener( 'message', function ( event ) {
		var data = event.data;

		if ( event.origin !== CFG.siteOrigin || ! data || 'id5-cms' !== data.source ) {
			return;
		}

		if ( 'page' === data.type ) {
			currentPath = data.path || '/';
			pageItems = Array.isArray( data.items ) ? data.items : [];
			pageLabel.textContent = ( data.title || '' ).split( ' — ' )[ 0 ] + '  ·  ' + currentPath;
			renderItems();

			if ( window.ID5SE.onPage ) {
				window.ID5SE.onPage( data );
			}

			// Keep this screen's own address in step, so a refresh stays put.
			var self = new URL( CFG.selfUrl );
			self.searchParams.set( 'path', currentPath );
			window.history.replaceState( null, '', self.toString() );

			if ( pendingOpen ) {
				var target = pendingOpen;
				pendingOpen = '';
				var match = pageItems.filter( function ( i ) { return i.target === target; } )[ 0 ];
				openTarget( target, match ? ( match.kind ? match.kind + ': ' : '' ) + match.title : '' );
			} else if ( pendingFocus && overlay.hidden ) {
				tellPreview( { type: 'focus', target: pendingFocus } );
				pendingFocus = null;
			}
		} else if ( 'edit' === data.type ) {
			openTarget( data.target, ( data.kind ? data.kind + ': ' : '' ) + ( data.title || '' ) );
		}
	} );

	// ── Panel: every content type ───────────────────────────────────────

	var typeList = document.getElementById( 'id5-se-types' );

	CFG.types.forEach( function ( info ) {
		var li = document.createElement( 'li' );

		var link = document.createElement( 'a' );
		link.href = '#';
		link.textContent = info.label;
		link.addEventListener( 'click', function ( event ) {
			event.preventDefault();
			openOverlay( info.listUrl, info.label );
		} );
		li.appendChild( link );

		if ( info.canCreate ) {
			var add = document.createElement( 'button' );
			add.type = 'button';
			add.className = 'id5-se__add';
			add.title = 'เพิ่ม' + info.label;
			add.innerHTML = '<span class="dashicons dashicons-plus-alt2"></span>';
			add.addEventListener( 'click', function () { openOverlay( info.newUrl, 'เพิ่ม' + info.label ); } );
			li.appendChild( add );
		}

		typeList.appendChild( li );
	} );

	root.querySelectorAll( '[data-open-url]' ).forEach( function ( link ) {
		link.addEventListener( 'click', function ( event ) {
			event.preventDefault();
			openOverlay( link.getAttribute( 'data-open-url' ), link.getAttribute( 'data-open-title' ) );
		} );
	} );

	// ── Panel: site-wide settings ───────────────────────────────────────

	root.querySelectorAll( '[data-settings-form]' ).forEach( function ( form ) {
		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();

			var button = form.querySelector( 'button[type=submit]' );
			var saved = form.querySelector( '.id5-se__saved' );
			var body = new FormData( form );

			body.append( 'action', 'id5_save_site_settings' );
			body.append( 'nonce', CFG.nonce );

			button.disabled = true;
			saved.hidden = true;

			fetch( CFG.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body } )
				.then( function ( response ) { return response.json(); } )
				.then( function ( result ) {
					if ( ! result || ! result.success ) {
						throw new Error( result && result.data && result.data.message ? result.data.message : 'บันทึกไม่สำเร็จ' );
					}
					saved.textContent = 'บันทึกแล้ว ✓';
					saved.classList.remove( 'is-error' );
					saved.hidden = false;
					reloadPreviewSoon( SYNC_DELAY );
				} )
				.catch( function ( error ) {
					saved.textContent = error.message || 'บันทึกไม่สำเร็จ กรุณาลองอีกครั้ง';
					saved.classList.add( 'is-error' );
					saved.hidden = false;
				} )
				.then( function () { button.disabled = false; } );
		} );
	} );

	root.querySelectorAll( '[data-image-field]' ).forEach( function ( field ) {
		var input = field.querySelector( 'input[type=hidden]' );
		var image = field.querySelector( 'img' );
		var clear = field.querySelector( '[data-image-clear]' );

		field.querySelector( '[data-image-choose]' ).addEventListener( 'click', function () {
			var frame = wp.media( { title: 'เลือกรูปภาพ', button: { text: 'ใช้รูปนี้' }, library: { type: 'image' }, multiple: false } );

			frame.on( 'select', function () {
				var chosen = frame.state().get( 'selection' ).first().toJSON();
				input.value = chosen.id;
				image.src = chosen.sizes && chosen.sizes.medium ? chosen.sizes.medium.url : chosen.url;
				image.hidden = false;
				clear.hidden = false;
			} );

			frame.open();
		} );

		clear.addEventListener( 'click', function () {
			input.value = '0';
			image.hidden = true;
			clear.hidden = true;
		} );
	} );

	// ── Panel: preview size and close ───────────────────────────────────

	root.querySelectorAll( '[data-device]' ).forEach( function ( button ) {
		if ( 'BUTTON' !== button.tagName ) {
			return;
		}
		button.addEventListener( 'click', function () {
			root.querySelectorAll( 'button[data-device]' ).forEach( function ( b ) { b.classList.remove( 'is-active' ); } );
			button.classList.add( 'is-active' );
			stage.setAttribute( 'data-device', button.getAttribute( 'data-device' ) );
		} );
	} );

	// Closing returns to the website itself, still in edit mode, on this page.
	document.getElementById( 'id5-se-close' ).addEventListener( 'click', function ( event ) {
		event.preventDefault();
		window.location.href = previewUrl( currentPath );
	} );

	loadPreview();
} )();
