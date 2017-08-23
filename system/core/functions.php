<?php

defined('BASEPATH') OR exit('No direct script access allowed');
defined('DIR_SEPARATOR') || define('DIR_SEPARATOR', '/');
defined('XUXO_VERSION') || define('XUXO_VERSION', '1.0.0');

/**
* PHP VERSION
*/
if (!function_exists('check_php')) {
	function check_php($version) {
		static $_is_php;
		$version = (string) $version;
		if (!isset($_is_php[$version])) $_is_php[$version] = version_compare(PHP_VERSION, $version, '>=');
		return $_is_php[$version];
	}
}

/**
* XUXO VERSION
*/
if(!function_exists('xuxo_version')) {
	function xuxo_version() { return XUXO_VERSION; }
}

/**
* LOAD CORE CLASS
*/
if (!function_exists('loadCore')) {
	function &loadCore($class, $param = NULL) {
		static $_classes = array();
		
		$class = trim(str_replace(" ","_",(ucwords(strtolower(str_replace("_"," ",$class))))));
		if (isset($_classes[$class])) return $_classes[$class];
		
		$className = NULL;
		$path_to_class = SYSPATH.'core'.DIR_SEPARATOR.(strtolower($class)).'.php';
		if (file_exists($path_to_class)) {
			$className = 'Xuxo_'.$class;
			if (class_exists($className, FALSE) === FALSE) require_once($path_to_class);
		}
		
		if ($className === NULL) {	
			throw new Exception('The core class file '.(strtolower($class)).'.php is not found');
			exit(5);
		}

		$_classes[$class] = (isset($param) && $param) ? new $className($param) : new $className();
		return $_classes[$class];
	}
}

/**
* GET ALL CONFIGS
*/
if (!function_exists('configs')) {
	function &configs(Array $override = array()) {
		static $config;

		if (empty($config)) {
			$path = APPPATH.'config/config.php';
			if (file_exists($path)) {
				require($path);
			} else {	
				throw new Exception('Configuration file is not found');
				exit(5);
			}
			
			if (!isset($config) || !is_array($config)) {	
				throw new Exception('Configuration elements are not set correctly');
				exit(5);
			}
		}
		
		foreach ($override as $key=>$value) {
			$config[$key] = $value;
		}

		return $config;
	}
}

/**
* GET A CONFIG ITEM
*/
if (!function_exists('config')) {
	function config($item, $child = NULL) {
		static $_config;
		if (empty($_config)) $_config[0] =& configs();
		
		if($child === NULL) {
			return isset($_config[0][$item]) ? $_config[0][$item] : NULL;
		} else {
			return isset($_config[0][$item][$child]) ? $_config[0][$item][$child] : NULL;
		}
	}
}

/**
* GET ALL MODULES
*/
if (!function_exists('modules')) {
	function &modules() {
		static $modules;

		if (empty($modules)) {
			$path = APPPATH.'config/modules.php';
			if (file_exists($path)) {
				require($path);
			} else {	
				throw new Exception('Module configuration file is not found');
				exit(5);
			}
			
			if (!isset($modules) || !is_array($modules)) {	
				throw new Exception('Module configuration elements are not set correctly');
				exit(5);
			}
		}
		return $modules;
	}
}

/**
* GET A MODULE BY NAME
*/
if (!function_exists('module')) {
	function module($name, $child = NULL) {
		static $_module;
		if (empty($_module)) $_module[0] =& modules();
		
		$name = strtolower($name);
		if($child === NULL) {
			return isset($_module[0][$name]) ? $_module[0][$name] : NULL;
		} else {
			return isset($_module[0][$name][$child]) ? $_module[0][$name][$child] : NULL;
		}
	}
}

/**
* SAVE LOG
*/
if (!function_exists('saveLog')) {
	function saveLog($level, $message) {
		static $_log;
		if ($_log === NULL) $_log[0] =& loadCore('log_handler');
		$_log[0]->write($level, $message);
	}
}

/**
* GENERAL ERROR HANDLER
*/
if (!function_exists('error_handler')) {
	function error_handler($severity, $message, $filepath, $line) {
		$_error =& loadCore('exceptions');
		$_error->saveLog($severity, $message, $filepath, $line);
		
		if (str_ireplace(array('off', 'none', 'no', 'false', 'null'), '', ini_get('display_errors'))) $_error->phpError($severity, $message, $filepath, $line);
		exit(1);
	}
}

/**
* EXCEPTION HANDLER
*/
if (!function_exists('exception_handler')) {	
	function exception_handler($exception) {
		$_error =& loadCore('exceptions');
		$_error->saveLog('error', 'Exception: '.$exception->getMessage(), $exception->getFile(), $exception->getLine());
		if (str_ireplace(array('off', 'none', 'no', 'false', 'null'), '', ini_get('display_errors'))) $_error->exception($exception);
		exit(1);
	}
}

