<?php
/**
 * Dynamic button: resolve global state on every render.
 *
 * @package Mintchat
 */

defined( 'ABSPATH' ) || exit;

$mintchat_raw_attributes = isset( $block->parsed_block['attrs'] ) && is_array( $block->parsed_block['attrs'] )
	? $block->parsed_block['attrs']
	: $attributes;

// An absent recipientId is the saved form of "Use global default" (the attribute default is
// not serialized); a present but malformed value fails closed and never falls back.
$mintchat_recipient_id = ! array_key_exists( 'recipientId', $mintchat_raw_attributes )
	? ''
	: ( is_string( $mintchat_raw_attributes['recipientId'] ) ? $mintchat_raw_attributes['recipientId'] : null );

$mintchat_recipient = \Mintchat\recipient( $mintchat_recipient_id );

if ( ! $mintchat_recipient ) {
	return;
}

$mintchat_use_default = true === ( $attributes['useDefaultMessage'] ?? false );
$mintchat_message     = '';

if ( $mintchat_use_default ) {
	$mintchat_post_id = \Mintchat\post_message_post_id();
	if ( $mintchat_post_id > 0 ) {
		$mintchat_message = \Mintchat\post_message( $mintchat_post_id );
	}
	if ( '' === $mintchat_message ) {
		$mintchat_message = $mintchat_recipient['default_message'] ?? '';
	}
} else {
	$mintchat_message = isset( $attributes['message'] ) && is_string( $attributes['message'] ) ? $attributes['message'] : '';
}

$mintchat_text    = isset( $attributes['buttonText'] ) && is_string( $attributes['buttonText'] ) ? trim( $attributes['buttonText'] ) : '';
$mintchat_text    = '' === $mintchat_text ? __( 'Send WhatsApp message', 'mintchat' ) : $mintchat_text;
$mintchat_new_tab = ! isset( $attributes['openInNewTab'] ) || true === $attributes['openInNewTab'];
?>
<div class="wp-block-button"><a <?php echo get_block_wrapper_attributes( array( 'class' => 'wp-block-button__link wp-element-button' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core escapes wrapper attributes. ?> href="<?php echo esc_url( \Mintchat\message_url( $mintchat_recipient['number'], $mintchat_message ) ); ?>"
	<?php if ( $mintchat_new_tab ) : ?> target="_blank" rel="noopener noreferrer"<?php endif; ?>>
	<?php if ( ! isset( $attributes['showIcon'] ) || true === $attributes['showIcon'] ) : ?>
		<span class="mintchat-button__icon" aria-hidden="true"></span>
	<?php endif; ?>
	<span class="mintchat-button__label"><?php echo esc_html( $mintchat_text ); ?></span>
	<?php if ( $mintchat_new_tab ) : ?>
		<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'mintchat' ); ?></span>
	<?php endif; ?>
</a></div>
