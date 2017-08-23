<?php
//namespace Xuxo;
/**
* XUXO MVC FRAMEWORK
*
* SITE DBCONTEXT FOR ORACLE CLASS
* -----------------------
* 
* @package    XUXO
* @subpackage DBCONTEXT
* @children	  ORACLE
* @author     XUXO SYSTEM MALAYSIA
* ----------------------------------------------------------------------
*/
class Xuxo_DbContext_Oracle {
	protected $_connection;
	protected $_config;
	protected $_key_transform;
	protected $_auto_commit;
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
		$this->_auto_commit = true;
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
			if(!$config || empty($config)) $this->_RenderError(XUXO_ERROR_CODE_004.': CONFIG ARRAY IS EMPTY OR INVALID');
			if (!isset($config['USE'])) $this->_RenderError(XUXO_ERROR_CODE_004.': CONFIG VALUE FOR \'USE\' IS EMPTY OR INVALID');
			if (!isset($config['NAME'])) $this->_RenderError(XUXO_ERROR_CODE_004.': CONFIG VALUE FOR \'NAME\' IS EMPTY OR INVALID');
			if (!isset($config['HOST'])) $this->_RenderError(XUXO_ERROR_CODE_004.': CONFIG VALUE FOR \'HOST\' IS EMPTY OR INVALID');
			if (!isset($config['PORT'])) $this->_RenderError(XUXO_ERROR_CODE_004.': CONFIG VALUE FOR \'PORT\' IS EMPTY OR INVALID');
			if (!isset($config['USER'])) $this->_RenderError(XUXO_ERROR_CODE_004.': CONFIG VALUE FOR \'USER\' IS EMPTY OR INVALID');
			if (!isset($config['PASS'])) $this->_RenderError(XUXO_ERROR_CODE_004.': CONFIG VALUE FOR \'PASS\' IS EMPTY OR INVALID');
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
	
	public function _SetAutoCommit($commit) {
		if(!is_bool($commit)) $this->_RenderError('VALUE MUST BE OF TYPE BOOLEAN','XUXO_ERROR_CODE_005');
		$this->_auto_commit = $commit;
		return $this->_auto_commit;
	}
	
	public function _GetAutoCommit($commit) { return $this->_auto_commit; }
	
	//////////////////////////////////////////////////////
	// CONNECT/DISCONNECT TO DATABASE SERVER
	public function _Connect() {		
		if($this->_connection) { $this->_Disconnect(); } 
		try {			
			// GET CONFIGURATION
			$config = $this->_config;
			if(!$config) $this->_RenderError(XUXO_ERROR_CODE_004.': CONFIG IS NOT SET');
			if(!$config['USE']) return NULL;
			// SET CONNECTION HOST STRING
			$conndb = $config['HOST'];
			$conndb .= ($config['PORT'] && isset($config['PORT']) && !empty($config['PORT']) && trim($config['PORT']) != "") ? ":".$config['PORT'] : "";
			$conndb .= ($config['NAME'] && isset($config['NAME']) && !empty($config['NAME']) && trim($config['NAME']) != "") ? "/".$config['NAME'] : "";
			//CONNECTING
			if(isset($config['PCON']) && $config['PCON']) {
				$this->_connection = oci_pconnect($config['USER'], $config['PASS'], $conndb);
			} else {
				$this->_connection = oci_connect($config['USER'], $config['PASS'], $conndb);
			}
			// RETURN ERROR IF CONNETION FAILED
			if (!$this->_connection) {
				$e = oci_error();
				$this->_RenderError('UNABLE TO CONNECT - '.$e['message'], 'XUXO_ERROR_CODE_004');
			}
			unset($conndb);
		} catch (Exception $e) {
				$e = oci_error();
				$this->_RenderError('UNABLE TO CONNECT - '.$e['message'], 'XUXO_ERROR_CODE_004');
		}
		return $this->_connection;
	}
	
