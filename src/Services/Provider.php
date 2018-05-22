<?php

namespace Snap\Core\Services;

class Provider implements Interfaces\Provider
{
	/**
	 * Called after all service providers have been registered and are available.
	 *
	 * @since 1.0.0
	 */
	public function boot()
	{
		// hooks/filters? call the whoops bootstrap
	}

	/**
	 * Register any services into the container.
	 *
	 * @since 1.0.0
	 */
	public function register()
	{

	}
}