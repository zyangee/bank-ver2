<?php
session_start();
include "../dbconn.php"; // 데이터베이스 연결

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline';");

$transactions = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 입력 데이터 검증
    $accountNumber = $_POST['select_account'] ?? '';
    $startDate = $_POST['start-date'] ?? null;
    $endDate = $_POST['end-date'] ?? null;
    $viewOption = $_POST['view-option'] ?? 'all';
    $order = $_POST['order'] ?? 'recent';

    $accountNumber = filter_var($accountNumber, FILTER_SANITIZE_STRING);
    $startDate = $startDate ? filter_var($startDate, FILTER_SANITIZE_STRING) : null;
    $endDate = $endDate ? filter_var($endDate, FILTER_SANITIZE_STRING) . ' 23:59:59' : null;

    $sql = "
        SELECT t.transaction_id, t.transaction_date, tt.type AS transaction_type, 
               a.account_number, t.receiver_account, t.amount, t.amount_after 
        FROM transactions t
        JOIN accounts a ON t.account_id = a.account_id
        LEFT JOIN transaction_types tt ON t.transaction_type_id = tt.id
        WHERE a.account_number = :accountNumber
    ";

    $params = [':accountNumber' => $accountNumber];

    if ($startDate && $endDate) {
        $sql .= " AND t.transaction_date BETWEEN :startDate AND :endDate";
        $params[':startDate'] = $startDate;
        $params[':endDate'] = $endDate;
    } elseif ($startDate) {
        $sql .= " AND t.transaction_date >= :startDate";
        $params[':startDate'] = $startDate;
    } elseif ($endDate) {
        $sql .= " AND t.transaction_date <= :endDate";
        $params[':endDate'] = $endDate;
    }

    if ($viewOption === 'deposit') {
        $sql .= " AND tt.type = '입금'";
    } elseif ($viewOption === 'interest') {
        $sql .= " AND tt.type = '출금'";
    }

    $sql .= ($order === 'recent') ? " ORDER BY t.transaction_date DESC" : " ORDER BY t.transaction_date ASC";

    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [['message' => '조회된 거래내역이 없습니다.']];
    } catch (PDOException $e) {
        error_log("DB 오류: " . $e->getMessage());
        die("거래 내역 조회 중 오류가 발생했습니다.");
    }
}
?>

<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>거래내역 조회</title>
    <link rel="stylesheet" href="../css/back.css">
    <link rel="stylesheet" href="../css/input.css">
    <link rel="stylesheet" href="../css/transaction.css">
</head>

<body>
    <div class="navbar">
        <span>megabank</span>
        <ul>
            <li><a href="../index.php">홈</a></li>
            <li>|</li>
            <?php if (isset($_SESSION['username'])): ?>
                <li><a href="../account/users.php"><?php echo htmlspecialchars($_SESSION['username']); ?></a>님</li>
                <li>|</li>
                <li><a href="../login/logout.php">로그아웃</a></li>
            <?php else: ?>
                <li><a href="../login/login.php">로그인</a></li>
            <?php endif; ?>
        </ul>
    </div>
    <div class="container">
        <h2 class="h2_pageinfo">거래내역 조회</h2>
        <div class="search-box">
            <form method="post" action="transactions.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
                <!-- 계좌번호 입력 -->
                <div class="search-group">
                    <label for="account-number">조회 계좌번호:</label>
                    <select id="select_account" name="select_account">
                        <option value="">선택하세요</option>
                        <?php
                        $select_user_num = $_SESSION['user_num'];
                        if ($select_user_num) {
                            $query = "SELECT * FROM accounts WHERE user_num = :select_user_num";
                            $stmt = $conn->prepare($query);
                            $stmt->bindParam(":select_user_num", $select_user_num);
                            $stmt->execute();

                            $selected_account = $_POST['select_account'] ?? '';
                            if ($stmt->rowCount() > 0) {
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    $selected = ($row['account_number'] == $selected_account) ? 'selected' : '';
                                    ?>
                                    <option value="<?= $row['account_number'] ?>" <?= $selected ?>><?= $row['account_number'] ?>
                                    </option>
                                    <?php
                                }
                            } else {
                                echo "<option value=''>계좌가 없습니다.</option>";
                            }

                        } else {
                            echo "<option value=''>user_num이 전달되지 않았습니다.</option>";
                        } ?>
                    </select>
                </div>

                <!-- 조회기간 선택 -->
                <div class="search-group">
                    <label>조회기간:</label>
                    <input type="date" name="start-date" value="<?= htmlspecialchars($_POST['start-date'] ?? '') ?>">
                    <span>~</span>
                    <input type="date" name="end-date" value="<?= htmlspecialchars($_POST['end-date'] ?? '') ?>">
                </div>

                <div class="options">
                    <div class="search-group">
                        <label>조회내용 :</label>
                        <input type="radio" id="all" name="view-option" value="all" <?= ($_POST['view-option'] ?? 'all') === 'all' ? 'checked' : '' ?>>
                        <span for="all">전체(입금+출금)</span>
                        <input type="radio" id="deposit" name="view-option" value="deposit" <?= ($_POST['view-option'] ?? '') === 'deposit' ? 'checked' : '' ?>>
                        <span for="deposit">입금내역</span>
                        <input type="radio" id="interest" name="view-option" value="interest" <?= ($_POST['view-option'] ?? '') === 'interest' ? 'checked' : '' ?>>
                        <span for="interest">출금내역</span>
                    </div>

                    <!-- 조회결과 정렬 -->
                    <div class="search-group">
                        <label>조회결과 순서:</label>
                        <input type="radio" name="order" id="recent" value="recent" <?= ($_POST['order'] ?? 'recent') === 'recent' ? 'checked' : '' ?>>
                        <span for="recent">최근거래순</span>
                        <input type="radio" name="order" id="past" value="past" <?= ($_POST['order'] ?? '') === 'past' ? 'checked' : '' ?>>
                        <span for="past">과거거래순</span>
                    </div>

                    <!-- 조회 버튼 -->
                    <div class="search-group">
                        <button type="submit" class="search-btn">조회</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- 결과 테이블 -->
        <div class="result-box">
            <table>
                <thead>
                    <tr>
                        <th>번호</th>
                        <th>거래 일자</th>
                        <th>거래유형</th>
                        <th>계좌번호</th>
                        <th>수신계좌번호</th>
                        <th>거래 금액</th>
                        <th>거래 후 잔액</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($transactions && !isset($transactions[0]['message'])): ?>
                        <?php foreach ($transactions as $index => $transaction): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($transaction['transaction_date']); ?></td>
                                <td><?= htmlspecialchars($transaction['transaction_type'] ?? '없음'); ?></td>
                                <td><?= htmlspecialchars($transaction['account_number']); ?></td>
                                <td><?= htmlspecialchars($transaction['receiver_account']); ?></td>
                                <td><?= number_format($transaction['amount']); ?></td>
                                <td><?= number_format($transaction['amount_after']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7"><?= htmlspecialchars($transactions[0]['message'] ?? '거래내역이 없습니다.'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>