<?php

defined('BASEPATH') OR exit('No direct script access allowed');
defined('DIR_SEPARATOR') || define('DIR_SEPARATOR', '/');
defined('XUXO_VERSION') || define('XUXO_VERSION', '1.0.0');

class Session {
	protected $_cookie = false;
	protected $_duration = 0;
	
	public function __construct() {
		$php = phpversion();
		$version = explode(".",$php);
		if ($version[0] > 5 || ($version[0] == 5 && $version[1] >= 5 && $version[2] >= 0)) {
			if (session_status() == PHP_SESSION_NONE) session_start();
		} else {
			if(session_id() == '') session_start();
		}
		unset($php, $version);
		
		$this->validateCookies();
		$this->validateSessions();
		
		return $this;
	}
	
	public function __destruct() { unset($this); }
	
	public function destroy($type = "ALL") {	
		if(strtoupper($type) == "SESSION") {
			$this->destroyAllSessions();
		} elseif(strtoupper($type) == "COOKIE") {
			$this->destroyAllCookies();
		} else {
			$this->destroyAllSessions();
			$this->destroyAllCookies();
		}
	}
	
	/**
	* SESSION
	*/
	public function setSessionValue() {
		$vars = func_get_args();		
		$num_args = func_num_args();
		
		if($num_args == 0) throw new Exception("SESSION VALUE CANNOT BE EMPTY");
		$session =  NULL;
		$last_num = $num_args - 1;
		$value = $vars[$last_num];		
		if($num_args == 1) {
			if(is_array($value) || is_object($value)) {
				foreach($value as $k=>$v) {
					if(trim($k)=="") {
						throw new Exception("CANNOT SET SESSION WITH EMPTY KEY");
					} elseif(trim($v)=="") {
						throw new Exception("CANNOT SET SESSION WITH EMPTY VALUE");
					} else {
						if(strtoupper($k) == "AUTH") {
							throw new Exception("KEY 'AUTH' IS RESERVED");
						} else {
							$skey = strtoupper($k);
							$session['XUXO_SESSION_'.$skey] = (!isset($session['XUXO_SESSION_'.$skey]) || empty($session['XUXO_SESSION_'.$skey])) ?  $v : $session['XUXO_SESSION_'.$skey];
						}
					}
				}
			} else {
				throw new Exception("CANNOT SET SESSION WITH EMPTY KEY");
			}
		} else {
			$keys = $vars;
			$last = &$session;
			
			foreach($keys as $k=>&$key) {					
				if($k < $last_num) {							
					$key = ($k == 0) ? "XUXO_SESSION_".strtoupper($key) : strtoupper($key);
					$last = &$last[$key];
				}
			}
			$last = $value;	
		}
		
		if($session) {
			foreach($session as $k=>$v) {				
				if(isset($_SESSION[$k])) {
					if(is_array($v)) {
						$p = (is_array($_SESSION[$k])) ? $_SESSION[$k] : array($_SESSION[$k]);
						$_SESSION[$k] = array_merge($p, $v);
					} else {
						$_SESSION[$k] = $v;
					}
				} else {
					$_SESSION[$k] = $v;
				}
			}
		}
		return $session;
	}
	
	public function getSessionValue() {
		$vars = func_get_args();		
		$num_args = func_num_args();
		
		if($num_args == 0) throw new Exception("SESSION VALUE CANNOT BE EMPTY");
		
		$session =  $_SESSION;		
		$keys = $vars;
		$value = NULL;
		foreach($keys as $k=>&$key) {				
			$key = ($k == 0) ? "XUXO_SESSION_".strtoupper($key) : strtoupper($key);
			if(isset($session[$key])) {
				$value = $session[$key];
				$session = $session[$key];
			}
		}
		return $value;
	}
		
	public function destroyAllSessions() {
		if(isset($_SESSION) && !empty($_SESSION)) {
			foreach($_SESSION as $key=>$value) {
				if(strpos($key,'XUXO_SESSION_') !== false) {
					$sKey = trim(strtolower(str_replace('XUXO_SESSION_','',$key)));
					$this->destroySessionValue($sKey);
				}
			}
		}
	}
	
	public function destroySessionValue() {		
		$vars = func_get_args();		
		$num_args = func_num_args();
		$last_num = $num_args - 1;
		
		$keys = $vars;
		$last = &$_SESSION;		
		foreach($keys as $k=>&$key) {	
			$key = ($k == 0) ? "XUXO_SESSION_".strtoupper($key) : strtoupper($key);		
			if($k < $last_num) {			
				$last = &$last[$key];
			}
		}
		unset($last[$key]);
		$this->validateCookies();
		$this->validateSessions();
		return NULL;
	}
	
