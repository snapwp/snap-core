<?php

namespace Snap\Templating;

class View
{
	private $strategy = null;

	public function __construct(Templating_Interface $strategy)
	{
		$this->strategy = $strategy;
	}

	public function render($slug, $data = [])
	{
		$this->strategy->render($slug, $data);
	}	

	public function partial($slug, $data = [])
	{
		$this->strategy->partial($slug, $data);
	}
}