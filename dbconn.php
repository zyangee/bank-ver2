<?php
//운영 환경 설정
ini_set('display_errors', 0);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    //세션 설정 강화
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.gc_maxlifetime', 1800);

    session_start();
}

// 세션이 유효한지 확인
if (isset($_SESSION['timeout']) && (time() - $_SESSION['timeout'] > 1800)) {
    session_unset();
    session_destroy();
    session_start();
}
$_SESSION['timeout'] = time();

// CSRF 토큰 생성
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$serverName = ""; //데이터베이스 서버의 IP
$database = ""; //데이터베이스
$uid = ""; //데이터베이스의 ID
$pwd = ""; //데이터베이스의 PW

//보안 헤더 설정
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");

try {
    $dsn = "mysql:host=$serverName;port=3306;dbname=$database";
    $conn = new PDO($dsn, $uid, $pwd);

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error connecting to MySQL: " . $e->getMessage());
}
?>
