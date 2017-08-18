<?php
require_once dirname(__FILE__).'/medoo.php';
$database = new medoo(array(
	'database_type' => $dbtype,
	'database_name' => $db,
	'database_file' => $dbpath,
	'server' => $host,
	'username' => $user,
	'password' => $pass,
	'charset' => 'utf8'
));