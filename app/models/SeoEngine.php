<?php
namespace App\Models;
class SeoEngine {

	protected $baseDomain;
	public function __construct($baseDomain)
	{
		$this->baseDomain = $baseDomain;
	}


}