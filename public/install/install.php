<?php
$globalConf = include(__DIR__ . '/../../config/autoload/global.php');
$localConf = include(__DIR__ . '/../../config/autoload/local.php');

$dsn = explode(':', $globalConf['db']['dsn'])[1];
list($dbname, $host) = explode(';', $dsn);

$dbname = explode('=', $dbname)[1];
$host = explode('=', $host)[1];

$username = $localConf['db']['username'];
$password = $localConf['db']['password'];

$script_path = __DIR__ . '/../../';

$command = "mysql -u{$username} -p{$password} "
	. "-h {$host} -D {$dbname} < {$script_path}";

$output = shell_exec($command . 'kpaste.sql');

echo 'Delete "public/install/" NOW!';

