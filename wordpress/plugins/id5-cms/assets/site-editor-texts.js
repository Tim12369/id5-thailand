/**
 * The wording panel of the site editor: every fixed line of text on the page
 * being previewed — menu labels, headings, buttons — with a small form to
 * change it in Thai and in English.
 *
 * A line is reached three ways: its pencil on the page, the list here (in the
 * order the page shows them), or the search box, which looks through the whole
 * website. Saving publishes at once and reloads the preview.
 */
( function () {
	'use strict';

	var CFG = window.ID5SiteEditor;
	var SE = window.ID5SE;
	var box = document.getElementById( 'id5-se-texts' );
	var search = document.getElementById( 'id5-se-text-search' );
	var heading = document.getElementById( 'id5-se-texts-title' );

	if ( ! CFG || ! SE || ! box || ! search ) {
		return;
	}

	var LANGS = [ [ 'th', 'ภาษาไทย' ], [ 'en', 'English' ] ];

	var pageKeys = [];   // lines used on the previewed page, in page order
	var pageLang = 'th'; // language the preview is showing
	var lines = {};      // key -> line, as the server describes it
	var listed = [];     // keys currently in the list
	var searchTimer = null;
	var requestNo = 0;

	// ── Talking to WordPress ────────────────────────────────────────────

	function post( action, fields ) {
		var body = new FormData();
		body.append( 'action', action );
		body.append( 'nonce', CFG.nonce );

		Object.keys( fields ).forEach( function ( name ) {
			if ( Array.isArray( fields[ name ] ) ) {
				fields[ name ].forEach( function ( value ) { body.append( name + '[]', value ); } );
			} else {
				body.append( name, fields[ name ] );
			}
		} );

		return fetch( CFG.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body } )
			.then( function ( response ) {
				return response.json().catch( function () { return { success: false, data: { message: 'เซิร์ฟเวอร์ตอบกลับไม่ถูกต้อง (' + response.status + ')' } }; } );
			} )
			.catch( function () {
				return { success: false, data: { message: 'เชื่อมต่อไม่ได้ กรุณาตรวจสอบอินเทอร์เน็ตแล้วลองใหม่' } };
			} );
	}

	function remember( list ) {
		( list || [] ).forEach( function ( line ) { lines[ line.key ] = line; } );
	}

	// Fetch the lines not yet known, then call back.
	function ensure( keys, done ) {
		var missing = keys.filter( function ( key ) { return ! lines[ key ]; } );

		if ( ! missing.length ) {
			done();
			return;
		}

		post( 'id5_texts_get', { keys: missing } ).then( function ( result ) {
			if ( result.success ) {
				remember( result.data.lines );
				done();
			} else {
				message( ( result.data && result.data.message ) || 'โหลดรายการข้อความไม่สำเร็จ' );
			}
		} );
	}

	// ── Drawing ─────────────────────────────────────────────────────────

	function el( tag, className, text ) {
		var node = document.createElement( tag );
		if ( className ) { node.className = className; }
		if ( text !== undefined ) { node.textContent = text; }
		return node;
	}

	function plain( text ) {
		return String( text || '' ).replace( /<[^>]*>/g, '' ).replace( /\s+/g, ' ' ).trim();
	}

	function message( text ) {
		box.textContent = '';
		box.appendChild( el( 'p', 'id5-se__empty', text ) );
		listed = [];
	}

	function isOpen( key ) {
		var item = find( key );
		return !! item && ! item.querySelector( 'form' ).hidden;
	}

	function find( key ) {
		return Array.prototype.filter.call( box.children, function ( child ) { return child.getAttribute( 'data-key' ) === key; } )[ 0 ] || null;
	}

	function draw( keys, emptyText ) {
		box.textContent = '';
		listed = keys.filter( function ( key ) { return !! lines[ key ]; } );

		if ( ! listed.length ) {
			message( emptyText );
			return;
		}

		listed.forEach( function ( key ) { box.appendChild( item( lines[ key ] ) ); } );
	}

	function item( line ) {
		var wrap = el( 'div', 'id5-se__text' );
		wrap.setAttribute( 'data-key', line.key );

		var head = el( 'button', 'id5-se__text-head' );
		head.type = 'button';
		var now = el( 'span', 'id5-se__text-now', plain( line.value[ pageLang ] || line.value.th || line.value.en ) || '(ว่าง)' );
		var group = el( 'span', 'id5-se__text-group', line.group + ( line.changed.length ? ' · แก้ไขแล้ว' : '' ) );
		head.appendChild( now );
		head.appendChild( group );
		wrap.classList.toggle( 'is-changed', line.changed.length > 0 );

		var form = el( 'form', 'id5-se__text-form' );
		form.hidden = true;

		LANGS.forEach( function ( lang ) {
			var code = lang[ 0 ];
			var field = el( 'label', 'id5-se__field' );
			field.appendChild( el( 'span', 'id5-se__label', lang[ 1 ] ) );

			var input = el( 'textarea' );
			input.name = code;
			input.rows = Math.min( 8, Math.max( 2, Math.ceil( ( line.value[ code ] || '' ).length / 34 ) ) );
			input.value = line.value[ code ] || '';
			input.setAttribute( 'lang', code );
			field.appendChild( input );
			form.appendChild( field );

			// Show the built-in wording whenever it differs from what is typed,
			// with one click to go back to it.
			var original = el( 'p', 'id5-se__text-original' );
			original.appendChild( el( 'span', '', 'ข้อความเดิม: ' + ( line.default[ code ] || '(ไม่มี)' ) + ' ' ) );
			var restore = el( 'a', '', 'ใช้ข้อความเดิม' );
			restore.href = '#';
			restore.addEventListener( 'click', function ( event ) {
				event.preventDefault();
				input.value = line.default[ code ] || '';
				sync();
				input.focus();
			} );
			original.appendChild( restore );
			form.appendChild( original );

			function sync() { original.hidden = input.value === ( line.default[ code ] || '' ); }
			input.addEventListener( 'input', sync );
			sync();
		} );

		if ( line.html ) {
			form.appendChild( el( 'p', 'id5-se__hint', 'ข้อความนี้ใช้ตัวหนาได้ โดยพิมพ์ <strong>ข้อความ</strong> ครอบคำที่ต้องการ' ) );
		}

		if ( line.placeholders.length ) {
			form.appendChild( el( 'p', 'id5-se__hint', 'ต้องคงคำว่า ' + line.placeholders.join( ' ' ) + ' ไว้ในข้อความ เว็บไซต์จะแทนที่ด้วยข้อมูลจริง' ) );
		}

		var actions = el( 'div', 'id5-se__actions' );
		var submit = el( 'button', 'button button-primary', 'เผยแพร่' );
		submit.type = 'submit';
		var status = el( 'span', 'id5-se__status' );
		status.setAttribute( 'role', 'status' );
		actions.appendChild( submit );
		actions.appendChild( status );
		form.appendChild( actions );

		head.addEventListener( 'click', function () {
			form.hidden = ! form.hidden;
			wrap.classList.toggle( 'is-open', ! form.hidden );

			if ( ! form.hidden ) {
				SE.tellPreview( { type: 'focus', target: 'text:' + line.key } );
			}
		} );
		head.addEventListener( 'mouseenter', function () { SE.tellPreview( { type: 'hover', target: 'text:' + line.key } ); } );
		head.addEventListener( 'mouseleave', function () { SE.tellPreview( { type: 'unhover', target: 'text:' + line.key } ); } );

		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();
			submit.disabled = true;
			status.className = 'id5-se__status';
			status.textContent = 'กำลังบันทึก…';
			SE.setState( 'กำลังบันทึก…', true );

			post( 'id5_texts_save', { key: line.key, th: form.elements.th.value, en: form.elements.en.value } ).then( function ( result ) {
				submit.disabled = false;

				if ( ! result.success ) {
					status.className = 'id5-se__status is-error';
					status.textContent = ( result.data && result.data.message ) || 'บันทึกไม่สำเร็จ';
					SE.setState( 'บันทึกไม่สำเร็จ', false );

					if ( result.data && result.data.locale && form.elements[ result.data.locale ] ) {
						form.elements[ result.data.locale ].focus();
					}
					return;
				}

				// Redraw this one line from what the server stored, keeping it open.
				lines[ line.key ] = result.data.line;
				var fresh = item( result.data.line );
				fresh.querySelector( 'form' ).hidden = false;
				fresh.classList.add( 'is-open' );
				var freshStatus = fresh.querySelector( '.id5-se__status' );
				freshStatus.className = 'id5-se__status is-ok';
				freshStatus.textContent = 'เผยแพร่แล้ว';
				wrap.parentNode.replaceChild( fresh, wrap );
				SE.reloadPreviewSoon();
			} );
		} );

		wrap.appendChild( head );
		wrap.appendChild( form );
		return wrap;
	}

	// ── The page's own lines ────────────────────────────────────────────

	function showPage( then ) {
		heading.textContent = 'ข้อความในหน้านี้';

		if ( ! pageKeys.length ) {
			message( 'กำลังโหลด…' );
			return;
		}

		ensure( pageKeys, function () {
			draw( pageKeys, 'หน้านี้ไม่มีข้อความที่แก้ไขได้' );
			if ( then ) { then(); }
		} );
	}

	// Called for every page the preview loads.
	SE.onPage = function ( data ) {
		var keys = Array.isArray( data.texts ) ? data.texts : [];
		var lang = 'en' === data.lang ? 'en' : 'th';
		var same = lang === pageLang && keys.length === pageKeys.length && keys.every( function ( key, i ) { return key === pageKeys[ i ]; } );

		pageKeys = keys;
		pageLang = lang;

		// The same page reloading after a save: leave the list alone, so a form
		// someone is still typing in is not thrown away.
		if ( same && listed.length || '' !== search.value.trim() ) {
			return;
		}

		showPage();
	};

	// Called when a pencil on a piece of wording is clicked.
	SE.openText = function ( keys ) {
		search.value = '';

		ensure( keys, function () {
			var show = function () {
				var first = null;

				keys.forEach( function ( key ) {
					var node = find( key );

					// A line the page did not list (drawn later by a script):
					// add it at the top.
					if ( ! node && lines[ key ] ) {
						node = item( lines[ key ] );
						box.insertBefore( node, box.firstChild );
						listed.unshift( key );
					}

					if ( node ) {
						node.querySelector( 'form' ).hidden = false;
						node.classList.add( 'is-open' );
						first = first || node;
					}
				} );

				if ( first ) {
					first.scrollIntoView( { behavior: 'smooth', block: 'start' } );
					var input = first.querySelector( 'textarea[name="' + pageLang + '"]' );
					setTimeout( function () { input.focus(); input.select(); }, 250 );
				}
			};

			if ( listed.length && 'ข้อความในหน้านี้' === heading.textContent ) {
				show();
			} else {
				showPage( show );
			}
		} );
	};

	// ── Search across the whole website ─────────────────────────────────

	search.addEventListener( 'input', function () {
		clearTimeout( searchTimer );
		var words = search.value.trim();

		if ( '' === words ) {
			showPage();
			return;
		}

		searchTimer = setTimeout( function () {
			var mine = ++requestNo;
			heading.textContent = 'ผลการค้นหา';
			message( 'กำลังค้นหา…' );

			post( 'id5_texts_get', { q: words } ).then( function ( result ) {
				if ( mine !== requestNo || search.value.trim() !== words ) {
					return; // a newer search has replaced this one
				}

				if ( ! result.success ) {
					message( ( result.data && result.data.message ) || 'ค้นหาไม่สำเร็จ' );
					return;
				}

				remember( result.data.lines );
				draw( result.data.lines.map( function ( line ) { return line.key; } ), 'ไม่พบข้อความที่มีคำว่า "' + words + '"' );
			} );
		}, 350 );
	} );

	// Used by the browser tests.
	SE.texts = { isOpen: isOpen, listed: function () { return listed.slice(); } };
} )();