	public function _Disconnect() {
		try {
			if($this->_connection) {
				$this->_connection = NULL;
				if(isset($this->_config['PCON']) && $this->_config['PCON']) oci_close();
			}
		} catch (Exception $e) {
				$e = oci_error();
				$this->_RenderError('UNABLE TO CONNECT - '.$e['message'], 'XUXO_ERROR_CODE_004');
		}
		return ($this->_connection) ? NULL : $this->_connection;
	}
	
	/////////////////////////////////////////////////////////////////////////////
	// SQL QUERY HELPER FUNCTIONS
	public function _Execute($sql) {
		try {
			$this->_error = NULL;
			if(!$this->_connection) { $this->_Connect(); }
			// RUN QUERY			
			$result = oci_parse($this->_connection, $sql);
			oci_execute($result);			
			// RETURN RESULT
			if(!$result) {
				$e = oci_error();
				$this->_RenderError($e['message'], 'XUXO_ERROR_CODE_004');
			}
			$this->_resource = ($result) ? $result : NULL;
			if($result && $this->_auto_commit) {
				if(strpos(strtoupper($sql),'SELECT') === FALSE) oci_commit($this->_connection);
			}
			return $result;
		} catch (Exception $e) {
			$e = oci_error();
			$this->_RenderError('UNABLE TO CONNECT - '.$e['message'], 'XUXO_ERROR_CODE_004');
		}
	}
	
	public function _FetchAll($sql) {
		try {
			$this->_error = NULL;
			if(!$this->_connection) { $this->_Connect(); }
			// EXECUTE AND ARRANGE RESULT
			$return = array();
			$result = $this->_Execute($sql);			
			if($result) {
				$i = 0;
				while ($row = oci_fetch_assoc($result)) {
					foreach ($row as $key=>$value) {
						$key = $this->_TransformKey($key);
						$return[$i][$key]=$value;
					}
					$i++;
				}
			}
			// RETURN RESULT
			return ($return && !empty($return)) ? $return : NULL;
		} catch (Exception $e) {
			$e = oci_error();
			$this->_RenderError('UNABLE TO CONNECT - '.$e['message'], 'XUXO_ERROR_CODE_004');
		}
	}
	
	public function _FetchSingle($sql) {
		try {
			$this->_error = NULL;
			if(!$this->_connection) { $this->_Connect(); }		
			$return = array();
			$result = $this->_Execute($sql);
			if($result) {		
				$row = oci_fetch_assoc($result);
				if($row) {
					foreach ($row as $key=>$value) {
						$key = $this->_TransformKey($key);
						$return[$key] = $value;
					}
				}
			}
			return ($return && !empty($return)) ? $return : NULL;
		} catch (Exception $e) {
			$e = oci_error();
			$this->_RenderError('UNABLE TO CONNECT - '.$e['message'], 'XUXO_ERROR_CODE_004');
		}
	}
	
	public function _LastInsertId() { $this->_RenderError('ORACLE DOES NOT HAVE LAST INSERT ID METHOD','XUXO_ERROR_CODE_004'); }
	
