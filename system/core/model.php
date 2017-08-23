<?php

defined('BASEPATH') OR exit('No direct script access allowed');
defined('DIR_SEPARATOR') || define('DIR_SEPARATOR', '/');
defined('XUXO_VERSION') || define('XUXO_VERSION', '1.0.0');
class Xuxo_Model {
	protected $_connection;
	protected $_active_db;
	protected $_db;
	protected $_config;
	protected $_resource;
	protected $_auto_commit;
	protected $_key_transform;
	protected $_error;
	protected $_on;
	protected $_sql;
	protected $_start_time;
	protected $_end_time;
	protected $_exec_time;
	
	public function __construct() {
		return $this;
	}
	
	public function on() { $this->_on = true; return $this->_on; }
	public function off() { $this->_on = false; return $this->_on; }
	public function isOn() { return ($this->_on) ? true : false; }
	
	public function init($config = NULL) {
		$module = getModuleInstance();
		
		if(is_array($config) && !empty($config)) {
			$this->_config = $config;
		} else {
			if($module) {
				$config_file = (($config === TRUE) ? rtrim(APPPATH,DIR_SEPARATOR) : rtrim($module->getPath(),DIR_SEPARATOR)).DIR_SEPARATOR.'config'.DIR_SEPARATOR.'database.php';
				if(!file_exists($config_file)) throw new Exception('Module database configuration file ('.$config_file.') is not found');
			} else {
				$config_file = (rtrim(APPPATH,DIR_SEPARATOR)).DIR_SEPARATOR.'config'.DIR_SEPARATOR.'database.php';
				if(!file_exists($config_file)) throw new Exception('Application database configuration file ('.$config_file.') is not found');
			}
			
			require($config_file);
			
			if(!isset($db) || empty($db)) throw new Exception('Database configuration is invalid');
			foreach($db as $key=>$value) {
				$this->_config[$key] = $value;
			}
		}
		if(!isset($this->_config['default'])) throw new Exception('Default database configuration is not found');
		
		$this->on();
		return $this->setDb('default');
	}
	
	public function getConfig($name = NULL, $key = NULL) {
		if($name === NULL) {
			return $this->_config;
		} else {
			if($key === NULL) {
				return (isset($this->_config[$name])) ? $this->_config[$name] : NULL;
			} else {
				return (isset($this->_config[$name][$key])) ? $this->_config[$name][$key] : NULL;
			}
		}
	}
	
	public function getActiveDbConfig() { return ($this->_active_db) ? $this->getConfig(	$this->_active_db	) : NULL; }
	
	public function getActiveDb() { return $this->_active_db; }
	
	public function getDb() { return $this->_db; }
	
	public function setDb($name) {
		if(!$name) $this->setError('Cannot get db config with empty name.');
		
		$list = (!empty($this->_config)) ? array_keys($this->_config) : array();
		if(!in_array($name, $list)) $this->setError('DB with name '.$name.' is not set in configuration.');
		
		$this->_active_db = $name;
		$this->activate($this->getConfig($name, 'connect-on-init'));
		return $this;
	}
	
	public function activate($connect = false) {
		if(!$this->_active_db) throw new Exception('ACTIVE DB IS NOT SET OR INVALID.');
		$config = $this->getConfig($this->_active_db);		
		$config = ($config && is_array($config)) ? array_change_key_case($config, CASE_UPPER) : NULL;
		
		if(!$config || empty($config)) throw new Exception('CONFIG ARRAY IS EMPTY OR INVALID');
		if (!isset($config['TYPE'])) throw new Exception('CONFIG VALUE FOR \'TYPE\' IS EMPTY OR INVALID');
		if (!isset($config['NAME'])) throw new Exception('CONFIG VALUE FOR \'NAME\' IS EMPTY OR INVALID');
		if (!isset($config['HOST'])) throw new Exception('CONFIG VALUE FOR \'HOST\' IS EMPTY OR INVALID');
		if (!isset($config['PORT'])) throw new Exception('CONFIG VALUE FOR \'PORT\' IS EMPTY OR INVALID');
		if (!isset($config['USER'])) throw new Exception('CONFIG VALUE FOR \'USER\' IS EMPTY OR INVALID');
		if (!isset($config['PASS'])) throw new Exception('CONFIG VALUE FOR \'PASS\' IS EMPTY OR INVALID');
		
		$config['USE'] = TRUE;
		$config['CONNECT-ON-INIT'] = isset($config['CONNECT-ON-INIT']) ? $config['CONNECT-ON-INIT'] : FALSE;
		$config['SAVE-LOG'] = isset($config['SAVE-LOG']) ? $config['SAVE-LOG'] : TRUE;
		$config['HOST'] = (strtoupper($config['HOST']) == 'LOCALHOST') ? '127.0.0.1' : $config['HOST'];
		
		$class = 'Xuxo_DbContext_MySql';
		switch(strtoupper($config['TYPE'])) {
			case 'MYSQL':
				$class = 'Xuxo_DbContext_MySql';
				break;
			case 'ORACLE':
				$class = 'Xuxo_DbContext_Oracle';
				break;
			case 'SQLITE':
				$class = 'Xuxo_DbContext_Sqlite';
				break;
			default:
				throw new Exception('UNKNOWN OR INVALID DATABASE TYPE');
				break;
		}
		
		$file = str_replace('\\',DIR_SEPARATOR,dirname(__DIR__)).DIR_SEPARATOR.'database'.DIR_SEPARATOR.$class.'.php';
		if(!file_exists($file)) throw new Exception('Core Database file '.$class.'.php is not found.');
		require_once($file);
		if(!class_exists($class)) throw new Exception('Class '.$class.' is not found.');
		
		$this->_db = new $class();
		
		$this->_db->_SetConfig($config);
		
		if($connect) $this->connect();
		
		return $this;
	}
	
