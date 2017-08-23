<?php

defined('BASEPATH') OR exit('No direct script access allowed');
defined('DIR_SEPARATOR') || define('DIR_SEPARATOR', '/');
defined('XUXO_VERSION') || define('XUXO_VERSION', '1.0.0');
class Xuxo_Controller {
	public $load;
	public $bootstrap;
	protected $_data = NULL;
	protected $_layout_rendered = false;
	protected $_ob_level = 1;
	
	public function __construct() {
		if(!isset($this->load)) $this->load = loadCore('loader');
		$this->load->auto(TRUE);
		$this->load->auto(FALSE);
		return $this;
	}
	
	public static function &getInstance() { return self::$instance; }
	
	public function getBootstrap($nameonly = FALSE) {
		if($nameonly) {
			return $this->bootstrap;
		} else {
			global $_bootstraps;		
			return (isset($_bootstraps[$this->bootstrap])) ? $_bootstraps[$this->bootstrap] : NULL;
		}
	}
	
	public function getClass() { return get_class($this); }
	
	public function getAction() { return (isset($this->_action)) ? $this->_action : NULL; }
	
	public function renderLayout() {
		if(isset($this->_data) && !empty($this->_data)) {
			foreach($this->_data as $key=>$value) {
				$$key = $value;
			}
		}
		
		if(isset($this->_layout) && trim($this->_layout)) {
			if(file_exists($this->_layout)) {
				$this->_layout_rendered = true;
				require_once($this->_layout);
			}
		}
		
		return $this;
	}
		
	public function renderView($view, $return = FALSE) {	
		if(isset($this->_data) && !empty($this->_data)) {
			foreach($this->_data as $key=>$value) {
				$$key = $value;
			}
		}
		
		ob_start();
		if(file_exists($view)) require($view);
		
		if ($return === TRUE) {
			$buffer = ob_get_contents();
			@ob_end_clean();
			return $buffer;
		}
		
		if (ob_get_level() > $this->_ob_level + 1) {
			ob_end_flush();
		} else {
			@ob_end_clean();
		}
		
		return $this;
	}
	
	public function setData($key, $value) { $this->_data[$key] = $value; return $this; }
	
	public function getData($key = NULL) { return ($key) ? ((isset($this->_data[$key])) ? $this->_data[$key] : NULL) : $this->_data; }
	
	public function _debug($item) {
		echo "<pre>";
		print_r($item);
		echo "</pre>";
	}
}