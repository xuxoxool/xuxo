<?php
defined('BASEPATH') OR exit('No direct script access allowed');
defined('DIR_SEPARATOR') || define('DIR_SEPARATOR', '/');
defined('XUXO_VERSION') || define('XUXO_VERSION', '1.0.0');

class Layouts {
	protected $path = 'layouts';
	protected $layout = 'default';
	protected $usemodule = TRUE;
	protected $view = null;
	protected $includes = null;
	protected $data = array();

	function __construct() {
		return $this;
	}

	function setPath($path) {
		$this->path = $path;
		return $this;
	}
	
	function getPath() { return $this->path; }

	function setLayout($layout, $usemodule = TRUE) {
		$this->layout = $layout;
		$this->usemodule = $usemodule;
		return $this;
	}
	
	function getLayout() { return $this->layout; }
	
	function setView($path, $data = array()) {
		$v = count($this->view);
		$this->view[$v] = $path;
		
		if($data && !empty($data)) $this->setData($data);
		
		return $this;
	}
	
	function getView($index = NULL) { return ($index === NULL) ? $this->view : ((isset($this->view[$index])) ? $this->view[$index] : NULL); }
	
	function setData($data) {
		if($data && !empty($data)) {
			foreach($data as $key=>$value) {
				$this->data[$key] = $value;
			}
		}
		return $this;
	}
	
	function getData($key = NULL) { return ($key) ? ((isset($this->data[$key])) ? $this->data[$key] : NULL) : $this->data; }
	
	public function setIncludes($path, $prepend_base_url = true){
		$inc = NULL;
		if($prepend_base_url) {
			$inc = base_url() . $path;
		}else{
			$inc = $path;
		}
		
		if($inc) {
			$i = count($this->includes);
			$this->includes[$v] = $inc;
		}
		
		return $this;
	}
	
	function getIncludes($index = NULL) { return ($index === NULL) ? $this->includes : ((isset($this->includes[$index])) ? $this->includes[$index] : NULL); }
	
	function includes($inc = NULL, $prepend_base_url = true) {
		if($inc) {
			if(!is_array($inc)) {
				$this->setIncludes($inc, $prepend_base_url);
			} else {
				foreach($inc as $path) {
					$this->setIncludes($path, $prepend_base_url);
				}
			}
		}
			
		$return = NULL;
		if($this->includes) {
			foreach($this->includes as $include){
				if(preg_match('/js$/', $include)){
					$return .= '<script type="text/javascript" src="' . $include . '"></script>\n';
				}elseif(preg_match('/css$/', $include)){
					$return .= '<link type="text/css" href="' . $include . '" rel="stylesheet" />\n';
				}else{
					$return .= '<script type="text/javascript" src="' . $include . '"></script>\n';
				}
			}
		}
		
		return $return;
	}
	
	function render($view = NULL, $data = NULL, $layout = NULL, $asmodule = TRUE) {
		if($layout)	$this->setLayout($layout, $asmodule);
		if($data && !empty($data)) $this->setData($data);
		if($view)	$this->setView($view, $data);
		
		if(strpos(strtolower($this->layout),'.php') === FALSE) $this->layout .= ".php";
		
		$c =& getControllerInstance();
		$c->load->layout($this->layout, $this->data, $this->usemodule);
		
		return $this;
	}
	
	function display($data = array()) {
		$c =& getControllerInstance();
		$m =& getModuleInstance();
		
		if($data && !empty($data)) $this->setData($data);
		if(!empty($this->view)) {		
			foreach($this->view as $key=>$value) {
				$c->load->view($value, $this->data);
			}
		}
		
		return $this;
	}
}



























?>