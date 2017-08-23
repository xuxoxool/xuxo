<?php

defined('BASEPATH') OR exit('No direct script access allowed');
defined('DIR_SEPARATOR') || define('DIR_SEPARATOR', '/');
defined('XUXO_VERSION') || define('XUXO_VERSION', '1.0.0');

class Xuxo_Loader {
	
	public function __construct() {
		return $this;
	}
	
	/**
	* AUTO
	*/
	public function auto($system = TRUE) {
		if($system) {
			$path = rtrim(APPPATH,DIR_SEPARATOR).DIR_SEPARATOR.'config/autoload.php';
		} else {			
			$module = getModuleInstance();				
			$module_path = ($module) ? $module->getPath() : NULL;
			$path = rtrim($module_path,DIR_SEPARATOR).DIR_SEPARATOR.'config/autoload.php';
		}
		
		if (file_exists($path)) {
			require($path);
		} else {
			return NULL;
		}
		
		$loadCalled = array();
		$loadAllowed = array('libraries','model','language','hooks','helpers');
		
		if(isset($autoload) && !empty($autoload) && is_array($autoload)) {
			foreach($autoload as $key=>$values) {
				$key = strtolower($key);
				if(in_array($key,$loadAllowed) && is_array($values) && !empty($values)) {
					foreach($values as $k=>$v) {
						if(is_string($k)) {
							$class = $k;
							$name = $v;
						} else {
							$class = $v;
							$name = $v;
						}
						$loadCalled[$key][$class] = array(
							'name' => $name,
							'system' => $system
						);
					}
				}
			}
		}
		unset($autoload);
		
		if(!empty($loadCalled)) {
			foreach($loadCalled as $key=>$values) {
				$key = strtolower($key);
				if(in_array($key,$loadAllowed) && is_array($values) && !empty($values)) {
					if($key == 'libraries') {
						foreach($values as $k=>$v) {
							$this->library($k, array(), ((isset($v['name']) && $v['name']) ? $v['name'] : strtolower($k)), ((isset($v['system'])) ? $v['system'] : 0));
						}
					}
					if($key == 'helpers') {
						foreach($values as $k=>$v) {
							$this->helper($k, ((isset($v['system'])) ? $v['system'] : 0));
						}
					}
				}
			}
		}

		return $this;
	}
	
	/**
	* MODULE
	*/
	public function module($module) {
		if(!$module) throw new Exception('Cannot load module with empty name.');
		global $_bootstraps;
		
		if(isset($_bootstraps[$module])) return $_bootstraps[$module];
		
		$modules = modules();
		$moduleNameList = (!empty($modules)) ? array_keys($modules) : array();
		
		$set = TRUE;
		if(!in_array($module, $moduleNameList)) {
			$set = FALSE;
			saveLog("ERROR", "Module '".$module."' does not exist in configuration.");
		}		
		if(!$set) throw new Exception("Module '".$module."' does not exist in configuration.");
		
		$path = module($module);
		if(!file_exists($path) || !is_dir($path)) throw new Exception('Invalid module path ('.$path.') for module '.$module.' or module path does not exist');
		
		$bootstrap_name = str_replace(' ','_',ucwords(strtolower(	str_replace('_',' ',$module)	)));
		$bootstrap_file = $path.DIR_SEPARATOR.'bootstrap.php';
		if(!file_exists($bootstrap_file) || !is_file($bootstrap_file)) throw new Exception('Invalid bootstrap file');
		
		loadCore('bootstrap');
		require_once($bootstrap_file);
		if(!class_exists($bootstrap_name)) throw new Exception('Class '.($bootstrap_name).' does not exist');
		
		$bootstrap = new $bootstrap_name();
		$bootstrap->setPath($path);
		$bootstrap->getConfig();
		if(!$bootstrap->load) $bootstrap->load = loadCore('loader');
		
		$_bootstraps[$module] =& $bootstrap;
		
		return $_bootstraps[$module];
	}
	
	/**
	* CONTROLLER
	*/
	public function controller($controller, $from = NULL) {		
		if(!$controller) throw new Exception('Cannot load controller with empty name.');
		
		if($from) {
			return $this->module($from)->render($controller)->getController($controller);
		} else {
			$module = getModuleInstance();
			return $module->setController($controller);
		}
	}
	
