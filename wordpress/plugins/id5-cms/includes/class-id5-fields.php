<?php
/**
 * Renders and saves every field declared in ID5_Schema.
 *
 * One generic renderer serves all twelve content types, so adding a field is a
 * one-line schema change rather than a new metabox class.
 *
 * Storage: scalars live in post meta under `_id5_<key>`; repeaters live in a
 * single `_id5_<key>` entry holding a JSON array of rows. Term fields live in
 * term meta under the same prefix.
 *
 * @package ID5_CMS
 */

defined( 'ABSPATH' ) || exit;

class ID5_Fields {

	const PREFIX = '_id5_';
	const NONCE  = 'id5_fields_nonce';

	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post', array( __CLASS__, 'save_post' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'admin_footer-post-new.php', array( __CLASS__, 'preselect_section' ) );
		add_action( 'edit_form_after_title', array( __CLASS__, 'editor_notice' ) );
		add_action( 'admin_notices', array( __CLASS__, 'show_validation_notice' ) );

		foreach ( array_keys( ID5_Schema::taxonomies() ) as $tax ) {
			add_action( "{$tax}_add_form_fields", array( __CLASS__, 'term_add_fields' ) );
			add_action( "{$tax}_edit_form_fields", array( __CLASS__, 'term_edit_fields' ), 10, 2 );
			add_action( "created_{$tax}", array( __CLASS__, 'save_term' ) );
			add_action( "edited_{$tax}", array( __CLASS__, 'save_term' ) );
		}
	}

	/**
	 * When "add a person" is opened from a row of the chart, tick that row's
	 * unit in the box on the right, as the rest of the form is pre-filled.
	 */
	public static function preselect_section() {
		$section = isset( $_GET['id5_section'] ) ? absint( $_GET['id5_section'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification

		if ( ! $section || ! term_exists( $section, 'id5_org_section' ) ) {
			return;
		}

		printf(
			// By value: WordPress numbers the boxes' ids differently in each list.
			'<script>(function(){var boxes=document.querySelectorAll(\'#taxonomy-id5_org_section input[type="checkbox"][value="%1$d"]\');for(var i=0;i<boxes.length;i++){boxes[i].checked=true;}})();</script>',
			(int) $section
		);
	}

	/** Read one field value, falling back to the schema default. */
	public static function get( $post_id, $field ) {
		$value = get_post_meta( $post_id, self::PREFIX . $field['key'], true );

		if ( '' === $value || null === $value ) {
			$prefill = self::prefill( $post_id, $field );

			if ( null !== $prefill ) {
				return $prefill;
			}

			return isset( $field['default'] ) ? $field['default'] : '';
		}

		return $value;
	}

	/**
	 * A value handed over in the address when a brand-new item is opened —
	 * the website's "+" tile on an organisation-chart row passes the row's
	 * name and position this way, so the editor does not have to retype them.
	 *
	 * Only for an item not saved yet, only plain text and numbers, and it does
	 * nothing the editor could not type into the same box.
	 */
	private static function prefill( $post_id, $field ) {
		$name = 'id5_' . $field['key'];

		if ( ! isset( $_GET[ $name ] ) || 'auto-draft' !== get_post_status( $post_id ) || ! in_array( $field['type'], array( 'text', 'number', 'select' ), true ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return null;
		}

		$value = sanitize_text_field( wp_unslash( $_GET[ $name ] ) ); // phpcs:ignore WordPress.Security.NonceVerification

		// A choice only if it is one of the field's own.
		if ( 'select' === $field['type'] ) {
			return array_key_exists( $value, isset( $field['options'] ) ? $field['options'] : array() ) ? $value : null;
		}

		return 'number' === $field['type'] ? (string) (int) $value : $value;
	}

	/**
	 * The rows that already exist on the organisation chart, for choosing one
	 * instead of typing its name: label => array( en, order ).
	 */
	private static function existing_tiers() {
		$tiers = array();

		$people = get_posts(
			array(
				'post_type'      => 'id5_person',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
			)
		);

		foreach ( $people as $id ) {
			$label = trim( (string) get_post_meta( $id, self::PREFIX . 'tier_label_th', true ) );

			if ( '' !== $label && ! isset( $tiers[ $label ] ) ) {
				$tiers[ $label ] = array(
					'en'    => (string) get_post_meta( $id, self::PREFIX . 'tier_label_en', true ),
					'order' => (int) get_post_meta( $id, self::PREFIX . 'tier_order', true ),
				);
			}
		}

		uasort( $tiers, function ( $a, $b ) { return $a['order'] <=> $b['order']; } );

		return $tiers;
	}

	/** Read a repeater's rows as an array. */
	public static function get_rows( $post_id, $key ) {
		$raw = get_post_meta( $post_id, self::PREFIX . $key, true );

		if ( ! $raw ) {
			return array();
		}

		$rows = json_decode( $raw, true );

		return is_array( $rows ) ? $rows : array();
	}

	// ─── Metaboxes ──────────────────────────────────────────────────────────

	public static function add_meta_boxes() {
		foreach ( ID5_Schema::types() as $post_type => $type ) {
			foreach ( $type['boxes'] as $box ) {
				add_meta_box(
					'id5_box_' . $box['id'],
					$box['title'],
					array( __CLASS__, 'render_box' ),
					$post_type,
					'normal',
					'high',
					array( 'box' => $box, 'post_type' => $post_type )
				);
			}
		}
	}

	/**
	 * A short line under the title explaining what the main editor holds, for
	 * the screens where "content" means something specific (an FAQ answer).
	 */
	public static function editor_notice( $post ) {
		$type = ID5_Schema::type( $post->post_type );

		if ( $type && ! empty( $type['editor_notice'] ) ) {
			printf(
				'<p class="id5-editor-notice">%s</p>',
				esc_html( $type['editor_notice'] )
			);
		}
	}

	public static function render_box( $post, $meta ) {
		$box = $meta['args']['box'];

		wp_nonce_field( self::NONCE, self::NONCE );

		if ( ! empty( $box['help'] ) ) {
			printf( '<p class="id5-box-help">%s</p>', esc_html( $box['help'] ) );
		}

		echo '<div class="id5-fields">';

		foreach ( $box['fields'] as $field ) {
			if ( 'repeater' === $field['type'] ) {
				self::render_repeater( $post->ID, $field );
			} else {
				self::render_field( $field, self::get( $post->ID, $field ), 'id5_' . $field['key'] );
			}
		}

		echo '</div>';
	}

	/**
	 * Render one scalar field.
	 *
	 * @param array  $field Schema definition.
	 * @param mixed  $value Current value.
	 * @param string $name  HTML input name.
	 */
	private static function render_field( $field, $value, $name ) {
		$id          = 'fld_' . md5( $name );
		$placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
		$required    = ! empty( $field['required'] ) ? ' required' : '';
		$label       = isset( $field['label'] ) ? $field['label'] : '';

		echo '<div class="id5-field id5-field--' . esc_attr( $field['type'] ) . '">';

		if ( '' !== $label ) {
			printf(
				'<label class="id5-field__label" for="%s">%s%s</label>',
				esc_attr( $id ),
				esc_html( $label ),
				$required ? ' <span class="id5-req">*</span>' : ''
			);
		}

		switch ( $field['type'] ) {
			case 'textarea':
				printf(
					'<textarea id="%s" name="%s" rows="4" placeholder="%s"%s>%s</textarea>',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $placeholder ),
					$required,
					esc_textarea( $value )
				);
				break;

			case 'wysiwyg':
				// `compact` is for short formatted guidance: a small toolbar and
				// no media button, so a screen can hold several of them.
				$compact = ! empty( $field['compact'] );

				wp_editor(
					$value,
					$id,
					array(
						'textarea_name' => $name,
						'textarea_rows' => $compact ? 6 : 10,
						'media_buttons' => ! $compact,
						'teeny'         => $compact,
					)
				);
				break;

			case 'checkbox':
				printf(
					'<label class="id5-checkbox"><input type="hidden" name="%s" value="0"><input type="checkbox" id="%s" name="%s" value="1" %s> %s</label>',
					esc_attr( $name ),
					esc_attr( $id ),
					esc_attr( $name ),
					checked( '1', (string) $value, false ),
					esc_html( isset( $field['checkbox_label'] ) ? $field['checkbox_label'] : 'ใช่' )
				);
				break;

			case 'select':
				printf( '<select id="%s" name="%s"%s>', esc_attr( $id ), esc_attr( $name ), $required );
				foreach ( $field['options'] as $opt_value => $opt_label ) {
					printf(
						'<option value="%s" %s>%s</option>',
						esc_attr( $opt_value ),
						selected( (string) $opt_value, (string) $value, false ),
						esc_html( $opt_label )
					);
				}
				echo '</select>';
				break;

			case 'number':
				printf(
					'<input type="number" id="%s" name="%s" value="%s" step="1"%s>',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $value ),
					$required
				);
				break;

			case 'date':
				printf(
					'<input type="date" id="%s" name="%s" value="%s">',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( substr( (string) $value, 0, 10 ) )
				);
				break;

			case 'color':
				// A colour is chosen from the browser's own picker, never typed.
				printf(
					'<input type="color" class="id5-color" id="%s" name="%s" value="%s">',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( self::hex_color( $value, isset( $field['default'] ) ? $field['default'] : '#4F46E5' ) )
				);
				break;

			case 'image':
			case 'file':
				self::render_media_picker( $id, $name, $value, $field['type'], isset( $field['mime'] ) ? $field['mime'] : '' );
				break;

			case 'url':
			case 'email':
			case 'text':
			default:
				$list = '';

				// A box that offers the existing rows of the chart as the editor
				// types, so a row's name is picked rather than retyped.
				if ( isset( $field['suggest'] ) && 'tiers' === $field['suggest'] ) {
					$list = $id . '_list';

					printf( '<datalist id="%s">', esc_attr( $list ) );
					foreach ( self::existing_tiers() as $tier_label => $tier ) {
						printf( '<option value="%s" data-en="%s" data-order="%d"></option>', esc_attr( $tier_label ), esc_attr( $tier['en'] ), (int) $tier['order'] );
					}
					echo '</datalist>';
				}

				printf(
					'<input type="%s" id="%s" name="%s" value="%s" placeholder="%s"%s%s>',
					esc_attr( 'text' === $field['type'] ? 'text' : $field['type'] ),
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $value ),
					esc_attr( $placeholder ),
					$required,
					$list ? ' list="' . esc_attr( $list ) . '" autocomplete="off"' : ''
				);
				break;
		}

		if ( ! empty( $field['help'] ) ) {
			printf( '<p class="id5-field__help">%s</p>', esc_html( $field['help'] ) );
		}

		echo '</div>';
	}

	/**
	 * A Media Library picker storing the attachment ID.
	 */
	private static function render_media_picker( $id, $name, $value, $kind, $mime = '' ) {
		$value   = (int) $value;
		$preview = '';

		if ( $value ) {
			$preview = 'image' === $kind
				? wp_get_attachment_image( $value, 'thumbnail' )
				: '<span class="id5-media__filename">' . esc_html( basename( (string) get_attached_file( $value ) ) ) . '</span>';
		}

		printf(
			'<div class="id5-media" data-kind="%s" data-mime="%s">
				<input type="hidden" id="%s" name="%s" value="%s">
				<div class="id5-media__preview">%s</div>
				<button type="button" class="button id5-media__choose">%s</button>
				<button type="button" class="button-link id5-media__clear"%s>%s</button>
			</div>',
			esc_attr( $kind ),
			esc_attr( $mime ),
			esc_attr( $id ),
			esc_attr( $name ),
			esc_attr( $value ),
			$preview, // Already escaped above.
			esc_html( 'image' === $kind ? 'เลือกรูปภาพ' : 'เลือกไฟล์' ),
			$value ? '' : ' style="display:none"',
			esc_html( 'ลบออก' )
		);
	}

	/**
	 * Render a repeater: a list of removable, reorderable rows.
	 */
	private static function render_repeater( $post_id, $field ) {
		$rows = self::get_rows( $post_id, $field['key'] );
		$key  = $field['key'];

		echo '<div class="id5-repeater" data-key="' . esc_attr( $key ) . '">';
		echo '<div class="id5-repeater__rows">';

		foreach ( $rows as $index => $row ) {
			self::render_repeater_row( $field, $row, $index );
		}

		echo '</div>';

		// A hidden blank row the JS clones when "add" is pressed.
		echo '<script type="text/html" class="id5-repeater__template">';
		self::render_repeater_row( $field, array(), '__INDEX__' );
		echo '</script>';

		printf(
			'<p><button type="button" class="button button-secondary id5-repeater__add">+ เพิ่ม%s</button></p>',
			esc_html( $field['item_label'] )
		);
		echo '</div>';
	}

	private static function render_repeater_row( $field, $row, $index ) {
		$key = $field['key'];

		echo '<div class="id5-repeater__row">';
		printf(
			'<div class="id5-repeater__head"><span class="id5-repeater__handle dashicons dashicons-menu"></span><strong>%s</strong><button type="button" class="button-link id5-repeater__remove">ลบ</button></div>',
			esc_html( $field['item_label'] )
		);
		echo '<div class="id5-repeater__body">';

		foreach ( $field['fields'] as $sub ) {
			$value = isset( $row[ $sub['key'] ] ) ? $row[ $sub['key'] ] : ( isset( $sub['default'] ) ? $sub['default'] : '' );
			$name  = sprintf( 'id5_%s[%s][%s]', $key, $index, $sub['key'] );

			// wp_editor cannot be cloned safely, so repeaters use plain textareas.
			if ( 'wysiwyg' === $sub['type'] ) {
				$sub['type'] = 'textarea';
			}

			self::render_field( $sub, $value, $name );
		}

		echo '</div></div>';
	}

	// ─── Saving ─────────────────────────────────────────────────────────────

	public static function save_post( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( ! ID5_Schema::type( $post->post_type ) ) {
			return;
		}

		if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ self::NONCE ] ) ), self::NONCE ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		foreach ( ID5_Schema::fields_for( $post->post_type ) as $key => $field ) {
			$input = 'id5_' . $key;

			if ( 'repeater' === $field['type'] ) {
				self::save_repeater( $post_id, $field, $input );
				continue;
			}

			if ( ! isset( $_POST[ $input ] ) ) {
				continue;
			}

			$value = self::sanitize( $field, wp_unslash( $_POST[ $input ] ) );

			if ( '' === $value ) {
				delete_post_meta( $post_id, self::PREFIX . $key );
			} else {
				update_post_meta( $post_id, self::PREFIX . $key, $value );
			}
		}

		self::hold_back_if_incomplete( $post_id, $post );
	}

	// ─── Validation ─────────────────────────────────────────────────────────

	/**
	 * Refuse to publish something the public website could not use.
	 *
	 * The website finds several kinds of content by a key — a form or office by
	 * its code, a homepage card by which card it is. An item published without
	 * that key, or with a key another item already uses, would either never
	 * appear on the website or would overwrite the other item, and in both
	 * cases the editor would see "Published" and no hint that anything was
	 * wrong. So the check happens here, where the editor is: the item is kept
	 * as a draft (nothing typed is lost) and a notice says exactly what to fix.
	 */
	private static function hold_back_if_incomplete( $post_id, $post ) {
		if ( ! in_array( $post->post_status, array( 'publish', 'future', 'pending' ), true ) ) {
			return;
		}

		$problems = self::problems( $post_id, $post->post_type );

		if ( ! $problems ) {
			return;
		}

		// Unhook while demoting so this handler does not run again on itself.
		remove_action( 'save_post', array( __CLASS__, 'save_post' ), 10 );
		wp_update_post( array( 'ID' => $post_id, 'post_status' => 'draft' ) );
		add_action( 'save_post', array( __CLASS__, 'save_post' ), 10, 2 );

		set_transient( 'id5_notice_' . get_current_user_id(), $problems, 120 );

		// Replace WordPress's green "Published" message, which would be untrue.
		add_filter(
			'redirect_post_location',
			function ( $location ) {
				return add_query_arg( 'message', 10, remove_query_arg( 'message', $location ) );
			}
		);
	}

	/**
	 * Everything that stops this item being usable by the website, as
	 * sentences an editor can act on. Empty when the item is fine.
	 */
	public static function problems( $post_id, $post_type ) {
		$type     = ID5_Schema::type( $post_type );
		$fields   = ID5_Schema::fields_for( $post_type );
		$problems = array();

		foreach ( $fields as $key => $field ) {
			if ( 'repeater' === $field['type'] ) {
				continue;
			}

			$value = (string) get_post_meta( $post_id, self::PREFIX . $key, true );

			if ( ! empty( $field['required'] ) && '' === trim( $value ) ) {
				$problems[] = sprintf( 'กรุณากรอก "%s"', $field['label'] );
				continue;
			}

			if ( ! empty( $field['unique'] ) && '' !== $value ) {
				$other = self::other_post_with( $post_id, $post_type, array( $key => $value ) );

				if ( $other ) {
					$shown      = isset( $field['options'][ $value ] ) ? $field['options'][ $value ] : $value;
					$problems[] = sprintf( '"%s" ค่า "%s" ถูกใช้อยู่แล้วในรายการ "%s" — แต่ละรายการต้องไม่ซ้ำกัน', $field['label'], $shown, get_the_title( $other ) );
				}
			}
		}

		if ( ! empty( $type['unique_together'] ) ) {
			$pair = array();

			foreach ( $type['unique_together'] as $key ) {
				$pair[ $key ] = (string) get_post_meta( $post_id, self::PREFIX . $key, true );
			}

			if ( ! in_array( '', $pair, true ) ) {
				$other = self::other_post_with( $post_id, $post_type, $pair );

				if ( $other ) {
					$problems[] = sprintf( 'มีรายการสำหรับชุดนี้อยู่แล้ว: "%s" — กรุณาแก้ไขรายการเดิมแทนการสร้างใหม่', get_the_title( $other ) );
				}
			}
		}

		if ( ! empty( $type['required_taxonomies'] ) ) {
			$taxonomies = ID5_Schema::taxonomies();

			foreach ( $type['required_taxonomies'] as $taxonomy ) {
				$terms = get_the_terms( $post_id, $taxonomy );

				if ( ! $terms || is_wp_error( $terms ) ) {
					$problems[] = sprintf( 'กรุณาเลือก "%s" (กล่องด้านขวา)', $taxonomies[ $taxonomy ]['label_th'] );
				}
			}
		}

		return $problems;
	}

	/**
	 * Another live item of the same type holding the same field values.
	 *
	 * @return int Post ID, or 0 when there is none.
	 */
	private static function other_post_with( $post_id, $post_type, $values ) {
		$meta_query = array( 'relation' => 'AND' );

		foreach ( $values as $key => $value ) {
			$meta_query[] = array( 'key' => self::PREFIX . $key, 'value' => $value );
		}

		$found = get_posts(
			array(
				'post_type'        => $post_type,
				'post_status'      => array( 'publish', 'draft', 'pending', 'private', 'future' ),
				'post__not_in'     => array( $post_id ),
				'numberposts'      => 1,
				'fields'           => 'ids',
				'suppress_filters' => false,
				'meta_query'       => $meta_query,
			)
		);

		return $found ? (int) $found[0] : 0;
	}

	/**
	 * Tell the editor why their item was kept as a draft.
	 */
	public static function show_validation_notice() {
		$key      = 'id5_notice_' . get_current_user_id();
		$problems = get_transient( $key );

		if ( ! $problems || ! is_array( $problems ) ) {
			return;
		}

		delete_transient( $key );

		echo '<div class="notice notice-error id5-validation-notice"><p><strong>ยังไม่ได้เผยแพร่ — บันทึกเป็นฉบับร่างไว้ให้แล้ว กรุณาแก้ไขรายการต่อไปนี้แล้วกด "เผยแพร่" อีกครั้ง</strong></p><ul style="list-style:disc;margin-left:20px">';

		foreach ( $problems as $problem ) {
			echo '<li>' . esc_html( $problem ) . '</li>';
		}

		echo '</ul></div>';
	}

	private static function save_repeater( $post_id, $field, $input ) {
		$raw = isset( $_POST[ $input ] ) && is_array( $_POST[ $input ] ) ? wp_unslash( $_POST[ $input ] ) : array();
		$out = array();

		foreach ( $raw as $index => $row ) {
			if ( '__INDEX__' === (string) $index || ! is_array( $row ) ) {
				continue;
			}

			$clean = array();
			$empty = true;

			foreach ( $field['fields'] as $sub ) {
				$value = isset( $row[ $sub['key'] ] ) ? $row[ $sub['key'] ] : '';
				$sub_t = 'wysiwyg' === $sub['type'] ? 'textarea' : $sub['type'];
				$clean[ $sub['key'] ] = self::sanitize( array( 'type' => $sub_t ) + $sub, $value );

				if ( ! in_array( $sub_t, array( 'checkbox', 'select' ), true ) && '' !== $clean[ $sub['key'] ] ) {
					$empty = false;
				}
			}

			// Drop rows an editor added but never filled in.
			if ( ! $empty ) {
				$out[] = $clean;
			}
		}

		if ( empty( $out ) ) {
			delete_post_meta( $post_id, self::PREFIX . $field['key'] );
		} else {
			update_post_meta(
				$post_id,
				self::PREFIX . $field['key'],
				wp_slash( wp_json_encode( $out, JSON_UNESCAPED_UNICODE ) )
			);
		}
	}

	/**
	 * Sanitize one submitted value according to its declared type.
	 */
	/**
	 * A colour as "#rrggbb", whatever form it was stored in.
	 *
	 * Colours used to be typed as text, so existing values include names such
	 * as "aqua" and "red"; the picker can only show a hex value, so the common
	 * names are translated and anything unrecognised becomes the fallback.
	 */
	public static function hex_color( $value, $fallback ) {
		$value = strtolower( trim( (string) $value ) );

		if ( preg_match( '/^#[0-9a-f]{6}$/', $value ) ) {
			return $value;
		}

		if ( preg_match( '/^#([0-9a-f])([0-9a-f])([0-9a-f])$/', $value, $m ) ) {
			return '#' . $m[1] . $m[1] . $m[2] . $m[2] . $m[3] . $m[3];
		}

		$names = array(
			'red' => '#ff0000', 'blue' => '#0000ff', 'green' => '#008000', 'aqua' => '#00ffff', 'cyan' => '#00ffff',
			'teal' => '#008080', 'navy' => '#000080', 'purple' => '#800080', 'pink' => '#ffc0cb', 'orange' => '#ffa500',
			'yellow' => '#ffff00', 'black' => '#000000', 'white' => '#ffffff', 'gray' => '#808080', 'grey' => '#808080',
			'brown' => '#a52a2a', 'gold' => '#ffd700', 'indigo' => '#4b0082', 'violet' => '#ee82ee', 'maroon' => '#800000',
			'lime' => '#00ff00', 'olive' => '#808000', 'silver' => '#c0c0c0', 'magenta' => '#ff00ff',
		);

		return isset( $names[ $value ] ) ? $names[ $value ] : $fallback;
	}

	private static function sanitize( $field, $value ) {
		if ( is_array( $value ) ) {
			return '';
		}

		switch ( $field['type'] ) {
			case 'wysiwyg':
				return wp_kses_post( $value );

			case 'textarea':
				return sanitize_textarea_field( $value );

			case 'checkbox':
				return '1' === (string) $value ? '1' : '0';

			case 'number':
				return '' === trim( $value ) ? '' : (string) (int) $value;

			case 'url':
				return esc_url_raw( trim( $value ) );

			case 'email':
				return sanitize_email( $value );

			case 'date':
				$value = trim( $value );
				return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ? $value : '';

			case 'image':
			case 'file':
				return $value ? (string) absint( $value ) : '';

			case 'select':
				$options = isset( $field['options'] ) ? $field['options'] : array();
				return array_key_exists( (string) $value, $options ) ? (string) $value : '';

			case 'color':
				return self::hex_color( $value, '' );

			default:
				return sanitize_text_field( $value );
		}
	}

	// ─── Taxonomy term fields ───────────────────────────────────────────────

	public static function term_add_fields( $taxonomy ) {
		$tax = ID5_Schema::taxonomies();

		if ( empty( $tax[ $taxonomy ]['fields'] ) ) {
			return;
		}

		wp_nonce_field( self::NONCE, self::NONCE );

		foreach ( $tax[ $taxonomy ]['fields'] as $field ) {
			echo '<div class="form-field id5-term-field">';
			self::render_field( $field, isset( $field['default'] ) ? $field['default'] : '', 'id5_' . $field['key'] );
			echo '</div>';
		}
	}

	public static function term_edit_fields( $term, $taxonomy ) {
		$tax = ID5_Schema::taxonomies();

		if ( empty( $tax[ $taxonomy ]['fields'] ) ) {
			return;
		}

		wp_nonce_field( self::NONCE, self::NONCE );

		foreach ( $tax[ $taxonomy ]['fields'] as $field ) {
			$value = get_term_meta( $term->term_id, self::PREFIX . $field['key'], true );

			if ( '' === $value && isset( $field['default'] ) ) {
				$value = $field['default'];
			}

			echo '<tr class="form-field id5-term-field"><th colspan="2">';
			self::render_field( $field, $value, 'id5_' . $field['key'] );
			echo '</th></tr>';
		}
	}

	public static function save_term( $term_id ) {
		if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ self::NONCE ] ) ), self::NONCE ) ) {
			return;
		}

		$term = get_term( $term_id );

		if ( ! $term || is_wp_error( $term ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_categories' ) ) {
			return;
		}

		$tax = ID5_Schema::taxonomies();

		if ( empty( $tax[ $term->taxonomy ]['fields'] ) ) {
			return;
		}

		foreach ( $tax[ $term->taxonomy ]['fields'] as $field ) {
			$input = 'id5_' . $field['key'];

			if ( ! isset( $_POST[ $input ] ) ) {
				continue;
			}

			update_term_meta(
				$term_id,
				self::PREFIX . $field['key'],
				self::sanitize( $field, wp_unslash( $_POST[ $input ] ) )
			);
		}
	}

	// ─── Assets ─────────────────────────────────────────────────────────────

	public static function enqueue( $hook ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		$is_post_screen = in_array( $hook, array( 'post.php', 'post-new.php' ), true )
			&& $screen && ID5_Schema::type( $screen->post_type );

		$is_term_screen = in_array( $hook, array( 'edit-tags.php', 'term.php' ), true )
			&& $screen && array_key_exists( $screen->taxonomy, ID5_Schema::taxonomies() );

		if ( ! $is_post_screen && ! $is_term_screen ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_style(
			'id5-cms-admin',
			ID5_CMS_URL . 'assets/admin.css',
			array(),
			ID5_CMS_VERSION
		);

		wp_enqueue_script(
			'id5-cms-admin',
			ID5_CMS_URL . 'assets/admin.js',
			array( 'jquery', 'jquery-ui-sortable' ),
			ID5_CMS_VERSION,
			true
		);
	}
}
