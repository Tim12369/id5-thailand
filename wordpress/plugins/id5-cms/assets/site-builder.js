/**
 * The page builder in the site editor.
 *
 * Content sections and pages are WordPress blocks. This script keeps the
 * blocks of every section shown in the preview, lets the editor change them
 * straight on the page (the preview frame draws the row, column and component
 * toolbars and reports what was clicked, typed or dragged), and offers the
 * side panel: "เพิ่มเนื้อหา" (add content: blocks and ready-made components)
 * and "คุณกำลังปรับแต่ง…" (customise the selected row, column or component,
 * on Layout / Style / Advanced tabs).
 *
 * Blocks are created, parsed and serialized by WordPress's own block library
 * (wp.blocks), so what is published is ordinary block markup — the same as
 * the block editor writes, and it opens there too. The preview is drawn from
 * the same save functions, with each block's element tagged so a click in the
 * preview can be matched to its block.
 *
 * Nothing is stored until "เผยแพร่" (publish); undo and redo cover every change.
 */
( function () {
	'use strict';

	var CFG = window.ID5SiteEditor;
	var OPT = window.ID5Builder || {};
	var SE = window.ID5SE;
	var root = document.getElementById( 'id5-se' );
	var panel = document.getElementById( 'id5-sb' );

	if ( ! CFG || ! SE || ! root || ! panel || ! window.wp || ! wp.blocks || ! wp.hooks ) {
		return;
	}

	var wpb = wp.blocks;

	if ( ! wpb.getBlockType( 'core/paragraph' ) && wp.blockLibrary ) {
		try { wp.blockLibrary.registerCoreBlocks(); } catch ( e ) { /* already registered */ }
	}

	var publishButton = document.getElementById( 'id5-sb-publish' );
	var undoButton = document.getElementById( 'id5-sb-undo' );
	var redoButton = document.getElementById( 'id5-sb-redo' );

	// ── State ───────────────────────────────────────────────────────────

	var docs = {};          // post ID => { id, type, title, blocks, saved, background, savedBackground, modified, canDelete }
	var onPage = [];        // post IDs of the sections in the preview, in order
	var slots = [];         // places a new section can be added on this page
	var selected = null;    // clientId of the selected block
	var selectedDoc = null; // post ID of the selected section (section itself, or the block's)
	var target = null;      // where "add content" puts things: { doc, parent, index }
	var view = '';          // panel view: '', 'add', 'custom', 'section', 'layout', 'ask'
	var tabFor = {};        // last tab used per kind of thing
	var addTab = 'components';
	var search = '';
	var addOpen = false;    // the "ADD CONTENT" fly-out beside the panel
	var addMode = 'list';   // 'list', 'layout' (choose a row layout) or 'ask'
	var folded = {};        // groups closed in the fly-out
	var undoStack = [];
	var redoStack = [];
	var typing = null;      // { doc, before } while text is being typed in the preview
	var copiedStyle = null;
	var dragTile = null;
	var busy = false;

	// ── Small helpers ───────────────────────────────────────────────────

	function h( tag, cls, text ) {
		var node = document.createElement( tag );
		if ( cls ) { node.className = cls; }
		if ( text !== undefined && text !== null ) { node.textContent = text; }
		return node;
	}

	function icon( name ) {
		return h( 'span', 'dashicons dashicons-' + name );
	}

	function esc( text ) {
		return String( text ).replace( /&/g, '&amp;' ).replace( /</g, '&lt;' ).replace( />/g, '&gt;' ).replace( /"/g, '&quot;' );
	}

	function plain( html ) {
		var box = document.createElement( 'div' );
		box.innerHTML = String( html || '' );
		return ( box.textContent || '' ).trim();
	}

	// Rich text attributes are RichTextData objects in current WordPress.
	function rich( html ) {
		var R = wp.richText && wp.richText.RichTextData;
		return R && R.fromHTMLString ? R.fromHTMLString( String( html ) ) : String( html );
	}

	function str( value ) {
		if ( value === undefined || value === null ) { return ''; }
		return String( value.toHTMLString ? value.toHTMLString() : value );
	}

	// What may be typed into text on the page: words and simple formatting.
	var INLINE = { B: 1, STRONG: 1, I: 1, EM: 1, A: 1, BR: 1, U: 1, S: 1, SUB: 1, SUP: 1, MARK: 1, CODE: 1 };

	function cleanInline( html ) {
		var box = document.createElement( 'div' );
		box.innerHTML = String( html || '' );

		( function clean( node ) {
			Array.prototype.slice.call( node.childNodes ).forEach( function ( child ) {
				if ( child.nodeType === 3 ) { return; }
				if ( child.nodeType !== 1 || /^(SCRIPT|STYLE|IFRAME|OBJECT)$/.test( child.tagName ) ) { node.removeChild( child ); return; }
				clean( child );

				if ( ! INLINE[ child.tagName ] ) {
					// Keep the words, drop the element (a <div> or <span> from pasting).
					while ( child.firstChild ) { node.insertBefore( child.firstChild, child ); }
					if ( /^(DIV|P)$/.test( child.tagName ) && child.nextSibling ) { node.insertBefore( document.createElement( 'br' ), child ); }
					node.removeChild( child );
					return;
				}

				Array.prototype.slice.call( child.attributes ).forEach( function ( attribute ) {
					var keep = child.tagName === 'A' && /^(href|target|rel)$/.test( attribute.name );
					if ( ! keep || /^\s*javascript:/i.test( attribute.value ) ) { child.removeAttribute( attribute.name ); }
				} );
			} );
		}( box ) );

		return box.innerHTML.replace( /(<br>)+$/, '' );
	}

	function classes( block ) {
		return String( block.attributes.className || '' ).split( /\s+/ ).filter( Boolean );
	}

	function hasClass( block, name ) {
		return classes( block ).indexOf( name ) >= 0;
	}

	// Replace the classes matching `pattern` with `value` (or with nothing).
	function setClass( block, pattern, value ) {
		var list = classes( block ).filter( function ( c ) { return ! pattern.test( c ); } );
		if ( value ) { list = list.concat( String( value ).split( ' ' ) ); }
		setAttr( block, 'className', list.join( ' ' ) );
	}

	function classValue( block, pattern ) {
		return classes( block ).filter( function ( c ) { return pattern.test( c ); } )[ 0 ] || '';
	}

	function supports( block, attribute ) {
		var type = wpb.getBlockType( block.name );
		return !! ( type && type.attributes && type.attributes[ attribute ] );
	}

	function setAttr( block, name, value ) {
		if ( value === undefined || value === null || value === '' || value === false ) {
			delete block.attributes[ name ];
		} else {
			block.attributes[ name ] = value;
		}
		// A block WordPress could not read is rewritten from its settings once changed.
		block.isValid = true;
		delete block.__unstableBlockSource;
	}

	function make( name, attributes, inner ) {
		return wpb.createBlock( name, attributes || {}, inner || [] );
	}

	function text( html, extra ) {
		return make( 'core/paragraph', Object.assign( { content: rich( html ) }, extra || {} ) );
	}

	function heading( html, level, extra ) {
		return make( 'core/heading', Object.assign( { content: rich( html ), level: level || 2 }, extra || {} ) );
	}

	function list( items, extra ) {
		return make( 'core/list', extra || {}, items.map( function ( item ) { return make( 'core/list-item', { content: rich( item ) } ); } ) );
	}

	function link( label, url ) {
		return '<a href="' + esc( url || '#' ) + '">' + esc( label ) + '</a>';
	}

	function buttons( label, url, extra ) {
		return make( 'core/buttons', {}, [ make( 'core/button', Object.assign( { text: rich( label ), url: url || '#' }, extra || {} ) ) ] );
	}

	function row( widths, fill ) {
		return make( 'core/columns', {}, widths.map( function ( width, i ) {
			return make( 'core/column', width ? { width: width } : {}, fill ? fill( i ) : [] );
		} ) );
	}

	function uid() {
		return Date.now().toString( 36 ) + Math.random().toString( 36 ).slice( 2, 8 );
	}

	// ── What each block is ──────────────────────────────────────────────

	var GROUPS = {
		'id5-tabs': 'แท็บ',
		'id5-tab': 'แท็บย่อย',
		'id5-slider': 'สไลด์ภาพ',
		'id5-carousel': 'ภาพหมุน (Carousel)',
		'id5-collage': 'ภาพคอลลาจ',
		'id5-card': 'การ์ด',
		'id5-accordion': 'แอคคอร์เดียน',
		'id5-accordion-menu': 'เมนูแบบพับ'
	};

	function labelOf( block ) {
		var name = block.name;
		var found = '';

		if ( name === 'core/group' ) {
			Object.keys( GROUPS ).forEach( function ( c ) { if ( ! found && hasClass( block, c ) ) { found = GROUPS[ c ]; } } );
			return found || 'กล่อง';
		}
		if ( name === 'core/paragraph' ) {
			return hasClass( block, 'id5-icon' ) ? 'ไอคอน' : hasClass( block, 'id5-map' ) ? 'แผนที่' : hasClass( block, 'id5-link' ) ? 'ลิงก์' : hasClass( block, 'id5-price' ) ? 'ราคา' : 'ข้อความ';
		}
		if ( name === 'core/list' ) {
			return hasClass( block, 'id5-social' ) ? 'ไอคอนโซเชียล' : hasClass( block, 'id5-vmenu' ) ? 'เมนูแนวตั้ง' : 'รายการ';
		}
		if ( name === 'core/details' ) {
			return hasClass( block, 'id5-dropdown' ) ? 'เมนูดรอปดาวน์' : 'หัวข้อพับได้';
		}

		return {
			'core/columns': 'แถว',
			'core/column': 'คอลัมน์',
			'core/heading': 'หัวข้อ',
			'core/image': 'รูปภาพ',
			'core/file': 'ไฟล์ PDF',
			'core/buttons': 'กลุ่มปุ่ม',
			'core/button': 'ปุ่ม',
			'core/spacer': 'ระยะห่าง',
			'core/separator': 'เส้นคั่น',
			'core/embed': 'วิดีโอ',
			'core/list-item': 'รายการย่อย',
			'core/table': 'ตาราง',
			'core/quote': 'คำพูด'
		}[ name ] || 'บล็อก';
	}

	function kindOf( block ) {
		if ( block.name === 'core/columns' ) { return 'row'; }
		if ( block.name === 'core/column' ) { return 'column'; }
		if ( block.name === 'core/buttons' ) { return 'wrap'; }
		if ( block.name === 'core/list-item' ) { return 'child'; }
		return 'comp';
	}

	// Blocks that hold other blocks an editor may put anything into.
	function isContainer( block ) {
		return block.name === 'core/column' || block.name === 'core/group' || block.name === 'core/details';
	}

	// Which text of a block can be typed into on the page.
	function editables( block ) {
		switch ( block.name ) {
			case 'core/paragraph':
				return hasClass( block, 'id5-icon' ) || hasClass( block, 'id5-map' ) ? [] : [ { s: '', a: 'content' } ];
			case 'core/heading':
			case 'core/list-item':
				return [ { s: '', a: 'content' } ];
			case 'core/button':
				return [ { s: '.wp-block-button__link', a: 'text' } ];
			case 'core/details':
				return [ { s: 'summary', a: 'summary' } ];
			case 'core/image':
				return [ { s: 'figcaption', a: 'caption' } ];
			case 'core/quote':
				return [ { s: 'cite', a: 'citation' } ];
			case 'core/file':
				return [ { s: 'a:not(.wp-block-file__button)', a: 'fileName' }, { s: '.wp-block-file__button', a: 'downloadButtonText' } ];
			case 'core/table':
				return [ { s: 'td, th', a: '@cell' } ];
		}
		return [];
	}

	// ── Finding blocks ──────────────────────────────────────────────────

	function walk( blocks, fn, parent ) {
		blocks.forEach( function ( block, index ) {
			fn( block, parent || null, blocks, index );
			walk( block.innerBlocks, fn, block );
		} );
	}

	function locate( clientId ) {
		var found = null;

		Object.keys( docs ).forEach( function ( id ) {
			if ( found ) { return; }
			walk( docs[ id ].blocks, function ( block, parent, siblings, index ) {
				if ( ! found && block.clientId === clientId ) {
					found = { doc: docs[ id ], block: block, parent: parent, list: siblings, index: index };
				}
			} );
		} );

		return found;
	}

	function path( clientId ) {
		var trail = [];
		var at = locate( clientId );

		while ( at ) {
			trail.unshift( at.block );
			at = at.parent ? locate( at.parent.clientId ) : null;
		}

		return trail;
	}

	function childrenOf( doc, parentId ) {
		if ( ! parentId ) { return doc.blocks; }
		var at = locate( parentId );
		return at ? at.block.innerBlocks : null;
	}

	// ── Changes, undo and redo ──────────────────────────────────────────

	function serialize( doc ) {
		return wpb.serialize( doc.blocks );
	}

	function isDirty( doc ) {
		return serialize( doc ) !== doc.saved || doc.background !== doc.savedBackground;
	}

	/**
	 * Make a change to one or more sections: run `fn`, then remember the
	 * before and after, redraw what changed and refresh the panel.
	 */
	function change( list, fn ) {
		list = list.filter( Boolean ).filter( function ( d, i, all ) { return all.indexOf( d ) === i; } );
		endTyping();

		var before = list.map( function ( doc ) { return { doc: doc, content: serialize( doc ), background: doc.background }; } );
		fn();
		var entry = [];

		before.forEach( function ( b ) {
			var after = serialize( b.doc );
			if ( after !== b.content || b.doc.background !== b.background ) {
				entry.push( { id: b.doc.id, before: b.content, after: after, bgBefore: b.background, bgAfter: b.doc.background } );
			}
		} );

		if ( entry.length ) {
			undoStack.push( entry );
			if ( undoStack.length > 200 ) { undoStack.shift(); }
			redoStack = [];
		}

		before.forEach( function ( b ) { draw( b.doc ); } );
		if ( selected && ! locate( selected ) ) { selected = null; }
		refresh();
	}

	function edit( doc, fn ) {
		change( [ doc ], fn );
	}

	function restore( entry, forward ) {
		entry.forEach( function ( e ) {
			var doc = docs[ e.id ];
			if ( ! doc ) { return; }
			doc.blocks = wpb.parse( forward ? e.after : e.before );
			doc.background = forward ? e.bgAfter : e.bgBefore;
			draw( doc );
		} );
		selected = null;
		if ( view === 'custom' ) { view = selectedDoc ? 'section' : ''; }
		refresh();
	}

	function undo() {
		endTyping();
		var entry = undoStack.pop();
		if ( entry ) { redoStack.push( entry ); restore( entry, false ); }
	}

	function redo() {
		endTyping();
		var entry = redoStack.pop();
		if ( entry ) { undoStack.push( entry ); restore( entry, true ); }
	}

	// Text typed on the page is one change from the first key to leaving it.
	function startTyping( doc ) {
		if ( ! typing ) { typing = { doc: doc, before: serialize( doc ) }; }
	}

	function endTyping() {
		if ( ! typing ) { return; }
		var t = typing;
		typing = null;
		var after = serialize( t.doc );
		if ( after !== t.before ) {
			undoStack.push( [ { id: t.doc.id, before: t.before, after: after, bgBefore: t.doc.background, bgAfter: t.doc.background } ] );
			redoStack = [];
		}
		refresh();
	}

	// ── Drawing into the preview ────────────────────────────────────────

	var tagging = null;

	// While the preview is drawn — and only then — every block's element
	// carries data-id5-b="clientId". What is published never has it.
	wp.hooks.addFilter( 'blocks.getSaveContent.extraProps', 'id5/builder-tag', function ( props, type, attributes ) {
		if ( tagging && tagging.has( attributes ) ) {
			props = Object.assign( {}, props );
			props[ 'data-id5-b' ] = tagging.get( attributes );
		}
		return props;
	}, 999 );

	function draw( doc ) {
		if ( onPage.indexOf( doc.id ) < 0 ) { return; }

		var meta = {};
		var html = '';
		tagging = new WeakMap();

		walk( doc.blocks, function ( block ) {
			tagging.set( block.attributes, block.clientId );
			meta[ block.clientId ] = { n: block.name, l: labelOf( block ), k: kindOf( block ), c: isContainer( block ) ? 1 : 0, e: editables( block ) };
		} );

		try {
			doc.blocks.forEach( function ( block ) {
				html += wpb.getSaveContent( block.name, block.attributes, block.innerBlocks );
			} );
		} catch ( e ) {
			html = '<p>แสดงตัวอย่างไม่ได้: ' + esc( e.message ) + '</p>';
		} finally {
			tagging = null;
		}

		SE.tellPreview( { type: 'builder-render', id: doc.id, html: html, meta: meta, sel: selected, bg: doc.background, title: doc.title } );
	}

	function highlight() {
		SE.tellPreview( { type: 'builder-select', id: selected, section: selectedDoc } );
	}

	// ── Talking to WordPress ────────────────────────────────────────────

	function ajax( action, fields ) {
		var body = new FormData();
		body.append( 'action', action );
		body.append( 'nonce', CFG.nonce );
		Object.keys( fields || {} ).forEach( function ( name ) { body.append( name, fields[ name ] ); } );

		return fetch( CFG.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body } )
			.then( function ( response ) { return response.json().catch( function () { return { success: false, data: { message: 'เซิร์ฟเวอร์ตอบกลับไม่ถูกต้อง (' + response.status + ')' } }; } ); } )
			.catch( function () { return { success: false, data: { message: 'เชื่อมต่อไม่ได้ กรุณาลองใหม่' } }; } );
	}

	function adopt( item ) {
		var blocks = wpb.parse( item.content || '' );
		var doc = docs[ item.id ] || {};

		doc.id = item.id;
		doc.type = item.type;
		doc.title = item.title;
		doc.blocks = blocks;
		doc.saved = wpb.serialize( blocks );
		doc.background = doc.savedBackground = item.background || 'none';
		doc.modified = item.modified;
		doc.canDelete = item.canDelete;
		docs[ item.id ] = doc;

		return doc;
	}

	function loadSections( list ) {
		onPage = list.map( function ( s ) { return Number( s.id ); } );

		// Unpublished changes stay as they are; everything else is read fresh,
		// since it may have been changed in a form in the meantime.
		var need = onPage.filter( function ( id ) { return ! docs[ id ] || ! isDirty( docs[ id ] ); } );

		onPage.forEach( function ( id ) { if ( docs[ id ] && need.indexOf( id ) < 0 ) { draw( docs[ id ] ); } } );

		if ( ! need.length ) {
			afterLoad();
			return;
		}

		ajax( 'id5_builder_load', { ids: need.join( ',' ) } ).then( function ( result ) {
			if ( ! result.success ) { return; }
			result.data.items.forEach( function ( item ) { draw( adopt( item ) ); } );
			afterLoad();
		} );
	}

	var afterLoadOnce = null;

	function afterLoad() {
		if ( afterLoadOnce ) {
			var fn = afterLoadOnce;
			afterLoadOnce = null;
			fn();
		}
		refresh();
	}

	function publish() {
		var list = Object.keys( docs ).map( function ( id ) { return docs[ id ]; } ).filter( isDirty );

		if ( ! list.length || busy ) { return; }

		endTyping();
		busy = true;
		refresh();
		SE.setState( 'กำลังเผยแพร่…', true );

		var failed = [];

		function next( i, force ) {
			if ( i >= list.length ) {
				busy = false;
				SE.setState( failed.length ? 'เผยแพร่ไม่ครบ' : 'เผยแพร่แล้ว', false );
				refresh();
				if ( failed.length ) { window.alert( failed.join( '\n' ) ); }
				return;
			}

			var doc = list[ i ];
			var content = serialize( doc );

			ajax( 'id5_builder_save', { id: doc.id, content: content, since: doc.modified || '', background: doc.background, force: force ? 1 : '' } ).then( function ( result ) {
				if ( ! result.success && result.data && result.data.conflict && ! force ) {
					if ( window.confirm( result.data.message + '\n\nบันทึกงานของคุณทับหรือไม่? (กด "ยกเลิก" เพื่อเก็บงานของคุณไว้ก่อน)' ) ) {
						next( i, true );
						return;
					}
					failed.push( '"' + doc.title + '" ยังไม่ได้เผยแพร่' );
				} else if ( ! result.success ) {
					failed.push( '"' + doc.title + '": ' + ( ( result.data && result.data.message ) || 'บันทึกไม่สำเร็จ' ) );
				} else {
					var item = result.data;
					doc.modified = item.modified;
					doc.savedBackground = item.background;

					// WordPress may have cleaned something out of the markup.
					var normal = wpb.serialize( wpb.parse( item.content ) );
					if ( normal !== content ) {
						doc.blocks = wpb.parse( item.content );
						draw( doc );
					}
					doc.saved = normal;
				}
				next( i + 1, false );
			} );
		}

		next( 0, false );
	}

	window.addEventListener( 'beforeunload', function ( event ) {
		var unsaved = Object.keys( docs ).some( function ( id ) { return isDirty( docs[ id ] ); } );
		if ( unsaved ) {
			event.preventDefault();
			event.returnValue = '';
		}
	} );

	// ── New sections ────────────────────────────────────────────────────

	function createSection( slot, blocks, then ) {
		if ( busy ) { return; }
		busy = true;
		SE.setState( 'กำลังเพิ่มส่วนเนื้อหา…', true );

		var content = wpb.serialize( blocks && blocks.length ? blocks : [ row( [ null ] ) ] );

		ajax( 'id5_builder_create', { page: slot.page, position: slot.position, content: content } ).then( function ( result ) {
			busy = false;

			if ( ! result.success ) {
				SE.setState( 'เพิ่มไม่สำเร็จ', false );
				window.alert( ( result.data && result.data.message ) || 'เพิ่มส่วนเนื้อหาไม่สำเร็จ' );
				return;
			}

			var doc = adopt( result.data );
			selectedDoc = doc.id;

			// The website shows the new section once it has received it.
			afterLoadOnce = function () {
				// Select what was added (the first thing inside a column), or
				// the empty column of a new, empty section.
				var column = null;
				var first = null;
				walk( doc.blocks, function ( block, parent ) {
					if ( ! column && block.name === 'core/column' ) { column = block; }
					if ( ! first && parent && parent.name === 'core/column' ) { first = block.name === 'core/buttons' && block.innerBlocks[ 0 ] ? block.innerBlocks[ 0 ] : block; }
				} );
				var pick = first || column;
				if ( pick ) { select( pick.clientId ); }
				SE.tellPreview( { type: 'builder-scroll', section: doc.id } );
				if ( first && /paragraph|heading/.test( first.name ) && editables( first ).length ) {
					SE.tellPreview( { type: 'builder-edit-text', id: first.clientId } );
				}
				if ( then ) { then( doc ); }
			};
			SE.reloadPreviewSoon();
		} );
	}

	// "+ เพิ่มส่วนเนื้อหา" on the page: build it here rather than in a form.
	SE.newSection = function ( value ) {
		var query = new URLSearchParams( value.slice( value.indexOf( '?' ) + 1 ) );
		var slot = { page: query.get( 'id5_page' ) || '/', position: query.get( 'id5_position' ) || 'bottom' };

		createSection( slot, null, function () { openAdd(); } );
		return true;
	};

	// ── Selecting and targeting ─────────────────────────────────────────

	function select( clientId ) {
		endTyping();
		var at = clientId ? locate( clientId ) : null;
		selected = at ? clientId : null;
		if ( at ) { selectedDoc = at.doc.id; }
		view = at ? 'custom' : ( view === 'custom' ? '' : view );
		// With "ADD CONTENT" open, what is clicked on the page is where
		// the next thing goes.
		if ( addOpen && addMode === 'list' ) { target = defaultTarget(); }
		highlight();
		refresh();
	}

	function selectSection( id ) {
		endTyping();
		selected = null;
		selectedDoc = docs[ id ] ? id : null;
		view = selectedDoc ? 'section' : '';
		highlight();
		refresh();
	}

	// Where new content goes when nothing more precise was chosen.
	function defaultTarget() {
		var at = selected ? locate( selected ) : null;

		if ( at ) {
			if ( at.block.name === 'core/column' ) {
				return { doc: at.doc.id, parent: at.block.clientId, index: at.block.innerBlocks.length };
			}
			// A button or a list line: add after the group it belongs to.
			while ( at.parent && ( kindOf( at.block ) === 'child' || at.block.name === 'core/button' || at.block.name === 'core/column' ) ) {
				at = locate( at.parent.clientId );
			}
			if ( at.block.name === 'core/column' ) {
				return { doc: at.doc.id, parent: at.block.clientId, index: at.block.innerBlocks.length };
			}
			return { doc: at.doc.id, parent: at.parent ? at.parent.clientId : null, index: at.index + 1 };
		}

		var id = selectedDoc && docs[ selectedDoc ] ? selectedDoc : onPage.filter( function ( i ) { return docs[ i ]; } ).slice( -1 )[ 0 ];
		return id ? { doc: id, parent: null, index: docs[ id ].blocks.length } : null;
	}

	function describeTarget( t ) {
		if ( ! t || ! docs[ t.doc ] ) {
			return slots.length ? 'ส่วนเนื้อหาใหม่ท้ายหน้านี้' : 'หน้านี้เพิ่มเนื้อหาไม่ได้';
		}
		if ( ! t.parent ) { return 'แถวใหม่ใน "' + docs[ t.doc ].title + '"'; }
		var at = locate( t.parent );
		return at ? labelOf( at.block ) + ( at.block.name === 'core/column' ? 'ที่ ' + ( at.index + 1 ) : '' ) + ' ที่เลือก' : '';
	}

	/**
	 * Put new blocks at a target. Content placed straight into a section
	 * (not inside a column) gets a row of one column around it, so every
	 * section stays made of rows and columns.
	 */
	function insert( t, blocks, then ) {
		closeAdd();
		if ( ! t || ! docs[ t.doc ] ) {
			var slot = slots.filter( function ( s ) { return s.position === 'bottom'; } )[ 0 ] || slots[ 0 ];
			if ( ! slot ) {
				window.alert( 'หน้านี้ยังเพิ่มเนื้อหาแบบลากวางไม่ได้ ลองเปิดหน้าอื่นของเว็บไซต์' );
				return;
			}
			createSection( slot, wrapForRoot( blocks ), null );
			return;
		}

		var doc = docs[ t.doc ];
		var into = childrenOf( doc, t.parent );
		if ( ! into ) { return; }

		var parentBlock = t.parent ? locate( t.parent ).block : null;
		var placed = parentBlock ? blocks : wrapForRoot( blocks );

		edit( doc, function () {
			Array.prototype.splice.apply( into, [ Math.min( t.index, into.length ), 0 ].concat( placed ) );
		} );

		var first = placed[ 0 ];
		if ( first ) {
			// Select what was just added, the thing itself rather than its row.
			var pick = first;
			if ( ! parentBlock && first.name === 'core/columns' && blocks[ 0 ] && blocks[ 0 ].name !== 'core/columns' ) { pick = blocks[ 0 ]; }
			if ( pick.name === 'core/columns' && pick.innerBlocks[ 0 ] ) { pick = pick.innerBlocks[ 0 ]; }
			if ( pick.name === 'core/buttons' && pick.innerBlocks[ 0 ] ) { pick = pick.innerBlocks[ 0 ]; }
			select( pick.clientId );
			SE.tellPreview( { type: 'builder-scroll', id: pick.clientId } );
			if ( editables( pick ).length && /paragraph|heading/.test( pick.name ) ) {
				SE.tellPreview( { type: 'builder-edit-text', id: pick.clientId } );
			}
		}
		if ( then ) { then(); }
	}

	function wrapForRoot( blocks ) {
		var out = [];
		var loose = [];

		function flush() {
			if ( loose.length ) { out.push( make( 'core/columns', {}, [ make( 'core/column', {}, loose ) ] ) ); loose = []; }
		}

		blocks.forEach( function ( block ) {
			if ( block.name === 'core/columns' ) { flush(); out.push( block ); } else { loose.push( block ); }
		} );
		flush();

		return out;
	}

	// ── The things that can be added ────────────────────────────────────

	function media( type, multiple, done ) {
		if ( ! window.wp || ! wp.media ) {
			window.alert( 'เปิดคลังสื่อไม่ได้' );
			return;
		}

		var frame = wp.media( {
			title: type === 'image' ? ( multiple ? 'เลือกรูปภาพ (เลือกได้หลายรูป)' : 'เลือกรูปภาพ' ) : 'เลือกไฟล์ PDF',
			button: { text: type === 'image' ? 'ใช้รูปนี้' : 'ใช้ไฟล์นี้' },
			library: { type: type === 'image' ? 'image' : 'application/pdf' },
			multiple: multiple ? 'add' : false
		} );

		frame.on( 'select', function () {
			var chosen = frame.state().get( 'selection' ).toJSON();
			if ( chosen.length ) { done( chosen ); }
		} );
		frame.open();
	}

	function imageAttrs( file ) {
		var large = file.sizes && ( file.sizes.large || file.sizes.full );
		return { id: file.id, url: large ? large.url : file.url, alt: file.alt || '', sizeSlug: large && file.sizes.large ? 'large' : 'full', linkDestination: 'none' };
	}

	function image( file, extra ) {
		return make( 'core/image', Object.assign( imageAttrs( file ), extra || {} ) );
	}

	function pdf( file ) {
		return make( 'core/file', {
			id: file.id,
			href: file.url,
			fileId: 'wp-block-file--media-' + uid(),
			fileName: rich( esc( file.title || file.filename || 'เอกสาร PDF' ) ),
			textLinkHref: file.url,
			showDownloadButton: true,
			downloadButtonText: rich( 'ดาวน์โหลด' ),
			displayPreview: true,
			previewHeight: 600
		} );
	}

	function youtube( url ) {
		var m = String( url ).match( /(?:youtube\.com\/(?:watch\?(?:.*&)?v=|embed\/|shorts\/|live\/)|youtu\.be\/)([A-Za-z0-9_-]{11})/ );
		return m ? 'https://www.youtube.com/watch?v=' + m[ 1 ] : '';
	}

	function video( url ) {
		return make( 'core/embed', { url: url, type: 'video', providerNameSlug: 'youtube', responsive: true, className: 'wp-embed-aspect-16-9 wp-has-aspect-ratio' } );
	}

	function map( place ) {
		return text( esc( place ), { className: 'id5-map id5-map-m' } );
	}

	function tab( title ) {
		return make( 'core/group', { className: 'id5-tab' }, [ heading( title, 3 ), text( 'เนื้อหาของ' + title + ' คลิกเพื่อแก้ไข' ) ] );
	}

	function fold( title, inner ) {
		return make( 'core/details', { summary: rich( title ) }, inner || [ text( 'รายละเอียดของ' + title + ' คลิกเพื่อแก้ไข' ) ] );
	}

	function menuList( extra ) {
		return list( [ link( 'หน้าแรก', '/' ), link( 'บริการ', '/services' ), link( 'ข่าวสาร', '/news' ), link( 'ติดต่อเรา', '/contact' ) ], { className: 'id5-vmenu' + ( extra ? ' ' + extra : '' ) } );
	}

	function card( title, price, note, featured ) {
		return make( 'core/group', { className: 'id5-card' + ( featured ? ' id5-card--featured' : '' ) }, [
			heading( title, 3 ),
			text( price, { className: 'id5-price' } ),
			text( note, { fontSize: 'id5-small' } ),
			list( [ 'รายละเอียดข้อที่ 1', 'รายละเอียดข้อที่ 2', 'รายละเอียดข้อที่ 3' ] ),
			buttons( 'ดูรายละเอียด', '#' )
		] );
	}

	function ask( title, label, hint, check, done ) {
		askSpec = { title: title, label: label, hint: hint, check: check, done: done };
		addOpen = true;
		addMode = 'ask';
		drawFlyout();
	}

	var askSpec = null;

	var ICONS = [
		[ 'phone', 'โทรศัพท์' ], [ 'mail', 'อีเมล' ], [ 'map-pin', 'สถานที่' ], [ 'clock', 'เวลา' ], [ 'calendar', 'ปฏิทิน' ],
		[ 'file-text', 'เอกสาร' ], [ 'file-download', 'ดาวน์โหลด' ], [ 'info-circle', 'ข้อมูล' ], [ 'alert-triangle', 'คำเตือน' ], [ 'circle-check', 'สำเร็จ' ],
		[ 'id', 'บัตร' ], [ 'passport', 'หนังสือเดินทาง' ], [ 'plane', 'เครื่องบิน' ], [ 'world', 'ต่างประเทศ' ], [ 'building', 'อาคาร' ],
		[ 'users', 'บุคคล' ], [ 'user', 'ผู้ใช้' ], [ 'shield-check', 'ความปลอดภัย' ], [ 'search', 'ค้นหา' ], [ 'home', 'หน้าแรก' ],
		[ 'star', 'ดาว' ], [ 'heart', 'หัวใจ' ], [ 'bell', 'แจ้งเตือน' ], [ 'speakerphone', 'ประกาศ' ], [ 'message-circle', 'ข้อความ' ],
		[ 'device-mobile', 'มือถือ' ], [ 'car', 'รถยนต์' ], [ 'ship', 'เรือ' ], [ 'link', 'ลิงก์' ], [ 'download', 'ดาวน์โหลด' ],
		[ 'brand-facebook', 'Facebook' ], [ 'brand-line', 'LINE' ], [ 'brand-youtube', 'YouTube' ], [ 'brand-x', 'X' ], [ 'arrow-right', 'ลูกศร' ]
	];

	// Each tile: key, label, dashicon, words to search by, and how to make it.
	var TILES = {
		basic: [
			{ k: 'columns', l: 'Columns', th: 'คอลัมน์ (แถว)', i: 'columns', w: 'columns row columns layout grid แถว คอลัมน์', row: true, make: function ( done ) { pickLayout( done ); } },
			{ k: 'text', l: 'Text', th: 'ข้อความ', i: 'editor-alignleft', w: 'text text paragraph ข้อความ ย่อหน้า', make: function ( done ) { done( [ text( 'ข้อความใหม่ คลิกเพื่อพิมพ์' ) ] ); } },
			{ k: 'heading', l: 'Heading', th: 'หัวข้อ', i: 'heading', w: 'heading heading title หัวข้อ', make: function ( done ) { done( [ heading( 'หัวข้อใหม่', 2 ) ] ); } },
			{ k: 'image', l: 'Image', th: 'รูปภาพ', i: 'format-image', w: 'image image picture photo รูป ภาพ', make: function ( done ) { media( 'image', false, function ( files ) { done( [ image( files[ 0 ] ) ] ); } ); } },
			{ k: 'button', l: 'Button', th: 'ปุ่ม', i: 'button', w: 'button button ปุ่ม', make: function ( done ) { done( [ buttons( 'ปุ่มใหม่', '#' ) ] ); } },
			{ k: 'link', l: 'Link', th: 'ลิงก์', i: 'admin-links', w: 'link link url ลิงก์', make: function ( done ) { done( [ text( link( 'ลิงก์ใหม่', '#' ), { className: 'id5-link' } ) ] ); } },
			{ k: 'icon', l: 'Icon', th: 'ไอคอน', i: 'smiley', w: 'icon icon ไอคอน สัญลักษณ์', make: function ( done ) { done( [ text( '', { className: 'id5-icon id5-icon-l ti ti-star', align: 'center', textColor: 'id5-blue' } ) ] ); } },
			{ k: 'spacer', l: 'Spacer', th: 'ระยะห่าง', i: 'sort', w: 'spacer spacer space ระยะ ช่องว่าง', make: function ( done ) { done( [ make( 'core/spacer', { height: '40px' } ) ] ); } },
			{ k: 'divider', l: 'Divider', th: 'เส้นคั่น', i: 'minus', w: 'divider divider separator line เส้น', make: function ( done ) { done( [ make( 'core/separator', { className: 'is-style-wide' } ) ] ); } },
			{ k: 'video', l: 'Video', th: 'วิดีโอ YouTube', i: 'video-alt2', w: 'video video youtube วิดีโอ คลิป', make: function ( done ) {
				ask( 'เพิ่มวิดีโอ', 'ลิงก์วิดีโอ YouTube', 'เช่น https://www.youtube.com/watch?v=… หรือ https://youtu.be/…', function ( v ) { return youtube( v ) ? '' : 'ต้องเป็นลิงก์ YouTube'; }, function ( v ) { done( [ video( youtube( v ) ) ] ); } );
			} },
			{ k: 'pdf', l: 'PDF', th: 'ไฟล์ PDF', i: 'pdf', w: 'pdf pdf file document ไฟล์ เอกสาร', make: function ( done ) { media( 'pdf', false, function ( files ) { done( [ pdf( files[ 0 ] ) ] ); } ); } },
			{ k: 'list', l: 'List', th: 'รายการ', i: 'editor-ul', w: 'list list bullet รายการ', make: function ( done ) { done( [ list( [ 'รายการที่ 1', 'รายการที่ 2', 'รายการที่ 3' ] ) ] ); } },
			{ k: 'table', l: 'Table', th: 'ตาราง', i: 'editor-table', w: 'table table ตาราง', make: function ( done ) { done( [ table( 3, 3, true ) ] ); } },
			{ k: 'quote', l: 'Quote', th: 'คำพูด', i: 'format-quote', w: 'quote quote คำพูด อ้างอิง', make: function ( done ) { done( [ make( 'core/quote', { citation: rich( 'ผู้กล่าว' ) }, [ text( 'ข้อความที่ยกมา' ) ] ) ] ); } },
			{ k: 'box', l: 'Box', th: 'กล่อง', i: 'screenoptions', w: 'box box group container กล่อง', make: function ( done ) { done( [ make( 'core/group', { className: 'id5-card' }, [ heading( 'หัวข้อกล่อง', 3 ), text( 'ข้อความในกล่อง' ) ] ) ] ); } }
		],
		components: [
			{ k: 'tabs', l: 'Tabs', th: 'แท็บ', i: 'index-card', w: 'tabs tabs แท็บ', make: function ( done ) { done( [ make( 'core/group', { className: 'id5-tabs' }, [ tab( 'แท็บที่ 1' ), tab( 'แท็บที่ 2' ), tab( 'แท็บที่ 3' ) ] ) ] ); } },
			{ k: 'carousel', l: 'Carousel', th: 'ภาพหมุน', i: 'images-alt2', w: 'carousel carousel ภาพหมุน รูป', make: function ( done ) { media( 'image', true, function ( files ) { done( [ make( 'core/group', { className: 'id5-carousel' }, files.map( function ( f ) { return image( f ); } ) ) ] ); } ); } },
			{ k: 'slider', l: 'Slider', th: 'สไลด์ภาพ', i: 'slides', w: 'slider slider slideshow สไลด์ รูป', make: function ( done ) { media( 'image', true, function ( files ) { done( [ make( 'core/group', { className: 'id5-slider id5-autoplay' }, files.map( function ( f ) { return image( f ); } ) ) ] ); } ); } },
			{ k: 'pricing', l: 'Pricing', th: 'การ์ดราคา', i: 'tag', w: 'pricing pricing price ราคา ค่าธรรมเนียม การ์ด', make: function ( done ) {
				done( [ make( 'core/columns', { className: 'id5-equal-height' }, [
					make( 'core/column', {}, [ card( 'แบบที่ 1', '฿500', 'ต่อครั้ง' ) ] ),
					make( 'core/column', {}, [ card( 'แบบที่ 2', '฿1,000', 'ต่อครั้ง', true ) ] ),
					make( 'core/column', {}, [ card( 'แบบที่ 3', '฿1,900', 'ต่อครั้ง' ) ] )
				] ) ] );
			} },
			{ k: 'pricetable', l: 'Pricing Table', th: 'ตารางราคา', i: 'editor-table', w: 'pricing table pricing table ตารางราคา ค่าธรรมเนียม', make: function ( done ) {
				done( [ table( 4, 3, true, [ [ 'รายการ', 'ค่าธรรมเนียม', 'หมายเหตุ' ], [ 'บริการที่ 1', '฿500', '' ], [ 'บริการที่ 2', '฿1,000', '' ], [ 'บริการที่ 3', '฿1,900', '' ] ] ) ] );
			} },
			{ k: 'collage', l: 'Image Collage', th: 'ภาพคอลลาจ', i: 'format-gallery', w: 'image collage image collage gallery คอลลาจ แกลเลอรี รูป', make: function ( done ) { media( 'image', true, function ( files ) { done( [ make( 'core/group', { className: 'id5-collage' }, files.map( function ( f ) { return image( f ); } ) ) ] ); } ); } },
			{ k: 'accordion', l: 'Accordion', th: 'แอคคอร์เดียน', i: 'list-view', w: 'accordion accordion faq แอคคอร์เดียน พับ', make: function ( done ) { done( [ make( 'core/group', { className: 'id5-accordion' }, [ fold( 'หัวข้อที่ 1' ), fold( 'หัวข้อที่ 2' ), fold( 'หัวข้อที่ 3' ) ] ) ] ); } },
			{ k: 'accordionmenu', l: 'Accordion menu', th: 'เมนูแบบพับ', i: 'menu-alt3', w: 'accordion menu accordion menu เมนู พับ', make: function ( done ) { done( [ make( 'core/group', { className: 'id5-accordion-menu' }, [ fold( 'เมนูที่ 1', [ menuList() ] ), fold( 'เมนูที่ 2', [ menuList() ] ) ] ) ] ); } },
			{ k: 'dropdown', l: 'Dropdown menu', th: 'เมนูดรอปดาวน์', i: 'arrow-down-alt2', w: 'dropdown menu dropdown menu เมนู ดรอปดาวน์', make: function ( done ) { done( [ make( 'core/details', { className: 'id5-dropdown', summary: rich( 'เมนู' ) }, [ menuList() ] ) ] ); } },
			{ k: 'vmenu', l: 'Vertical menu', th: 'เมนูแนวตั้ง', i: 'menu', w: 'vertical menu vertical menu เมนู แนวตั้ง', make: function ( done ) { done( [ menuList() ] ); } },
			{ k: 'map', l: 'Map', th: 'แผนที่', i: 'location', w: 'map map google แผนที่ ที่ตั้ง', make: function ( done ) {
				ask( 'เพิ่มแผนที่', 'สถานที่หรือที่อยู่', 'เช่น "สำนักงานตรวจคนเข้าเมือง แจ้งวัฒนะ" ระบบจะแสดงแผนที่ Google ของสถานที่นี้', function ( v ) { return v.trim() ? '' : 'กรุณาพิมพ์สถานที่'; }, function ( v ) { done( [ map( v.trim() ) ] ); } );
			} },
			{ k: 'social', l: 'Social Icons', th: 'ไอคอนโซเชียล', i: 'thumbs-up', w: 'social icons social icons facebook line youtube โซเชียล', make: function ( done ) {
				done( [ list( [ link( 'Facebook', 'https://www.facebook.com/' ), link( 'LINE', 'https://line.me/' ), link( 'YouTube', 'https://www.youtube.com/' ), link( 'X', 'https://x.com/' ) ], { className: 'id5-social' } ) ] );
			} },
		],
		sections: [
			{ k: 'imagetext', l: 'Image + Text', th: 'รูปคู่ข้อความ', i: 'align-pull-left', w: 'image + text image text รูป ข้อความ', make: function ( done ) {
				media( 'image', false, function ( files ) {
					done( [ make( 'core/columns', { verticalAlignment: 'center' }, [
						make( 'core/column', {}, [ image( files[ 0 ], { className: 'id5-rounded' } ) ] ),
						make( 'core/column', {}, [ heading( 'หัวข้อ', 2 ), text( 'คำอธิบาย คลิกเพื่อแก้ไข' ), buttons( 'อ่านต่อ', '#' ) ] )
					] ) ] );
				} );
			} },
			{ k: 'textimage', l: 'Text + Image', th: 'ข้อความคู่รูป', i: 'align-pull-right', w: 'text image ข้อความ รูป', make: function ( done ) {
				media( 'image', false, function ( files ) {
					done( [ make( 'core/columns', { verticalAlignment: 'center' }, [
						make( 'core/column', {}, [ heading( 'หัวข้อ', 2 ), text( 'คำอธิบาย คลิกเพื่อแก้ไข' ), buttons( 'อ่านต่อ', '#' ) ] ),
						make( 'core/column', {}, [ image( files[ 0 ], { className: 'id5-rounded' } ) ] )
					] ) ] );
				} );
			} },
			{ k: 'cards', l: '3 Cards', th: 'การ์ด 3 ใบ', i: 'grid-view', w: '3 cards cards features การ์ด', make: function ( done ) {
				done( [ make( 'core/columns', { className: 'id5-equal-height' }, [ 1, 2, 3 ].map( function ( n ) {
					return make( 'core/column', {}, [ make( 'core/group', { className: 'id5-card' }, [ text( '', { className: 'id5-icon id5-icon-m ti ti-' + [ 'id', 'file-text', 'clock' ][ n - 1 ], textColor: 'id5-blue' } ), heading( 'หัวข้อที่ ' + n, 3 ), text( 'คำอธิบายสั้น ๆ' ) ] ) ] );
				} ) ) ] );
			} },
			{ k: 'cta', l: 'Call to action', th: 'แถบเชิญชวน', i: 'megaphone', w: 'call to action cta call to action banner เชิญชวน', make: function ( done ) {
				done( [ make( 'core/group', { className: 'id5-pad-l id5-rounded', backgroundColor: 'id5-blue', textColor: 'id5-white' }, [ heading( 'หัวข้อเชิญชวน', 2, { textAlign: 'center', textColor: 'id5-white' } ), text( 'ข้อความสั้น ๆ', { align: 'center' } ), make( 'core/buttons', { className: 'id5-justify-center' }, [ make( 'core/button', { text: rich( 'ติดต่อเรา' ), url: '/contact', backgroundColor: 'id5-gold' } ) ] ) ] ) ] );
			} },
			{ k: 'pdfs', l: 'PDF downloads', th: 'รายการไฟล์ PDF', i: 'media-document', w: 'pdf downloads pdf files documents ไฟล์ เอกสาร หลายไฟล์', make: function ( done ) { media( 'pdf', true, function ( files ) { done( files.map( function ( f ) { var b = pdf( f ); b.attributes.displayPreview = false; return b; } ) ); } ); } }
		]
	};

	function table( rows, cols, head, cells ) {
		function cell( r, c, tag ) {
			return { content: rich( cells && cells[ r ] ? esc( cells[ r ][ c ] ) : ( tag === 'th' ? 'หัวข้อ ' + ( c + 1 ) : '' ) ), tag: tag };
		}
		var attrs = { className: 'is-style-stripes', body: [] };
		var start = 0;
		if ( head ) {
			attrs.head = [ { cells: Array.apply( null, Array( cols ) ).map( function ( x, c ) { return cell( 0, c, 'th' ); } ) } ];
			start = 1;
		}
		for ( var r = start; r < rows; r++ ) {
			attrs.body.push( { cells: Array.apply( null, Array( cols ) ).map( function ( x, c ) { return cell( r, c, 'td' ); } ) } );
		}
		return make( 'core/table', attrs );
	}

	function allTiles() {
		return TILES.basic.concat( TILES.components, TILES.sections );
	}

	function tileByKey( key ) {
		return allTiles().filter( function ( t ) { return t.k === key; } )[ 0 ];
	}

	function useTile( tile, at ) {
		var t = at || target || defaultTarget();
		tile.make( function ( blocks ) {
			addMode = 'list';
			insert( t, blocks );
		} );
	}

	// ── Row layouts ─────────────────────────────────────────────────────

	var LAYOUTS = [
		{ l: '1 คอลัมน์', w: [ null ] },
		{ l: '2 คอลัมน์', w: [ null, null ] },
		{ l: '3 คอลัมน์', w: [ null, null, null ] },
		{ l: '4 คอลัมน์', w: [ null, null, null, null ] },
		{ l: '1/3 + 2/3', w: [ '33.33%', '66.66%' ] },
		{ l: '2/3 + 1/3', w: [ '66.66%', '33.33%' ] },
		{ l: '1/4 + 3/4', w: [ '25%', '75%' ] },
		{ l: '3/4 + 1/4', w: [ '75%', '25%' ] },
		{ l: '1/4 + 1/2 + 1/4', w: [ '25%', '50%', '25%' ] },
		{ l: '5 คอลัมน์', w: [ null, null, null, null, null ] },
		{ l: '6 คอลัมน์', w: [ null, null, null, null, null, null ] }
	];

	var layoutDone = null;

	function pickLayout( done ) {
		layoutDone = done;
		addOpen = true;
		addMode = 'layout';
		drawFlyout();
	}

	function layoutPreview( widths ) {
		var box = h( 'span', 'id5-sb__layout-preview' );
		widths.forEach( function ( w ) {
			var col = h( 'span' );
			col.style.flex = w ? '0 0 calc(' + w + ' - 3px)' : '1';
			box.appendChild( col );
		} );
		return box;
	}

	// ── Operations on blocks (the toolbar and menu actions) ─────────────

	function duplicate( clientId ) {
		var at = locate( clientId );
		if ( ! at ) { return; }
		var copy = wpb.cloneBlock( at.block );
		edit( at.doc, function () { at.list.splice( at.index + 1, 0, copy ); } );
		select( copy.clientId );
	}

	function remove( clientId ) {
		var at = locate( clientId );
		if ( ! at ) { return; }

		edit( at.doc, function () {
			at.list.splice( at.index, 1 );
			// An empty row, button group or list goes with its last child.
			var parent = at.parent ? locate( at.parent.clientId ) : null;
			if ( parent && ! parent.block.innerBlocks.length && /core\/(columns|buttons|list)$/.test( parent.block.name ) ) {
				parent.list.splice( parent.index, 1 );
			}
		} );
		if ( selected === clientId ) { select( null ); view = selectedDoc ? 'section' : ''; refresh(); }
	}

	function shift( clientId, step ) {
		var at = locate( clientId );
		if ( ! at ) { return; }
		var to = at.index + step;
		if ( to < 0 || to >= at.list.length ) { return; }
		edit( at.doc, function () {
			at.list.splice( at.index, 1 );
			at.list.splice( to, 0, at.block );
		} );
	}

	function addColumn( rowId ) {
		var at = locate( rowId );
		if ( ! at || at.block.innerBlocks.length >= 6 ) {
			if ( at ) { window.alert( 'แถวหนึ่งมีได้ไม่เกิน 6 คอลัมน์' ); }
			return;
		}
		var column = make( 'core/column' );
		edit( at.doc, function () {
			// Fixed widths would no longer add up: share the row equally again.
			at.block.innerBlocks.forEach( function ( c ) { setAttr( c, 'width', null ); } );
			at.block.innerBlocks.push( column );
		} );
		select( column.clientId );
	}

	function removeColumn( rowId ) {
		var at = locate( rowId );
		if ( ! at ) { return; }
		var last = at.block.innerBlocks[ at.block.innerBlocks.length - 1 ];
		if ( ! last ) { return; }
		if ( last.innerBlocks.length && ! window.confirm( 'คอลัมน์สุดท้ายมีเนื้อหาอยู่ ลบคอลัมน์นี้พร้อมเนื้อหา?' ) ) { return; }
		if ( at.block.innerBlocks.length === 1 ) {
			remove( rowId );
			return;
		}
		edit( at.doc, function () {
			at.block.innerBlocks.pop();
			at.block.innerBlocks.forEach( function ( c ) { setAttr( c, 'width', null ); } );
		} );
	}

	function addRow( clientId, after, widths ) {
		var at = locate( clientId );
		if ( ! at ) { return; }
		var fresh = row( widths );
		edit( at.doc, function () { at.list.splice( at.index + ( after ? 1 : 0 ), 0, fresh ); } );
		select( fresh.innerBlocks[ 0 ].clientId );
	}

	var STYLE = /^(id5-(pad|gap|mb|rounded|shadow|border|equal-height|text-|justify-)|is-style-)/;

	function copyStyle( clientId ) {
		var at = locate( clientId );
		if ( ! at ) { return; }
		var a = at.block.attributes;
		copiedStyle = { classes: classes( at.block ).filter( function ( c ) { return STYLE.test( c ); } ), backgroundColor: a.backgroundColor, textColor: a.textColor, fontSize: a.fontSize, verticalAlignment: a.verticalAlignment };
		SE.setState( 'คัดลอกสไตล์แล้ว', false );
		refresh();
	}

	function pasteStyle( clientId ) {
		var at = locate( clientId );
		if ( ! at || ! copiedStyle ) { return; }
		edit( at.doc, function () {
			var keep = classes( at.block ).filter( function ( c ) { return ! STYLE.test( c ); } );
			setAttr( at.block, 'className', keep.concat( copiedStyle.classes ).join( ' ' ) );
			[ 'backgroundColor', 'textColor', 'fontSize', 'verticalAlignment' ].forEach( function ( name ) {
				if ( supports( at.block, name ) ) { setAttr( at.block, name, copiedStyle[ name ] ); }
			} );
		} );
	}

	function move( clientId, intoId, docId, index ) {
		var from = locate( clientId );
		var doc = docs[ docId ];
		if ( ! from || ! doc ) { return; }

		// Not into itself or anything inside it.
		if ( intoId && path( intoId ).some( function ( b ) { return b.clientId === clientId; } ) ) { return; }

		var block = from.block;
		var isRow = block.name === 'core/columns';
		var into = childrenOf( doc, intoId );
		if ( ! into ) { return; }

		change( [ from.doc, doc ], function () {
			var same = into === from.list;
			from.list.splice( from.index, 1 );
			var at = same && index > from.index ? index - 1 : index;
			var placed = ! intoId && ! isRow ? wrapForRoot( [ block ] ) : [ block ];
			Array.prototype.splice.apply( into, [ Math.min( at, into.length ), 0 ].concat( placed ) );

			var parent = from.parent ? locate( from.parent.clientId ) : null;
			if ( parent && ! parent.block.innerBlocks.length && /core\/(buttons|list)$/.test( parent.block.name ) ) {
				parent.list.splice( parent.index, 1 );
			}
		} );
		select( clientId );
	}

	function setWidths( rowId, widths ) {
		var at = locate( rowId );
		if ( ! at ) { return; }
		edit( at.doc, function () {
			at.block.innerBlocks.forEach( function ( column, i ) {
				if ( widths[ i ] ) { setAttr( column, 'width', Number( widths[ i ] ).toFixed( 2 ).replace( /\.?0+$/, '' ) + '%' ); }
			} );
		} );
	}

	// ── Text typed on the page ──────────────────────────────────────────

	function typed( data ) {
		var at = locate( data.id );
		if ( ! at ) { return; }
		startTyping( at.doc );
		var html = cleanInline( data.html );
		var b = at.block;

		if ( data.a === '@cell' ) {
			var section = b.attributes[ data.sec ];
			if ( section && section[ data.r ] && section[ data.r ].cells[ data.c ] ) {
				section[ data.r ].cells[ data.c ].content = rich( html );
				setAttr( b, data.sec, section.slice() );
			}
		} else if ( data.a === 'content' && hasClass( b, 'id5-link' ) ) {
			// A link line keeps being one link.
			var href = ( str( b.attributes.content ).match( /href="([^"]*)"/ ) || [] )[ 1 ] || '#';
			setAttr( b, 'content', rich( link( plain( html ), href.replace( /&amp;/g, '&' ) ) ) );
		} else if ( /^(content|text|summary|caption|citation|fileName|downloadButtonText)$/.test( data.a ) ) {
			setAttr( b, data.a, rich( html ) );
		}

		refreshButtons();
	}

	// ── Messages from the preview ───────────────────────────────────────

	window.addEventListener( 'message', function ( event ) {
		var data = event.data;

		if ( event.origin !== CFG.siteOrigin || ! data || data.source !== 'id5-cms' || String( data.type ).indexOf( 'builder-' ) !== 0 ) {
			return;
		}

		switch ( data.type ) {
			case 'builder-sections':
				slots = Array.isArray( data.slots ) ? data.slots : [];
				loadSections( Array.isArray( data.sections ) ? data.sections : [] );
				break;
			case 'builder-select':
				if ( data.section && ! data.id ) { selectSection( Number( data.section ) ); } else { select( data.id || null ); }
				break;
			case 'builder-text':
			case 'builder-cell':
				typed( data );
				break;
			case 'builder-text-end':
				endTyping();
				break;
			case 'builder-action':
				act( data.act, data.id, data );
				break;
			case 'builder-move':
				move( data.id, data.into || null, Number( data.root ), Number( data.index ) );
				break;
			case 'builder-drop-tile':
				if ( dragTile ) {
					var tile = dragTile;
					dragTile = null;
					useTile( tile, { doc: Number( data.root ), parent: data.into || null, index: Number( data.index ) } );
				}
				break;
			case 'builder-widths':
				setWidths( data.id, data.widths || [] );
				break;
			case 'builder-key':
				if ( data.key === 'undo' ) { undo(); } else if ( data.key === 'redo' ) { redo(); } else if ( data.key === 'delete' && selected ) { remove( selected ); } else if ( data.key === 'escape' ) { select( null ); }
				break;
		}
	} );

	function act( name, clientId, data ) {
		var at = clientId ? locate( clientId ) : null;

		switch ( name ) {
			case 'select': select( clientId ); break;
			case 'edit-row':
				tabFor.row = 'layout';
				select( clientId );
				break;
			case 'settings': select( clientId ); break;
			case 'duplicate': duplicate( clientId ); break;
			case 'delete': remove( clientId ); break;
			case 'up': shift( clientId, -1 ); break;
			case 'down': shift( clientId, 1 ); break;
			case 'col-left': shift( clientId, -1 ); break;
			case 'col-right': shift( clientId, 1 ); break;
			case 'add-column': addColumn( clientId ); break;
			case 'remove-column': removeColumn( clientId ); break;
			case 'row-above': pickLayout( function ( blocks ) { addRow( clientId, false, layoutWidths( blocks ) ); } ); break;
			case 'row-below': pickLayout( function ( blocks ) { addRow( clientId, true, layoutWidths( blocks ) ); } ); break;
			case 'copy-style': copyStyle( clientId ); break;
			case 'paste-style': pasteStyle( clientId ); break;
			case 'add-into':
				if ( at ) { selected = clientId; selectedDoc = at.doc.id; highlight(); openAdd( { doc: at.doc.id, parent: clientId, index: at.block.innerBlocks.length } ); }
				break;
			case 'add-after':
				if ( at ) { selected = clientId; selectedDoc = at.doc.id; highlight(); openAdd( { doc: at.doc.id, parent: at.parent ? at.parent.clientId : null, index: at.index + 1 } ); }
				break;
			case 'add-row':
				var doc = docs[ Number( data.root ) ];
				if ( doc ) {
					selectedDoc = doc.id;
					pickLayout( function ( blocks ) { insert( { doc: doc.id, parent: null, index: doc.blocks.length }, blocks ); } );
				}
				break;
			case 'add-content':
				var d = docs[ Number( data.root ) ];
				if ( d ) { selectedDoc = d.id; openAdd( { doc: d.id, parent: null, index: d.blocks.length } ); }
				break;
			case 'section': selectSection( Number( data.root ) ); break;
			case 'section-settings': sectionSettings( Number( data.root ) ); break;
			case 'section-up': moveSection( Number( data.root ), 'up' ); break;
			case 'section-down': moveSection( Number( data.root ), 'down' ); break;
			case 'section-delete': deleteSection( Number( data.root ) ); break;
			case 'li-enter':
				if ( at && at.block.name === 'core/list-item' ) {
					var item = make( 'core/list-item', { content: rich( '' ) } );
					edit( at.doc, function () { at.list.splice( at.index + 1, 0, item ); } );
					SE.tellPreview( { type: 'builder-edit-text', id: item.clientId } );
				}
				break;
		}
	}

	function layoutWidths( blocks ) {
		return blocks[ 0 ].innerBlocks.map( function ( c ) { return c.attributes.width || null; } );
	}

	// ── Whole sections ──────────────────────────────────────────────────

	function saveThen( doc, fn ) {
		if ( ! isDirty( doc ) ) { fn(); return; }
		if ( ! window.confirm( 'ต้องเผยแพร่การแก้ไขของ "' + doc.title + '" ก่อน ดำเนินการต่อหรือไม่?' ) ) { return; }
		publish();
		var wait = setInterval( function () {
			if ( ! busy ) { clearInterval( wait ); if ( ! isDirty( doc ) ) { fn(); } }
		}, 300 );
	}

	function sectionSettings( id ) {
		var doc = docs[ id ];
		if ( ! doc ) { return; }
		saveThen( doc, function () {
			SE.openTarget( 'post:' + id, 'ตั้งค่าส่วนเนื้อหา: ' + doc.title );
		} );
	}

	function moveSection( id, step ) {
		var doc = docs[ id ];
		if ( ! doc || doc.type !== 'id5_section' ) { return; }
		saveThen( doc, function () {
			SE.setState( 'กำลังย้าย…', true );
			ajax( 'id5_builder_move', { id: id, step: step } ).then( function ( result ) {
				if ( result.success && result.data.moved ) {
					SE.tellPreview( { type: 'builder-scroll', section: id } );
					afterLoadOnce = function () { selectSection( id ); SE.tellPreview( { type: 'builder-scroll', section: id } ); };
					SE.reloadPreviewSoon();
				} else {
					SE.setState( 'เผยแพร่แล้ว', false );
				}
			} );
		} );
	}

	function deleteSection( id ) {
		var doc = docs[ id ];
		if ( ! doc ) { return; }
		if ( ! window.confirm( 'ลบส่วนเนื้อหา "' + doc.title + '" ทั้งส่วนออกจากเว็บไซต์?\n\nส่วนนี้จะถูกย้ายไปถังขยะ และกู้คืนได้ภายใน 30 วัน' ) ) { return; }
		SE.setState( 'กำลังลบ…', true );
		ajax( 'id5_item_delete', { target: 'post:' + id } ).then( function ( result ) {
			if ( ! result.success ) {
				SE.setState( 'ลบไม่สำเร็จ', false );
				window.alert( ( result.data && result.data.message ) || 'ลบไม่สำเร็จ' );
				return;
			}
			delete docs[ id ];
			undoStack = undoStack.map( function ( e ) { return e.filter( function ( x ) { return x.id !== id; } ); } ).filter( function ( e ) { return e.length; } );
			redoStack = [];
			selected = null;
			selectedDoc = null;
			view = '';
			SE.reloadPreviewSoon();
			refresh();
		} );
	}

	// ── The panel ───────────────────────────────────────────────────────

	function openAdd( t ) {
		target = t || defaultTarget();
		addOpen = true;
		addMode = 'list';
		drawFlyout();
		var input = flyout.querySelector( '.id5-sba__search input' );
		if ( input ) { input.focus(); }
	}

	function closeAdd() {
		addOpen = false;
		addMode = 'list';
		layoutDone = null;
		drawFlyout();
	}

	function closePanel() {
		endTyping();
		view = '';
		selected = null;
		target = null;
		highlight();
		refresh();
	}

	function refreshButtons() {
		var unsaved = Object.keys( docs ).filter( function ( id ) { return isDirty( docs[ id ] ); } ).length;
		publishButton.disabled = busy || ! unsaved;
		publishButton.textContent = unsaved ? 'เผยแพร่ (' + unsaved + ')' : 'เผยแพร่';
		undoButton.disabled = ! undoStack.length && ! typing;
		redoButton.disabled = ! redoStack.length;
		if ( unsaved && ! busy ) { SE.setState( 'ยังไม่ได้เผยแพร่', false ); }
	}

	function refresh() {
		refreshButtons();
		root.classList.toggle( 'is-building', !! view );
		panel.hidden = ! view;
		panel.textContent = '';
		launcher.hidden = ! onPage.length && ! slots.length;

		updateFoot();

		if ( ! view ) { return; }

		var bar = h( 'div', 'id5-sb__bar' );
		var back = h( 'button', 'id5-sb__back' );
		back.type = 'button';
		back.appendChild( icon( 'arrow-left-alt2' ) );
		back.appendChild( document.createTextNode( 'กลับ' ) );
		back.addEventListener( 'click', closePanel );
		bar.appendChild( back );

		if ( view ) {
			var addButton = h( 'button', 'button id5-sb__add-open' );
			addButton.type = 'button';
			addButton.appendChild( icon( 'plus-alt2' ) );
			addButton.appendChild( document.createTextNode( 'เพิ่มเนื้อหา' ) );
			addButton.addEventListener( 'click', function () { openAdd(); } );
			bar.appendChild( addButton );
		}
		panel.appendChild( bar );

		var body = h( 'div', 'id5-sb__body' );
		panel.appendChild( body );

		if ( view === 'section' ) { drawSection( body ); }
		if ( view === 'custom' ) {
			var at = selected ? locate( selected ) : null;
			if ( at ) { drawCustom( body, at ); } else { view = ''; refresh(); }
		}
	}

	// ── "ADD CONTENT": the fly-out beside the panel ─────────────────────
	// Laid out like the page builder the office knows (Colibri): two tabs,
	// a search box, and groups of things to click or drag onto the page.

	var flyout = h( 'div', 'id5-sba' );
	flyout.id = 'id5-sba';
	flyout.hidden = true;
	flyout.setAttribute( 'role', 'dialog' );
	flyout.setAttribute( 'aria-label', 'ADD CONTENT เพิ่มเนื้อหา' );
	root.appendChild( flyout );

	function updateFoot() {
		var foot = flyout.querySelector( '.id5-sba__foot' );
		if ( foot ) { foot.textContent = 'จะเพิ่มที่: ' + describeTarget( target ); }
	}

	function tileButton( tile ) {
		var b = h( 'button', 'id5-sba__item' );
		b.type = 'button';
		b.draggable = true;
		b.title = tile.th + ' — คลิกเพื่อเพิ่ม หรือลากไปวางบนหน้าเว็บ';
		b.setAttribute( 'data-tile', tile.k );
		b.appendChild( icon( tile.i ) );
		b.appendChild( h( 'span', '', tile.l ) );
		b.addEventListener( 'click', function () { useTile( tile ); } );
		b.addEventListener( 'dragstart', function ( event ) {
			dragTile = tile;
			event.dataTransfer.effectAllowed = 'copy';
			event.dataTransfer.setData( 'text/plain', 'id5:' + tile.k );
			SE.tellPreview( { type: 'builder-drag-start', row: !! tile.row, label: tile.l } );
			// Let the page under the fly-out take the drop too (after the
			// drag has begun: changing it during dragstart cancels the drag).
			setTimeout( function () { root.classList.add( 'is-dragging-tile' ); }, 0 );
		} );
		b.addEventListener( 'dragend', function () {
			root.classList.remove( 'is-dragging-tile' );
			SE.tellPreview( { type: 'builder-drag-end' } );
			setTimeout( function () { dragTile = null; }, 500 );
		} );
		return b;
	}

	function drawFlyout() {
		flyout.hidden = ! addOpen;
		root.classList.toggle( 'is-adding', addOpen );
		flyout.textContent = '';
		if ( ! addOpen ) { return; }

		var head = h( 'div', 'id5-sba__head' );
		head.appendChild( h( 'span', 'id5-sba__title', addMode === 'layout' ? 'CHOOSE ROW LAYOUT' : addMode === 'ask' ? askSpec.title : 'ADD CONTENT' ) );
		var close = h( 'button', 'id5-sba__close' );
		close.type = 'button';
		close.title = 'ปิด';
		close.setAttribute( 'aria-label', 'ปิด' );
		close.appendChild( icon( 'dismiss' ) );
		close.addEventListener( 'click', closeAdd );
		head.appendChild( close );
		flyout.appendChild( head );

		if ( addMode === 'layout' ) { drawLayouts( flyout ); return; }
		if ( addMode === 'ask' ) { drawAsk( flyout ); return; }

		var tabs = h( 'div', 'id5-sba__tabs' );
		[ [ 'blocks', 'Blocks', 'ชุดเนื้อหาสำเร็จรูปทั้งแถว' ], [ 'components', 'Components', 'ส่วนประกอบทีละชิ้น' ] ].forEach( function ( t ) {
			var b = h( 'button', addTab === t[ 0 ] ? 'is-active' : '', t[ 1 ] );
			b.type = 'button';
			b.title = t[ 2 ];
			b.addEventListener( 'click', function () { addTab = t[ 0 ]; search = ''; drawFlyout(); } );
			tabs.appendChild( b );
		} );
		flyout.appendChild( tabs );

		var searchBox = h( 'div', 'id5-sba__search' );
		var input = h( 'input' );
		input.type = 'search';
		input.placeholder = addTab === 'blocks' ? 'Block search' : 'Component search';
		input.value = search;
		input.setAttribute( 'aria-label', 'ค้นหา' );
		searchBox.appendChild( input );
		flyout.appendChild( searchBox );

		var list = h( 'div', 'id5-sba__list' );
		flyout.appendChild( list );

		function groupBox( key, title, tiles ) {
			var group = h( 'div', 'id5-sba__group' + ( folded[ key ] ? ' is-folded' : '' ) );
			var toggle = h( 'button', 'id5-sba__group-head' );
			toggle.type = 'button';
			toggle.setAttribute( 'aria-expanded', String( ! folded[ key ] ) );
			toggle.appendChild( icon( 'arrow-down' ) );
			toggle.appendChild( document.createTextNode( title ) );
			toggle.addEventListener( 'click', function () { folded[ key ] = ! folded[ key ]; drawFlyout(); } );
			group.appendChild( toggle );
			var items = h( 'div', 'id5-sba__items' );
			tiles.forEach( function ( tile ) { items.appendChild( tileButton( tile ) ); } );
			group.appendChild( items );
			list.appendChild( group );
		}

		function fill() {
			list.textContent = '';
			var q = search.trim().toLowerCase();
			var match = function ( t ) { return ! q || ( t.l + ' ' + t.th + ' ' + t.w ).toLowerCase().indexOf( q ) >= 0; };

			if ( q ) {
				var found = allTiles().filter( match );
				if ( found.length ) { groupBox( 'found', 'Results', found ); } else { list.appendChild( h( 'p', 'id5-sba__none', 'ไม่พบ "' + search + '"' ) ); }
			} else if ( addTab === 'blocks' ) {
				groupBox( 'sections', 'Ready-made rows', TILES.sections );
			} else {
				groupBox( 'basic', 'Basic', TILES.basic );
				groupBox( 'components', 'Components', TILES.components );
			}
		}

		input.addEventListener( 'input', function () { search = input.value; fill(); } );
		input.addEventListener( 'keydown', function ( event ) { if ( event.key === 'Escape' ) { closeAdd(); } } );
		fill();

		flyout.appendChild( h( 'div', 'id5-sba__foot' ) );
		updateFoot();
	}

	function backToList() {
		addMode = 'list';
		layoutDone = null;
		drawFlyout();
	}

	function drawLayouts( holder ) {
		var body = h( 'div', 'id5-sba__body' );
		holder.appendChild( body );
		body.appendChild( backLink() );
		body.appendChild( h( 'p', 'id5-sb__note', 'เลือกรูปแบบแถว: แบ่งเป็นคอลัมน์ได้ 1–6 คอลัมน์ ปรับความกว้างได้ภายหลังโดยลากเส้นระหว่างคอลัมน์' ) );
		var grid = h( 'div', 'id5-sb__layouts' );
		LAYOUTS.forEach( function ( layout ) {
			var b = h( 'button', 'id5-sb__layout' );
			b.type = 'button';
			b.appendChild( layoutPreview( layout.w ) );
			b.appendChild( h( 'span', '', layout.l ) );
			b.addEventListener( 'click', function () {
				var done = layoutDone;
				layoutDone = null;
				addMode = 'list';
				if ( done ) { done( [ row( layout.w ) ] ); }
				if ( addOpen ) { closeAdd(); }
			} );
			grid.appendChild( b );
		} );
		body.appendChild( grid );
	}

	function backLink() {
		var back = h( 'button', 'id5-sb__back' );
		back.type = 'button';
		back.appendChild( icon( 'arrow-left-alt2' ) );
		back.appendChild( document.createTextNode( 'ยกเลิก' ) );
		back.addEventListener( 'click', backToList );
		return back;
	}

	function drawAsk( holder ) {
		var spec = askSpec;
		var body = h( 'div', 'id5-sba__body' );
		holder.appendChild( body );
		body.appendChild( backLink() );
		var form = h( 'form', 'id5-sb__ask' );
		var label = h( 'label', 'id5-sb__ctl-title', spec.label );
		var input = h( 'input', 'id5-sb__input' );
		input.type = 'text';
		var error = h( 'p', 'id5-sb__error' );
		error.hidden = true;
		var ok = h( 'button', 'button button-primary', 'เพิ่ม' );
		ok.type = 'submit';
		label.appendChild( input );
		form.appendChild( label );
		form.appendChild( h( 'p', 'id5-sb__note', spec.hint ) );
		form.appendChild( error );
		form.appendChild( ok );
		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();
			var problem = spec.check( input.value );
			if ( problem ) { error.textContent = problem; error.hidden = false; return; }
			addMode = 'list';
			spec.done( input.value );
		} );
		body.appendChild( form );
		setTimeout( function () { input.focus(); }, 50 );
	}

	// ── Controls ────────────────────────────────────────────────────────

	function control( box, title ) {
		var wrap = h( 'div', 'id5-sb__ctl' );
		if ( title ) { wrap.appendChild( h( 'div', 'id5-sb__ctl-title', title ) ); }
		box.appendChild( wrap );
		return wrap;
	}

	function choice( box, title, options, current, pick ) {
		var wrap = control( box, title );
		var group = h( 'div', 'id5-sb__choice' );
		options.forEach( function ( o ) {
			var b = h( 'button', String( o[ 0 ] ) === String( current || '' ) ? 'is-active' : '' );
			b.type = 'button';
			if ( o[ 2 ] ) { b.appendChild( icon( o[ 2 ] ) ); b.title = o[ 1 ]; } else { b.textContent = o[ 1 ]; }
			b.addEventListener( 'click', function () { pick( o[ 0 ] ); } );
			group.appendChild( b );
		} );
		wrap.appendChild( group );
	}

	function toggle( box, title, on, set, hint ) {
		var wrap = control( box );
		var label = h( 'label', 'id5-sb__toggle' );
		var input = h( 'input' );
		input.type = 'checkbox';
		input.checked = !! on;
		input.addEventListener( 'change', function () { set( input.checked ); } );
		label.appendChild( input );
		label.appendChild( h( 'span', 'id5-sb__switch' ) );
		label.appendChild( h( 'span', '', title ) );
		wrap.appendChild( label );
		if ( hint ) { wrap.appendChild( h( 'p', 'id5-sb__note', hint ) ); }
	}

	function swatches( box, title, current, pick ) {
		var wrap = control( box, title );
		var group = h( 'div', 'id5-sb__swatches' );
		var none = h( 'button', 'id5-sb__swatch is-none' + ( ! current ? ' is-active' : '' ) );
		none.type = 'button';
		none.title = 'ไม่มีสี';
		none.addEventListener( 'click', function () { pick( null ); } );
		group.appendChild( none );
		( OPT.palette || [] ).forEach( function ( c ) {
			var b = h( 'button', 'id5-sb__swatch' + ( current === c.slug ? ' is-active' : '' ) );
			b.type = 'button';
			b.title = c.name;
			b.style.background = c.color;
			b.addEventListener( 'click', function () { pick( c.slug ); } );
			group.appendChild( b );
		} );
		wrap.appendChild( group );
	}

	function field( box, title, value, set, opts ) {
		opts = opts || {};
		var wrap = control( box, title );
		var input = h( opts.area ? 'textarea' : 'input', 'id5-sb__input' );
		if ( ! opts.area ) { input.type = opts.type || 'text'; }
		if ( opts.placeholder ) { input.placeholder = opts.placeholder; }
		input.value = value || '';
		input.addEventListener( 'change', function () { set( input.value ); } );
		input.addEventListener( 'keydown', function ( event ) { if ( event.key === 'Enter' && ! opts.area ) { input.blur(); } } );
		wrap.appendChild( input );
		if ( opts.hint ) { wrap.appendChild( h( 'p', 'id5-sb__note', opts.hint ) ); }
		return input;
	}

	function range( box, title, min, max, step, value, unit, set ) {
		var wrap = control( box, title + ': ' + value + unit );
		var input = h( 'input', 'id5-sb__range' );
		input.type = 'range';
		input.min = min;
		input.max = max;
		input.step = step;
		input.value = value;
		input.addEventListener( 'input', function () { wrap.firstChild.textContent = title + ': ' + input.value + unit; } );
		input.addEventListener( 'change', function () { set( Number( input.value ) ); } );
		wrap.appendChild( input );
	}

	function actionButton( box, label, name, fn, cls ) {
		var b = h( 'button', 'button id5-sb__action' + ( cls ? ' ' + cls : '' ) );
		b.type = 'button';
		b.appendChild( icon( name ) );
		b.appendChild( document.createTextNode( label ) );
		b.addEventListener( 'click', fn );
		box.appendChild( b );
		return b;
	}

	var PADS = [ [ '', 'ปกติ' ], [ 'id5-pad-none', 'ไม่มี' ], [ 'id5-pad-s', 'เล็ก' ], [ 'id5-pad-m', 'กลาง' ], [ 'id5-pad-l', 'ใหญ่' ], [ 'id5-pad-xl', 'ใหญ่มาก' ] ];
	var GAPS = [ [ '', 'ปกติ' ], [ 'id5-gap-none', 'ไม่มี' ], [ 'id5-gap-s', 'เล็ก' ], [ 'id5-gap-m', 'กลาง' ], [ 'id5-gap-l', 'ใหญ่' ] ];
	var MARGINS = [ [ '', 'ปกติ' ], [ 'id5-mb-0', 'ไม่มี' ], [ 'id5-mb-s', 'เล็ก' ], [ 'id5-mb-m', 'กลาง' ], [ 'id5-mb-l', 'ใหญ่' ] ];
	var ALIGN = [ [ '', 'ชิดซ้าย', 'editor-alignleft' ], [ 'center', 'กึ่งกลาง', 'editor-aligncenter' ], [ 'right', 'ชิดขวา', 'editor-alignright' ] ];
	var VALIGN = [ [ '', 'ปกติ' ], [ 'top', 'บน' ], [ 'center', 'กลาง' ], [ 'bottom', 'ล่าง' ] ];
	var WIDTHS = [ [ '', 'อัตโนมัติ' ], [ '25%', '25%' ], [ '33.33%', '33%' ], [ '50%', '50%' ], [ '66.66%', '66%' ], [ '75%', '75%' ], [ '100%', '100%' ] ];

	function drawCustom( body, at ) {
		var block = at.block;
		var doc = at.doc;
		var kind = block.name === 'core/columns' ? 'row' : block.name === 'core/column' ? 'column' : block.name;

		// Where this is: section › row › column › component (click to go up).
		var crumbs = h( 'div', 'id5-sb__crumbs' );
		var sectionLink = h( 'button', '', doc.title );
		sectionLink.type = 'button';
		sectionLink.addEventListener( 'click', function () { selectSection( doc.id ); } );
		crumbs.appendChild( sectionLink );
		path( block.clientId ).forEach( function ( b ) {
			crumbs.appendChild( h( 'span', 'id5-sb__crumb-sep', '›' ) );
			var c = h( 'button', b === block ? 'is-current' : '', labelOf( b ) );
			c.type = 'button';
			c.addEventListener( 'click', function () { select( b.clientId ); SE.tellPreview( { type: 'builder-scroll', id: b.clientId } ); } );
			crumbs.appendChild( c );
		} );
		body.appendChild( crumbs );

		body.appendChild( customHead( labelOf( block ), isContainer( block ) ? { doc: doc.id, parent: block.clientId, index: block.innerBlocks.length } : null ) );

		var tabs = h( 'div', 'id5-sb__tabs is-icons' );
		var current = tabFor[ kind ] || 'layout';
		var pane = h( 'div', 'id5-sb__pane' );

		[ [ 'layout', 'เค้าโครง', 'edit' ], [ 'style', 'สไตล์', 'art' ], [ 'advanced', 'ขั้นสูง', 'admin-generic' ] ].forEach( function ( pair ) {
			var b = h( 'button', current === pair[ 0 ] ? 'is-active' : '' );
			b.appendChild( icon( pair[ 2 ] ) );
			b.appendChild( document.createTextNode( pair[ 1 ] ) );
			b.type = 'button';
			b.addEventListener( 'click', function () { tabFor[ kind ] = pair[ 0 ]; refresh(); } );
			tabs.appendChild( b );
		} );
		body.appendChild( tabs );
		body.appendChild( pane );

		function set( fn ) { edit( doc, fn ); }

		if ( current === 'layout' ) { layoutControls( pane, block, at, set ); }
		if ( current === 'style' ) { styleControls( pane, block, set ); }
		if ( current === 'advanced' ) { advancedControls( pane, block, set ); }

		var actions = h( 'div', 'id5-sb__actions' );
		if ( block.name === 'core/columns' ) {
			actionButton( actions, 'เพิ่มแถวด้านล่าง', 'plus', function () { act( 'row-below', block.clientId ); } );
		} else if ( isContainer( block ) ) {
			actionButton( actions, 'เพิ่มเนื้อหาในนี้', 'plus', function () { act( 'add-into', block.clientId ); } );
		} else {
			actionButton( actions, 'เพิ่มเนื้อหาต่อจากนี้', 'plus', function () { act( 'add-after', block.clientId ); } );
		}
		actionButton( actions, 'ทำสำเนา', 'admin-page', function () { duplicate( block.clientId ); } );
		actionButton( actions, 'คัดลอกสไตล์', 'art', function () { copyStyle( block.clientId ); } );
		if ( copiedStyle ) { actionButton( actions, 'วางสไตล์', 'admin-appearance', function () { pasteStyle( block.clientId ); } ); }
		actionButton( actions, 'ลบ' + labelOf( block ), 'trash', function () { remove( block.clientId ); }, 'is-danger' );
		body.appendChild( actions );
	}

	function layoutControls( pane, block, at, set ) {
		var a = block.attributes;

		switch ( block.name ) {
			case 'core/columns': {
				var cols = block.innerBlocks;
				var equal = cols.every( function ( c ) { return ! c.attributes.width; } );

				var count = control( pane, 'จำนวนคอลัมน์: ' + cols.length );
				var stepper = h( 'div', 'id5-sb__choice' );
				[ [ '−', 'ลดคอลัมน์', function () { removeColumn( block.clientId ); } ], [ '+', 'เพิ่มคอลัมน์', function () { addColumn( block.clientId ); } ] ].forEach( function ( s ) {
					var b = h( 'button', '', s[ 0 ] + ' ' + s[ 1 ] );
					b.type = 'button';
					b.addEventListener( 'click', s[ 2 ] );
					stepper.appendChild( b );
				} );
				count.appendChild( stepper );

				var structure = control( pane, 'โครงสร้างแถว' );
				var grid = h( 'div', 'id5-sb__layouts is-small' );
				LAYOUTS.filter( function ( l ) { return l.w.length === cols.length; } ).forEach( function ( layout ) {
					var same = layout.w.every( function ( w, i ) { return ( w || '' ) === ( cols[ i ].attributes.width || '' ); } );
					var b = h( 'button', 'id5-sb__layout' + ( same ? ' is-active' : '' ) );
					b.type = 'button';
					b.title = layout.l;
					b.appendChild( layoutPreview( layout.w ) );
					b.appendChild( h( 'span', '', layout.l ) );
					b.addEventListener( 'click', function () { set( function () { cols.forEach( function ( c, i ) { setAttr( c, 'width', layout.w[ i ] ); } ); } ); } );
					grid.appendChild( b );
				} );
				structure.appendChild( grid );

				toggle( pane, 'คอลัมน์กว้างเท่ากัน', equal, function ( on ) {
					if ( on ) { set( function () { cols.forEach( function ( c ) { setAttr( c, 'width', null ); } ); } ); } else { tabFor.row = 'layout'; refresh(); }
				}, equal ? '' : 'กำหนดความกว้างแต่ละคอลัมน์ได้ใน "จัดการคอลัมน์" ด้านล่าง หรือลากเส้นระหว่างคอลัมน์บนหน้าเว็บ' );

				toggle( pane, 'ทำให้คอลัมน์สูงเท่ากัน', hasClass( block, 'id5-equal-height' ), function ( on ) {
					set( function () { setClass( block, /^id5-equal-height$/, on ? 'id5-equal-height' : '' ); if ( on ) { setAttr( block, 'verticalAlignment', null ); } } );
				} );

				pane.appendChild( h( 'h3', 'id5-sb__sub', 'ระยะห่างและการจัดวาง' ) );
				choice( pane, 'ระยะห่างระหว่างคอลัมน์', GAPS, classValue( block, /^id5-gap-/ ), function ( v ) { set( function () { setClass( block, /^id5-gap-/, v ); } ); } );
				choice( pane, 'จัดแนวตั้งของคอลัมน์', VALIGN, a.verticalAlignment, function ( v ) { set( function () { setAttr( block, 'verticalAlignment', v ); setClass( block, /^id5-equal-height$/, '' ); } ); } );
				toggle( pane, 'เรียงคอลัมน์ซ้อนกันบนมือถือ', a.isStackedOnMobile !== false, function ( on ) { set( function () { block.attributes.isStackedOnMobile = on; } ); } );

				pane.appendChild( h( 'h3', 'id5-sb__sub', 'จัดการคอลัมน์' ) );
				var manage = h( 'div', 'id5-sb__manage' );
				cols.forEach( function ( c, i ) {
					var line = h( 'div', 'id5-sb__manage-row' );
					var name = h( 'button', 'id5-sb__manage-name', 'คอลัมน์ ' + ( i + 1 ) );
					name.type = 'button';
					name.title = 'เลือกคอลัมน์นี้';
					name.addEventListener( 'click', function () { select( c.clientId ); } );
					line.appendChild( name );

					var width = h( 'select', 'id5-sb__select' );
					WIDTHS.forEach( function ( w ) {
						var o = h( 'option', '', w[ 1 ] );
						o.value = w[ 0 ];
						width.appendChild( o );
					} );
					if ( c.attributes.width && ! WIDTHS.some( function ( w ) { return w[ 0 ] === c.attributes.width; } ) ) {
						var custom = h( 'option', '', c.attributes.width );
						custom.value = c.attributes.width;
						width.appendChild( custom );
					}
					width.value = c.attributes.width || '';
					width.addEventListener( 'change', function () { set( function () { setAttr( c, 'width', width.value ); } ); } );
					line.appendChild( width );

					[ [ 'arrow-left-alt2', 'ย้ายไปซ้าย', -1 ], [ 'arrow-right-alt2', 'ย้ายไปขวา', 1 ] ].forEach( function ( m ) {
						var b = h( 'button', 'id5-sb__icon-btn' );
						b.type = 'button';
						b.title = m[ 1 ];
						b.disabled = ( m[ 2 ] < 0 && i === 0 ) || ( m[ 2 ] > 0 && i === cols.length - 1 );
						b.appendChild( icon( m[ 0 ] ) );
						b.addEventListener( 'click', function () { shift( c.clientId, m[ 2 ] ); } );
						line.appendChild( b );
					} );

					var del = h( 'button', 'id5-sb__icon-btn is-danger' );
					del.type = 'button';
					del.title = 'ลบคอลัมน์นี้';
					del.appendChild( icon( 'no-alt' ) );
					del.addEventListener( 'click', function () {
						if ( c.innerBlocks.length && ! window.confirm( 'ลบคอลัมน์ ' + ( i + 1 ) + ' พร้อมเนื้อหาข้างใน?' ) ) { return; }
						remove( c.clientId );
					} );
					line.appendChild( del );
					manage.appendChild( line );
				} );
				pane.appendChild( manage );
				actionButton( pane, 'เพิ่มคอลัมน์', 'plus', function () { addColumn( block.clientId ); } );
				break;
			}

			case 'core/column':
				choice( pane, 'ความกว้าง', WIDTHS, a.width, function ( v ) { set( function () { setAttr( block, 'width', v ); } ); } );
				choice( pane, 'จัดแนวตั้ง', VALIGN, a.verticalAlignment, function ( v ) { set( function () { setAttr( block, 'verticalAlignment', v ); } ); } );
				choice( pane, 'จัดข้อความ', [ [ '', 'ปกติ' ], [ 'id5-text-left', 'ซ้าย' ], [ 'id5-text-center', 'กลาง' ], [ 'id5-text-right', 'ขวา' ] ], classValue( block, /^id5-text-/ ), function ( v ) { set( function () { setClass( block, /^id5-text-/, v ); } ); } );
				actionButton( pane, 'ย้ายไปซ้าย', 'arrow-left-alt2', function () { shift( block.clientId, -1 ); } );
				actionButton( pane, 'ย้ายไปขวา', 'arrow-right-alt2', function () { shift( block.clientId, 1 ); } );
				break;

			case 'core/paragraph':
				if ( hasClass( block, 'id5-icon' ) ) {
					iconControls( pane, block, set );
				} else if ( hasClass( block, 'id5-map' ) ) {
					field( pane, 'สถานที่หรือที่อยู่', plain( str( a.content ) ), function ( v ) { set( function () { setAttr( block, 'content', rich( esc( v.trim() ) ) ); } ); } );
					choice( pane, 'ความสูงแผนที่', [ [ 'id5-map-s', 'เตี้ย' ], [ 'id5-map-m', 'กลาง' ], [ 'id5-map-l', 'สูง' ] ], classValue( block, /^id5-map-[sml]$/ ) || 'id5-map-m', function ( v ) { set( function () { setClass( block, /^id5-map-[sml]$/, v ); } ); } );
				} else {
					if ( hasClass( block, 'id5-link' ) ) { linkControls( pane, block, set ); }
					textNote( pane );
					choice( pane, 'จัดข้อความ', ALIGN, a.align, function ( v ) { set( function () { setAttr( block, 'align', v ); } ); } );
					sizeControl( pane, block, set );
				}
				break;

			case 'core/heading':
				textNote( pane );
				choice( pane, 'ระดับหัวข้อ', [ [ 1, 'H1' ], [ 2, 'H2' ], [ 3, 'H3' ], [ 4, 'H4' ], [ 5, 'H5' ] ], a.level || 2, function ( v ) { set( function () { block.attributes.level = Number( v ); } ); } );
				choice( pane, 'จัดข้อความ', ALIGN, a.textAlign, function ( v ) { set( function () { setAttr( block, 'textAlign', v ); } ); } );
				sizeControl( pane, block, set );
				break;

			case 'core/image': {
				var preview = h( 'img', 'id5-sb__thumb' );
				preview.src = a.url || '';
				preview.alt = '';
				pane.appendChild( preview );
				actionButton( pane, 'เปลี่ยนรูปภาพ', 'format-image', function () {
					media( 'image', false, function ( files ) { set( function () { Object.assign( block.attributes, imageAttrs( files[ 0 ] ) ); setAttr( block, 'alt', files[ 0 ].alt || a.alt ); } ); } );
				} );
				field( pane, 'คำอธิบายรูป (alt) สำหรับผู้พิการทางสายตา', a.alt, function ( v ) { set( function () { setAttr( block, 'alt', v ); } ); } );
				field( pane, 'คำบรรยายใต้รูป', plain( str( a.caption ) ), function ( v ) { set( function () { setAttr( block, 'caption', v ? rich( esc( v ) ) : null ); } ); } );
				choice( pane, 'ขนาด', [ [ '', 'เต็ม' ], [ 'id5-w-75', '75%' ], [ 'id5-w-50', '50%' ], [ 'id5-w-25', '25%' ] ], classValue( block, /^id5-w-/ ), function ( v ) { set( function () { setClass( block, /^id5-w-/, v ); } ); } );
				choice( pane, 'ตำแหน่ง', [ [ '', 'ปกติ' ], [ 'left', 'ซ้าย', 'align-left' ], [ 'center', 'กลาง', 'align-center' ], [ 'right', 'ขวา', 'align-right' ] ], a.align, function ( v ) { set( function () { setAttr( block, 'align', v ); } ); } );
				field( pane, 'ลิงก์เมื่อคลิกรูป', a.href, function ( v ) { set( function () { setAttr( block, 'href', v.trim() ); setAttr( block, 'linkDestination', v.trim() ? 'custom' : 'none' ); } ); }, { placeholder: 'https://…' } );
				break;
			}

			case 'core/button':
				field( pane, 'ข้อความบนปุ่ม', plain( str( a.text ) ), function ( v ) { set( function () { setAttr( block, 'text', rich( esc( v ) ) ); } ); } );
				field( pane, 'ลิงก์', a.url, function ( v ) { set( function () { setAttr( block, 'url', v.trim() ); } ); }, { placeholder: 'https://… หรือ /contact' } );
				toggle( pane, 'เปิดในแท็บใหม่', a.linkTarget === '_blank', function ( on ) { set( function () { setAttr( block, 'linkTarget', on ? '_blank' : null ); setAttr( block, 'rel', on ? 'noopener' : null ); } ); } );
				choice( pane, 'ความกว้างปุ่ม', [ [ '', 'พอดีข้อความ' ], [ 25, '25%' ], [ 50, '50%' ], [ 75, '75%' ], [ 100, '100%' ] ], a.width, function ( v ) { set( function () { setAttr( block, 'width', v ? Number( v ) : null ); } ); } );
				var group = at.parent && at.parent.name === 'core/buttons' ? at.parent : null;
				if ( group ) {
					choice( pane, 'ตำแหน่งปุ่ม', [ [ '', 'ซ้าย' ], [ 'id5-justify-center', 'กลาง' ], [ 'id5-justify-right', 'ขวา' ] ], classValue( group, /^id5-justify-/ ), function ( v ) { set( function () { setClass( group, /^id5-justify-/, v ); } ); } );
					actionButton( pane, 'เพิ่มปุ่มข้าง ๆ', 'plus', function () {
						var extra = make( 'core/button', { text: rich( 'ปุ่มใหม่' ), url: '#' } );
						set( function () { group.innerBlocks.splice( at.index + 1, 0, extra ); } );
						select( extra.clientId );
					} );
				}
				break;

			case 'core/spacer':
				range( pane, 'ความสูง', 8, 240, 4, parseInt( a.height, 10 ) || 40, 'px', function ( v ) { set( function () { block.attributes.height = v + 'px'; } ); } );
				break;

			case 'core/separator':
				choice( pane, 'รูปแบบเส้น', [ [ '', 'สั้น' ], [ 'is-style-wide', 'เต็มความกว้าง' ], [ 'is-style-dots', 'จุด' ] ], classValue( block, /^is-style-/ ), function ( v ) { set( function () { setClass( block, /^is-style-/, v ); } ); } );
				break;

			case 'core/embed':
				field( pane, 'ลิงก์วิดีโอ YouTube', a.url, function ( v ) {
					var url = youtube( v );
					if ( ! url ) { window.alert( 'ต้องเป็นลิงก์ YouTube' ); refresh(); return; }
					set( function () { block.attributes.url = url; } );
				} );
				field( pane, 'คำบรรยายใต้วิดีโอ', plain( str( a.caption ) ), function ( v ) { set( function () { setAttr( block, 'caption', v ? rich( esc( v ) ) : null ); } ); } );
				break;

			case 'core/file':
				pane.appendChild( h( 'p', 'id5-sb__note', 'ไฟล์: ' + ( a.href || '' ).split( '/' ).pop() ) );
				actionButton( pane, 'เปลี่ยนไฟล์ PDF', 'pdf', function () {
					media( 'pdf', false, function ( files ) { set( function () { var fresh = pdf( files[ 0 ] ).attributes; [ 'id', 'href', 'textLinkHref', 'fileName' ].forEach( function ( k ) { block.attributes[ k ] = fresh[ k ]; } ); } ); } );
				} );
				field( pane, 'ชื่อที่แสดง', plain( str( a.fileName ) ), function ( v ) { set( function () { setAttr( block, 'fileName', rich( esc( v ) ) ); } ); } );
				toggle( pane, 'แสดงปุ่มดาวน์โหลด', a.showDownloadButton !== false, function ( on ) { set( function () { block.attributes.showDownloadButton = on; } ); } );
				field( pane, 'ข้อความบนปุ่มดาวน์โหลด', plain( str( a.downloadButtonText ) ), function ( v ) { set( function () { setAttr( block, 'downloadButtonText', rich( esc( v ) ) ); } ); } );
				toggle( pane, 'แสดงตัวอย่างเอกสารบนหน้าเว็บ', !! a.displayPreview, function ( on ) { set( function () { block.attributes.displayPreview = on; if ( on && ! a.previewHeight ) { block.attributes.previewHeight = 600; } } ); } );
				if ( a.displayPreview ) {
					range( pane, 'ความสูงตัวอย่าง', 200, 1200, 50, a.previewHeight || 600, 'px', function ( v ) { set( function () { block.attributes.previewHeight = v; } ); } );
				}
				break;

			case 'core/list':
				listControls( pane, block, set );
				break;

			case 'core/table':
				tableControls( pane, block, set );
				break;

			case 'core/quote':
				textNote( pane );
				field( pane, 'ผู้กล่าว', plain( str( a.citation ) ), function ( v ) { set( function () { setAttr( block, 'citation', v ? rich( esc( v ) ) : null ); } ); } );
				break;

			case 'core/details':
				field( pane, 'หัวข้อ (คลิกเพื่อเปิด/ปิด)', plain( str( a.summary ) ), function ( v ) { set( function () { setAttr( block, 'summary', rich( esc( v ) ) ); } ); } );
				toggle( pane, 'เปิดไว้ตั้งแต่แรก', !! a.showContent, function ( on ) { set( function () { setAttr( block, 'showContent', on ); } ); } );
				break;

			case 'core/group':
				groupControls( pane, block, set );
				break;

			default:
				pane.appendChild( h( 'p', 'id5-sb__note', 'ไม่มีตัวเลือกเค้าโครงสำหรับ' + labelOf( block ) ) );
		}
	}

	// "คุณกำลังปรับแต่ง / แถว" with the round blue + (add content), as in Colibri.
	function customHead( name, into ) {
		var head = h( 'div', 'id5-sb__custom-head' );
		var words = h( 'div', 'id5-sb__custom-words' );
		words.appendChild( h( 'span', 'id5-sb__custom-small', 'คุณกำลังปรับแต่ง' ) );
		words.appendChild( h( 'strong', 'id5-sb__custom-name', name ) );
		head.appendChild( words );
		var plus = h( 'button', 'id5-sb__plus' );
		plus.type = 'button';
		plus.title = 'เพิ่มเนื้อหา (ADD CONTENT)';
		plus.setAttribute( 'aria-label', 'เพิ่มเนื้อหา' );
		plus.appendChild( icon( 'plus-alt2' ) );
		plus.addEventListener( 'click', function () { openAdd( into || undefined ); } );
		head.appendChild( plus );
		return head;
	}

	function textNote( pane ) {
		pane.appendChild( h( 'p', 'id5-sb__note is-tip', 'คลิกที่ข้อความบนหน้าเว็บเพื่อพิมพ์แก้ได้ทันที เลือกข้อความแล้วใช้แถบเครื่องมือเพื่อทำตัวหนา ตัวเอียง หรือใส่ลิงก์' ) );
	}

	function sizeControl( pane, block, set ) {
		var sizes = [ [ '', 'ปกติ' ] ].concat( ( OPT.sizes || [] ).filter( function ( s ) { return s.slug !== 'id5-normal'; } ).map( function ( s ) { return [ s.slug, s.name ]; } ) );
		choice( pane, 'ขนาดตัวอักษร', sizes, block.attributes.fontSize, function ( v ) { set( function () { setAttr( block, 'fontSize', v ); } ); } );
	}

	function linkControls( pane, block, set ) {
		var content = str( block.attributes.content );
		var href = ( content.match( /href="([^"]*)"/ ) || [] )[ 1 ] || '';
		field( pane, 'ข้อความลิงก์', plain( content ), function ( v ) { set( function () { setAttr( block, 'content', rich( link( v, href.replace( /&amp;/g, '&' ) ) ) ); } ); } );
		field( pane, 'ลิงก์ไปที่', href.replace( /&amp;/g, '&' ), function ( v ) { set( function () { setAttr( block, 'content', rich( link( plain( content ), v.trim() || '#' ) ) ); } ); }, { placeholder: 'https://… หรือ /contact' } );
	}

	function iconControls( pane, block, set ) {
		var current = classValue( block, /^ti-/ );
		var wrap = control( pane, 'เลือกไอคอน' );
		var grid = h( 'div', 'id5-sb__icons' + ( OPT.icons ? '' : ' has-names' ) );
		ICONS.forEach( function ( pair ) {
			var b = h( 'button', 'id5-sb__icon' + ( current === 'ti-' + pair[ 0 ] ? ' is-active' : '' ) );
			b.type = 'button';
			b.title = pair[ 1 ];
			// Without the website's icon font (WordPress on another address,
			// as on a development computer) the names are shown instead.
			b.appendChild( OPT.icons ? h( 'i', 'ti ti-' + pair[ 0 ] ) : h( 'span', 'id5-sb__icon-name', pair[ 1 ] ) );
			b.addEventListener( 'click', function () { set( function () { setClass( block, /^ti-/, 'ti-' + pair[ 0 ] ); } ); } );
			grid.appendChild( b );
		} );
		wrap.appendChild( grid );
		choice( pane, 'ขนาดไอคอน', [ [ 'id5-icon-s', 'เล็ก' ], [ 'id5-icon-m', 'กลาง' ], [ 'id5-icon-l', 'ใหญ่' ], [ 'id5-icon-xl', 'ใหญ่มาก' ] ], classValue( block, /^id5-icon-(s|m|l|xl)$/ ) || 'id5-icon-m', function ( v ) { set( function () { setClass( block, /^id5-icon-(s|m|l|xl)$/, v ); } ); } );
		choice( pane, 'ตำแหน่ง', ALIGN, block.attributes.align, function ( v ) { set( function () { setAttr( block, 'align', v ); } ); } );
	}

	// One line of a list: its words and, if any, where it links to.
	function itemParts( item ) {
		var content = str( item.attributes.content );
		var m = content.match( /^\s*<a [^>]*href="([^"]*)"[^>]*>([\s\S]*)<\/a>\s*$/ );
		return m ? { text: plain( m[ 2 ] ), href: m[ 1 ].replace( /&amp;/g, '&' ) } : { text: plain( content ), href: '' };
	}

	function listControls( pane, block, set ) {
		var links = hasClass( block, 'id5-social' ) || hasClass( block, 'id5-vmenu' );

		if ( ! links ) {
			toggle( pane, 'รายการแบบมีลำดับ (1, 2, 3)', !! block.attributes.ordered, function ( on ) { set( function () { setAttr( block, 'ordered', on ); } ); } );
		}
		if ( hasClass( block, 'id5-social' ) ) {
			pane.appendChild( h( 'p', 'id5-sb__note', 'ไอคอนเลือกให้อัตโนมัติจากลิงก์ (Facebook, LINE, YouTube, X, Instagram, TikTok, อีเมล, โทรศัพท์)' ) );
		}

		pane.appendChild( h( 'h3', 'id5-sb__sub', 'รายการ' ) );
		block.innerBlocks.forEach( function ( item, i ) {
			var parts = itemParts( item );
			var box = h( 'div', 'id5-sb__item' );
			var words = h( 'input', 'id5-sb__input' );
			words.value = parts.text;
			words.placeholder = 'ข้อความ';
			var href = h( 'input', 'id5-sb__input' );
			href.value = parts.href;
			href.placeholder = links ? 'ลิงก์ https://…' : 'ลิงก์ (ถ้ามี)';

			function save() {
				set( function () { setAttr( item, 'content', rich( href.value.trim() ? link( words.value, href.value.trim() ) : esc( words.value ) ) ); } );
			}

			words.addEventListener( 'change', save );
			href.addEventListener( 'change', save );
			box.appendChild( words );
			box.appendChild( href );

			var del = h( 'button', 'id5-sb__icon-btn is-danger' );
			del.type = 'button';
			del.title = 'ลบรายการนี้';
			del.appendChild( icon( 'no-alt' ) );
			del.addEventListener( 'click', function () { set( function () { block.innerBlocks.splice( i, 1 ); } ); } );
			box.appendChild( del );
			pane.appendChild( box );
		} );

		actionButton( pane, 'เพิ่มรายการ', 'plus', function () {
			set( function () { block.innerBlocks.push( make( 'core/list-item', { content: rich( links ? link( 'ลิงก์ใหม่', '#' ) : 'รายการใหม่' ) } ) ); } );
		} );
	}

	function tableControls( pane, block, set ) {
		var a = block.attributes;
		var body = a.body || [];
		var cols = ( ( a.head && a.head[ 0 ] ) || body[ 0 ] || { cells: [] } ).cells.length;

		textNote( pane );
		pane.appendChild( h( 'p', 'id5-sb__note', body.length + ' แถว × ' + cols + ' คอลัมน์' ) );

		function cell( tag ) { return { content: rich( '' ), tag: tag }; }
		function filled( n, tag ) { return Array.apply( null, Array( n ) ).map( function () { return cell( tag ); } ); }

		var box = h( 'div', 'id5-sb__actions is-inline' );
		actionButton( box, 'เพิ่มแถว', 'plus', function () { set( function () { block.attributes.body = body.concat( [ { cells: filled( cols, 'td' ) } ] ); } ); } );
		actionButton( box, 'ลบแถวสุดท้าย', 'minus', function () { if ( body.length > 1 ) { set( function () { block.attributes.body = body.slice( 0, -1 ); } ); } } );
		actionButton( box, 'เพิ่มคอลัมน์', 'plus', function () {
			set( function () {
				[ 'head', 'body', 'foot' ].forEach( function ( s ) {
					if ( a[ s ] ) { block.attributes[ s ] = a[ s ].map( function ( r ) { return { cells: r.cells.concat( [ cell( s === 'head' ? 'th' : 'td' ) ] ) }; } ); }
				} );
			} );
		} );
		actionButton( box, 'ลบคอลัมน์สุดท้าย', 'minus', function () {
			if ( cols < 2 ) { return; }
			set( function () {
				[ 'head', 'body', 'foot' ].forEach( function ( s ) {
					if ( a[ s ] ) { block.attributes[ s ] = a[ s ].map( function ( r ) { return { cells: r.cells.slice( 0, -1 ) }; } ); }
				} );
			} );
		} );
		pane.appendChild( box );

		toggle( pane, 'แถวหัวตาราง', !! ( a.head && a.head.length ), function ( on ) { set( function () { setAttr( block, 'head', on ? [ { cells: filled( cols, 'th' ) } ] : null ); } ); } );
		toggle( pane, 'สลับสีแถว', hasClass( block, 'is-style-stripes' ), function ( on ) { set( function () { setClass( block, /^is-style-/, on ? 'is-style-stripes' : '' ); } ); } );
		toggle( pane, 'คอลัมน์กว้างเท่ากัน', !! a.hasFixedLayout, function ( on ) { set( function () { setAttr( block, 'hasFixedLayout', on ); } ); } );
	}

	function groupControls( pane, block, set ) {
		var kids = block.innerBlocks;
		var images = hasClass( block, 'id5-slider' ) || hasClass( block, 'id5-carousel' ) || hasClass( block, 'id5-collage' );

		if ( hasClass( block, 'id5-slider' ) || hasClass( block, 'id5-carousel' ) ) {
			toggle( pane, 'เลื่อนอัตโนมัติ', hasClass( block, 'id5-autoplay' ), function ( on ) { set( function () { setClass( block, /^id5-autoplay$/, on ? 'id5-autoplay' : '' ); } ); }, 'บนหน้าเว็บจริงจะเลื่อนทุก 5 วินาที (ไม่เลื่อนระหว่างแก้ไข)' );
		}
		if ( hasClass( block, 'id5-card' ) ) {
			toggle( pane, 'เน้นการ์ดนี้', hasClass( block, 'id5-card--featured' ), function ( on ) { set( function () { setClass( block, /^id5-card--featured$/, on ? 'id5-card--featured' : '' ); } ); } );
		}

		var what = hasClass( block, 'id5-tabs' ) ? 'แท็บ' : hasClass( block, 'id5-accordion' ) || hasClass( block, 'id5-accordion-menu' ) ? 'หัวข้อ' : images ? 'รูป' : '';
		if ( ! what ) {
			pane.appendChild( h( 'p', 'id5-sb__note', 'ลากเนื้อหามาวางในกล่องนี้ หรือกด "เพิ่มเนื้อหาในนี้" ด้านล่าง' ) );
			return;
		}

		pane.appendChild( h( 'h3', 'id5-sb__sub', what + 'ทั้งหมด (' + kids.length + ')' ) );
		kids.forEach( function ( kid, i ) {
			var line = h( 'div', 'id5-sb__manage-row' );
			var name = kid.name === 'core/details' ? plain( str( kid.attributes.summary ) ) : kid.name === 'core/image' ? ( kid.attributes.alt || 'รูปที่ ' + ( i + 1 ) ) : plain( str( ( kid.innerBlocks[ 0 ] || { attributes: {} } ).attributes.content ) );
			var pick = h( 'button', 'id5-sb__manage-name', name || what + 'ที่ ' + ( i + 1 ) );
			pick.type = 'button';
			pick.addEventListener( 'click', function () { select( kid.clientId ); SE.tellPreview( { type: 'builder-scroll', id: kid.clientId } ); } );
			line.appendChild( pick );
			[ [ 'arrow-up-alt2', 'เลื่อนขึ้น', -1 ], [ 'arrow-down-alt2', 'เลื่อนลง', 1 ] ].forEach( function ( m ) {
				var b = h( 'button', 'id5-sb__icon-btn' );
				b.type = 'button';
				b.title = m[ 1 ];
				b.appendChild( icon( m[ 0 ] ) );
				b.addEventListener( 'click', function () { shift( kid.clientId, m[ 2 ] ); } );
				line.appendChild( b );
			} );
			var del = h( 'button', 'id5-sb__icon-btn is-danger' );
			del.type = 'button';
			del.title = 'ลบ';
			del.appendChild( icon( 'no-alt' ) );
			del.addEventListener( 'click', function () { remove( kid.clientId ); } );
			line.appendChild( del );
			pane.appendChild( line );
		} );

		actionButton( pane, 'เพิ่ม' + what, 'plus', function () {
			if ( images ) {
				media( 'image', true, function ( files ) { set( function () { files.forEach( function ( f ) { kids.push( image( f ) ); } ); } ); } );
			} else if ( hasClass( block, 'id5-tabs' ) ) {
				set( function () { kids.push( tab( 'แท็บที่ ' + ( kids.length + 1 ) ) ); } );
			} else if ( hasClass( block, 'id5-accordion-menu' ) ) {
				set( function () { kids.push( fold( 'เมนูที่ ' + ( kids.length + 1 ), [ menuList() ] ) ); } );
			} else {
				set( function () { kids.push( fold( 'หัวข้อที่ ' + ( kids.length + 1 ) ) ); } );
			}
		} );
	}

	function styleControls( pane, block, set ) {
		var a = block.attributes;
		var any = false;

		if ( supports( block, 'textColor' ) ) {
			any = true;
			swatches( pane, block.name === 'core/paragraph' && hasClass( block, 'id5-icon' ) ? 'สีไอคอน' : 'สีตัวอักษร', a.textColor, function ( v ) { set( function () { setAttr( block, 'textColor', v ); } ); } );
		}
		if ( supports( block, 'backgroundColor' ) ) {
			any = true;
			swatches( pane, block.name === 'core/button' ? 'สีปุ่ม' : 'สีพื้นหลัง', a.backgroundColor, function ( v ) { set( function () { setAttr( block, 'backgroundColor', v ); } ); } );
		}
		if ( block.name === 'core/button' ) {
			choice( pane, 'รูปแบบปุ่ม', [ [ '', 'ทึบ' ], [ 'is-style-outline', 'เส้นขอบ' ] ], classValue( block, /^is-style-/ ), function ( v ) { set( function () { setClass( block, /^is-style-/, v ); } ); } );
		}
		if ( block.name === 'core/image' ) {
			choice( pane, 'มุมรูป', [ [ '', 'ปกติ' ], [ 'id5-rounded', 'มน' ], [ 'is-style-rounded', 'วงกลม' ], [ 'id5-square', 'เหลี่ยม' ] ], classValue( block, /^(id5-rounded|is-style-rounded|id5-square)$/ ), function ( v ) { set( function () { setClass( block, /^(id5-rounded|is-style-rounded|id5-square)$/, v ); } ); } );
			toggle( pane, 'เงา', hasClass( block, 'id5-shadow' ), function ( on ) { set( function () { setClass( block, /^id5-shadow$/, on ? 'id5-shadow' : '' ); } ); } );
			return;
		}

		if ( /core\/(columns|column|group|paragraph|heading|list|quote|details|table)$/.test( block.name ) ) {
			any = true;
			choice( pane, 'ระยะห่างภายใน (padding)', PADS, classValue( block, /^id5-pad-/ ), function ( v ) { set( function () { setClass( block, /^id5-pad-/, v ); } ); } );
			toggle( pane, 'มุมโค้งมน', hasClass( block, 'id5-rounded' ), function ( on ) { set( function () { setClass( block, /^id5-rounded$/, on ? 'id5-rounded' : '' ); } ); } );
			toggle( pane, 'เส้นขอบ', hasClass( block, 'id5-border' ), function ( on ) { set( function () { setClass( block, /^id5-border$/, on ? 'id5-border' : '' ); } ); } );
			toggle( pane, 'เงา', hasClass( block, 'id5-shadow' ), function ( on ) { set( function () { setClass( block, /^id5-shadow$/, on ? 'id5-shadow' : '' ); } ); } );
		}

		if ( ! any ) {
			pane.appendChild( h( 'p', 'id5-sb__note', 'ไม่มีตัวเลือกสไตล์สำหรับ' + labelOf( block ) ) );
		}
	}

	function advancedControls( pane, block, set ) {
		pane.appendChild( h( 'h3', 'id5-sb__sub', 'แสดงบนอุปกรณ์' ) );
		[ [ 'id5-hide-mobile', 'ซ่อนบนมือถือ' ], [ 'id5-hide-tablet', 'ซ่อนบนแท็บเล็ต' ], [ 'id5-hide-desktop', 'ซ่อนบนคอมพิวเตอร์' ] ].forEach( function ( pair ) {
			toggle( pane, pair[ 1 ], hasClass( block, pair[ 0 ] ), function ( on ) { set( function () { setClass( block, new RegExp( '^' + pair[ 0 ] + '$' ), on ? pair[ 0 ] : '' ); } ); } );
		} );
		pane.appendChild( h( 'p', 'id5-sb__note', 'ใช้ปุ่มขนาดหน้าจอด้านล่างของแผงนี้เพื่อดูผลบนแต่ละอุปกรณ์' ) );

		choice( pane, 'ระยะห่างด้านล่าง', MARGINS, classValue( block, /^id5-mb-/ ), function ( v ) { set( function () { setClass( block, /^id5-mb-/, v ); } ); } );

		if ( supports( block, 'anchor' ) ) {
			field( pane, 'ชื่อจุดลิงก์ (anchor)', block.attributes.anchor, function ( v ) {
				set( function () { setAttr( block, 'anchor', v.trim().toLowerCase().replace( /[^a-z0-9_-]+/g, '-' ).replace( /^-+|-+$/g, '' ) ); } );
			}, { hint: 'ใช้ลิงก์มาที่ตรงนี้ได้ด้วย #ชื่อ เช่น #contact (ภาษาอังกฤษ ตัวเลข และ - เท่านั้น)' } );
		}
	}

	function drawSection( body ) {
		var doc = docs[ selectedDoc ];
		if ( ! doc ) { view = ''; refresh(); return; }

		body.appendChild( h( 'div', 'id5-sb__crumbs', doc.title ) );
		body.appendChild( customHead( doc.type === 'id5_page' ? 'หน้าเว็บ' : 'ส่วนเนื้อหา', { doc: doc.id, parent: null, index: doc.blocks.length } ) );

		var pane = h( 'div', 'id5-sb__pane' );
		body.appendChild( pane );

		if ( doc.type === 'id5_section' ) {
			choice( pane, 'พื้นหลังของส่วนนี้', [ [ 'none', 'ไม่มี' ], [ 'white', 'ขาว' ], [ 'light', 'ฟ้าอ่อน' ], [ 'brand', 'น้ำเงิน' ] ], doc.background, function ( v ) {
				edit( doc, function () { doc.background = v; } );
			} );
		}

		pane.appendChild( h( 'p', 'id5-sb__note', 'ส่วนนี้มี ' + doc.blocks.length + ' แถว คลิกที่แถว คอลัมน์ หรือเนื้อหาบนหน้าเว็บเพื่อปรับแต่ง' ) );
		actionButton( pane, 'เพิ่มแถว', 'plus', function () { act( 'add-row', null, { root: doc.id } ); } );
		actionButton( pane, 'เพิ่มเนื้อหา', 'plus-alt2', function () { openAdd( { doc: doc.id, parent: null, index: doc.blocks.length } ); } );

		var actions = h( 'div', 'id5-sb__actions' );
		actionButton( actions, 'ชื่อ ภาษา และตำแหน่งในหน้า', 'admin-generic', function () { sectionSettings( doc.id ); } );
		if ( doc.type === 'id5_section' ) {
			actionButton( actions, 'เลื่อนส่วนนี้ขึ้น', 'arrow-up-alt2', function () { moveSection( doc.id, 'up' ); } );
			actionButton( actions, 'เลื่อนส่วนนี้ลง', 'arrow-down-alt2', function () { moveSection( doc.id, 'down' ); } );
			if ( doc.canDelete ) { actionButton( actions, 'ลบส่วนนี้ทั้งส่วน', 'trash', function () { deleteSection( doc.id ); }, 'is-danger' ); }
		}
		body.appendChild( actions );
	}

	// ── Always-visible entry point in the panel ─────────────────────────

	var launcher = h( 'div', 'id5-sb__launcher' );
	launcher.hidden = true;
	var launch = h( 'button', 'button button-primary button-hero id5-sb__launch' );
	launch.type = 'button';
	launch.appendChild( icon( 'plus-alt2' ) );
	launch.appendChild( document.createTextNode( 'เพิ่มเนื้อหา (ลากวาง)' ) );
	launch.addEventListener( 'click', function () { openAdd(); } );
	launcher.appendChild( launch );
	launcher.appendChild( h( 'p', 'id5-sb__note', 'เพิ่มแถว คอลัมน์ ข้อความ รูปภาพ PDF และชุดสำเร็จรูปลงบนหน้านี้ หรือชี้ที่ส่วนเนื้อหาบนหน้าเว็บเพื่อแก้ไขตรงนั้น' ) );
	var scroll = root.querySelector( '.id5-se__scroll' );
	if ( scroll ) { scroll.insertBefore( launcher, scroll.firstChild ); }

	publishButton.addEventListener( 'click', publish );
	undoButton.addEventListener( 'click', undo );
	redoButton.addEventListener( 'click', redo );

	document.addEventListener( 'keydown', function ( event ) {
		if ( event.key === 'Escape' && addOpen ) { closeAdd(); return; }
		var tag = ( event.target && event.target.tagName ) || '';
		if ( /^(INPUT|TEXTAREA|SELECT)$/.test( tag ) ) { return; }
		var key = event.key.toLowerCase();
		if ( ( event.ctrlKey || event.metaKey ) && key === 'z' ) { event.preventDefault(); if ( event.shiftKey ) { redo(); } else { undo(); } }
		if ( ( event.ctrlKey || event.metaKey ) && key === 'y' ) { event.preventDefault(); redo(); }
	} );

	// The page in the frame changed: forget where things were.
	SE.onBuilderPage = function () {
		target = null;
	};

	refresh();
}() );
