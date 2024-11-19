<?php
session_start(); // 세션 시작

include "../dbconn.php";
// 사용자 ID는 세션에서 가져온다고 가정합니다.
// // 로그인 성공 후
$user_num = $_SESSION['user_num']; // $user_num은 현재 로그인한 사용자의 ID
$result = $conn->query("SELECT SUM(balance) AS total_assets FROM accounts WHERE user_num = '$user_num'");

// fetch_assoc 대신 fetch(PDO::FETCH_ASSOC) 사용
$row = $result->fetch(PDO::FETCH_ASSOC);
$totalAssets = $row['total_assets'];

// 금리 설정
$interestRates = [
    '신용대출' => [100000000 => 7.0, 300000000 => 5.5, 500000000 => 3.5, 0 => 7.5], // 1억 미만 7.5%
    '담보대출' => [100000000 => 5.0, 300000000 => 4.5, 500000000 => 4.0, 0 => 5.5], // 1억 미만 5.5%
    '자동차대출' => [100000000 => 4.5, 300000000 => 4.2, 500000000 => 3.8, 0 => 4.8], // 1억 미만 4.8%
    '사업자대출' => [100000000 => 7.0, 300000000 => 6.0, 500000000 => 4.5, 0 => 7.5] // 1억 미만 7.5%
];

// 선택한 금리를 저장하는 배열
$selectedRates = [];
foreach ($interestRates as $type => $rates) {
    foreach ($rates as $limit => $rate) {
        if ($totalAssets >= $limit) {
            $selectedRates[$type] = $rate;
        }
    }
}

// 대출 종료 날짜 설정
$loanDuration = 0;
if ($totalAssets >= 500000000) {
    $loanDuration = 7; // 7년
} elseif ($totalAssets >= 300000000) {
    $loanDuration = 5; // 5년
} elseif ($totalAssets >= 100000000) {
    $loanDuration = 4; // 4년
} elseif ($totalAssets < 100000000) {
    $loanDuration = 3; // 3년 (1억원 미만)
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // 대출 신청 정보 수집
    $loan_type = $_POST['loanType']; // 선택한 대출 유형
    $loan_amount = $_POST['loanAmount']; // 대출 금액
    $interest_rate = $_POST['interestRate']; // 금리
    $loan_start_date = $_POST['loanStartDate']; // 대출 시작 날짜
    $loan_end_date = $_POST['loanEndDate']; // 대출 종료 날짜
    $loan_status_id = 1; // 대출 상태 ID 예시 (여기서는 1로 고정)

    // user_num을 고정 값으로 설정
    $user_num = 2; // 고정 값으로 2를 사용

    // 대출 유형 ID 가져오기
    $stmt = $conn->prepare("SELECT id FROM loan_types WHERE type = :loanType");
    $stmt->bindParam(':loanType', $loan_type);
    $stmt->execute();
    $loan_type_id = $stmt->fetchColumn();

    // 대출 유형이 존재하지 않는 경우 처리
    if (!$loan_type_id) {
        die("유효하지 않은 대출 유형입니다."); // 오류 메시지 출력
    }

    try {
        // SQL 쿼리 준비
        $sql = "INSERT INTO loans (user_num, loan_type_id, loan_amount, interest_rate, loan_start_date, loan_end_date, loan_status_id) 
                VALUES (:userNum, :loanType, :loanAmount, :interestRate, :loanStartDate, :loanEndDate, :loanStatusId)";

        // 쿼리 준비
        $stmt = $conn->prepare($sql);

        // 파라미터 바인딩
        $stmt->bindParam(':userNum', $user_num); // 고정된 사용자 ID
        $stmt->bindParam(':loanType', $loan_type_id); // 대출 유형 ID
        $stmt->bindParam(':loanAmount', $loan_amount); // 대출 금액
        $stmt->bindParam(':interestRate', $interest_rate); // 금리
        $stmt->bindParam(':loanStartDate', $loan_start_date); // 대출 시작 날짜
        $stmt->bindParam(':loanEndDate', $loan_end_date); // 대출 종료 날짜
        $stmt->bindParam(':loanStatusId', $loan_status_id); // 대출 상태 ID

        // 쿼리 실행
        $stmt->execute();

        echo "<script>
            alert('대출 신청이 성공적으로 완료되었습니다.');
            window.location.href = 'loan_history.php'; // 대출 조회 페이지로 이동
          </script>";
    } catch (PDOException $e) {
        // 쿼리 실행 실패 시 오류 메시지 출력
        echo "<script>
            alert('대출 신청 중 오류가 발생했습니다: " . addslashes($e->getMessage()) . "');
            window.location.href = 'loan_product.php'; // 대출 상품 페이지로 이동
          </script>";
    }
}

?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>대출 신청</title>
    <link rel="stylesheet" href="../css/back.css">
    <link rel="stylesheet" href="../css/input.css">
    <link rel="stylesheet" href="../css/input_account.css">
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
                        break; // 조건에 맞는 첫 번째 금리만 가져옴
                    }
                }
            }

            // 금리 필드에 값을 설정
            document.getElementById("interestRate").value = interestRate;
        }

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
                include "../dbconn.php";
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
        <form class="form_css" action="" method="POST"> <!-- action=""으로 수정하여 같은 페이지로 POST 요청 -->
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
                <input class="input_text" type="date" id="loanStartDate" name="loanStartDate" onchange="updateEndDate()"
                    required>
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
