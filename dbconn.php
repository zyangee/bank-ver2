<?php
//운영 환경 설정
ini_set('display_errors', 0);
error_reporting(E_ALL);

$serverName = "210.217.27.205"; //데이터베이스 서버 공인ip
$database = "bank";
$uid = "bankuser1";
$pwd = "Bankuser1!";

//보안 헤더 설정
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");

//세션 설정 강화
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');

session_start();

// CSRF 토큰 생성
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// CSRF 토큰 검증
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_POST['csrf_token'], $_SESSION['csrf_token'] ?? '')) {
        error_log("CSRF 공격 시도 감지: " . $_SERVER['REMOTE_ADDR']);
        http_response_code(403);
        die("보안 검증에 실패했습니다.");
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