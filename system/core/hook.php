<?php

defined('BASEPATH') OR exit('No direct script access allowed');
defined('DIR_SEPARATOR') || define('DIR_SEPARATOR', '/');
defined('XUXO_VERSION') || define('XUXO_VERSION', '1.0.0');
class Xuxo_Hook {
	protected $points = NULL;
	protected $ready = FALSE;
	protected $load = NULL;
	
	public function __construct() {		
		$this->load = loadCore('loader');
		
		$hookfilepath = APPPATH."config/hook.php";
		
		if (file_exists($hookfilepath)) {
			require_once($hookfilepath);
		
			if (!isset($hook) || !is_array($hook)) {	
				throw new Exception('Hook elements are not set correctly');
				exit(5);
			}
			
			if($points = array_keys($hook)) {
				foreach($points as $point) {
					if(!in_array(strtolower($point), array('precall','postcall','prerender','postrender'))) {
						throw new Exception('Hook point is invalid [only precall / postcall / prerender / postrender]');
						exit(5);
					}
				}
			}
		
			$this->points = $hook;
			$this->ready = TRUE;
		}
			
		return $this;
	}
	
	public function trigger($point) {
		if(!$this->ready) return;
		if(!$point) throw new Exception('Cannot load hook with empty point name.');
		if(!in_array(strtolower($point), array('precall','postcall','prerender','postrender'))) return;
		$point = strtolower($point);
		
		return (isset($this->points[$point])) ? $this->points[$point]() : $this;
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
}