	private function validateSessions() {
		if(isset($_SESSION) && !empty($_SESSION)) {
			foreach($_SESSION as $key=>$value) {
				if(strpos($key,'XUXO_SESSION_') !== false) {
					$skey = trim(strtolower(str_replace('XUXO_SESSION_','',$key)));
					if ( !in_array( strtoupper(str_replace('_','',$skey)), array('AUTH','COOKIE') ) ) {
						$this->setSessionValue($skey, $value);
					} elseif(strtoupper(str_replace('_','',$skey)) == 'COOKIE' && $value) {
						if($value && (is_array($value) || is_object($value))) {
							foreach($value as $cKey=>$cVal) {
								$this->setCookieValue($cKey, $cVal);
							}
						} else {
							$this->destroySessionValue("COOKIE");
						}
					}
				}
			}
		}
		if(isset($_SESSION['XUXO_SESSION_COOKIE']) && empty($_SESSION['XUXO_SESSION_COOKIE'])) unset($_SESSION['XUXO_SESSION_COOKIE']);
	}
	
	/**
	* COOKIE
	*/
	public function setCookieValue($key, $value, $duration = 0) {
		if(!$key) throw new Exception("COOKIE KEY IS NOT SET");
		if(!is_string($key)) throw new Exception("COOKIE KEY MUST BE OF TYPE STRING");
		if(!is_numeric($duration)) throw new Exception("COOKIE DURATION MUST BE OF TYPE NUMBER");
		
		if($duration) $this->setCookieDuration($duration);
		$duration = ($duration) ? $duration : $this->getCookieDuration();
		
		if(!$this->getSessionValue("COOKIE", strtoupper($key))) $this->setSessionValue("COOKIE", strtoupper($key), (($value) ? $value : TRUE));
		if(empty($this->_cookie)) $this->_cookie = array();
											 $this->_cookie[strtoupper($key)] = $value;
		
		setcookie("XUXO_COOKIE_".(strtoupper($key)), (($value) ? ((is_array($value) || is_object($value)) ? json_encode($value) : $value) : TRUE), $duration, '/');
		$_COOKIE["XUXO_COOKIE_".(strtoupper($key))] = (($value) ? ((is_array($value) || is_object($value)) ? json_encode($value) : $value) : TRUE);
		return $this->_cookie[$key];
	}
	
	public function setCookieDuration($duration = 0) {
		$this->_duration = ($duration) ? $duration : (time() + (365 * 24 * 60 * 60));
		return $this->_duration;
	}
	
	public function getCookieDuration($duration = 0) {
		return ($this->_duration) ? $this->_duration : (time() + (365 * 24 * 60 * 60));
	}
		
	public function destroyAllCookies() {
		if(isset($_COOKIE) && !empty($_COOKIE)) {
			foreach($_COOKIE as $key=>$value) {
				if(strpos($key,'XUXO_COOKIE_') !== false) {
					$cKey = trim(strtolower(str_replace('XUXO_COOKIE_','',$key)));
					$this->destroyCookieValue($cKey);
				}
			}
		}
	}
		
	public function destroyCookieValue($key) {
		if($key) {
			if($this->getSessionValue("COOKIE", strtoupper($key))) $this->destroySessionValue("COOKIE", strtoupper($key));
			if(!empty($this->_cookie) && isset($this->_cookie[strtoupper($key)])) unset($this->_cookie[strtoupper($key)]);
			
			$duration = (time() - (365 * 24 * 60 * 60));
			setcookie("XUXO_COOKIE_".(strtoupper($key)), FALSE, $duration, '/');
			unset($_COOKIE["XUXO_COOKIE_".(strtoupper($key))]);
		}
		$this->validateCookies();
		$this->validateSessions();
		return NULL;
	}
	
	private function validateCookies() {		
		$this->setCookieDuration();		
		if(isset($_COOKIE) && !empty($_COOKIE)) {
			$c = 0;
			foreach($_COOKIE as $key=>$value) {
				if(strpos($key,'XUXO_COOKIE_') !== false && $value) {
					$skey = trim(strtolower(str_replace('XUXO_COOKIE_','',$key)));
					$this->setSessionValue("COOKIE", $skey, (array) json_decode($value));
					$c++;
				}
			}
			if(!$c && isset($_SESSION['XUXO_SESSION_COOKIE'])) unset($_SESSION['XUXO_SESSION_COOKIE']);
		}
	}
	
	//////////////////////////////////////////////////////////////////////////
	// HELPER
	public function _debug($item) {
		echo "<pre>";
		print_r($item);
		echo "</pre><br>";
	}
}
?>