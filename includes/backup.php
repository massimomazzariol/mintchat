<?php
/**
 * Backup: every Mintchat setting and per-page message in one JSON file, to keep or to move to
 * another site (local to live). Pages are matched by type and address, not by ID.
 *
 * @package Mintchat
 */

namespace Mintchat;

defined( 'ABSPATH' ) || exit;

const BACKUP_FORMAT = 'mintchat';

add_action( 'admin_post_mintchat_export', __NAMESPACE__ . '\\download_backup' );
add_action( 'admin_post_mintchat_import', __NAMESPACE__ . '\\upload_backup' );

/**
 * Settings and per-page messages.
 *
 * @return array{format: string, version: int, settings: array, post_messages: array}
 */
function export_data() {
	$messages = array();
	foreach ( get_posts(
		array(
			'post_type'      => 'any',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'meta_key'       => POST_MESSAGE_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Admin export only.
			'fields'         => 'ids',
		)
	) as $id ) {
		$messages[] = array(
			'type'    => get_post_type( $id ),
			'path'    => get_page_uri( $id ),
			'message' => post_message( $id ),
		);
	}
	$settings = settings();
	unset( $settings['delete_data'] ); // A restore must never arm the deletion of the data it restores.
	return array(
		'format'        => BACKUP_FORMAT,
		'version'       => 1,
		'settings'      => $settings,
		'post_messages' => $messages,
	);
}

/**
 * Restore a backup: settings are validated like a save from the settings screen, per-page messages
 * go to the pages with the same type and address.
 *
 * @param mixed $data Decoded backup.
 * @return array{messages: int}|\WP_Error
 */
function import_data( $data ) {
	if ( ! is_array( $data ) || BACKUP_FORMAT !== ( $data['format'] ?? '' ) || ! isset( $data['settings']['recipients'] ) || ! is_array( $data['settings']['recipients'] ) ) {
		return new \WP_Error( 'mintchat_import', __( 'This is not a Mintchat backup.', 'mintchat' ) );
	}
	$input                = $data['settings'];
	$input['delete_data'] = settings()['delete_data'];
	$input['recipients']  = $input['recipients'] ? $input['recipients'] : array( '_empty' => '1' );
	$errors               = count( get_settings_errors( 'mintchat_settings' ) );
	$clean                = sanitize_settings( $input );
	if ( count( get_settings_errors( 'mintchat_settings' ) ) > $errors ) {
		return new \WP_Error( 'mintchat_import', __( 'The contacts in this backup are not valid. Nothing was changed.', 'mintchat' ) );
	}
	update_option( 'mintchat_settings', $clean );
	$count = 0;
	foreach ( (array) ( $data['post_messages'] ?? array() ) as $item ) {
		if ( ! is_array( $item ) || ! is_string( $item['path'] ?? null ) || ! is_string( $item['type'] ?? null ) || ! is_string( $item['message'] ?? null ) ) {
			continue;
		}
		$post = get_page_by_path( $item['path'], OBJECT, sanitize_key( $item['type'] ) );
		if ( $post ) {
			update_post_meta( $post->ID, POST_MESSAGE_KEY, sanitize_textarea_field( $item['message'] ) );
			++$count;
		}
	}
	return array( 'messages' => $count );
}

/** Download the backup file. */
function download_backup() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to export Mintchat settings.', 'mintchat' ), 403 );
	}
	check_admin_referer( 'mintchat_export' );
	$host = sanitize_file_name( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
	nocache_headers();
	header( 'Content-Type: application/json; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="mintchat-' . $host . '-' . gmdate( 'Y-m-d' ) . '.json"' );
	echo wp_json_encode( export_data(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	exit;
}

/** Restore an uploaded backup file. */
function upload_backup() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to import Mintchat settings.', 'mintchat' ), 403 );
	}
	check_admin_referer( 'mintchat_import' );
	$file   = isset( $_FILES['file']['tmp_name'] ) && is_uploaded_file( $_FILES['file']['tmp_name'] ) ? $_FILES['file']['tmp_name'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Temporary path checked by is_uploaded_file().
	$result = '' !== $file ? import_data( json_decode( (string) file_get_contents( $file ), true ) ) : new \WP_Error( 'mintchat_import', __( 'The file could not be read.', 'mintchat' ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local uploaded file.
	$notice = is_wp_error( $result ) ? 'error:' . $result->get_error_message() : 'ok:' . sprintf(
		/* translators: %d: number of pages with their own message. */
		_n( 'Backup restored, with %d page message.', 'Backup restored, with %d page messages.', $result['messages'], 'mintchat' ),
		$result['messages']
	);
	set_transient( 'mintchat_import_' . get_current_user_id(), $notice, MINUTE_IN_SECONDS );
	wp_safe_redirect( admin_url( 'options-general.php?page=mintchat' ) );
	exit;
}

/**
 * The data section of the settings screen: delete-on-uninstall choice (inside the settings form).
 *
 * @param array $value Current settings.
 */
function settings_data( $value ) {
	?>
	<h2><?php esc_html_e( 'Your data', 'mintchat' ); ?></h2>
	<table class="form-table" role="presentation">
		<tr><th scope="row"><?php esc_html_e( 'When the plugin is deleted', 'mintchat' ); ?></th><td>
			<label><input type="checkbox" name="mintchat_settings[delete_data]" value="1" aria-describedby="mintchat-delete-help" <?php checked( $value['delete_data'] ); ?>> <?php esc_html_e( 'Delete all Mintchat data', 'mintchat' ); ?></label>
			<p class="description" id="mintchat-delete-help"><?php esc_html_e( 'Off: deleting the plugin keeps contacts, the contact modal and page messages, and installing it again brings everything back. On: they are erased for good. Page content is never changed.', 'mintchat' ); ?></p>
		</td></tr>
	</table>
	<?php
}

/** Backup download and restore (outside the settings form). */
function settings_backup() {
	$notice = get_transient( 'mintchat_import_' . get_current_user_id() );
	if ( is_string( $notice ) ) {
		delete_transient( 'mintchat_import_' . get_current_user_id() );
		list( $type, $text ) = explode( ':', $notice, 2 );
		printf( '<div class="notice notice-%s"><p>%s</p></div>', 'ok' === $type ? 'success' : 'error', esc_html( $text ) );
	}
	?>
	<h2><?php esc_html_e( 'Backup', 'mintchat' ); ?></h2>
	<p><?php esc_html_e( 'One file with the contacts, the contact modal and the page messages: keep it, or restore it here or on another site.', 'mintchat' ); ?></p>
	<p><a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mintchat_export' ), 'mintchat_export' ) ); ?>"><?php esc_html_e( 'Download backup', 'mintchat' ); ?></a></p>
	<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" enctype="multipart/form-data">
		<input type="hidden" name="action" value="mintchat_import">
		<?php wp_nonce_field( 'mintchat_import' ); ?>
		<label for="mintchat-import-file"><?php esc_html_e( 'Restore from a backup file (.json)', 'mintchat' ); ?></label>
		<input type="file" id="mintchat-import-file" name="file" accept=".json,application/json" required>
		<?php submit_button( __( 'Restore', 'mintchat' ), 'secondary', '', false ); ?>
		<p class="description"><?php esc_html_e( 'Restoring replaces the current contacts and contact modal.', 'mintchat' ); ?></p>
	</form>
	<?php
}