	public function _AffectedRows() { return ($this->_resource) ? oci_num_rows($this->_resource) : NULL; }
	
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
				$e = oci_error();
				$this->_RenderError($e['message'], 'XUXO_ERROR_CODE_004');
			}
			return ($result) ? TRUE : FALSE;
		} catch (Exception $e) {
			$e = oci_error();
			$this->_RenderError('UNABLE TO CONNECT - '.$e['message'], 'XUXO_ERROR_CODE_004');
		}		
	}
	
	public function _InsertIntoTable($name, $param = array()) {
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
				$sql .= ($c>0) ? ", '".$item."'" : "'".$item."'";
				$c++;
			}
			$sql .= ")";
			
			$result = $this->_Execute($sql);
			if(!$result) {
				$e = oci_error();
				$this->_RenderError($e['message'], 'XUXO_ERROR_CODE_004');
			}
			return ($result) ? TRUE : FALSE;
		} catch (Exception $e) {
			$e = oci_error();
			$this->_RenderError('UNABLE TO CONNECT - '.$e['message'], 'XUXO_ERROR_CODE_004');
		}		
	}
	
	public function _UpdateTable($name, $param = array()) {		
		try {
			if(!$name) $this->_RenderError('TABLE NAME CANNOT BE EMPTY','XUXO_ERROR_CODE_004');
			if(!is_array($param)) $this->_RenderError('PARAMETER MUST BE AN ARRAY','XUXO_ERROR_CODE_004');
			if(empty($param)) $this->_RenderError('PARAMETER CANNOT BE EMPTY','XUXO_ERROR_CODE_004');
			$sql = "UPDATE ".$name." SET ";
			$c = 0;
			foreach($param as $key=>&$item) {
				$key = $this->_TransformKey($key);
				$sql .= ($c>0) ? (", ".$key." = '".$item."'") : ($key." = '".$item."'");
				$c++;
			}
			
			$result = $this->_Execute($sql);
			if(!$result) {
				$e = oci_error();
				$this->_RenderError($e['message'], 'XUXO_ERROR_CODE_004');
			}
			return ($result) ? TRUE : FALSE;
		} catch (Exception $e) {
			$e = oci_error();
			$this->_RenderError('UNABLE TO CONNECT - '.$e['message'], 'XUXO_ERROR_CODE_004');
		}
	}
	
	public function _DeleteFromTable($name, $param = array()) {			
		try {
			if(!$name) $this->_RenderError('TABLE NAME CANNOT BE EMPTY','XUXO_ERROR_CODE_004');
			if(!is_array($param)) $this->_RenderError('PARAMETER MUST BE AN ARRAY','XUXO_ERROR_CODE_004');
			if(empty($param)) $this->_RenderError('PARAMETER CANNOT BE EMPTY','XUXO_ERROR_CODE_004');
			
			$sql = "DELETE FROM ".$name;
			$c = 0;
			foreach($param as $key=>&$item) {
				$key = $this->_TransformKey($key);
				$sql .= ($c>0) ? (" AND ".$key." = '".$item."'") : (" WHERE ".$key." = '".$item."'");
				$c++;
			}
			
			$result = $this->_Execute($sql);
			if(!$result) {
				$e = oci_error();
				$this->_RenderError($e['message'], 'XUXO_ERROR_CODE_004');
			}
			return ($result) ? TRUE : FALSE;
		} catch (Exception $e) {
			$e = oci_error();
			$this->_RenderError('UNABLE TO CONNECT - '.$e['message'], 'XUXO_ERROR_CODE_004');
		}
	}
	
	public function _SelectFromTable($name, $column = array(), $selection = array(), $single = false) {		
		try {
			if(!$name) $this->_RenderError('TABLE NAME CANNOT BE EMPTY','XUXO_ERROR_CODE_004');
			if(!is_array($column)) $this->_RenderError('COLUMNS PARAMETER MUST BE AN ARRAY','XUXO_ERROR_CODE_004');
			if(!is_array($selection)) $this->_RenderError('SELECTION (WHERE... AND...) PARAMETER MUST BE AN ARRAY','XUXO_ERROR_CODE_004');
			// PROCESS
			$sql = "SELECT ";
			if(empty($param)) {
				$sql .= '*';
			} else {
				$c = 0;
				foreach($param as $key=>&$item) {
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
					$sql .= ($c>0) ? (" AND ".$key." = '".$item."'") : (" WHERE ".$key." = '".$item."'");
					$c++;
				}
			}
			
			$result = ($single) ? $this->_FetchSingle($sql) : $this->_FetchAll($sql);
			return $result;
		} catch (Exception $e) {			
			$e = oci_error();
			$this->_RenderError('UNABLE TO CONNECT - '.$e['message'], 'XUXO_ERROR_CODE_004');
		}
	}
	
	public function _CreateSynonym($name, $table, $type = 'PUBLIC') {
		if(!$name) $this->_RenderError('SYNONYM NAME CANNOT BE EMPTY','XUXO_ERROR_CODE_004');
		if(!$table) $this->_RenderError('SYNONYM TABLE CANNOT BE EMPTY','XUXO_ERROR_CODE_004');
		if(!$type) $this->_RenderError('SYNONYM TYPE CANNOT BE EMPTY','XUXO_ERROR_CODE_004');
		if(!in_array(strtoupper($type), array('PUBLIC', 'PRIVATE'))) $this->_RenderError('SYNONYM TYPE SHOULD BE PUBLIC OR PRIVATE ONLY','XUXO_ERROR_CODE_004');
		$type = strtoupper($type);
		// GET CONFIGURATION
		$config = $this->_config;
		if(!$config) $this->_RenderError('CONFIG IS NOT SET','XUXO_ERROR_CODE_004');
		if(!isset($config['USER']) || !$config['USER']) $this->_RenderError('USER IS NOT SET','XUXO_ERROR_CODE_004');
		$owner = strtoupper($config['USER']);
		$sql = "CREATE ".$type." SYNONYM ".$name." FOR ".$owner.".".$table;
		$result = $this->_Execute($sql);
		return ($result);
	}
	
	public function _GrantPrivilege($privileges, $object, $user) {
		if(!$privileges || count($privileges) == 0) $this->_RenderError('PRIVILEGES CANNOT BE EMPTY','XUXO_ERROR_CODE_004');
		if(!$object) $this->_RenderError('GRANT OBJECT CANNOT BE EMPTY','XUXO_ERROR_CODE_004');
		if(!$user) $this->_RenderError('GRANTEE CANNOT BE EMPTY','XUXO_ERROR_CODE_004');
		////////////////////////////
		$result = NULL;
		if($privileges) {
			foreach($privileges as &$privilege) {
				if(!in_array($privilege,array('SELECT','INSERT','UPDATE','DELETE','REFERENCES','ALTER','INDEX','ALL'))) {
					$this->_RenderError('INVALID PRIVILEGE REQUESTED','XUXO_ERROR_CODE_004');
				}
			}
			$p_string = (count($privileges) > 1) ? implode(', ',$privileges) : $privileges[0];
			$sql = "GRANT ".(strtoupper($p_string))." ON ".(strtoupper($object))." TO ".(strtoupper($user));
			$result = $this->_Execute($sql);
		}
		return ($result) ? TRUE : FALSE;
	}
	
	public function _RevokePrivilege($privileges, $object, $user) {
		if(!$privileges || count($privileges) == 0) $this->_RenderError('PRIVILEGES CANNOT BE EMPTY','XUXO_ERROR_CODE_004');
		if(!$object) $this->_RenderError('GRANT OBJECT CANNOT BE EMPTY','XUXO_ERROR_CODE_004');
		if(!$user) $this->_RenderError('GRANTEE CANNOT BE EMPTY','XUXO_ERROR_CODE_004');
		////////////////////////////
		$result = NULL;
		if($privileges) {
			foreach($privileges as &$privilege) {
				if(!in_array($privilege,array('SELECT','INSERT','UPDATE','DELETE','REFERENCES','ALTER','INDEX','ALL'))) {
					$this->_RenderError('INVALID PRIVILEGE REQUESTED','XUXO_ERROR_CODE_004');
				}
			}
			$p_string = (count($privileges) > 1) ? implode(', ',$privileges) : $privileges[0];
			$sql = "REVOKE ".(strtoupper($p_string))." ON ".(strtoupper($object))." FROM ".(strtoupper($user));
			$result = $this->_Execute($sql);
		}
		return ($result) ? TRUE : FALSE;
	}
	
	public function _Commit() {
		if(!$this->_connection) $this->_RenderError('CONNECTION IS NOT SET','XUXO_ERROR_CODE_004');
		return oci_commit($this->_connection);
	}
	
	public function _Rollback() {
		if(!$this->_connection) $this->_RenderError('CONNECTION IS NOT SET','XUXO_ERROR_CODE_004');
		return oci_rollback($this->_connection);
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