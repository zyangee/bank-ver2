<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$serverName = getenv('MYSQL_HOST');
$database = getenv('MYSQL_DB');
$uid = getenv('MYSQL_USER');
$pwd = getenv('MYSQL_PASSWORD');

try {
    $dsn = "mysql:host=$serverName;port=3306;dbname=$database";
    $conn = new PDO($dsn, $uid, $pwd);

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error connecting to MySQL: ". $e->getMessage());
}
?>
