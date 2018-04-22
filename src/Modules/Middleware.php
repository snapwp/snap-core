<?php

namespace Snap\Modules;

use Snap\Hookable;
use Snap\Request;

/**
 * Additional code directly affecting admin area, or the removal of functionality.
 *
 * @since  1.0.0
 */
class Middleware extends Hookable {

	protected $filters = [
		'snap_middleware_is_logged_in' => 'is_logged_in'
	];


	public function is_logged_in( Request $request, $redirect = null ) {
		if ( is_user_logged_in() ) {
			return true;
		} else {
			if ( $redirect === 'login' ) {
				Request::redirect_to_login();
			} elseif ( $redirect === '404' ) {
				Request::set_404();
			} elseif ( $redirect === 'admin' ) {
				Request::redirect_to_admin();
			} else {
				return false; 
			}
		}
	}

}