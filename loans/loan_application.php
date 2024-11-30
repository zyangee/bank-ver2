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
    header('Location: ../login/login.php');
    exit();
}

$user_num = $_SESSION['user_num'];

//세션 관리 강화
if (!isset($_SESSION['user_ip'])) {
    $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
} elseif ($_SESSION['user_ip'] !== $_SERVER['REMOTE_ADDR']) {
    session_unset();
    session_destroy();
    die("비정상적인 접근이 감지되었습니다.");
}

// 서버 측 날짜 검증
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $loan_start_date = $_POST['loanStartDate'];
    $today = date('Y-m-d');

    if ($loan_start_date < $today) {
        die("대출 시작일은 오늘 이후의 날짜여야 합니다.");
    }
}

include "../dbconn.php";
$user_num = $_SESSION['user_num'];
try {
    $stmt = $conn->prepare("SELECT SUM(balance) AS total_assets FROM accounts WHERE user_num = :user_num");
    $stmt->bindParam(':user_num', $user_num, PDO::PARAM_INT); // SQL 인젝션 방지 
    $stmt->execute(); // 쿼리를 실행
    $row = $stmt->fetch(PDO::FETCH_ASSOC); // 실행 결과 가져오기$row = $result->fetch(PDO::FETCH_ASSOC);
    $totalAssets = $row['total_assets'];
} catch (PDOException $e) {
    die("데이터베이스 오류: " . $e->getMessage());
}
$interestRates = [
    '신용대출' => [100000000 => 7.0, 300000000 => 5.5, 500000000 => 3.5, 0 => 7.5],
    '담보대출' => [100000000 => 5.0, 300000000 => 4.5, 500000000 => 4.0, 0 => 5.5],
    '자동차대출' => [100000000 => 4.5, 300000000 => 4.2, 500000000 => 3.8, 0 => 4.8],
    '사업자대출' => [100000000 => 7.0, 300000000 => 6.0, 500000000 => 4.5, 0 => 7.5]
];

$selectedRates = [];
foreach ($interestRates as $type => $rates) {
    foreach ($rates as $limit => $rate) {
        if ($totalAssets >= $limit) {
            $selectedRates[$type] = $rate;
        }
    }
}

