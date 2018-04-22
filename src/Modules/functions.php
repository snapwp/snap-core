<?php

use Snap\Snap;
use Snap\Hookable;
use Snap\Modules\Utils;

/**
 * A helper function for calling Snap::module.
 *
 * Render a module template from the modules directory, optionally passing data to the template.
 *
 * @since  1.0.0
 * 
 * @see Snap::module
 */
function snap_render_module( $name, $slug = '', $data = null, $extract = false ) {
    Snap::module( $name, $slug, $data, $extract );
}

/**
 * Counts the number of widgets for a given sidebar ID.
 *
 * @since 1.0.0
 * 
 * @see Snap\Utils::get_widget_count
 */
function snap_get_widget_count( $sidebar_id ) {
    return Utils::get_widget_count( $sidebar_id );
}

/**
 * Returns the translated role of the current user or for a given user object. 
 *
 * @since 1.0.0
 * 
 * @see Snap\Utils::get_user_role
 */
function snap_get_user_role( $user = null ) {
    return Utils::get_user_role( $user );
}


/**
 * Runs the standard WP loop, and renders a module for each post.
 *
 * A replacement for the standard have_posts loop that also works on custom WP_Query objects,
 * and allows easy module choice for each iteration.
 *
 * @since 1.0.0
 * 
 * @see Snap\Utils::get_user_role
 */
function snap_loop( $module = null, $module_overrides = null, $wp_query = null ) {
    Snap::loop( $module, $module_overrides, $wp_query );
}

/**
 * Lists debug info about all callbacks for a given hook.
 *
 * Returns information for all callbacks in order of execution and priority.
 *
 * @since  1.0.0
 * 
 * @see Snap_Hookable::debug_hook
 */
function snap_debug_hook( $hook ) {
	return Hookable::debug_hook( $hook );
}

/**
 * Gets the current full URL of the page with querystrings, host, and scheme.
 *
 * @since  1.0.0
 * 
 * @see Snap\Utils::get_current_url
 */
function snap_get_current_url( $remove_query = false ) {
    return Utils::get_current_url( $remove_query );
}
