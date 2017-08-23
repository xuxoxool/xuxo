<?php
/**
* ----------------------------------------------------------------------
* FILE		   	: Xuxo_DbContext_MySql.php
* VERSION     	: 1.1
* DATE			: 2013-06-23
* AUTHOR      	: Zul Zolkaffly (X.FY.RE TEAM)
* CONTACT		: xfyre_team@gmail.com
* LICENSE      : GNU-LGPL v3 (http:*www.gnu.org/copyleft/lesser.html)
* ----------------------------------------------------------------------
* Copyright (C) 2013-2015 [ ZUL ZOLKAFFLY - X.FY.RE TEAM ]
* ----------------------------------------------------------------------
* This file is part of Xuxo.
*
* Xuxo is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Xuxo is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Xuxo.  If not, see <http:*www.gnu.org/licenses/>.
* ----------------------------------------------------------------------
* DISCLAIMER:
*  XUXO_DBCONTEXT_MYSQL IS A DATABASE CONNECTION MANAGER FOR MYSQL.
*  IT IS A PART OF PROJECT XUXO, EXTENSION OF XUXO_DBCONTEXT CLASS.
*  THIS FILE, ALONG WITH REST OF XUXO PROJECT FILES ARE A PART OF
*  THE INTELLECTUAL PROPERTIES OF ZUL ZOLKAFFLY (X.FY.RE TEAM).
*  ANY USE OF THIS FILE AND ANY FILE FROM THE PROJECT XUXO IS WITHIN
*  YOUR OWN CONSENT. ANY MISCONDUCT, MISUSE, HARMS, INJURIES
*  AND ANY KIND OF UNDESIRABLE SITUATION, IN ANY TYPES AND WAYS
*  SHOULD NOT BE REFERRED TO THE ORIGINAL AUTHOR AND HIS TEAM,
*  AND THE ORIGINAL AUTHOR  AND HIS TEAM SHOULD NOT BE HELD
*  RESPONSIBLE FOR IT, IN ANY LEGAL OR ILLEGAL CONSEQUENCES OR
*  ACTIONS, FINANCIALLY, PHYSICALLY OR ANY OTHER WAYS MEANT FOR
*  DIRECT OR INDIRECT PUNISHMENTS OR PAYMENTS FOR AND TO THE
*  ORIGINAL AUTHOR AND HIS TEAM. ANY USE OF THIS FILE AND ANY FILE
*  FROM THE PROJECT XUXO SHOULD ALWAYS INCLUDE A FILE NAMED
*  <XUXO_LICENSE_AND_DISCLAIMER.txt>. YOU MAY MODIFY ANY PART OF
*  THIS FILE OR ANY FILE FROM THE PROJECT XUXO ACCORDING TO YOUR
*  REQUIREMENTS AND NEEDS. HOWEVER, THIS FILE AND ANY FILE FROM
*  THE PROJECT SHOULD NOT BE CLAIMED AS BELONG TO YOU, YOUR TEAM,
*  ORGANIZATION OR ANY PARTY OTHER THAN THE ORIGINAL AUTHOR AND
*  HIS TEAM. ANY CLAIM OF OWNAGE OTHER THAN THE ORIGINAL AUTHOR
*  AND HIS TEAM IS A SERIOUS OFFENSE OF HUMAN RIGHTS AND RESPECT.
* ----------------------------------------------------------------------
*/

class Xuxo_DbContext_MySql {
	protected $_connection;
	protected $_config;
	protected $_resource;
	protected $_key_transform;
	protected $_error;
	