	/**
	* KEY TRANSFORMATIONS
	*/
	public function setKeyTransform($case = NULL, $append_by = NULL, $append_pos = 0) {
		$this->_key_transform['CASE'] = $case;
		$this->_key_transform['APPEND_BY'] = $append_by;
		$this->_key_transform['APPEND_POS'] = $append_pos;
		if($this->_db) $this->_db->_SetKeyTransform($case, $append_by, $append_pos);
		return $this->_key_transform;
	}
	
	public function getKeyTransform() { return $this->_key_transform;	}
	
	public function resetKeyTransform() {
		$this->_key_transform['CASE'] = NULL;
		$this->_key_transform['APPEND_BY'] = NULL;
		$this->_key_transform['APPEND_POS'] = 0;
		if($this->_db) $this->_db->_ResetKeyTransform();
		return $this->_key_transform;
	}
	
	public function transformKey($key) {
		if($this->_key_transform['CASE']) {
			switch (strtoupper($this->_key_transform['CASE'])) {
				case 'UPPER':
					$key = strtoupper($key);
					break;
				case 'LOWER':
					$key = strtolower($key);
					break;
				case 'UCFIRST':
					$key = ucfirst(strtolower($key));
					break;
				case 'UCWORDS':
					$key = ucwords(strtolower($key));
					break;
				default:
					$key = $key;
					break;
			}
		}
		
		$key = substr_replace($key, $this->_key_transform['APPEND_BY'], $this->_key_transform['APPEND_POS'], 0);
		return $key;
	}
	
	/**
	* RESOURCE
	*/
	public function _GetResource() { return ($this->_db) ? $this->_db->_GetResource : $this->_resource; }
	
	public function _ClearResource() {
		$this->_resource = NULL;
		if ($this->_db) $this->_db->_ClearResource;
		return $this->_resource;
	}
	
	/**
	* SQL
	*/
	public function setSQL($sql, $bind_type = NULL, $bind_detail = NULL) {
		if($bind_type && $bind_detail) {
			$matches = explode("?",$sql);
			$statement = $matches[0];
			foreach($bind_detail as $key=>$value) {
				if(strtolower($bind_type) == "i" || strtolower($bind_type) == "i") {
					$statement .= $value;
				} else {
					$statement .= "'".$value."'";
				}
				$statement .= (isset($matches[$key+1])) ? $matches[$key+1] : NULL;
			}
			
			$this->_sql = $statement;
		} else {
			$this->_sql = $sql;
		}
	}
	
	public function getSQL() { return $this->_sql; }
	
