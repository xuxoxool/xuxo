<?php

defined('BASEPATH') OR exit('No direct script access allowed');
defined('DIR_SEPARATOR') || define('DIR_SEPARATOR', '/');
defined('XUXO_VERSION') || define('XUXO_VERSION', '1.0.0');
class Xuxo_Bootstrap {
	
	protected $_path = NULL;
	protected $_config = NULL;
	protected $_controller = NULL;
	protected $_action = NULL;
	public $load = NULL;
	
	public function __construct() {
		if($this->load) $this->load->auto(TRUE);
		if($this->load) $this->load->auto(FALSE);
		
		return $this;
	}
	
	public function render($controller = NULL, $action = NULL, $param = array()) {		
		$config = $this->getConfig();
		
		if(!$this->load) $this->load = loadCore('loader');
		if($this->load) $this->load->auto(TRUE);
		if($this->load) $this->load->auto(FALSE);
		
		if(!$controller) $controller = ($config['default-controller']) ? $config['default-controller'] : 'index';
		$this->setController($controller, $param);
		
		if(!$action) $action = ($config['default-action']) ? $config['default-action'] : 'index';
		$this->setAction($controller, $action, $param);
		
		return $this;
	}
	
	/**
	* SET FUNCTIONS
	*/
	public function setPath($path) {	
		if(!isset($path) || !$path) throw new Exception('Invalid Bootstrap path during initialization');
		
		return ($this->_path = $path);
	}
		
	public function setController($controller, $param = array()) {	
		$module = get_class($this);
		if(!$controller) throw new Exception('Cannot render controller with empty name [module: '.$module.']');
		
		$module_name = str_replace(' ','_',ucwords(strtolower(	str_replace('_',' ',$module)	)));
		$controller_name = $module_name . "_" . (str_replace(' ','_',ucwords(strtolower(	str_replace('_',' ',$controller)	)))) . "_Controller";
		$controller_path = $this->_path.DIR_SEPARATOR.'controllers';
		$controller_file = $controller_path.DIR_SEPARATOR.$controller.'.php';
		
		if(!file_exists($controller_path) || !is_dir($controller_path)) throw new Exception('Invalid controller path.');
		if(!file_exists($controller_file) || !is_file($controller_file)) throw new Exception('Controller file \''.$controller_file.'\'does not exist.');
		
		loadCore('controller');
		require_once($controller_file);
		if(!class_exists($controller_name)) throw new Exception('Class '.($controller_name).' does not exist');
		
		$param = (empty($param)) ? getCall('param') : $param;
		$param = ($param && is_array($param)) ? $param : array();
		
		if(method_exists($this, 'preInitController'))	
			call_user_func_array(	array($this,'preInitController'), $param	);

		$this->_controller[$controller] = new $controller_name();
		
		$vars = get_object_vars($this);
		if($vars) {
			foreach($vars as $key=>$value) {	
				if(trim($key) == "") continue;
				if(trim(substr($key,0,1)) == '_') continue;
				if(in_array(strtolower($key), array('load','bootstrap'))) continue;
				
				$this->_controller[$controller]->$key = $value;
			}
		}
		
		$initparam = array_merge(array('controller'=>$this->_controller[$controller]), $param);
		
		$this->_controller[$controller]->bootstrap = get_class($this);
		if(!$this->_controller[$controller]->load) $this->_controller[$controller]->load = loadCore('loader');
		
		if(method_exists($this, 'postInitController'))
			call_user_func_array(	array($this,'postInitController'), $initparam	);
		return $this->_controller[$controller];
	}
	
	public function setAction($c, $action = NULL, $param = array()) {
		$module = get_class($this);
		if(!$c) throw new Exception('Cannot render controller with empty name [module: '.$module.']');
		
		$controller = (isset($this->_controller[$c]) && $this->_controller[$c]) ? $this->_controller[$c] : NULL;
		if(!$controller) throw new Exception('Controller '.$c.' has not been initialized [module: '.$module.']');
		if(!$action) throw new Exception('Cannot render action with empty name [module: '.$module.', controller: '.(get_class($controller)).']');
			
		if(!method_exists($controller, $action)) throw new Exception('Action '.$action.' does not exist [module: '.$module.', controller: '.(get_class($controller)).']');
		$controller->_action = $action;
		
		$param = (empty($param)) ? getCall('param') : $param;
		$param = ($param && is_array($param)) ? $param : array();
		$initparam = array_merge(array('controller'=>$controller), $param);
		
		if(method_exists($this, 'preInitAction')) call_user_func_array(	array($this,'preInitAction'), $initparam	);
		
		call_user_func_array(	array($controller,$action), $param	);
		
		if(method_exists($this, 'postInitAction')) call_user_func_array(	array($this,'postInitAction'), $initparam	);
		
		return $this->_action;
	}
		
	public function setConfig($key, $value = NULL) {
		if(!$key) return NULL;		
		return ($this->_config[$key] = $value);
	}
	
	/**
	* GET FUNCTIONS
	*/
	public function getPath() { return $this->_path; }
	
	public function getControllers() { return $this->_controller; }
	public function getController($controller) { return (isset($this->_controller[$controller])) ? $this->_controller[$controller] : NULL; }
	
	public function getConfig() {
		$path = $this->_path.DIR_SEPARATOR.'config/config.php';
		if (file_exists($path)) {
			require($path);
		} else {	
			throw new Exception('Module configuration file is not found');
			exit(5);
		}
		
		if (!isset($config) || (isset($config) && (!is_array($config) || empty($config)))) {	
			throw new Exception('Module Configuration elements are not set correctly');
			exit(5);
		}

		return ($this->_config = $config);
	}
	
	public function getCall($index = NULL) {
		global $_uri;
		
		if(!$_uri) throw new Exception('URI Handler has not been set');
		
		if ($index === NULL) {
			return $_uri->getCall();
		} else {
			$call = $_uri->getCall();
			return (isset($call[$index])) ? $call[$index] : NULL;
		}
	}
	
	public function getCallString() {
		global $_uri;
		
		if(!$_uri) throw new Exception('URI Handler has not been set');
		
		return $_uri->getCallString();
	}
	
	/**
	* HELPERS
	*/
	public function _debug($item) {
		echo "<pre>";
		print_r($item);
		echo "</pre>";
	}
}

























