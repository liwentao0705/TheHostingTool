<?php
/* Copyright © 2014 TheHostingTool
 *
 * This file is part of TheHostingTool.
 *
 * TheHostingTool is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * TheHostingTool is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with TheHostingTool.  If not, see <http://www.gnu.org/licenses/>.
 */

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

spl_autoload_register(function($class) {
    // Convert namespace and class path to a file path
    $path = LINK . DIRECTORY_SEPARATOR . str_replace("\\", DIRECTORY_SEPARATOR,
        substr(ltrim(strtolower($class), "thehostingtool"), 1)) . ".php";
    // Fail autoload silently
    if(file_exists($path) && is_readable($path)) {
        include_once($path);
    }
});

// Define the main THT
define("THT", 1);

// Helps prevent against CSRF attacks.
require_once("csrf-magic.php");

// We don't want this to be called directly.
$compile = explode("/", $_SERVER["SCRIPT_FILENAME"]);
if($compile[count($compile)-1] == "compiler.php") {
	die("Please do not call \"compiler.php\" directly.");
}

#Page generated
$starttime = explode(' ', microtime());
$starttime = $starttime[1] + $starttime[0];

#Start us up
if(CRON != 1) {
	session_start();
}

#Stop the output
ob_start();

#Check for Dependencies
$d = checkForDependencies();
if($d !== true) {
	die((string)$d);
}

#Check PHP Version
$version = explode(".", phpversion());

//Grab DB First
require LINK."/class_db.php"; # Get the file
if(file_exists(LINK."/conf.inc.php")) {
	include LINK."/conf.inc.php"; # Get the config
	define("NOCONFIG", false);
}
else {
	define("NOCONFIG", true);
	define("PASSWORD_COST", 11);
}
if($sec['cost']) {
	define("PASSWORD_COST", $sec['cost']);
}
if($sql['install']) {
	define("INSTALL", true);
	$db = new db; # Create the class
	global $db; # Globalise it
}
else {
    define("INSTALL", false);
    define("PASSWORD_COST", 11);
}

$folder = LINK;
if ($handle = opendir($folder)) { # Open the folder
	while (false !== ($file = readdir($handle))) { # Read the files
		if($file != "." && $file != "..") { # Check aren't these names
			$base = explode(".", $file); # Explode the file name, for checking
			if($base[1] == "php") { # Is it a php?
				$base2 = explode("_", $base[0]);
				if($base2[0] == "class" && $base2[1] != "db") {
					require $folder."/".$file; # Get the file
					${$base2[1]} = new $base2[1]; # Create the class
					global ${$base2[1]}; # Globalise it
				}
			}
		}
	}
}
closedir($handle); #Close the folder
if(INSTALL == 1) {
    date_default_timezone_set($db->config("timezone")); // Sets the default timezone
	define("THEME", $db->config("theme")); // Set the default theme
	// Sets the URL THT is located at
	$url = $db->config("url");
	$wwwInUrl = preg_match('%^(http(?:s)?://)(www\.)?(.*)%', $url, $urlregout);
	$wwwInUrl = ($urlregout[2]=='www.'?true:false);
	$wwwInCurrent = preg_match('%^(www\.)?(.+)%', $_SERVER['HTTP_HOST'], $currentregout);
	$wwwInCurrent = ($currentregout[1]=='www.'?true:false);
	switch ($db->config("wwwsubdomain")) {
		case 'both':
			if($wwwInUrl && !$wwwInCurrent) {
				// Remove WWW
				$url = $urlregout[1] . $urlregout[3];
			}
			elseif(!$wwwInUrl && $wwwInCurrent) {
				// Add WWW
				$url = $urlregout[1] . 'www.' . $urlregout[3];
			}
			break;
		case 'nowww':
			if($wwwInCurrent) {
				// Remove WWW
				header('Location: http'.($_SERVER['HTTPS']?'s':'').'://'.$currentregout[2].$_SERVER['REQUEST_URI']);
				exit();
			}
			break;
		case 'www':
			if(!$wwwInCurrent) {
				header('Location: http'.($_SERVER['HTTPS']?'s':'').'://www.'.$currentregout[2].$_SERVER['REQUEST_URI']);
				exit();
			}
			break;
	}
	if($_SERVER["HTTPS"] && ($urlregout[1] != "https://")) {
		// HTTPS support
		$url = str_replace("http://", "https://", $url);
	}
	elseif(!$_SERVER["HTTPS"] && ($urlregout[1] != "http://")) {
		// HTTP support (if URL is using HTTPS)
		$url = str_replace("https://", "http://", $url);
	}
	define('URL', $url);
	define("NAME", $db->config("name")); // Sets the name of the website
	define("ADMINDIR", $db->config("admindir")); // Sets the Admin Directory
}
else {
	define("THEME", "Reloaded2");
	define("ADMINDIR", "admin");
}
// Converts the $_POST global array into $main->postvar - DB Friendly.
if(isset($_POST)) {
	foreach($_POST as $key => $value) {
		if(INSTALL == 1) {
			$main->postvar[$key] = $db->strip($value);
		}
		else {
			$main->postvar[$key] = $value;
		}
	}
}
// Converts the $_GET global array into $main->getvar - DB Friendly.
if(isset($_GET)) {
	foreach($_GET as $key => $value) {
		if(INSTALL == 1) {
			$main->getvar[$key] = $db->strip($value);
		}
		else {
			$main->getvar[$key] = $value;
		}
	}
}
// Converts the $_REQUEST global array into $main->requestvar - DB Friendly.
if(isset($_REQUEST)) {
	foreach($_REQUEST as $key => $value) {
		if(INSTALL == 1) {
			$main->requestvar[$key] = $db->strip($value);
		}
		else {
			$main->requestvar[$key] = $value;
		}
	}
}

