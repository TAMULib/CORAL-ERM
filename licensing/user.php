<?php
/*
**************************************************************************************************************************
** CORAL Licensing Module v. 1.0
**
** Copyright (c) 2010 University of Notre Dame
**
** This file is part of CORAL.
**
** CORAL is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
**
** CORAL is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License along with CORAL.  If not, see <http://www.gnu.org/licenses/>.
**
**************************************************************************************************************************
*/
session_start();

$util = new Utility();
$config = new Configuration();

$addURL = '';

//if set to use auth module
if ($config->settings->authModule == 'Y'){


	//check if a cookie has been set for this user in a session
	$loginID = $util->getLoginCookie();

	//load user and verify they have a valid open session
	$user = new User(new NamedArguments(array('primaryKey' => $loginID)));
$sessionID = $util->getSessionCookie();

	//if the user has an open session
	if (($loginID) && ($user->hasOpenSession())){

		session_start();
		$_SESSION['loginID'] = $loginID;

	//no open session
	}else{

		//redirect to auth page
		if (isset($user->loginID)) {
			$addURL = '?timeout&service=';
		}else{
			if (($loginID) && (!isset($user->loginID))){
				$addURL = '?invalid&service=';
			}else{
				$addURL = '?service=';
			}
		}


		$authURL = $util->getCORALURL() . "auth/" . $addURL . htmlentities($_SERVER['REQUEST_URI']);
		header('Location: ' . $authURL, true);
		exit; //PREVENT SECURITY HOLE

	}


//otherwise plug into apache server variable
}else{

	//get login id from server
	if (!isset($_SESSION['loginID']) || ($_SESSION['loginID'] == '')){


		$varName = $config->settings->remoteAuthVariableName;

		//the following code takes the remote auth variable name from the config settings and evaluates it to get the actual value from web server

		//if the first character is a $ it needs to be stripped off for the eval to work
		$theVarStem = ltrim($varName, "$");

		//evaluate the remote variable
		$remoteAuth=eval("return \$$theVarStem;");

		//use the split in case the remote login is supplied as an email address
		list ($loginID,$restofAddr) = explode("@", $remoteAuth);



		session_start();
		$_SESSION['loginID'] = $loginID;


	}else{

		$loginID = $_SESSION['loginID'];

	}

}



//for the licensing module we require that the user exists in the database before granting access
//thus, setuser.php is not used
if ($loginID){
	//Load user
	$user = new User(new NamedArguments(array('primaryKey' => $loginID)));
	$privilege = new Privilege(new NamedArguments(array('primaryKey' => $user->privilegeID)));

	//if the user doesn't exist in database we need to redirect them to a page to give instructions on how to be added
	if ($user->privilegeID == ""){
		header('Location: not_available.php');
		exit; //PREVENT SECURITY HOLE
	}
}


?>