	//////////////////////////////////////////////////////
	// CONSTRUCTOR AND DESTRUCTOR
	public function __construct($config = array(), $connect = false) {
		// DEFINE CONSTANCT
		defined('DIR_SEPARATOR') 	|| define('DIR_SEPARATOR', 		'/'); // FORWARD SLASH FOR LINUX/UNIX COMPATIBILITY
		defined('XUXO_ERROR_CODE_001') || define('XUXO_ERROR_CODE_001', 'ERROR');
		defined('XUXO_ERROR_CODE_002') || define('XUXO_ERROR_CODE_002', 'SERVER ERROR');
		defined('XUXO_ERROR_CODE_003') || define('XUXO_ERROR_CODE_003', 'GENERAL ERROR');
		defined('XUXO_ERROR_CODE_004') || define('XUXO_ERROR_CODE_004', 'DATABASE ERROR');
		defined('XUXO_ERROR_CODE_005') || define('XUXO_ERROR_CODE_005', 'SYSTEM ERROR');
		// INITIALIZE VARIABLE
		$this->_connection = NULL;
		$this->_config = NULL;
		$this->_ResetKeyTransform();
		$this->_error = NULL;
		// SET CONFIGURATION
		if($config && !empty($config)) $this->_SetConfig($config);		
		// RUN CUSTOM INITIALIZER
		if(method_exists($this,'__initialize')) $this->__initialize();
		// CONNECT
		if($connect) $this->_Connect();
	}
	
	public function __destruct() { $this->_Disconnect(); unset($this); }
	
	/////////////////////////////////////////////////////////////////
	// HELPER FUNCTIONS
	public function _SetConfig($config = array()) {
		if(!$this->_config) {
			if(!$config || empty($config)) throw new Exception('CONFIG ARRAY IS EMPTY OR INVALID');
			if (!isset($config['USE'])) throw new Exception('CONFIG VALUE FOR \'USE\' IS EMPTY OR INVALID');
			if (!isset($config['NAME'])) throw new Exception('CONFIG VALUE FOR \'NAME\' IS EMPTY OR INVALID');
			if (!isset($config['HOST'])) throw new Exception('CONFIG VALUE FOR \'HOST\' IS EMPTY OR INVALID');
			if (!isset($config['PORT'])) throw new Exception('CONFIG VALUE FOR \'PORT\' IS EMPTY OR INVALID');
			if (!isset($config['USER'])) throw new Exception('CONFIG VALUE FOR \'USER\' IS EMPTY OR INVALID');
			if (!isset($config['PASS'])) throw new Exception('CONFIG VALUE FOR \'PASS\' IS EMPTY OR INVALID');
			$this->_config = $config;
		}
	}
	
	public function _GetConfig($key) {
		if(!$key) $this->_RenderError('KEY NAME MUST BE SPECIFIED','XUXO_ERROR_CODE_004');
		return (isset($this->_config[strtoupper($key)])) ? $this->_config[strtoupper($key)] : NULL;
	}
	
	public function _AddConfig($key, $value) {
		if(!$key) $this->_RenderError('KEY NAME MUST BE SPECIFIED','XUXO_ERROR_CODE_004');
		$this->_config[strtoupper($key)] = $value;
		return $this->_config;
	}
	
	public function _RemoveConfig($key) {
		if(!$key) $this->_RenderError('KEY NAME MUST BE SPECIFIED','XUXO_ERROR_CODE_004');
		if(isset($this->_config[strtoupper($key)])) unset($this->_config[strtoupper($key)]);
		return $this->_config;
	}
	
	public function _RenderError($error = NULL, $code = NULL) {
		$error = ( ($code) ? ( (defined($code)) ? '<strong>'.constant($code).'</strong>'.": " : "" ) : "") . (($error) ? $error : "");
		$this->_error = $error;
		if($error !== NULL) die('<br />'.$error);
		return;
	}
	
	public function _GetError() { return $this->_error; }
	
	public function _SetKeyTransform($case = NULL, $append_by = NULL, $append_pos = 0) {
		$this->_key_transform['CASE'] = $case;
		$this->_key_transform['APPEND_BY'] = $append_by;
		$this->_key_transform['APPEND_POS'] = $append_pos;
		return $this->_key_transform;
	}
	
	public function _GetKeyTransform() { return $this->_key_transform;	}
	
	public function _ResetKeyTransform() {
		$this->_key_transform['CASE'] = NULL;
		$this->_key_transform['APPEND_BY'] = NULL;
		$this->_key_transform['APPEND_POS'] = 0;
		return $this->_key_transform;
	}
	