/**
* SHUTDOWN HANDLER
*/
if (!function_exists('shutdown_handler')) {
	function shutdown_handler() {
		$last_error = error_get_last();
		if (isset($last_error) && ($last_error['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING)))
			error_handler($last_error['type'], $last_error['message'], $last_error['file'], $last_error['line']);
	}
}

/**
* GET BASE URL
*/
if (!function_exists('base_url')) {
	function base_url($path = NULL) {
		return Xuxo_URI::getBaseUrl().DIR_SEPARATOR.(($path)?$path.DIR_SEPARATOR:NULL);
	}
}

/**
* GET APPLICATION URL
*/
if (!function_exists('app_path')) {
	function app_path($path = NULL) {
		return (base_url()).APPPATH.(($path)?$path.DIR_SEPARATOR:NULL);
	}
}

/**
* GET MODULE URL
*/
if (!function_exists('module_url')) {
	function module_url($path = NULL) {
		if ($instance = getModuleInstance()) {
			return (base_url()).(strtolower(get_class($instance))).DIR_SEPARATOR.(($path)?$path.DIR_SEPARATOR:NULL);
		} else {
			return ($path) ? $path.DIR_SEPARATOR : NULL;
		}
	}
}

/**
* GET MODULE PATH
*/
if (!function_exists('module_path')) {
	function module_path($path = NULL) {
		if ($instance = getModuleInstance()) {
			return (base_url()).APPPATH.'modules'.DIR_SEPARATOR.(strtolower(get_class($instance))).DIR_SEPARATOR.(($path)?$path.DIR_SEPARATOR:NULL);
		} else {
			return ($path) ? $path.DIR_SEPARATOR : NULL;
		}
	}
}

/**
* REDIRECT
*/
if(!function_exists('redirect')) {
	function redirect($url, $wait = 0, $message = NULL) {		
		if($wait) { 
			header("Refresh: ".$wait."; url=".$url);
			if($message) echo $message;
		} else {
			header("Location: ".$url, true, 303);
		}
		die();
	}
}

/**
* GET URL CALL STRING
*/
if(!function_exists('getCall')) {
	function getCall($type = NULL) {
		global $_uri;
		
		if(!$_uri) throw new Exception('URI Handler has not been set');
		
		if ($type === NULL) {
			return $_uri->getCall();
		} else {
			$call = $_uri->getCall();
			return ($type && in_array(strtolower($type), array('module','controller','action','param')) && isset($call[strtolower($type)])) ? $call[strtolower($type)] : NULL;
		}
	}
}

/**
* GET MODULE INSTANCE
*/
if(!function_exists('getModuleInstance')) {
	function &getModuleInstance($name = FALSE) {
		$return = NULL;
		if($trace = debug_backtrace()) {
			foreach($trace as $value) {
				if(isset($value['class']) && strtolower($value['class']) == 'xuxo_bootstrap') {
					$return = (isset($value['object'])) ? $value['object'] : NULL;
					break;
				}
			}
		}
		$module =& $return;
		if ($name && is_object($module)) $module = get_class($module);
		return $module;
	}
}

/**
* GET CONTROLLER INSTANCE
*/
if(!function_exists('getControllerInstance')) {
	function &getControllerInstance($module = NULL, $name = FALSE) {
		global $_load;
		$m = strtolower($module);
		$module = ($module) ? $_load->module(strtolower($module)) : getModuleInstance();
		
		$return = NULL;
		if($trace = debug_backtrace()) {
			//if($m == 'home') debug($trace);
			
			foreach($trace as $value) {
				if(isset($value['class']) && (strtolower(get_parent_class($value['class'])) == 'xuxo_controller' || strtolower($value['class']) == 'xuxo_controller')) {
					$return = (isset($value['object'])) ? $value['object'] : NULL;
					if($module) {
						if(strpos(strtolower(get_class($return)), strtolower(get_class($module))) === FALSE) {
							$return = NULL;
						}
					}
					break;
				}
			}
		}
		$controller =& $return;
		//if($m == 'home') debug($controller);
		//if($m == 'home') exit();
		
		if(!$controller) $controller = $module->getController(getCall('controller'));
		
		if ($name && is_object($controller)) $controller = get_class($controller);
		return $controller;
	}
}

/**
* DEBUG
*/
if(!function_exists('debug')) {
	function debug() {
		$vars = func_get_args();
		if($vars && !empty($vars)) {
			echo "<pre>";
			foreach($vars as $item) {
				print_r($item);
				echo "<br />";
			}
			echo "</pre><br />";
		}
	}
}

/**
* DEBUG AND EXIT
*/
if(!function_exists('debugexit')) {
	function debugexit() {
		$vars = func_get_args();
		if($vars && !empty($vars)) {
			echo "<pre>";
			foreach($vars as $item) {
				print_r($item);
				echo "<br />";
			}
			echo "</pre><br />";
			exit();
		}
	}
}

/**
* DUMP
*/
if(!function_exists('dump')) {
	function dump() {
		$vars = func_get_args();
		if($vars && !empty($vars)) {
			echo "<pre>";
			foreach($vars as $item) {
				var_dump($item);
				echo "<br />";
			}
			echo "</pre><br />";
		}
	}
}

/**
* DUMP AND EXIT
*/
if(!function_exists('dumpexit')) {
	function dumpexit() {
		$vars = func_get_args();
		if($vars && !empty($vars)) {
			echo "<pre>";
			foreach($vars as $item) {
				var_dump($item);
				echo "<br />";
			}
			echo "</pre><br />";
			exit();
		}
	}
}

























