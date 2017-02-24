<?php
$db_tiki='mysql';
$dbversion_tiki='8.0';
$host_tiki='';
$user_tiki='';
$pass_tiki='';
$dbs_tiki='';
$client_charset='utf8';

foreach ($_SERVER as $key => $value) {
    if (strpos($key, "MYSQLCONNSTR_localdb") !== 0) {
        continue;
    }
    
    $host_tiki = preg_replace("/^.*Data Source=(.+?);.*$/", "\\1", $value);
    $dbs_tiki = preg_replace("/^.*Database=(.+?);.*$/", "\\1", $value);
    $user_tiki = preg_replace("/^.*User Id=(.+?);.*$/", "\\1", $value);
    $pass_tiki = preg_replace("/^.*Password=(.+?)$/", "\\1", $value);
}

$link = mysqli_connect($host_tiki, $user_tiki, $pass_tiki, $dbs_tiki);

if (!$link) {
    echo "Error: Unable to connect to MySQL." . PHP_EOL;
    echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
    echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
    exit;
}

echo "Success: A proper connection to MySQL was made! The my_db database is great." . PHP_EOL;
echo "Host information: " . mysqli_get_host_info($link) . PHP_EOL;

mysqli_close($link);
