<?php
/*
 *
 *
 */
declare(strict_types = 1);

// Set base directory
include 'vendor/autoload.php';
include 'functions.php';

// Define fixed variables
define('BOT_TOKEN', '');
define('API_URL', 'https://api.telegram.org/bot'.BOT_TOKEN.'/');

// Bot telegram ID (for exclusion purpose)
$cfg["botID"]	= '0000000000000000';
$cfg["botUser"]	= 'UNIQUE_USER_NAME';

// MySQL variables
$db_host = 'localhost';
$db_name = '';
$db_user = '';
$db_pass = '';

// Connect to MySQL database through PDO
$dbh = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
$dbh->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');
