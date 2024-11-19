<?php
session_start();
include "../dbconn.php";
if (!isset($_SESSION["userid"]) || !isset($_SESSION["username"])) {
    echo "로그인이 필요합니다.";
}
$select_user_num = $_SESSION['user_num'];
?>

<!DOCTYPE html>
<html>

<head>
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
            include "dbconn.php";
            if (isset($_SESSION['username'])): ?>
                <li><a href="../account/users.php"><?php echo $_SESSION['username']; ?></a>님</li>
                <li>|</li>
                <li><a href="../login/logout.php">로그아웃</a></li>
            <?php else: ?>
                <li><a href="../login/login.php">로그인</a></li>
            <?php endif; ?>
        </ul>
    </div>
    <div class="container">
        <h2 class="h2_pageinfo">송금</h2>
        <form class="form_css" method="POST">
            <div> <!--출금계좌선택-->
                <label class="input">출금계좌번호</label>
                <select class="select" id="out_account" name="out_account">
                    <option value="">선택하세요</option>
                    <?php
                    if ($select_user_num) {
                        $query = "SELECT * FROM accounts WHERE user_num = :select_user_num";
                        $stmt = $conn->prepare($query);
                        $stmt->bindParam(":select_user_num", $select_user_num);
                        $stmt->execute();

                        if ($stmt->rowCount() > 0) {
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                ?>
                                <option value="<?= $row['account_number'] ?>"><?= $row['account_number'] ?></option>
                                <?php
                            }
                        } else {
                            echo "<option value=''>계좌가 없습니다.</option>";
                        }

                    } else {
                        echo "<option value=''>user_num이 전달되지 않았습니다.</option>";
                    } ?>
                </select>
                <div class="align-right-input">
                    <div class="input" id="balance">잔액: 0원</div>
                    <button class="input_button" type="button" onclick="myAccount()">출금가능금액 조회</button>
                </div>
            </div>
            <div> <!--입금계좌번호 입력-->
                <label class="input">입금계좌번호</label>
                <input class="input_text" type="text" id="in_account" name="in_account" required>
            </div>
            <div> <!--이체금액 입력-->
                <label class="input">이체금액</label>
                <input class="input_text" type="number" id="transfer_amount" name="transfer_amount" required>
            </div>
            <div><!--비밀번호 입력-->
                <label class="input">계좌 비밀번호 입력</label>
                <input class="input_text" type="password" id="input_password" name="input_password" required>
            </div>
            <button class="submit_button" type="submit" onclick="transferSubmit()">이체하기</button>
        </form>
    </div>
</body>

</html>