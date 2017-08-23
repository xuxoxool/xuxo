<?php

defined('BASEPATH') OR exit('No direct script access allowed');
defined('DIR_SEPARATOR') || define('DIR_SEPARATOR', '/');
defined('XUXO_VERSION') || define('XUXO_VERSION', '1.0.0');

class Auth {
	public static $_instance;
	protected $_load;
	protected $_session;
	protected $_auth;
	protected $_db;
	protected $_table;
	protected $_access;
	protected $_validation;
	protected $_return;
	protected $_error_code;
	protected $_error_msg;
	
	public function __construct() {
		$this->_load = loadCore('loader');
		$this->_session = $this->_load->library('session');
		$this->_error = NULL;
		
		if(!empty($_SESSION) && isset($_SESSION['XUXO_SESSION_AUTH']) && !empty($_SESSION['XUXO_SESSION_AUTH'])) {
			$session = $_SESSION['XUXO_SESSION_AUTH'];
			$this->_auth = array();
			foreach($session as $key=>$value) {
				$skey = trim(strtoupper($key));
				$this->_auth[$skey] = $value;
			}
			if(empty($this->_auth)) $this->_auth = NULL;
		}
		
		if($a = Auth::$_instance) {
			return $a;
		} else {
			self::$_instance = &$this;
			return self::$_instance;
		}
		return $this;
	}
	
	public function __destruct() { unset($this); }
	
	public function setDb($database = NULL, $config = NULL) {
		if(!$database) throw new Exception("DATABASE FOR AUTHENTICATION IS NOT SET");
				
		$db = loadCore('model');
		$db->init($config);
		$this->_db = $db->setDb($database);
	}
	
	public function setTable($table = NULL) {
		if(!$table) throw new Exception("TABLE FOR AUTHENTICATION IS NOT SET");
		if(!$this->_db) throw new Exception("DATABASE FOR AUTHENTICATION IS NOT SET");
		
		$sql = "SELECT COUNT(1) CT FROM ".($table)." LIMIT 0,1";
		$result = $this->_db->fetch($sql);
		
		if($result && isset($result['CT'])) {
			$this->_table = $table;
			return $this->_table;
		} else {
			throw new Exception("TABLE FOR AUTHENTICATION IS NOT VALID OR DOES NOT EXIST");
			return NULL;
		}
	}
	
	public function setAccess($column = NULL) {
		if(!$column) throw new Exception("ACCESS COLUMN FOR AUTHENTICATION IS NOT SET");
		if(!$this->_db) throw new Exception("DATABASE FOR AUTHENTICATION IS NOT SET");
		if(!$this->_table) throw new Exception("TABLE FOR AUTHENTICATION IS NOT SET");
		
		$sql = "SELECT COUNT(1) CT FROM ".($this->_table)." WHERE (".($column)." IS NULL OR ".($column)." IS NOT NULL) LIMIT 0,1";
		$result = $this->_db->fetch($sql);
		
		if($result && isset($result['CT'])) {
			$this->_access = $column;
			return $this->_access;
		} else {
			throw new Exception("ACCESS COLUMN FOR AUTHENTICATION IS NOT VALID OR DOES NOT EXIST");
			return NULL;
		}
	}
	
	public function setValidation($column = NULL) {
		if(!$column) throw new Exception("VALIDATION COLUMN FOR AUTHENTICATION IS NOT SET");
		if(!$this->_db) throw new Exception("DATABASE FOR AUTHENTICATION IS NOT SET");
		if(!$this->_table) throw new Exception("TABLE FOR AUTHENTICATION IS NOT SET");
		if(!$this->_access) throw new Exception("ACCESS COLUMN FOR AUTHENTICATION IS NOT SET");
		
		$sql = "SELECT COUNT(1) CT FROM ".($this->_table)." WHERE (".($column)." IS NULL OR ".($column)." IS NOT NULL) LIMIT 0,1";
		$result = $this->_db->fetch($sql);
		if($result && isset($result['CT'])) {
			$this->_validation = $column;
			return $this->_validation;
		} else {
			throw new Exception("VALIDATION COLUMN FOR AUTHENTICATION IS NOT VALID OR DOES NOT EXIST");
			return NULL;
		}
	}
	