	/**
	* VIEW
	*/
	public function view($view, $data = array(), $module = NULL, $return = FALSE) {
		
		if(!$view) throw new Exception('Invalid view path');
		
		$currentModule = getModuleInstance();
		$currentController = getControllerInstance();
		
		$module = ($module) ? $this->module($module) : getModuleInstance();
		$controller = getControllerInstance(get_class($module));
		$module_name = get_class($module);
		$module_path = $module->getPath();
		
		$view_path = $module_path.DIR_SEPARATOR.'views';
		if(!file_exists($view_path) || !is_dir($view_path)) throw new Exception('Module <strong>'.$module_name.'</strong> view path is not valid or does not exist');
		
		$view = (preg_replace('/\\.[^.\\s]{1,10}$/', '', trim($view,DIR_SEPARATOR))).".php";
		$view_file = $view_path.DIR_SEPARATOR.(trim($view,DIR_SEPARATOR));
		if(!file_exists($view_file) || !is_file($view_file)) throw new Exception('Module <strong>'.$module_name.'</strong> view '.$view.' does not exist');
		
		if(!$controller) {
			if(get_class($currentModule) !== $module_name) {
				if(!$currentController) throw new Exception('Current controller is not initialized');
				$controller = $currentController;
			}
		}
		if(!$controller) throw new Exception('Module controller is not initialized');
		$controller->_view[] = $view_file;
		
		if($data && is_array($data) && !empty($data)) {
			foreach($data as $key=>$value) {
				if(in_array(strtolower($key), array('load','bootstrap','_controller','_action','_view','_data','_model','_library','_layout')))
					throw new Exception('Cannot assign data with reserved names (load, bootstrap, _controller, _action, _view, _data, _model,_library, _layout)');
				
				if(trim($key) == "") throw new Exception('Cannot assign data with empty name');
				$controller->setData($key,$value);
			}
		}
		
		return $controller->renderView($view_file, $return);
	}
	
	/**
	* LAYOUT
	*/
	public function layout($layout, $data = array(), $usemodule = TRUE) {
		if(!$layout) throw new Exception('Invalid layout path');
		
		$module = getModuleInstance();
		$module_path = $module->getPath();
		$controller = getControllerInstance(get_class($module));
		
		$layout_path = ($usemodule) ? $module_path.DIR_SEPARATOR.'layouts' : APPPATH."layouts";
		if(!file_exists($layout_path) || !is_dir($layout_path)) throw new Exception('Module layout path is not valid or does not exist');
		
		$layout = (preg_replace('/\\.[^.\\s]{1,10}$/', '', trim($layout,DIR_SEPARATOR))).".php";
		$layout_file = $layout_path.DIR_SEPARATOR.(trim($layout,DIR_SEPARATOR));
		if(!file_exists($layout_file) || !is_file($layout_file)) throw new Exception('Module layout '.$layout_file.' does not exist');
		
		if($data) {
			foreach($data as $key=>$value) {
				$controller->setData($key, $value);
			}
		}
		
		$controller->_layout = $layout_file;
		$controller->renderLayout();
		return $layout_file;
	}
	
	/**
	* MODEL
	*/
	public function model($model, $name = NULL, $from = NULL, $config = NULL) {
		if(!$model) throw new Exception('Cannot load model with empty name.');
		
		$appendpath = NULL;
		$model = str_replace('\\',DIR_SEPARATOR,$model);
		if(strpos($model, DIR_SEPARATOR)) {
			$mexp = explode(DIR_SEPARATOR, $model);
			$model = end($mexp);
			for($i = 0; $i < (count($mexp)-1); $i++) {
				$appendpath .= DIR_SEPARATOR.$mexp[$i];
			}
		}
		$name = ($name === NULL) ? strtolower($model) : $name;
		
		global $_bootstraps;
		
		$module = getModuleInstance(); // CURRENTLY LOADED MODULE
		if($from !== NULL && $from !== TRUE) {
			if(!isset($_bootstraps[$from])) {
				//throw new Exception('Module '.$from.' has not been instantiated.');
				$module_called = $this->module($from);
			} else {
				$module_called = $_bootstraps[$from];
			}
		} else {
			$module_called = $module;
		}
		
		$module_name = ($from === TRUE) ? NULL : str_replace(' ','_',ucwords(strtolower(	str_replace('_',' ',get_class($module_called))	)));
		$module_path = ($from === TRUE) ? rtrim(APPPATH,DIR_SEPARATOR) : rtrim($module_called->getPath(), DIR_SEPARATOR);
		$model_path = $module_path.DIR_SEPARATOR.'models';
		$model_name = str_replace(' ','_',ucwords(strtolower(	str_replace('_',' ',$model)	))) . "_Model";
		$model_name = ($from === TRUE) ? $model_name : $module_name . "_" . $model_name;
		
		if(!file_exists($model_path) || !is_dir($model_path)) throw new Exception('Module model path ('.$model_path.') is not valid or does not exist');
		
		$model_file_name = (preg_replace('/\\.[^.\\s]{1,10}$/', '', $model)).".php";
		$model_file_path = $model_path.$appendpath.DIR_SEPARATOR.(trim(strtolower($model_file_name),DIR_SEPARATOR));
		
		if(!file_exists($model_file_path) || !is_file($model_file_path))
			throw new Exception('Module model \''.$model_name.'\' file \''.$model_file_path.'\' does not exist');		
		
		loadCore('model');
		require_once($model_file_path);
		if(!class_exists($model_name)) throw new Exception('Class '.($model_name).' does not exist');
		if(isset($controller->_model) && isset($controller->_model[$name]))
				throw new Exception('Instance with name '.$name.' has already been instantiated. Cannot duplicate.');
			
		$controller = ($module) ? getControllerInstance(get_class($module)) : NULL;
		if($controller && isset($controller->_model) && isset($controller->_model[$name])) return $controller->_model[$name];
		
		$m = new $model_name();
		$m->init($config);
		
		if($controller) {
			$controller->_model[$name] = &$m;
			$controller->$name = $m;
			
			return $controller->_model[$name];
		} else {
			return $m;
		}
	}
	
