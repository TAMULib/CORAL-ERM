<?php
session_start();
require_once('../directory.php');

if (!function_exists('debug')) {
  function debug($value) {
    echo '<pre>'.print_r($value, true).'</pre>';
  }
}

class CORALInstaller {
  
  protected $db;
  public $error;
  protected $config;
  protected $updates = array(
    "1.1" => array(
      "privileges" => array("ALTER","CREATE"),
      "description" => "<p>The 1.1 update to the CORAL Resources module includes a number of enhancements:</p>
      <ul>
        <li>Added a cataloging tab to resource records, allowing tracking of cataloging details and notes.</li>
        <li>Search resources by active routing steps and cataloging status, as well as some minor performance enhancements to the search listings.</li>
        <li>The export file has been completely revamped.  Clicking the Excel icon on the resources search page now downloads a CSV file which includes many more fields and should open much more quickly.</li>
      </ul> 
      <p>This upgrade will connect to MySQL and run the CORAL Resources structure changes. No changes to the configuration file are required.  Database structure changes include:</p>
    	<ul>
    		<li>Create table CatalogingStatus and CatalogingType (configurable in the admin)</li>
    		<li>Add cataloging columns to the Resource table</li>
    		<li>Numerous indexes to improve search performance</li>
    	</ul>"
    ),
    "1.2" => array(
      "privileges" => array("ALTER","CREATE"),
      "description" => "<p>The 1.2 update to the CORAL Resources module includes a number of enhancements:</p>
      <ul>
        <li>Added coverage to the resource record.</li>
        <li>Added an alternative URL to the resources record.</li>
        <li>Add subject terms to the Resource record</li>
        <li>Changed how the related products are displayed for the resource</li>		
        <li>Add defaultSort to the configuration.ini.  If used this changes the default sort order for the resources</li>				
      </ul> 
      <p>This upgrade will connect to MySQL and run the CORAL Resources structure changes. No changes to the configuration file are required.  Database structure changes include:</p>
    	<ul>
    		<li>Create subjects tables GeneralSubject, DetailedSubject, GeneralDetailsubjectlink, and ResourceSubject. (subjects are configurable in the admin)</li>
    		<li>Add coverageText, resourceAltURL columns to the Resource table</li>
    	</ul>"
    )	
  );
  
  public function __construct() {
    if (is_file($this->configFilePath())) {
      $this->config = new Configuration();
      $this->connect();
    }
  }
  
  public function connect($username = null, $password = null) {
    $this->error = '';
		$host = $this->config->database->host;
		if ($username === null) {
		  $username = $this->config->database->username;
		}
		if ($password === null) {
		  $password = $this->config->database->password;
	  }
		$this->db = @mysql_connect($host, $username, $password);
		if (!$this->db) {
		  
		  $this->error = mysql_error();
		  if (!$this->error) {
		    $this->error = "Access denied for user '$username'";
		  }
	  } else {
  		$databaseName = $this->config->database->name;
  		mysql_select_db($databaseName, $this->db);
  		$this->error = mysql_error($this->db);
		}
		
		if ($this->error) {
		  $this->db = null;
		}
	}
	
	public function query($sql) {
		$result = mysql_query($sql, $this->db);
		
		$this->checkForError();
		$data = array();

		if (is_resource($result)) {
			while ($row = mysql_fetch_array($result)) {
				array_push($data, $row);
			}
		} else if ($result) {
			$data = mysql_insert_id($this->db);
		}

		return $data;
	}
	
	protected function checkForError() {
		if ($this->error = mysql_error($this->db)) {
			throw new Exception("There was a problem with the database: " . $this->error);
		}
	}
	
	public function getDatabaseName() {
	  return $this->config->database->name;
	}
  
  public function addErrorMessage($error) {
    if (!$this->hasErrorMessages()) {
      $_SESSION['installer_error_messages'] = array();
    }
    $_SESSION['installer_error_messages'] []= $error;
  }
  
  public function hasErrorMessages() {
    return isset($_SESSION['installer_error_messages']);
  }
  
  public function displayErrorMessages() {
    if ($this->hasErrorMessages()) {
			echo "<div style='color:red'><p><b>The following errors occurred:</b></p><ul>";
			foreach ($_SESSION['installer_error_messages'] as $err) {
				echo "<li>" . $err . "</li>";
			}
			echo "</ul></div>";
			unset($_SESSION['installer_error_messages']);
		}
  }
  
  public function addMessage($msg) {
    if (!$this->hasMessages()) {
      $_SESSION['installer_messages'] = array();
    }
    $_SESSION['installer_messages'] []= $msg;
  }
  
  public function hasMessages() {
    return isset($_SESSION['installer_messages']);
  }
  
  public function displayMessages() {
    if ($this->hasMessages()) {
			echo "<div style='color:green'><ul>";
			foreach ($_SESSION['installer_messages'] as $msg) {
				echo "<li>" . $msg . "</li>";
			}
			echo "</ul></div>";
			unset($_SESSION['installer_messages']);
		}
  }
  
  public function modulePath() {
    //returns file path for this module, i.e. /coral/resources/
    $replace_path = preg_quote(DIRECTORY_SEPARATOR."install");
    return preg_replace("@$replace_path$@", "", dirname(__FILE__));
  }

  public function configFilePath() {
    return $this->modulePath().'/admin/configuration.ini';
  }
  
  public function isDatabaseConfigValid() {
    return $this->config && $this->db;
  }
  
  public function hasPermission($permission) {
    if ($this->isDatabaseConfigValid()) {
      $grants = array();
      $permission = "(ALL PRIVILEGES|".strtoupper($permission).")";
      foreach ($this->query("SHOW GRANTS FOR CURRENT_USER()") as $row) {
        $grant = $row[0];
        if (strpos(str_replace('\\', '', $grant), $this->config->database->name) || strpos($grant, "ON *.*")) {
          if (preg_match("/(GRANT|,) $permission(,| ON)/i",$grant)) {
            return true;
          }
        }
      }
    }
    return false;
  }
  
  public function hasPermissions($permissions) {
    foreach($permissions as $permission) {
      if (!$this->hasPermission($permission)) {
        return false;
      }
    }
    return true;
  }
  
  public function tableExists($table) {
    foreach ($this->query("SHOW TABLES") as $row) {
      if (strtolower($row[0]) == strtolower($table)) {
        return true;
      }
    }
    return false;
  }
  
  public function indexExists($table, $index) {
    $result = $this->query("SHOW INDEXES FROM $table WHERE Key_name = '$index'");
    return count($result) > 0;
  }
  
  public function installed() {
    if ($this->isDatabaseConfigValid()) {
      foreach (array("Resource","Workflow") as $table) {
        if (!$this->tableExists($table)) {
          return false;
        }
      }
      return true;
    }
    return false;
  }
  
  public function getNextUpdateVersion() {
    foreach($this->updates as $version => $details) {
      if (!$this->isUpdateInstalled($version)) {
        return $version;
      }
    }
  }
  
  public function isUpdateReady($version) {
    return $this->getNextUpdateVersion() == $version;
  }
  
  public function isUpdateInstalled($version) {
    if ($this->installed()) {
      switch ($version) {
        case "1.1":
          return $this->tableExists("CatalogingStatus");
      }
    }
    return false;
  }
  
  public function getUpdate($version) {
    return $this->updates[$version];
  }
  
  public function header($title = 'CORAL Installation') {
    include('header.php');  
  }
  
  public function footer() {
    include('footer.php');  
  }
}
?>