	public function setReturn($column = NULL) {
		if(!$column) throw new Exception("RETURN COLUMN AFTER AUTHENTICATION IS NOT SET");
		if(!$this->_db) throw new Exception("DATABASE FOR AUTHENTICATION IS NOT SET");
		if(!$this->_table) throw new Exception("TABLE FOR AUTHENTICATION IS NOT SET");
		if(!$this->_access) throw new Exception("ACCESS COLUMN FOR AUTHENTICATION IS NOT SET");
		if(!$this->_validation) throw new Exception("VALIDATION COLUMN FOR AUTHENTICATION IS NOT SET");
		
		if(is_string($column)) {
			$sql = "SELECT COUNT(1) CT FROM ".($this->_table)." WHERE (".($column)." IS NULL OR ".($column)." IS NOT NULL) LIMIT 0,1";
			$result = $this->_db->fetch($sql);
			if($result && isset($result['CT'])) {
				$c = count($this->_return);
				$this->_return[$c] = $column;
				return $this->_return;
			} else {
				throw new Exception("RETURN COLUMN AFTER AUTHENTICATION IS NOT VALID OR DOES NOT EXIST");
				return NULL;
			}
		} elseif(is_array($column)) {
			foreach($column as $key=>$col) {
				$sql = "SELECT COUNT(1) CT FROM ".($this->_table)." WHERE (".($col)." IS NULL OR ".($col)." IS NOT NULL) LIMIT 0,1";
				$result = $this->_db->fetch($sql);
				if($result && isset($result['CT'])) {
					$c = count($this->_return);
					$this->_return[$c] = $col;
				} else {
					throw new Exception("RETURN COLUMN AFTER AUTHENTICATION IS NOT VALID OR DOES NOT EXIST");
					$this->_return = NULL;
					break;
				}
			}
			return $this->_return;
		} else {
			throw new Exception("RETURN COLUMN SHOULD BE PASSED AS STRING OR ARRAY");
			return NULL;
		}
	}
	
	public function getReturn($string = TRUE) {
		if($string) {
			if($this->_return) {
				$return = NULL;
				foreach($this->_return as $key=>$value) {
					$return .= ($key > 0) ? ", ".($value) : $value;
				}
				return $return;
			} else {
				return NULL;
			}
		} else {
			return $this->_return;
		}
	}
			
	public function setParam($param) {
		if(!$param) throw new Exception('PARAMETER LIST CANNOT BE EMPTY');
		if(!is_array($param)) throw new Exception('PARAMETER SHOULD BE SET IN AN ARRAY.');
		if(!isset($param['db'])) throw new Exception("DATABASE FOR AUTHENTICATION IS NOT SET");
		if(!isset($param['table'])) throw new Exception("TABLE FOR AUTHENTICATION IS NOT SET");
		if(!isset($param['access'])) throw new Exception("ACCESS COLUMN FOR AUTHENTICATION IS NOT SET");
		if(!isset($param['validation'])) throw new Exception("VALIDATION COLUMN FOR AUTHENTICATION IS NOT SET");
		if(!isset($param['return'])) $param['return'] = NULL;
		if(!isset($param['config'])) $param['config'] = NULL;
		
		$this->setDb($param['db'], $param['config']);
		$this->setTable($param['table']);
		$this->setAccess($param['access']);
		$this->setValidation($param['validation']);	
		$this->setReturn($param['return']);	
	}
	
	public function isAuth() {
		return (!empty($this->_auth) && isset($this->_auth['ACTIVE']) && $this->_auth['ACTIVE']) ? TRUE : FALSE;
	}
	
	public function getAuth($key = NULL) {
		return ($key) ? ( (isset($this->_auth[strtoupper($key)])) ? $this->_auth[strtoupper($key)] : NULL ) : $this->_auth;
	}
	
