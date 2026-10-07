<?php
/**
 * Dynamic modal trigger: renders only while the global modal is active.
 *
 * @package Mintchat
 */

defined( 'ABSPATH' ) || exit;

$mintchat_config = \Mintchat\modal_configuration();
if ( ! $mintchat_config ) {
	return;
}
$mintchat_text = isset( $attributes['buttonText'] ) && is_string( $attributes['buttonText'] ) ? trim( $attributes['buttonText'] ) : '';
$mintchat_text = '' !== $mintchat_text ? $mintchat_text : \Mintchat\modal_trigger_text( $mintchat_config['settings'] );
?>
<button <?php echo get_block_wrapper_attributes( array( 'class' => 'mintchat-modal-trigger wp-element-button' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core escapes wrapper attributes. ?> type="button" data-mintchat-modal-open aria-controls="mintchat-modal" aria-haspopup="dialog" aria-expanded="false"><?php echo esc_html( $mintchat_text ); ?></button>
