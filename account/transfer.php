<?php
include "../dbconn.php";
if (!isset($_SESSION["userid"]) || !isset($_SESSION["username"])) {
    header("Location: ../login/login.php");
    exit;
}
$select_user_num = $_SESSION['user_num'];
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="Content-Security-Policy"
        content="default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';">
    <title>계좌이체</title>
    <script src="../javascript/transfer.js"></script>
    <link rel="stylesheet" href="../css/back.css">
    <link rel="stylesheet" href="../css/input.css">
    <link rel="stylesheet" href="../css/input_account.css">
</head>
<style>
    .align-right-input .input {
        color: gray;
    }
</style>

<body>
    <div class="navbar">
        <span>megabank</span>
        <ul>
            <li><a href="../index.php">홈</a></li>
            <li>|</li>
            <?php
            if (isset($_SESSION['username'])): ?>
                <li><a href="../account/users.php"><?php echo htmlspecialchars($_SESSION['username']); ?></a>님</li>
                <li>|</li>
                <li><a href="../login/logout.php">로그아웃</a></li>
            <?php else: ?>
                <li><a href="../login/login.php">로그인</a></li>
            <?php endif; ?>
        </ul>
    </div>
    <div class="container">
        <h2 class="h2_pageinfo">송금</h2>
        <form class="form_css" method="POST" onsubmit="return transferSubmit(event)">
            <!--CSRF 토큰 삽입-->
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div> <!--출금계좌선택-->
                <label class="input" for="out_account">출금계좌번호</label>
                <select class="select" id="out_account" name="out_account" required>
                    <option value="">선택하세요</option>
                    <?php
                    try {
                        $query = "SELECT account_number, balance FROM accounts WHERE user_num = :select_user_num";
                        $stmt = $conn->prepare($query);
                        $stmt->bindParam(":select_user_num", $select_user_num);
                        $stmt->execute();
                        $balance = 0;

                        while ($row = $stmt->fetch()) {
                            echo '<option value="' . htmlspecialchars($row['account_number']) . '">' . htmlspecialchars($row['account_number']) . '</option>';
                        }
                    } catch (PDOException $e) {
                        error_log("계좌 조회 오류: " . $e->getMessage());
                        echo '<option value="">계좌 정보를 불러올 수 없습니다.</option>';
                    } ?>
                </select>
                <div class="align-right-input">
                    <div class="input" id="balance">잔액: 0원</div>
                    <button class="input_button" type="button" onclick="myAccount()">출금가능금액 조회</button>
                </div>
            </div>
            <div> <!--입금계좌번호 입력-->
                <label class="input" for="in_account">입금계좌번호</label>
                <input class="input_text" type="text" id="in_account" name="in_account" pattern="[0-9]{10,14}"
                    maxlength="14" required oninput="this.value = this.value.replace(/[^0-9]/g, '')">
            </div>
            <div> <!--이체금액 입력-->
                <label class="input" for="transfer_amount">이체금액</label>
                <input class="input_text" type="number" id="transfer_amount" name="transfer_amount" min="1"
                    max="100000000" required oninput="validateAmount(this)">
            </div>
            <div><!--비밀번호 입력-->
                <label class="input" for="input_password">계좌 비밀번호 입력</label>
                <input class="input_text" type="password" id="input_password" name="input_password" pattern="[0-9]{4}"
                    maxlength="4" required oninput="this.value = this.value.replace(/[^0-9]/g, '')">
            </div>
            <button class="submit_button" type="submit">이체하기</button>
        </form>
    </div>

    <script>
        function validateAmount(input) {
            if (input.value < 0) input.value = 0;
            if (input.value > 100000000) input.value = 100000000;
        }
    </script>
</body>

</html>