$loanDuration = 0;
if ($totalAssets >= 500000000) {
    $loanDuration = 7;
} elseif ($totalAssets >= 300000000) {
    $loanDuration = 5;
} elseif ($totalAssets >= 100000000) {
    $loanDuration = 4;
} elseif ($totalAssets < 100000000) {
    $loanDuration = 3;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // CSRF 토큰 검증
    if (isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
        // CSRF 토큰이 일치하면, 요청을 처리합니다.

        $loan_type = $_POST['loanType'];
        $loan_amount = $_POST['loanAmount'];
        $interest_rate = $_POST['interestRate'];
        $loan_start_date = $_POST['loanStartDate'];
        $loan_end_date = $_POST['loanEndDate'];
        $loan_status_id = 1;
        $user_num = $_SESSION['user_num']; //세션추가
        $stmt = $conn->prepare("SELECT id FROM loan_types WHERE type = :loanType");
        $stmt->bindParam(':loanType', $loan_type);
        $stmt->execute();
        $loan_type_id = $stmt->fetchColumn();

        if (!$loan_type_id) {
            die("유효하지 않은 대출 유형입니다.");
        }

        try {
            $sql = "INSERT INTO loans (user_num, loan_type_id, loan_amount, interest_rate, loan_start_date, loan_end_date, loan_status_id) 
                    VALUES (:userNum, :loanType, :loanAmount, :interestRate, :loanStartDate, :loanEndDate, :loanStatusId)";

            $stmt = $conn->prepare($sql);

            $stmt->bindParam(':userNum', $user_num);
            $stmt->bindParam(':loanType', $loan_type_id);
            $stmt->bindParam(':loanAmount', $loan_amount);
            $stmt->bindParam(':interestRate', $interest_rate);
            $stmt->bindParam(':loanStartDate', $loan_start_date);
            $stmt->bindParam(':loanEndDate', $loan_end_date);
            $stmt->bindParam(':loanStatusId', $loan_status_id);
            $stmt->execute();

            echo "<script>
                alert('대출 신청이 성공적으로 완료되었습니다.');
                window.location.href = 'loan_history.php'; // 대출 조회 페이지로 이동
            </script>";
        } catch (PDOException $e) {
            echo "<script>
                alert('대출 신청 중 오류가 발생했습니다: " . addslashes($e->getMessage()) . "');
                window.location.href = 'loan_product.php'; // 대출 상품 페이지로 이동
            </script>";
        }
    } else {
        // CSRF 토큰 검증 실패 시 오류 처리
        echo "<script>alert('CSRF 검증 실패. 다시 시도해 주세요.'); window.location.href = 'loan_product.php';</script>";
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>대출 신청</title>
    <link rel="stylesheet" href="css/back.css">
    <link rel="stylesheet" href="css/input.css">
    <link rel="stylesheet" href="css/input_account.css">
    <script>
        function updateInterestRates() {
            const loanType = document.getElementById("loanType").value;
            const interestRates = <?php echo json_encode($interestRates); ?>;
            const totalAssets = <?php echo $totalAssets; ?>;
            let interestRate = 'N/A';

            if (loanType && loanType !== 'default') {
                for (const [limit, rate] of Object.entries(interestRates[loanType])) {
                    if (totalAssets >= limit) {
                        interestRate = rate;
                        break;
                    }
                }
            }

            document.getElementById("interestRate").value = interestRate;
        }

        // 페이지 로드 시, 시작일과 종료일 제한 설정
        window.onload = function () {
            const today = new Date();
            const nextYear = new Date();
            nextYear.setFullYear(today.getFullYear() + 1); // 현재 날짜로부터 1년 후 날짜

            const todayString = today.toISOString().split('T')[0]; // yyyy-mm-dd 형식
            const nextYearString = nextYear.toISOString().split('T')[0]; // 1년 후 날짜

            // 대출 시작일 input의 최소, 최대 날짜 설정
            document.getElementById("loanStartDate").setAttribute("min", todayString);
            document.getElementById("loanStartDate").setAttribute("max", nextYearString);
        };

        // 대출 시작일이 오늘부터 1년 이내로만 설정되도록 하는 코드
        document.getElementById("loanStartDate").addEventListener("change", function () {
            const today = new Date();
            today.setHours(0, 0, 0, 0); // 오늘 날짜의 시간을 초기화
            const selectedDate = new Date(this.value);

            const nextYear = new Date(today);
            nextYear.setFullYear(today.getFullYear() + 1); // 현재 날짜로부터 1년 후

            // 선택된 날짜가 오늘보다 이전이거나 1년 이후일 경우 알림
            if (selectedDate < today || selectedDate > nextYear) {
                alert("대출 시작일은 오늘부터 1년 이내의 날짜만 선택할 수 있습니다.");
                this.value = ""; // 잘못된 날짜를 선택한 경우 초기화
            }
        });


        function updateEndDate() {
            const startDate = new Date(document.getElementById("loanStartDate").value);
            const loanDuration = <?php echo $loanDuration; ?>;

            if (!isNaN(startDate.getTime())) {
                startDate.setFullYear(startDate.getFullYear() + loanDuration);
                document.getElementById("loanEndDate").value = startDate.toISOString().split('T')[0];
            }
        }

        function toggleSubmitButton() {
            const checkbox = document.getElementById("confirmationCheckbox");
            document.getElementById("submitButton").disabled = !checkbox.checked;
        }
    </script>
</head>

<body>
    <header>
        <div class="navbar">
            <span>megabank</span>
            <ul>
                <li><a href="../index.php">홈</a></li>
                <li>|</li>
                <?php
                include "../dbconn.php.php";
                if (isset($_SESSION['username'])): ?>
                    <li><a href="../account/users.php"><?php echo $_SESSION['username']; ?></a>님</li>
                    <li>|</li>
                    <li><a href="../login/logout.php">로그아웃</a></li>
                <?php else: ?>
                    <li><a href="../login/login.php">로그인</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </header>
    <div class="container">
        <h2 class="h2_pageinfo">대출신청</h2>
        <form class="form_css" action="" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>"> <!-- CSRF 토큰 추가 -->
            <div>
                <label class="input" for="loanType">대출 종류</label>
                <select class="select" id="loanType" name="loanType" onchange="updateInterestRates()">
                    <option value="default">선택</option>
                    <option value="신용대출">신용대출</option>
                    <option value="담보대출">담보대출</option>
                    <option value="자동차대출">자동차대출</option>
                    <option value="사업자대출">사업자대출</option>
                </select>
            </div>
            <div>
                <label class="input" for="loanAmount">대출 금액</label>
                <input class="input_text" type="number" id="loanAmount" name="loanAmount" required min="1000000"
                    max="1000000000" step="1000000">
            </div>
            <div>
                <label class="input" for="totalAssets">총 자산</label>
                <div id="memo">자동으로 적용됩니다.</div>
                <input class="input_text" type="text" id="totalAssets" name="totalAssets"
                    value="<?php echo number_format($totalAssets); ?>" readonly />
            </div>
            <div>
                <label class="input" for="interestRate">적용 금리 (%)</label>
                <div id="memo">자동으로 적용됩니다.</div>
                <input class="input_text" type="text" id="interestRate" name="interestRate" readonly>
            </div>
            <div>
                <label class="input" for="loanStartDate">대출 시작일</label>
                <?php
                $today = date('Y-m-d'); // 오늘 날짜를 'YYYY-MM-DD' 형식으로 가져옴
                ?>
                <input class="input_text" type="date" id="loanStartDate" name="loanStartDate"
                    min="<?php echo $today; ?>" onchange="updateEndDate()" required> <!--오늘날짜이전 선택불가-->
            </div>
            <div>
                <label class="input" for="loanEndDate">대출 종료일</label>
                <div id="memo">자동으로 적용됩니다.</div>
                <input class="input_text" type="date" id="loanEndDate" name="loanEndDate" readonly required>
            </div>
            <div>
                <label class="input" for="loanPurpose">대출 용도</label>
                <select class="select" id="loanPurpose" name="loanPurpose">
                    <option value="default">선택</option>
                    <option value="homePurchase">주택구입자금</option>
                    <option value="rentalDeposit">주거목적임차자금</option>
                    <option value="livingExpenses">생활자금</option>
                    <option value="debtRepayment">부채상환</option>
                    <option value="relocation">이주비</option>
                    <option value="interimPayment">중도금대출</option>
                    <option value="realEstatePurchase">부동산구입자금</option>
                </select>
            </div>
            <div>
                <label class="input" for="loanRepaymentIncome">대출상환소득</label>
                <select class="select" id="loanRepaymentIncome" name="loanRepaymentIncome">
                    <option value="default">선택</option>
                    <option value="salary">근로소득</option>
                    <option value="businessIncome">사업소득</option>
                    <option value="rentalIncome">임대소득</option>
                    <option value="pension">연금소득</option>
                    <option value="other">기타소득</option>
                </select>
            </div>
            <div>
                <label class="input">
                    <input type="checkbox" id="confirmationCheckbox" onclick="toggleSubmitButton()">
                    위 내용이 사실과 다름이 없음을 확인합니다.
                </label>
            </div>
            <button class="submit_button" type="submit" id="submitButton" disabled>신청하기</button> <!-- disabled 속성 추가 -->
        </form>
    </div>
</body>

</html>