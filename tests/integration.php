<?php
/** Run with: wp eval-file wp-content/plugins/mintchat/tests/integration.php
 * In WordPress Studio, prefix the command with studio.
 * Uses generated fictitious numbers and restores the option in finally.
 *
 * @package Mintchat
 */

defined( 'ABSPATH' ) || exit;
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit;
}

$mintchat_old       = get_option( 'mintchat_settings', null );
$mintchat_passes    = 0;
$mintchat_test_post = 0;
$mintchat_assert    = static function ( $condition, $description ) use ( &$mintchat_passes ) {
	if ( ! $condition ) {
		throw new \RuntimeException( esc_html( $description ) );
	}
	++$mintchat_passes;
	WP_CLI::log( 'PASS: ' . $description );
};
$mintchat_render    = static function ( $attributes ) {
	return render_block(
		array(
			'blockName'    => 'mintchat/chat-button',
			'attrs'        => $attributes,
			'innerBlocks'  => array(),
			'innerHTML'    => '',
			'innerContent' => array(),
		)
	);
};

try {
	( static function () use ( $mintchat_assert, $mintchat_render, &$mintchat_passes, &$mintchat_test_post ) {
		// Parse all shipped PHP using the running PHP engine, without executing it.
		$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( dirname( __DIR__ ) ) );
		foreach ( $files as $file ) {
			if ( 'php' === $file->getExtension() && false === strpos( $file->getPathname(), 'node_modules' ) && false === strpos( $file->getPathname(), 'vendor' ) ) {
				token_get_all( file_get_contents( $file->getPathname() ), TOKEN_PARSE ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local PHP syntax test; no URL or network access.
			}
		}
		$mintchat_assert( true, 'All plugin PHP files pass syntax validation' );
		$mintchat_assert( WP_Block_Type_Registry::get_instance()->is_registered( 'mintchat/chat-button' ), 'Block is registered' );
		$type = WP_Block_Type_Registry::get_instance()->get_registered( 'mintchat/chat-button' );
		$mintchat_assert( $type->is_dynamic() && 3 === $type->api_version, 'Dynamic Block API v3' );
		$mintchat_assert( empty( $type->view_script_handles ) && empty( $type->script_handles ) && empty( $type->view_script_module_ids ), 'No frontend JavaScript registered' );

		update_option(
			'mintchat_settings',
			array(
				'recipients'           => array(),
				'default_recipient_id' => '',
			)
		);
		$number_a = '1' . str_repeat( '2', 10 );
		$number_b = '1' . str_repeat( '3', 10 );
		$number_c = '1' . str_repeat( '4', 10 );
		$input    = array(
			'recipients'           => array(
				'new-a' => array(
					'label'           => 'Recipient A',
					'number'          => '+' . $number_a,
					'default_message' => 'Default A',
				),
				'new-b' => array(
					'label'  => 'Recipient B',
					'number' => $number_b,
				),
			),
			'default_recipient_id' => 'new-a',
		);
		$settings = \Mintchat\sanitize_settings( $input );
		$mintchat_assert( \Mintchat\sanitize_settings( $settings ) === $settings, 'Sanitization is idempotent, including UUIDs and default' );
		update_option( 'mintchat_settings', $settings );
		$a = $settings['recipients'][0]['id'];
		$b = $settings['recipients'][1]['id'];
		$mintchat_assert( wp_is_uuid( $a ) && wp_is_uuid( $b ) && $a !== $b, 'New recipients have distinct stable UUIDs' );
		$mintchat_assert( \Mintchat\recipient( '' )['number'] === $number_a, 'Empty recipient ID uses default A' );
		$mintchat_assert( false !== strpos( $mintchat_render( array() ), 'https://wa.me/' . $number_a ), 'Default block points to A' );
		$mintchat_assert( false !== strpos( $mintchat_render( array( 'useDefaultMessage' => true ) ), 'Default%20A' ), 'Block can use the contact default message' );
		$mintchat_assert(
			false !== strpos(
				$mintchat_render(
					array(
						'useDefaultMessage' => false,
						'message'           => 'Override',
					)
				),
				'Override'
			),
			'Block message override is honored'
		);
		$mintchat_assert( false !== strpos( $mintchat_render( array( 'recipientId' => $b ) ), 'https://wa.me/' . $number_b ), 'Explicit block points to B' );
		$mintchat_assert( substr_count( $mintchat_render( array( 'recipientId' => $a ) ) . $mintchat_render( array( 'recipientId' => $b ) ), 'wp-block-mintchat-chat-button' ) === 2, 'Multiple blocks render independently' );

		$saved                 = serialize_block(
			array(
				'blockName'    => 'mintchat/chat-button',
				'attrs'        => array(
					'recipientId' => $a,
					'message'     => 'Test explicit recipient',
				),
				'innerBlocks'  => array(),
				'innerHTML'    => '',
				'innerContent' => array(),
			)
		);
		$mintchat_test_post = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'draft',
				'post_title'   => 'Mintchat Integration Test',
				'post_content' => wp_slash( $saved ),
			),
			true
		);
		$mintchat_assert( ! is_wp_error( $mintchat_test_post ) && $mintchat_test_post > 0, 'Created isolated draft for persistence test' );
		$settings['recipients'][0]['number'] = $number_c;
		update_option( 'mintchat_settings', $settings );
		$persisted = get_post_field( 'post_content', $mintchat_test_post );
		$mintchat_assert( $persisted === $saved && false !== strpos( do_blocks( $persisted ), 'https://wa.me/' . $number_c ), 'Stored draft stays unchanged while rendered number updates' );
		$mintchat_assert( false !== strpos( do_blocks( $saved ), 'https://wa.me/' . $number_c ), 'Saved content uses global number change without resaving' );
		$mintchat_assert( false === strpos( $saved, $number_a ) && false === strpos( $saved, 'wa.me' ), 'Saved block contains no phone number or URL' );
		$settings['default_recipient_id'] = $b;
		update_option( 'mintchat_settings', $settings );
		$mintchat_assert( false !== strpos( $mintchat_render( array() ), 'https://wa.me/' . $number_b ), 'Default change redirects only intentional default block' );
		$mintchat_assert( false !== strpos( do_blocks( $saved ), 'https://wa.me/' . $number_c ), 'Explicit A does not follow default change' );

		// Abilities API.
		$mintchat_assert( wp_has_ability_category( 'mintchat' ) && wp_has_ability( 'mintchat/list-contacts' ) && wp_has_ability( 'mintchat/add-chat-button' ), 'Ability category and both abilities are registered' );
		$mintchat_assert( wp_get_ability( 'mintchat/list-contacts' )->get_meta_item( 'show_in_rest' ) === true && array( 'public' => true ) === wp_get_ability( 'mintchat/add-chat-button' )->get_meta_item( 'mcp' ), 'Abilities are public for REST and the MCP Adapter' );
		$previous_user = get_current_user_id();
		wp_set_current_user( 0 );
		$mintchat_assert( is_wp_error( wp_get_ability( 'mintchat/list-contacts' )->execute() ), 'Anonymous users cannot list contacts' );
		$mintchat_assert( is_wp_error( wp_get_ability( 'mintchat/add-chat-button' )->execute( array( 'post_id' => $mintchat_test_post ) ) ), 'Anonymous users cannot add buttons' );
		$admins = get_users(
			array(
				'role'   => 'administrator',
				'number' => 1,
				'fields' => 'ID',
			)
		);
		wp_set_current_user( (int) $admins[0] );
		$contacts = wp_get_ability( 'mintchat/list-contacts' )->execute();
		$mintchat_assert( is_array( $contacts ) && 2 === count( $contacts ), 'list-contacts returns both contacts' );
		$mintchat_assert( false === strpos( wp_json_encode( $contacts ), $number_b ) && false === strpos( wp_json_encode( $contacts ), $number_c ), 'list-contacts never returns phone numbers' );
		$mintchat_assert( 1 === count( array_filter( array_column( $contacts, 'is_default' ) ) ) && $b === $contacts[ array_search( true, array_column( $contacts, 'is_default' ), true ) ]['id'], 'list-contacts flags the default contact' );
		$added = wp_get_ability( 'mintchat/add-chat-button' )->execute(
			array(
				'post_id'     => $mintchat_test_post,
				'contact_id'  => $a,
				'button_text' => 'Chat with A',
				'style'       => 'outline',
				'position'    => 'start',
			)
		);
		$content = get_post_field( 'post_content', $mintchat_test_post );
		$mintchat_assert( is_array( $added ) && 0 === strpos( $content, $added['block'] ) && false !== strpos( $content, $saved ), 'add-chat-button prepends a block and keeps existing content' );
		$parsed = parse_blocks( $content );
		$mintchat_assert( 'mintchat/chat-button' === $parsed[0]['blockName'] && 'is-style-outline' === $parsed[0]['attrs']['className'] && 'Chat with A' === $parsed[0]['attrs']['buttonText'], 'Added block carries contact, label and style' );
		$mintchat_assert( false !== strpos( render_block( $parsed[0] ), 'https://wa.me/' . $number_c ) && false === strpos( $added['block'], 'wa.me' ), 'Added block renders the contact link and stores no number' );
		$mintchat_assert( is_wp_error( wp_get_ability( 'mintchat/add-chat-button' )->execute( array( 'post_id' => $mintchat_test_post, 'contact_id' => wp_generate_uuid4() ) ) ), 'Unknown contact ID is rejected' );
		$mintchat_assert( is_wp_error( wp_get_ability( 'mintchat/add-chat-button' )->execute( array( 'post_id' => $mintchat_test_post, 'style' => 'neon' ) ) ), 'Input schema rejects an unknown style' );
		wp_update_post(
			array(
				'ID'           => $mintchat_test_post,
				'post_content' => wp_slash( $saved ),
			)
		);
		wp_set_current_user( $previous_user );

		$reordered = array(
			'recipients'           => array(
				$b => $settings['recipients'][1],
				$a => $settings['recipients'][0],
			),
			'default_recipient_id' => $b,
		);
		$reordered = \Mintchat\sanitize_settings( $reordered );
		update_option( 'mintchat_settings', $reordered );
		$mintchat_assert( $reordered['recipients'][0]['id'] === $b && \Mintchat\recipient( $a )['number'] === $number_c, 'Reordering preserves identities' );
		$removed = \Mintchat\sanitize_settings(
			array(
				'recipients'           => array( $a => $settings['recipients'][0] ),
				'default_recipient_id' => $b,
			)
		);
		update_option( 'mintchat_settings', $removed );
		$mintchat_assert( $a === $removed['default_recipient_id'], 'Deleted default becomes first remaining valid recipient' );
		$mintchat_assert( '' === $mintchat_render( array( 'recipientId' => $b ) ), 'Deleted explicit recipient renders no CTA and never falls back' );
		$mintchat_assert( '' === $mintchat_render( array( 'recipientId' => 'unknown' ) ), 'Unknown explicit ID renders nothing' );

		$message = "Ciao! È disponibile?\nà è é ì ò ù 'apostrofi' \"Alpha & Beta\" #2026 % 😊 🏺";
		$url     = \Mintchat\message_url( $number_c, $message );
		parse_str( wp_parse_url( $url, PHP_URL_QUERY ), $query );
		$mintchat_assert( $query['text'] === $message, 'URL message round-trip preserves punctuation, accents, emoji and newline exactly' );
		$mintchat_assert( \Mintchat\message_url( $number_c, '' ) === 'https://wa.me/' . $number_c, 'Empty message omits query parameter' );
		foreach (
			array(
				'+39 333 123 4567'   => '393331234567',
				'+49 (170) 123-4567' => '491701234567',
			) as $formatted => $normalized
		) {
			$mintchat_assert( \Mintchat\normalize_phone( $formatted ) === $normalized, 'Supported international formatting normalizes predictably' );
		}
		foreach ( array( '', '0039 333 123 4567', '++393331234567', '+39abc3331234567', '+393331234567 ext 2', '123456', str_repeat( '1', 16 ), array() ) as $invalid ) {
			$mintchat_assert( '' === \Mintchat\normalize_phone( $invalid ), 'Malformed phone input is rejected' );
		}
		$html = $mintchat_render(
			array(
				'recipientId' => $a,
				'message'     => $message,
				'buttonText'  => '<script>alert(1)</script> Visible',
				'showIcon'    => true,
			)
		);
		$mintchat_assert( false === strpos( $html, '<script>' ) && false !== strpos( $html, '&lt;script&gt;' ), 'Visible label is escaped' );
		$mintchat_assert( false !== strpos( $html, 'target="_blank" rel="noopener noreferrer"' ) && false !== strpos( $html, 'wp-element-button' ), 'Button uses native class and new tab includes safe rel' );
		$mintchat_assert( false !== strpos( $html, '<span class="mintchat-button__icon" aria-hidden="true"></span>' ), 'Icon is an empty decorative span painted by the stylesheet' );
		$mintchat_assert( false === strpos( $html, '<svg' ) && false === strpos( $html, '<img' ) && strlen( $html ) < 900, 'No per-button SVG or image: the button markup stays small' );
		$html = $mintchat_render(
			array(
				'showIcon'     => false,
				'openInNewTab' => false,
				'buttonText'   => '',
			)
		);
		$mintchat_assert( false === strpos( $html, 'mintchat-button__icon' ) && false === strpos( $html, 'target=' ), 'Icon and new-tab toggles are honored' );
		$mintchat_assert( false !== strpos( $html, esc_html__( 'Send WhatsApp message', 'mintchat' ) ), 'Empty label falls back to the default visible text' );
		$html = $mintchat_render(
			array(
				'recipientId'       => $a,
				'message'           => array( '<script>' ),
				'buttonText'        => array( '<script>' ),
				'useDefaultMessage' => 'true',
				'showIcon'          => 'true',
				'openInNewTab'      => 'true',
			)
		);
		$mintchat_assert( false !== strpos( $html, 'https://wa.me/' . $number_c ) && false === strpos( $html, '<script>' ), 'Unexpected block attribute types fail safely without changing the recipient' );
		$mintchat_assert( '' === $mintchat_render( array( 'recipientId' => array( $a ) ) ), 'Malformed contact reference never falls back to the default' );
		$html = $mintchat_render(
			array(
				'align' => 'center',
				'style' => array(
					'color'      => array(
						'background' => '#123456',
						'text'       => '#ffffff',
					),
					'spacing'    => array( 'padding' => array( 'top' => '20px' ) ),
					'border'     => array( 'radius' => '12px' ),
					'typography' => array(
						'fontSize'      => '24px',
						'fontWeight'    => '700',
						'letterSpacing' => '0.12em',
						'textTransform' => 'uppercase',
					),
				),
			)
		);
		$mintchat_assert( false !== strpos( $html, 'background-color:#123456' ) && false !== strpos( $html, 'padding-top:20px' ) && false !== strpos( $html, 'border-radius:12px' ) && preg_match( '/font-size:(24px|clamp\()/', $html ) && false !== strpos( $html, 'aligncenter' ), 'Native style supports and positioning reach frontend markup' ); // Themes with fluid typography (Twenty Twenty-Five) turn 24px into clamp().
		$mintchat_assert( false !== strpos( $html, 'letter-spacing:0.12em' ) && false !== strpos( $html, 'text-transform:uppercase' ), 'Letter spacing and text transform reach frontend markup' );
		$mintchat_assert( false !== strpos( $mintchat_render( array( 'className' => 'is-style-outline' ) ), 'is-style-outline' ), 'Block style class reaches frontend markup' );
		$mintchat_assert( registered_meta_key_exists( 'post', 'mintchat_post_message' ), 'Per-post message meta is registered for every post type' );

		foreach ( array( '00' . $number_a, '12', str_repeat( '1', 16 ), '<script>' ) as $invalid ) {
			$mintchat_assert(
				\Mintchat\sanitize_settings(
					array(
						'recipients' => array(
							$a => array(
								'label'  => 'A',
								'number' => $invalid,
							),
						),
					)
				) === $removed,
				'Invalid number preserves prior option atomically'
			);
		}
		$mintchat_assert(
			\Mintchat\sanitize_settings(
				array(
					'recipients' => array(
						array(
							'label'  => array(),
							'number' => $number_a,
						),
					),
				)
			) === $removed,
			'Malformed input preserves prior option'
		);
		$mintchat_assert( \Mintchat\sanitize_settings( array( 'recipients' => array( $removed['recipients'][0], $removed['recipients'][0] ) ) ) === $removed, 'Duplicate UUIDs are rejected atomically' );
		foreach (
			array(
				null,
				false,
				'broken',
				array( 'recipients' => 'broken' ),
				array( 'recipients' => array( array( 'id' => $a ) ) ),
				array( 'recipients' => array( $removed['recipients'][0], $removed['recipients'][0] ) ),
			) as $malformed
		) {
			$filter = static function () use ( $malformed ) {
				return $malformed;
			};
			add_filter( 'option_mintchat_settings', $filter );
			$mintchat_assert(
				\Mintchat\settings() === array(
					'recipients'           => array(),
					'default_recipient_id' => '',
					'modal'                => \Mintchat\normalize_modal( array(), array() ),
				),
				'Malformed stored option fails closed without warnings'
			);
			remove_filter( 'option_mintchat_settings', $filter );
		}
		$invalid_default                         = $removed;
		$invalid_default['default_recipient_id'] = wp_generate_uuid4();
		$filter                                  = static function () use ( $invalid_default ) {
			return $invalid_default;
		};
		add_filter( 'option_mintchat_settings', $filter );
		$mintchat_assert( '' === \Mintchat\settings()['default_recipient_id'], 'Malformed stored default never reroutes to another contact' );
		remove_filter( 'option_mintchat_settings', $filter );
		// Contact modal: settings validation, trigger, dialog markup and assets.
		$modal = \Mintchat\sanitize_settings(
			array(
				'recipients' => array(
					'new-0' => array(
						'label'           => 'Modal desk',
						'number'          => '+39 333 000 0000',
						'default_message' => 'Modal hello',
					),
					'new-1' => array(
						'label'  => '',
						'number' => '',
					),
				),
				'modal'      => array(
					'enabled'       => '1',
					'recipient_id'  => 'new-0',
					'title'         => 'Talk to us <b>now</b>',
					'description'   => "Line one\nLine two",
					'email'         => 'desk@example.com',
					'map_latitude'  => '45.5',
					'map_longitude' => '200',
					'map_zoom'      => '42',
				),
			)
		);
		$mintchat_assert( 1 === count( $modal['recipients'] ), 'An unused new recipient row is ignored' );
		$mintchat_assert( $modal['modal']['recipient_id'] === $modal['recipients'][0]['id'], 'Modal recipient maps a new row key to its UUID' );
		$mintchat_assert( 'Talk to us now' === $modal['modal']['title'] && "Line one\nLine two" === $modal['modal']['description'], 'Modal text is sanitized, descriptions keep newlines' );
		$mintchat_assert( '45.5' === $modal['modal']['map_latitude'] && '' === $modal['modal']['map_longitude'] && '19' === $modal['modal']['map_zoom'], 'Modal map values are range-checked and clamped' );
		update_option( 'mintchat_settings', $modal );
		$trigger = render_block(
			array(
				'blockName'    => 'mintchat/modal-trigger',
				'attrs'        => array(),
				'innerBlocks'  => array(),
				'innerHTML'    => '',
				'innerContent' => array(),
			)
		);
		$mintchat_assert( false !== strpos( $trigger, 'data-mintchat-modal-open' ) && false !== strpos( $trigger, 'aria-haspopup="dialog"' ) && false !== strpos( $trigger, '>Contact us<' ), 'Modal trigger renders an accessible button with the default text' );
		ob_start();
		\Mintchat\render_modal();
		$dialog = ob_get_clean();
		$mintchat_assert( false !== strpos( $dialog, 'role="dialog"' ) && false !== strpos( $dialog, 'aria-labelledby="mintchat-modal-title"' ) && false !== strpos( $dialog, 'Line one<br />' ), 'Modal renders a labelled dialog' );
		$mintchat_assert( false !== strpos( $dialog, 'wp-block-mintchat-chat-button' ) && false !== strpos( $dialog, 'https://wa.me/393330000000?text=Modal%20hello' ), 'Modal WhatsApp action is a chat button with the default message' );
		$mintchat_assert( false === strpos( $dialog, 'openstreetmap' ), 'Modal map is omitted without both coordinates' );
		\Mintchat\modal_assets();
		$mintchat_assert( wp_style_is( 'mintchat-modal', 'enqueued' ) && wp_script_is( 'mintchat-modal', 'enqueued' ), 'Modal assets load while the modal is active' );
		wp_dequeue_style( 'mintchat-modal' );
		wp_dequeue_script( 'mintchat-modal' );
		$modal['modal']['enabled'] = false;
		update_option( 'mintchat_settings', $modal );
		ob_start();
		\Mintchat\render_modal();
		$mintchat_assert( '' === ob_get_clean() && '' === render_block( array( 'blockName' => 'mintchat/modal-trigger', 'attrs' => array(), 'innerBlocks' => array(), 'innerHTML' => '', 'innerContent' => array() ) ), 'Disabled modal renders neither dialog nor trigger' );
		$filter = static fn( $message ) => 'Filtered: ' . $message;
		add_filter( 'mintchat_message', $filter );
		$mintchat_assert( 'https://wa.me/393330000000?text=Filtered%3A%20Hi' === \Mintchat\message_url( '393330000000', 'Hi' ), 'The mintchat_message filter can change the pre-filled message' );
		remove_filter( 'mintchat_message', $filter );
		// A translation plugin offering languages gets the per-language default messages on save.
		$languages = static fn() => array( 'it_IT' => array( 'name' => 'Italiano', 'flag' => '', 'prefix' => 'it' ) );
		$handed    = array();
		$receiver  = static function ( $source, $locale, $text ) use ( &$handed ) {
			$handed[] = array( $source, $locale, $text );
		};
		add_filter( 'langsail_languages', $languages );
		add_action( 'langsail_set_translation', $receiver, 10, 3 );
		ob_start();
		\Mintchat\settings_row( 'new-0', array( 'label' => 'A', 'number' => '', 'default_message' => 'Hello' ), '' );
		$row_html = ob_get_clean();
		\Mintchat\sanitize_settings( array( 'recipients' => array( 'new-0' => array( 'label' => 'Desk', 'number' => '+39 333 000 0000', 'default_message' => 'Hello', 'translations' => array( 'it_IT' => 'Ciao <b>!</b>' ) ) ) ) );
		remove_filter( 'langsail_languages', $languages );
		remove_action( 'langsail_set_translation', $receiver, 10 );
		$mintchat_assert( str_contains( $row_html, '[translations][it_IT]' ) && str_contains( $row_html, 'Italiano' ), 'Each site language gets a default message field' );
		$mintchat_assert( array( array( 'Hello', 'it_IT', 'Ciao !' ) ) === $handed, 'Per-language messages are sanitized and handed to the translation plugin' );
		$empty = \Mintchat\sanitize_settings( array( 'recipients' => array( '_empty' => '1' ) ) );
		update_option( 'mintchat_settings', $empty );
		$mintchat_assert( '' === $empty['default_recipient_id'] && '' === $mintchat_render( array() ), 'Removing all recipients clears default and hides CTA' );
		if ( ! is_multisite() ) {
			if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
				define( 'WP_UNINSTALL_PLUGIN', 'mintchat/mintchat.php' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Core-required uninstall guard, defined only in this CLI test.
			}
			update_post_meta( $mintchat_test_post, 'mintchat_post_message', 'Uninstall probe' );
			require dirname( __DIR__ ) . '/uninstall.php';
			$mintchat_assert( null === get_option( 'mintchat_settings', null ), 'Uninstall deletes the Mintchat option' );
			$mintchat_assert( '' === get_post_meta( $mintchat_test_post, 'mintchat_post_message', true ), 'Uninstall deletes per-post messages' );
			$mintchat_assert( get_post_field( 'post_content', $mintchat_test_post ) === $saved, 'Uninstall leaves Gutenberg content untouched' );
		}
		WP_CLI::success( $mintchat_passes . ' integration checks passed on WordPress ' . get_bloginfo( 'version' ) . ' / PHP ' . PHP_VERSION );
	} )();

	// Restore even when a check fails.
} finally {
	if ( is_int( $mintchat_test_post ) && $mintchat_test_post > 0 ) {
		wp_delete_post( $mintchat_test_post, true );
	}
	if ( null === $mintchat_old ) {
		delete_option( 'mintchat_settings' );
	} else {
		update_option( 'mintchat_settings', $mintchat_old );
	}
}
