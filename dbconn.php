<?php
$serverName = "210.217.27.205"; //데이터베이스 서버 공인ip
$database = "bank";
$uid = "bankuser1";
$pwd = "Bankuser1!";

// CSRF 토큰 생성
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // CSRF 토큰 생성
}

// CSRF 토큰 검증
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF 공격이 감지되었습니다.");
    }
}

try {
    $dsn = "mysql:host=$serverName;port=3306;dbname=$database";
    $conn = new PDO($dsn, $uid, $pwd);

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error connecting to MySQL: " . $e->getMessage());
}
?>