	public function _TransformKey($key) {
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
	
	public function _GetResource() { return $this->_resource; }
	
	public function _ClearResource() {
		$this->_resource = NULL;
		return $this->_resource;
	}
	
	public function _CheckPHPVersion() {
		$php = phpversion();
		$version = explode(".",$php);
		return ($version[0] > 5 || ($version[0] == 5 && $version[1] >= 4 && $version[2] >= 0));
	}
	
	//////////////////////////////////////////////////////
	// CONNECT/DISCONNECT TO DATABASE SERVER
	public function _Connect() {	
		if($this->_connection) { $this->_Disconnect(); } 
		try {
			$v = $this->_CheckPHPVersion();	
			// GET CONFIGURATION
			$config = $this->_config;
			if(!$config) throw new Exception('CONFIG IS NOT SET');
			if(!$config['USE']) return NULL;
			// SET CONNECTION HOST STRING
			$conndb = $config['HOST'];
			$conndb .= ($config['PORT'] && isset($config['PORT']) && !empty($config['PORT']) && trim($config['PORT']) != "3306") ? ":".$config['PORT'] : "";
			//CONNECTING
			if(isset($config['PDO']) && $config['PDO']) {
			}
			
			if(isset($config['PCON']) && $config['PCON']) {
				if($v) {
					$this->_connection = mysqli_connect($conndb, $config['USER'], $config['PASS']);
					if (!$this->_connection || mysqli_connect_errno()) $this->_RenderError('UNABLE TO CONNECT - '.mysql_error(), 'XUXO_ERROR_CODE_004');
				} else {
					$this->_connection = mysql_pconnect($conndb, $config['USER'], $config['PASS']);
					// RETURN ERROR IF CONNETION FAILED
					if (!$this->_connection) $this->_RenderError('UNABLE TO CONNECT - '.mysql_error(), 'XUXO_ERROR_CODE_004');
				}
			} else {
				if($v) {
				//debug($conndb, $config);
				//exit();
					$this->_connection = new mysqli($conndb, $config['USER'], $config['PASS']);
					if (!$this->_connection || mysqli_connect_errno()) $this->_RenderError('UNABLE TO CONNECT - '.mysql_error(), 'XUXO_ERROR_CODE_004');
				} else {
					$this->_connection = mysql_connect($conndb, $config['USER'], $config['PASS']);
					// RETURN ERROR IF CONNETION FAILED
					if (!$this->_connection) $this->_RenderError('UNABLE TO CONNECT - '.mysql_error(), 'XUXO_ERROR_CODE_004');
				}
			}
			unset($conndb);
		} catch (Exception $e) {
			$this->_RenderError($e->getMessage(), 'XUXO_ERROR_CODE_004');
		}
		return $this->_connection;
	}
	
	public function _Disconnect() {
		try {
			$v = $this->_CheckPHPVersion();
			if($this->_connection) {
				$this->_connection = NULL;
				if(isset($this->_config['PCON']) && $this->_config['PCON']) {
					if($v) {
						mysqli_close();
					} else {
						mysql_close();
					}
				}
			}
		} catch (Exception $e) {
			$this->_RenderError($e->getMessage(), 'XUXO_ERROR_CODE_004');
		}
		return ($this->_connection) ? NULL : $this->_connection;
	}
	
	/////////////////////////////////////////////////////////////////////////////
	// SQL QUERY HELPER FUNCTIONS
	public function _Execute($sql, $bind_type = NULL, $bind_detail = array()) {
		try {
			$v = $this->_CheckPHPVersion();	
			$this->_error = NULL;
			if(!$this->_connection) { $this->_Connect(); }
			// SELECT DB
			if($v) {
				mysqli_select_db($this->_connection, $this->_config['NAME']);
			} else {
				mysql_select_db($this->_config['NAME'], $this->_connection);
			}
			// RUN QUERY
			if($v) {
				if($bind_type && !empty($bind_detail)) {
					$stmt = mysqli_prepare($this->_connection, $sql);
					
					call_user_func_array('mysqli_stmt_bind_param', array_merge (array($stmt, $bind_type), $bind_detail)); 
					$result = (mysqli_stmt_execute($stmt)) ? $stmt : NULL;					
				} else {
					$result = mysqli_query($this->_connection, $sql);
				}
			} else {
				$result = mysql_query($sql);
			}
			// RETURN RESULT
			if(!$result) {
				if($v) {
					$this->_RenderError("(".mysqli_errno($this->_connection).") ".mysqli_error($this->_connection), 'XUXO_ERROR_CODE_004');
				} else {
					$this->_RenderError("(".mysql_errno($this->_connection).") ".mysql_error($this->_connection), 'XUXO_ERROR_CODE_004');
				}
			}
			$this->_resource = ($result) ? $result : NULL;
			return $result;
		} catch (Exception $e) {
			$this->_RenderError($e->getMessage(),'XUXO_ERROR_CODE_004');
		}
	}
	
	public function _FetchAll($sql, $bind_type = NULL, $bind_detail = array()) {
		try {
			$v = $this->_CheckPHPVersion();	
			$this->_error = NULL;
			if(!$this->_connection) { $this->_Connect(); }
			// EXECUTE AND ARRANGE RESULT
			$return = array();
			$result = $this->_Execute($sql, $bind_type, $bind_detail);
			if($result) {
				if($bind_type && !empty($bind_detail)) {								
					$meta = $result->result_metadata();
					$fields = array();
					$fields[0] = &$result;
					$count = 1;
					while($field = mysqli_fetch_field($meta)) {
							$fields[$count] = &$return[$field->name];
							$count++;
					}
					call_user_func_array('mysqli_stmt_bind_result', $fields);
					mysqli_stmt_fetch($result);
					mysqli_stmt_close($result);
				} else {
					$i = 0;
					if($v) {
						while($row = mysqli_fetch_assoc($result)) {
							foreach ($row as $key=>$value) {
								$key = $this->_TransformKey($key);
								$return[$i][$key]=$value;
							}
							$i++;
						}
					} else {
						while($row = mysql_fetch_assoc($result)) {
							foreach ($row as $key=>$value) {
								$key = $this->_TransformKey($key);
								$return[$i][$key]=$value;
							}
							$i++;
						}
					}
				}
			}
			// RETURN RESULT
			return ($return && !empty($return)) ? $return : NULL;
		} catch (Exception $e) {
			$this->_RenderError($e->getMessage(),'XUXO_ERROR_CODE_004');
		}
	}
	
	public function _FetchSingle($sql, $bind_type = NULL, $bind_detail = array()) {
		try {
			$v = $this->_CheckPHPVersion();	
			$this->_error = NULL;
			if(!$this->_connection) { $this->_Connect(); }		
			$return = array();
			$result = $this->_Execute($sql, $bind_type, $bind_detail);
			if($result) {	
				if($bind_type && !empty($bind_detail)) {								
					$meta = $result->result_metadata();
					$fields = array();
					$fields[0] = &$result;
					$count = 1;
					while($field = mysqli_fetch_field($meta)) {
							$fields[$count] = &$return[$field->name];
							$count++;
					}
					call_user_func_array('mysqli_stmt_bind_result', $fields);
					mysqli_stmt_fetch($result);
					mysqli_stmt_close($result);
				} else {
					if($v) {	
						$row = mysqli_fetch_assoc($result);
						if($row) {
							foreach ($row as $key=>$value) {
								$key = $this->_TransformKey($key);
								$return[$key] = $value;
							}
						}
					} else {	
						$row = mysql_fetch_assoc($result);
						if($row) {
							foreach ($row as $key=>$value) {
								$key = $this->_TransformKey($key);
								$return[$key] = $value;
							}
						}
					}
				}
			}
			return ($return && !empty($return)) ? $return : NULL;
		} catch (Exception $e) {
			$this->_RenderError($e->getMessage(),'XUXO_ERROR_CODE_004');
		}
	}
	
	public function _LastInsertId() {
		$v = $this->_CheckPHPVersion();	
		return ($v) ? mysqli_insert_id($this->_connection) : mysql_insert_id();
	}
	
	public function _AffectedRows() {
		$v = $this->_CheckPHPVersion();	
		return ($v) ? mysqli_affected_rows($this->_connection) : mysql_affected_rows();
	}
	
	public function _CreateTable($name, $param = array()) {
		try {
			if(!$name) $this->_RenderError('TABLE NAME CANNOT BE EMPTY','XUXO_ERROR_CODE_004');
			if(!is_array($param)) $this->_RenderError('PARAMETER MUST BE AN ARRAY','XUXO_ERROR_CODE_004');
			if(empty($param)) $this->_RenderError('PARAMETER CANNOT BE EMPTY','XUXO_ERROR_CODE_004');
			// PROCESS
			$sql = "CREATE TABLE ".$name." (";
			$c = 0;
			foreach($param as $key=>&$item) {
				$key = $this->_TransformKey($key);
				$sql .= ($c>0) ? (", ".$key." ".$item) : ($key." ".$item);
				$c++;
			}
			$sql .= ")";
			$result = $this->_Execute($sql);
			if(!$result) {
				$v = $this->_CheckPHPVersion();	
				if($v) {
					$this->_RenderError(mysqli_error(), 'XUXO_ERROR_CODE_004');
				} else {
					$this->_RenderError(mysql_error(), 'XUXO_ERROR_CODE_004');
				}
			}
			return ($result) ? TRUE : FALSE;
		} catch (Exception $e) {
			$this->_RenderError($e->getMessage(),'XUXO_ERROR_CODE_004');
		}		
	}
	
	public function _InsertIntoTable($name, $param = array(), $returnID = FALSE) {
		try {
			if(!$name) $this->_RenderError('TABLE NAME CANNOT BE EMPTY','XUXO_ERROR_CODE_004');
			if(!is_array($param)) $this->_RenderError('PARAMETER MUST BE AN ARRAY','XUXO_ERROR_CODE_004');
			if(empty($param)) $this->_RenderError('PARAMETER CANNOT BE EMPTY','XUXO_ERROR_CODE_004');
			$sql = "INSERT INTO ".$name." (";
			$c = 0;
			foreach($param as $key=>&$item) {
				$key = $this->_TransformKey($key);
				$sql .= ($c>0) ? ", ".$key : $key;
				$c++;
			}
			$sql .= ") VALUES (";
			$c = 0;
			foreach($param as $key=>&$item) {
				$sql .= ($c>0) ? ", '".$this->_CleanText($item)."'" : "'".$this->_CleanText($item)."'";
				$c++;
			}
			$sql .= ")";
			
			$result = $this->_Execute($sql);
			if(!$result) {
				$v = $this->_CheckPHPVersion();	
				if($v) {
					$this->_RenderError(mysqli_error(), 'XUXO_ERROR_CODE_004');
				} else {
					$this->_RenderError(mysql_error(), 'XUXO_ERROR_CODE_004');
				}
			}
			if($returnID) {
				return ($result) ? $this->_LastInsertId() : 0;
			} else {
				return ($result) ? TRUE : FALSE;
			}
		} catch (Exception $e) {
			$this->_RenderError($e->getMessage(),'XUXO_ERROR_CODE_004');
		}		
	}
	
	public function _UpdateTable($name, $columns = array(), $where = array(), $returnAffected = FALSE) {		
		try {
			if(!$name) $this->_RenderError('TABLE NAME CANNOT BE EMPTY','XUXO_ERROR_CODE_004');
			if(!is_array($columns)) $this->_RenderError('COLUMN PARAMETER MUST BE AN ARRAY','XUXO_ERROR_CODE_004');
			if(empty($columns)) $this->_RenderError('COLUMN PARAMETER CANNOT BE EMPTY','XUXO_ERROR_CODE_004');
			if(!is_array($where)) $this->_RenderError('WHERE PARAMETER MUST BE AN ARRAY','XUXO_ERROR_CODE_004');
			if(empty($where)) $this->_RenderError('WHERE PARAMETER CANNOT BE EMPTY','XUXO_ERROR_CODE_004');
			
			$sql = "UPDATE ".$name." SET ";
			$c = 0;
			foreach($columns as $key=>&$item) {
				$key = $this->_TransformKey($key);
				$sql .= ($c>0) ? (", ".$key." = '".$this->_CleanText($item)."'") : ($key." = '".$this->_CleanText($item)."'");
				$c++;
			}
			$c = 0;
			foreach($where as $key=>&$item) {
				$key = $this->_TransformKey($key);
				$sql .= ($c>0) ? (" AND ".$key." = '".$this->_CleanText($item)."'") : (" WHERE ".$key." = '".$this->_CleanText($item)."'");
				$c++;
			}			
			
			$result = $this->_Execute($sql);
			if(!$result) {
				$v = $this->_CheckPHPVersion();	
				if($v) {
					$this->_RenderError(mysqli_error(), 'XUXO_ERROR_CODE_004');
				} else {
					$this->_RenderError(mysql_error(), 'XUXO_ERROR_CODE_004');
				}
			}
			if($returnAffected) {
				return ($result) ? $this->_AffectedRows() : 0;
			} else {
				return ($result) ? TRUE : FALSE;
			}
		} catch (Exception $e) {
			$this->_RenderError($e->getMessage(),'XUXO_ERROR_CODE_004');
		}
	}
	
	public function _DeleteFromTable($name, $param = array(), $returnAffected = FALSE) {			
		try {
			if(!$name) $this->_RenderError('TABLE NAME CANNOT BE EMPTY','XUXO_ERROR_CODE_004');
			if(!is_array($param)) $this->_RenderError('PARAMETER MUST BE AN ARRAY','XUXO_ERROR_CODE_004');
			if(empty($param)) $this->_RenderError('PARAMETER CANNOT BE EMPTY','XUXO_ERROR_CODE_004');
			
			$sql = "DELETE FROM ".$name;
			$c = 0;
			foreach($param as $key=>&$item) {
				$key = $this->_TransformKey($key);
				$sql .= ($c>0) ? (" AND ".$key." = '".$this->_CleanText($item)."'") : (" WHERE ".$key." = '".$this->_CleanText($item)."'");
				$c++;
			}
			
			$result = $this->_Execute($sql);
			if(!$result) {
				$v = $this->_CheckPHPVersion();	
				if($v) {
					$this->_RenderError(mysqli_error(), 'XUXO_ERROR_CODE_004');
				} else {
					$this->_RenderError(mysql_error(), 'XUXO_ERROR_CODE_004');
				}
			}
			if($returnAffected) {
				return ($result) ? $this->_AffectedRows() : 0;
			} else {
				return ($result) ? TRUE : FALSE;
			}
		} catch (Exception $e) {
			$this->_RenderError($e->getMessage(),'XUXO_ERROR_CODE_004');
		}
	}
	
	public function _SelectFromTable($name, $columns = array(), $where = array(), $single = false) {		
		try {
			if(!$name) $this->_RenderError('TABLE NAME CANNOT BE EMPTY','XUXO_ERROR_CODE_004');
			if(!is_array($columns)) $this->_RenderError('COLUMNS PARAMETER MUST BE AN ARRAY','XUXO_ERROR_CODE_004');
			if(!is_array($selection)) $this->_RenderError('SELECTION (WHERE... AND...) PARAMETER MUST BE AN ARRAY','XUXO_ERROR_CODE_004');
			// PROCESS
			$sql = "SELECT ";
			if(empty($columns)) {
				$sql .= '*';
			} else {
				$c = 0;
				foreach($columns as $key=>&$item) {
					$item = $this->_TransformKey($item);
					$sql .= ($c>0) ? ", ".$item : $item;
					$c++;
				}
			}
			$sql .= " FROM ".$name;
			if(!empty($where)) {
				$c = 0;
				foreach($where as $key=>&$item) {
					$key = $this->_TransformKey($key);
					$sql .= ($c>0) ? (" AND ".$key." = '".$this->_CleanText($item)."'") : (" WHERE ".$key." = '".$this->_CleanText($item)."'");
					$c++;
				}
			}
			
			$result = ($single) ? $this->_FetchSingle($sql) : $this->_FetchAll($sql);
			return $result;
		} catch (Exception $e) {
			$this->_RenderError($e->getMessage(),'XUXO_ERROR_CODE_004');
		}
	}
	
	///////////////////////////////////////////////////////////////////////////////
	// CLEAN UP TEXT VALUE TO REMOVE DANGEROUS TAGS AND BAD WORDS
	// SOURCE: Can You Hack Your Own Site? A Look at Some Essential Security Considerations
	// URL: http://net.tutsplus.com/tutorials/tools-and-tips/can-you-hack-your-own-site-a-look-at-some-essential-security-considerations/
	// AUTHOR: Ben Charnock (Mar 10th 2011)
	// MODIFIED BY: Zul Zolkaffly (31/5/2013)
	public function _CleanText($text) {
		$white_rx = "/<\s*/";
		$tag_rx = "/<[^>]*>/";
		
		$safelist = Array(
			'/<p>|<\/p>/i',
			'/<h[0-9]>[^<]*<\/h[0-9]>/i',
			'/<a\shref=.[\/h#][^>]*>|<\/a>/i',
			'/<b>|<\/b>/i',
			'/<strong>|<\/strong>/i',
			'/<i>|<\/i>/i',
			'/<em>|<\/em>/i',
			'/<br[^>]*>/i',
			'/<img\ssrc=.[\/h][^>]*\/>|<img\ssrc="[\/h][^>]*>[^<]*<\/img>/i',
			'/<hr[^>]*>/',
			'/<ol[^>]*>|<\/ol[^>]*>/i',
			'/<ul[^>]*>|<\/ul[^>]*>/i',
			'/<li[^>]*>|<\/li[^>]*>/i',
			'/<code[^>]*>|<\/code[^>]*>/i',
			'/<pre[^>]*>|<\/pre[^>]*>/i'
		);
		
		$unsafe_words = Array(
			"/javascript/e",
			"/script/e",
			"/onclick/e",
			"/onm\w{1,}=/e",
			"/JAVASCRIPT/e",
			"/SCRIPT/e",
			"/ONCLICK/e",
			"/ONM\w{1,}=/e"
		);
		
		$dirty_words = Array(
			"/fuck/e",
			"/bitch/e",
			"/asshole/e",
			"/damn/e",
			"/whore/e",
			"/babi/e",
			"/buto/e",
			"/sundal/e",
			"/sial/e",
			"/pepek/e",
			"/pantat/e",
			"/puki/e",
			"/pukimak/e",
			"/tetek/e",
			"/kelentit/e",
			"/konek/e",
			"/kontol/e",
			"/motherfucker/e",
			"/boobies/e",
			"/FUCK/e",
			"/BITCH/e",
			"/ASSHOLE/e",
			"/DAMN/e",
			"/WHORE/e",
			"/BABI/e",
			"/BUTO/e",
			"/SUNDAL/e",
			"/SIAL/e",
			"/PEPEK/e",
			"/PANTAT/e",
			"/PUKI/e",
			"/PUKIMAK/e",
			"/TETEK/e",
			"/KELENTIT/e",
			"/KONEK/e",
			"/KONTOL/e",
			"/MOTHERFUCKER/e",
			"/BOOBIES/e",
		);
		
		$text = addcslashes($text,"\x00\'\x1a\x3c\x3e\x25");
		$text = preg_replace($white_rx, "<", $text);
		
		preg_match_all($tag_rx, $text, $matches);
		$finds = $matches[0];
		foreach($finds as $find) {
			$clean = true;
			foreach($safelist as $safetag) {
				if(preg_match($safetag, $find) > 0) {
					$clean = false;
					break;
				}
			}
			if($clean === true) {
				$text = str_ireplace($find, htmlentities($find), $text);
			}
		}
		
		$text = preg_replace($unsafe_words, "zaseuuoqHSJAEWAdgsrppoVNAS", $text);
		$text = preg_replace($dirty_words, "zaseuuoqHSJAEWAdgsrppoVNAS", $text);
		$text = str_replace("zaseuuoqHSJAEWAdgsrppoVNAS","<i>BEEP</i>",$text);
		if(function_exists("mysql_real_escape_string")) {
			$text = mysql_real_escape_string($text);
		} elseif(function_exists("mysqli_real_escape_string")) {
			$text = mysqli_real_escape_string($text);
		}
		return $text;
	}
	///////////////////////////////////////////////////////////////////////////////////////
}
?>