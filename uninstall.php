<?php
// If uninstall not called from WordPress exit
defined( 'WP_UNINSTALL_PLUGIN' ) or die( 'Cheatin&#8217; uh?' );

// Delete Imagify options
delete_site_option( 'imagify_settings' );

// Delete all transients
delete_site_transient( 'imagify_check_licence_1' );

// Delete all user meta related to Imagify
delete_metadata( 'user', '', '_imagify_ignore_notices', '', true );