<?php
include "../dbconn.php";
// 사용자 ID는 세션에서 가져온다고 가정합니다.
$user_num = $_SESSION['user_num']; // 예시로 사용자 num을 설정

// 대출 정보를 가져오는 SQL 쿼리
$sql = "SELECT *
        FROM loans l
        JOIN loan_types lt ON l.loan_type_id = lt.id
        JOIN loan_statuses ls ON l.loan_status_id = ls.id
        WHERE l.user_num = :user_num";

try {
    $stmt = $conn->prepare($sql); // SQL 쿼리 준비
    $stmt->bindParam(':user_num', $user_num); // 사용자 번호 바인딩
    $stmt->execute(); // 쿼리 실행

    $loans = $stmt->fetchAll(PDO::FETCH_ASSOC); // 모든 결과 가져오기
} catch (Exception $e) {
    echo "쿼리 오류: " . $e->getMessage(); // 쿼리 실행 오류 처리
    exit; // 스크립트 종료
}

// 연결 종료
$conn = null; // 연결 종료
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
            /*짝수행에만 색 적용 홀수하려면 odd*/
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
                            <td><?php echo ($loan['type']); ?></td>
                            <td><?php echo number_format($loan['loan_amount']); ?>원</td>
                            <td><?php echo ($loan['interest_rate']); ?>%</td>
                            <td><?php echo ($loan['loan_start_date']); ?></td>
                            <td><?php echo ($loan['loan_end_date']); ?></td>
                            <td><?php echo ($loan['status']); ?></td>
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