$path = dirname($_SERVER['PHP_SELF']);
$position = strrpos($path,'/') + 1;
define("FOLDER", substr($path,$position)); # Add current folder name to global

// Cheap. I know.
if(!is_dir("../includes") && !is_dir("../themes") && !is_dir("../admin")) {
	$check = explode("/", dirname($_SERVER["SCRIPT_NAME"]));
	if($check[count($check)-1] == "install") {
		die("Please change your THT directory's name from something else other than \"install\". Please?");
	}
}

if(FOLDER != "install" && FOLDER != "includes" && INSTALL != 1) { # Are we installing?
                // Old Method - Uncomment if having trouble installing
        //$error['Error'] = "THT isn't Installed!";
        //$error['What to do'] = "Please run the install script @ <a href='".LINK."../install'>here</a>";
        //die($main->error($error));

                // Lets just redirect to the installer, shall we?
        $installURL = LINK . "../install";
        header("Location: $installURL");
}

// If payment..
if(FOLDER == "client" && $main->getvar['page'] == "invoices" && $main->getvar['iid'] && $_SESSION['clogged'] == 1) {
	$invoice->pay($main->getvar['iid'], "client/index.php?page=invoices");
	echo "You made it this far.. something went wrong.";
}

function checkForDependencies() {
	// Here, we're going to see if we have the functions that we need. :D
	$needed = array();
	// First things first:
	if(version_compare(PHP_VERSION, '5.3.0', '<')) {
		die("PHP Version 5.3 or greater is required! You're currently running PHP " . PHP_VERSION);
	}
    // Check for a few extensions that are commonly unavailable
	if(!extension_loaded("curl")) {
		$needed[] = "cURL";
	}
	if(!extension_loaded("mysql")) {
		$needed[] = "MySQL";
	}
    if(!extension_loaded("gd")) {
        $needed[] = "GD";
    }
    if(!extension_loaded("json")) {
        $needed[] = "JSON";
    }
    if(!extension_loaded("SimpleXML")) {
        $needed[] = "SimpleXML";
    }
	if(count($needed) == 0) {
		return true;
	}
	else {
		$output = "The following extension".(count($needed)==1?" is":"s are")." needed for
		TheHostingTool to run properly: <ul>";
		foreach($needed as $key => $value) {
			$output .= "<li>$value</li>";
		}
		$output .= "</ul>";
		return $output;
	}
}
