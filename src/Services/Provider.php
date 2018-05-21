<?php

namespace Snap\Core\Services;

class Provider implements Interfaces\Provider
{
	public $services = [];
	
	public $factories = [];

	public function __construct()
	{
		
	}

	/**
	 * Called after all service providers have been registered.
	 *
	 * @since 1.0.0
	 */
	public function boot()
	{
		// hooks/filters? call the whoops bootstrap
	}

	/**
	 * Register any
	 * @return [type] [description]
	 */
	public function register()
	{

	}
}