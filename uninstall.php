<?php
/** Delete Mintchat settings and per-post messages only when the site owner chose so; leave post content untouched.
 *
 * @package Mintchat
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/** Remove this site's plugin data. */
function mintchat_uninstall_site() {
	// Data stays unless the site owner asked for it to go (Settings > Mintchat > Your data).
	$settings = get_option( 'mintchat_settings' );
	if ( ! is_array( $settings ) || empty( $settings['delete_data'] ) ) {
		return;
	}
	delete_option( 'mintchat_settings' );
	delete_post_meta_by_key( 'mintchat_post_message' );
}

if ( is_multisite() ) {
	foreach ( get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	) as $mintchat_site_id ) {
		switch_to_blog( $mintchat_site_id );
		mintchat_uninstall_site();
		restore_current_blog();
	}
} else {
	mintchat_uninstall_site();
}