	/**
	* CONNECTIONS
	*/
	public function connect() {
		if($this->_connection) { $this->disconnect(); } 
		try {
			if (!$this->_db) throw new Exception('DB IS NOT ACTIVATED');
			
			if($this->isOn()) {
				$this->_connection = $this->_db->_Connect();
				if (!$this->_connection) throw new Exception('UNABLE TO CONNECT');
			}
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
	}
	
	public function disconnect() {
		try {
			if($this->_connection) {
				if (!$this->_db) throw new Exception('DB IS NOT ACTIVATED');
				
				$this->_connection = $this->_db->_Disconnect();
			}
			if ($this->_connection) throw new Exception('UNABLE TO DISCONNECT');	
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
	}
	
	/**
	* QUERY HELPER
	*/
	public function execute($sql, $bind_type = NULL, $bind_detail = array()) {
		try {
			$this->_error = NULL;
			if (!$this->_db) throw new Exception('DB IS NOT ACTIVATED');
			
			$this->_start_time = microtime(true);
			if(!$this->_connection) { $this->connect(); }
			if($this->isOn()) {
				$result = $this->_db->_Execute($sql, $bind_type, $bind_detail);
				$this->_resource = ($result) ? $result : NULL;
			}
			$this->_end_time = microtime(true);
			$this->_exec_time = $this->_end_time - $this->_start_time;
			
			$this->setSQL($sql, $bind_type, $bind_detail);
			$log = $this->getConfig($this->_active_db, 'save-log');
			$log = ($log) ? $log : TRUE;
			if($log) saveLog('INFO', 'Query Executed : '.$this->getSQL());
			
			return $result;
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
	}
	
	public function query($sql, $bind_type = NULL, $bind_detail = array()) { return $this->execute($sql, $bind_type, $bind_detail); }
	
	public function fetchAll($sql, $bind_type = NULL, $bind_detail = array()) {
		try {
			$this->_error = NULL;
			if (!$this->_db) throw new Exception('DB IS NOT ACTIVATED');
			$this->_start_time = microtime(true);
			if(!$this->_connection) { $this->connect(); }
			// EXECUTE
			$result = NULL;
			if($this->isOn()) {
				$result = $this->_db->_FetchAll($sql, $bind_type, $bind_detail);
				$this->_resource = $this->_db->_GetResource();
			}
			$this->_end_time = microtime(true);
			$this->_exec_time = $this->_end_time - $this->_start_time;
			
			$this->setSQL($sql, $bind_type, $bind_detail);
			$log = $this->getConfig($this->_active_db, 'save-log');
			$log = ($log) ? $log : TRUE;
			if($log) saveLog('INFO', 'Query Executed : \''.$this->getSQL().'\'');
			
			return ($result && !empty($result)) ? $result : NULL;
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
	}
	
	public function fetchSingle($sql, $bind_type = NULL, $bind_detail = array()) {
		try {
			$this->_error = NULL;
			if (!$this->_db) throw new Exception('DB IS NOT ACTIVATED');
			$this->_start_time = microtime(true);
			if(!$this->_connection) { $this->connect(); }
			// EXECUTE
			$result = NULL;
			if($this->isOn()) {
				$result = $this->_db->_FetchSingle($sql, $bind_type, $bind_detail);
				$this->_resource = $this->_db->_GetResource();
			}
			$this->_end_time = microtime(true);
			$this->_exec_time = $this->_end_time - $this->_start_time;
			
			$this->setSQL($sql, $bind_type, $bind_detail);
			$log = $this->getConfig($this->_active_db, 'save-log');
			$log = ($log) ? $log : TRUE;
			if($log) saveLog('INFO', 'Query Executed : '.$this->getSQL());
			
			return ($result && !empty($result)) ? $result : NULL;
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
	}
	
	public function fetchMulti($sql, $bind_type = NULL, $bind_detail = array()) { return $this->fetchAll($sql, $bind_type, $bind_detail); }
	public function fetchRow($sql, $bind_type = NULL, $bind_detail = array()) { return $this->fetchSingle($sql, $bind_type, $bind_detail); }
	public function fetch($sql, $bind_type = NULL, $bind_detail = array()) { return $this->fetchSingle($sql, $bind_type, $bind_detail); }
	
	public function lastInsertId() {
		if(!$this->_active_db) throw new Exception('ACTIVE DB IS NOT SET OR INVALID');
		return ($this->_db) ? $this->_db->_LastInsertId() : NULL;
	}
	
	public function affectedRows() {
		if(!$this->_active_db) throw new Exception('ACTIVE DB IS NOT SET OR INVALID');
		return ($this->_db) ? $this->_db->_AffectedRows() : NULL;
	}
	
	public function createTable($name, $param = array()) {
		if(!$this->_active_db) throw new Exception('ACTIVE DB IS NOT SET OR INVALID');
		try {
			if(!$name) throw new Exception('TABLE NAME CANNOT BE EMPTY');
			if(!is_array($param)) throw new Exception('PARAMETER MUST BE AN ARRAY');
			if(empty($param)) throw new Exception('PARAMETER CANNOT BE EMPTY');
			$this->_start_time = microtime(true);
			// PROCESS
			$result = NULL;
			if($this->isOn()) $result = ($this->_db) ? $this->_db->_CreateTable($name, $param) : FALSE;
			
			$this->_end_time = microtime(true);
			$this->_exec_time = $this->_end_time - $this->_start_time;
			return $result;
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
	}
	
	public function insert($name, $param = array(), $returnID = FALSE) {
		if(!$this->_active_db) throw new Exception('ACTIVE DB IS NOT SET OR INVALID');
		try {
			if(!$name) throw new Exception('TABLE NAME CANNOT BE EMPTY');
			if(!is_array($param)) throw new Exception('PARAMETER MUST BE AN ARRAY');
			if(empty($param)) throw new Exception('PARAMETER CANNOT BE EMPTY');
			$this->_start_time = microtime(true);
			// PROCESS
			$result = NULL;
			if($this->isOn()) $result = ($this->_db) ? $this->_db->_InsertIntoTable($name, $param, $returnID) : FALSE;
			
			$this->_end_time = microtime(true);
			$this->_exec_time = $this->_end_time - $this->_start_time;
			return $result;
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
	}
	
	public function update($name, $columns = array(), $where = array(), $returnAffected = FALSE) {		
		if(!$this->_active_db) throw new Exception('ACTIVE DB IS NOT SET OR INVALID');
		try {
			if(!$name) throw new Exception('TABLE NAME CANNOT BE EMPTY');
			if(!is_array($columns)) throw new Exception('COLUMN PARAMETER MUST BE AN ARRAY');
			if(empty($columns)) throw new Exception('COLUMN PARAMETER CANNOT BE EMPTY');
			if(!is_array($where)) throw new Exception('WHERE PARAMETER MUST BE AN ARRAY');
			if(empty($where)) throw new Exception('WHERE PARAMETER CANNOT BE EMPTY');
			$this->_start_time = microtime(true);
			// PROCESS
			$result = NULL;
			if($this->isOn()) {
				$result = ($this->_db) ? $this->_db->_UpdateTable($name, $columns, $where, $returnAffected) : FALSE;
			}
			$this->_end_time = microtime(true);
			$this->_exec_time = $this->_end_time - $this->_start_time;
			return $result;
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
	}
	
	public function delete($name, $param = array(), $returnAffected = FALSE) {
		if(!$this->_active_db) throw new Exception('ACTIVE DB IS NOT SET OR INVALID');
		try {
			if(!$name) throw new Exception('TABLE NAME CANNOT BE EMPTY');
			if(!is_array($param)) throw new Exception('PARAMETER MUST BE AN ARRAY');
			if(empty($param)) throw new Exception('PARAMETER CANNOT BE EMPTY');
			$this->_start_time = microtime(true);
			// PROCESS
			$result = NULL;
			if($this->isOn()) {
				$result = ($this->_db) ? $this->_db->_DeleteFromTable($name, $param, $returnAffected) : FALSE;
			}
			$this->_end_time = microtime(true);
			$this->_exec_time = $this->_end_time - $this->_start_time;
			return $result;
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
	}
	
	public function select($name, $columns = array(), $where = array(), $single = false) {		
		if(!$this->_active_db) throw new Exception('ACTIVE DB IS NOT SET OR INVALID');
		try {
			if(!$name) throw new Exception('TABLE NAME CANNOT BE EMPTY');
			if(!is_array($columns)) throw new Exception('COLUMNS PARAMETER MUST BE AN ARRAY');
			if(!is_array($where)) throw new Exception('SELECTION (WHERE... AND...) PARAMETER MUST BE AN ARRAY');
			$this->_start_time = microtime(true);
			// PROCESS
			$result = NULL;
			if($this->isOn()) {
				$result = ($this->_db) ? $this->_db->_SelectFromTable($name, $columns, $where, $single) : FALSE;
			}
			$this->_end_time = microtime(true);
			$this->_exec_time = $this->_end_time - $this->_start_time;
			return $result;
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
	}
	
	
	
	/**
	* HELPER FUNCTIONS
	*/
	public function setError($message) { $this->_error[] = $message; saveLog('Error', $message); }
	public function getError() { return $this->_error; }
	public function getLastError() { return ($this->_error && is_array($this->_error)) ? end($this->_error) : $this->_error; }
	
	private function objToArray($item) { return ($item) ? json_decode(json_encode($item),true) : NULL; }
	
	public function _debug($item) {
		echo "<pre>";
		print_r($item);
		echo "</pre>";
	}
	
}






























