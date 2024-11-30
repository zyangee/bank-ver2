<?php
// HTTPS 강제 사용
//if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
//    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
//    exit;
//}
session_start();
//CSRF 토큰 추가
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF 토큰이 유효하지 않습니다.");
    }
} else {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 세션 유효성 검사: 세션에 `user_num`이 없으면 로그인 페이지로 리다이렉트
if (!isset($_SESSION['user_num']) || !is_numeric($_SESSION['user_num'])) {
    header('Location: login.php');
    exit();
}

//세션 관리 강화
if (!isset($_SESSION['user_ip'])) {
    $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
} elseif ($_SESSION['user_ip'] !== $_SERVER['REMOTE_ADDR']) {
    session_unset();
    session_destroy();
    die("비정상적인 접근이 감지되었습니다.");
}

include "../dbconn.php";
$user_num = $_SESSION['user_num'];

$sql = "SELECT *
        FROM loans l
        JOIN loan_types lt ON l.loan_type_id = lt.id
        JOIN loan_statuses ls ON l.loan_status_id = ls.id
        WHERE l.user_num = :user_num";

try {
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_num', $user_num, PDO::PARAM_INT);
    $stmt->execute();
    $loans = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage()); // 서버 로그에 기록
    die("데이터를 불러오는 도중 문제가 발생했습니다. 잠시 후 다시 시도해주세요."); // 사용자에게는 간단한 메시지
}
$conn = null;
?>

<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>대출 내역 조회</title>
    <link rel="stylesheet" href="../css/back.css">
    <link rel="stylesheet" href="../css/input.css">
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            padding: 12px;
            text-align: center;
            border: 1px solid #cccccc;
        }

        th {
            background-color: #003366;
            color: white;
        }

        tbody tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        tbody tr:hover {
            background-color: #e0e0e0;
        }

        td {
            font-size: 16px;
        }
    </style>
</head>

<body>
    <header>
        <div class="navbar">
            <span>megabank</span>
            <ul>
                <li><a href="../index.php">홈</a></li>
                <li>|</li>
                <?php
                include "../dbconn.php";
                if (isset($_SESSION['username'])): ?>
                    <li><a href="../acccount/users.php"><?php echo $_SESSION['username']; ?></a>님</li>
                    <li>|</li>
                    <li><a href="../logout/logout.php">로그아웃</a></li>
                <?php else: ?>
                    <li><a href="../login/login.php">로그인</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </header>
    <div class="container">
        <h2 class="h2_pageinfo">대출 내역 조회</h2>
        <table>
            <thead>
                <tr>
                    <th>대출 종류</th>
                    <th>총 대출액</th>
                    <th>적용 금리</th>
                    <th>대출 시작일</th>
                    <th>대출 종료일</th>
                    <th>대출 상태</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($loans): ?>
                    <?php foreach ($loans as $loan): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($loan['type'] ?? ''); ?></td> <!--htmlspecialchats-->
                            <td><?php echo htmlspecialchars(number_format($loan['loan_amount'] ?? 0)); ?>원</td>
                            <td><?php echo htmlspecialchars($loan['interest_rate'] ?? 0); ?>%</td>
                            <td><?php echo htmlspecialchars($loan['loan_start_date'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($loan['loan_end_date'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($loan['status'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">대출 정보가 없습니다.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>

</html>