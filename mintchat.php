<?php
/**
 * Plugin Name: Mintchat: Click to Chat
 * Description: Lightweight, cookie-free click-to-chat block for WhatsApp with multiple contacts and zero frontend JavaScript.
 * Version: 1.2.0
 * Requires at least: 7.0
 * Requires PHP: 7.4
 * Plugin URI: https://github.com/massimomazzariol/mintchat
 * Author: Massimo Mazzariol
 * Author URI: https://github.com/massimomazzariol
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: mintchat
 *
 * @package Mintchat
 * @copyright 2026 Massimo Mazzariol - https://github.com/massimomazzariol/mintchat
 */

namespace Mintchat;

defined( 'ABSPATH' ) || exit;

const VERSION = '1.2.0';

require_once __DIR__ . '/includes/recipients.php';
require_once __DIR__ . '/includes/settings.php';
require_once __DIR__ . '/includes/post-message.php';
require_once __DIR__ . '/includes/abilities.php';
require_once __DIR__ . '/includes/modal.php';

add_action( 'init', __NAMESPACE__ . '\\register_block' );
add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\\editor_data' );

/** Register the dynamic blocks and their local assets. */
function register_block() {
	register_block_type( __DIR__ . '/build/chat-button' );
	register_block_type( __DIR__ . '/build/modal-trigger' );
	wp_set_script_translations( 'mintchat-chat-button-editor-script', 'mintchat' );
	wp_set_script_translations( 'mintchat-modal-trigger-editor-script', 'mintchat' );
}

/** Supply editor-only metadata; numbers remain on the server. */
function editor_data() {
	$settings = settings();
	$data     = array(
		'recipients' => array_map(
			static function ( $recipient ) {
				return array(
					'id'              => $recipient['id'],
					'label'           => $recipient['label'],
					'default_message' => $recipient['default_message'] ?? '',
				);
			},
			$settings['recipients']
		),
		'defaultId'  => $settings['default_recipient_id'],
		'modal'      => array(
			'enabled'     => null !== modal_configuration(),
			'triggerText' => modal_trigger_text( $settings['modal'] ),
		),
	);
	wp_add_inline_script(
		'mintchat-chat-button-editor-script',
		'window.mintchatEditorData = ' . wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . ';',
		'before'
	);
}