	public function validate($access = NULL, $validation = NULL, $return = NULL, $cookie = FALSE) {
		if(!$access) throw new Exception("ACCESS VALUE TO VALIDATE IS NOT SET");
		if(!$validation) throw new Exception("VALIDATION VALUE TO VALIDATE IS NOT SET");
		
		if(!$this->_db) throw new Exception("DATABASE FOR AUTHENTICATION IS NOT SET");
		if(!$this->_table) throw new Exception("TABLE FOR AUTHENTICATION IS NOT SET");
		if(!$this->_access) throw new Exception("ACCESS COLUMN FOR AUTHENTICATION IS NOT SET");
		if(!$this->_validation) throw new Exception("VALIDATION COLUMN FOR AUTHENTICATION IS NOT SET");
		if($return) $this->setReturn($return);
		
		$a = array($access);
		$refs = array();
    if (strnatcmp(phpversion(),'5.3') >= 0) {
			foreach($a as $key => $value)
				$refs[$key] = &$a[$key];
    }
		
		$sql = "SELECT COUNT(1) CT FROM ".($this->_table)." WHERE ".(strtoupper($this->_access))." = ? LIMIT 0,1";		
		$result = $this->_db->fetch($sql, 's', $refs);
		if($result && isset($result['CT']) && $result['CT'] > 0) {
			$sql = "SELECT ".(strtoupper($this->_validation))." VA FROM ".($this->_table)." WHERE ".(strtoupper($this->_access))." = ? LIMIT 0,1";
			$result = $this->_db->fetch($sql, 's', $refs);
			$va = $result['VA'];
			
			if($va == $validation) {		
				$ret = $this->getReturn(TRUE);		
				$sql = "SELECT ".(($ret) ? $ret : "*")." FROM ".($this->_table)." WHERE ".(strtoupper($this->_access))." = ? LIMIT 0,1";
				$result = $this->_db->fetch($sql, 's', $refs);
				
				if ($result) {				
					$this->_error_code = 0;
					$this->_error_msg = "SUCCESS";
					return $result;
				} else {				
					$this->_error_code = 3;
					$this->_error_msg = "FAIL TO RETRIEVE THE USER DETAIL";
					return NULL;
				}
			} else {
				$this->_error_code = 2;
				$this->_error_msg = "THE USER WITH ACCESS ".(($access) ? "'".$access."'" : "")." VALIDATION DOES NOT MATCH";
				return FALSE;
			}
		} else {
			$this->_error_code = 1;
			$this->_error_msg = "THE USER WITH ACCESS ".(($access) ? "'".$access."'" : "")." DOES NOT EXIST";
			return FALSE;
		}
	}
	
	public function authenticate($values = NULL, $session = TRUE, $cookie = FALSE) {
		if($values && (!is_array($values) && !is_object($values))) throw new Exception("AUTH VALUES MUST BE OF TYPE ARRAY OR OBJECT", 5);
		
		if(empty($this->_auth)) $this->_auth = array();
		if($values && !empty($values)) {
			$this->_auth['ACTIVE'] = TRUE;
			$this->_auth['HEX_KEY'] = sha1(md5(implode('',$values)).md5('XUXO'));
			foreach($values as $key=>$value) {
				$this->_auth[(strtoupper($key))] = $value;
			}
		}
		
		if($cookie) $this->_session->setCookieValue("AUTH",$this->_auth);
		if($session) $this->_session->setSessionValue("AUTH",$this->_auth);
	}
	
	public function unauthenticate($session = TRUE, $cookie = TRUE) {
		if(!empty($this->_auth)) $this->_auth = NULL;
		if($cookie) $this->_session->destroyCookieValue("AUTH");
		if($session) $this->_session->destroySessionValue("AUTH");
	}
	
	public function getError() { return array('code'=>$this->_error_code, 'msg'=>$this->_error_msg); }
	public function getErrorCode() { return $this->_error_code; }
	public function getErrorMsg() { return $this->_error_msg; }
	
	/**
	* HELPER
	*/	
	public function _debug($item) {
		echo "<pre>";
		print_r($item);
		echo "</pre><br />";
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
}
?>