	/**
	* LIBRARY
	*/
	public function library($library, $param = array(), $name = NULL, $system = TRUE) {
		if(!$library) throw new Exception('Cannot load library with empty name.');
		$name = ($name === NULL) ? strtolower($library) : strtolower($name);
		
		$module = getModuleInstance();
		$controller = getControllerInstance(get_class($module));	
		if(isset($controller->_library) && isset($controller->_library[$name])) return $controller->_library[$name];
		
		if($system) {
			$lib_path = SYSPATH.'libraries';
		} else {			
			$module_path = ($module) ? $module->getPath() : NULL;
			$lib_path = $module_path.DIR_SEPARATOR.'libraries';
		}
		if(!file_exists($lib_path) || !is_dir($lib_path)) throw new Exception((($system)?'System':'Module').' library path is not valid or does not exist');
		
		$lib_file_name = (preg_replace('/\\.[^.\\s]{1,10}$/', '', $library)).".php";
		$lib_file_path = $lib_path.DIR_SEPARATOR.(trim($lib_file_name,DIR_SEPARATOR));
		
		if(!file_exists($lib_file_path) || !is_file($lib_file_path))
			throw new Exception((($system)?'System':'Module').' library \''.$library.'\' file \''.$lib_file_name.'\' does not exist');		
		
		require_once($lib_file_path);
		if(!class_exists($library)) throw new Exception('Class '.($library).' does not exist');
		if(isset($controller->_library) && isset($controller->_library[$name]))
				throw new Exception('Instance with name '.$name.' has already been instantiated. Cannot duplicate.');
		
		$reflector = new ReflectionClass($library);
		$l = $reflector->newInstanceArgs(array($param));
	
		$module->_library[$name] = $l;
		$module->$name = $l;
		
		if($controller) {
			$controller->_library[$name] = $l;
			$controller->$name = $l;
		}
		
		return $l;
	}
	
	/**
	* HELPER
	*/
	public function helper($helper, $system = TRUE) {
		if(!$helper) throw new Exception('Cannot load helper with empty name.');
		$name = strtolower($helper);
		
		$module = getModuleInstance();
		$controller = getControllerInstance(get_class($module));	
		if(isset($controller->_helper) && isset($controller->_helper[$name])) return $controller->_helper[$name];
		
		if($system) {
			$help_path = APPPATH.'helpers';
		} else {			
			$module_path = ($module) ? $module->getPath() : NULL;
			$help_path = $module_path.DIR_SEPARATOR.'helpers';
		}
		if(!file_exists($help_path) || !is_dir($help_path)) throw new Exception((($system)?'System':'Module').' helper path ('.$help_path.') is not valid or does not exist');
		
		$help_file_name = (preg_replace('/\\.[^.\\s]{1,10}$/', '', $helper)).".php";
		$help_file_path = $help_path.DIR_SEPARATOR.(trim($help_file_name,DIR_SEPARATOR));
		
		if(!file_exists($help_file_path) || !is_file($help_file_path))
			throw new Exception((($system)?'System':'Module').' helper \''.$helper.'\' file \''.$help_file_path.'\' does not exist');		
		
		require_once($help_file_path);
		
		if($controller) {
			$controller->_helper[$name] = $name;
		} elseif($module) {
			$module->_helper[$name] = $name;
		}
		
		return $name;
	}
	
	/**
	* DEBUG
	*/
	public function _debug($item) {
		echo "<pre>";
		print_r($item);
		echo "</pre>";
	}
}






























