<?php
session_start();
include "../dbconn.php";

// 보안 헤더 설정
header("X-Frame-Options: DENY");
header("Content-Security-Policy: default-src 'self'");
header("X-Content-Type-Options: nosniff");
header('Content-Type: application/json; charset=utf-8');

//세션 유호셩 검사
if (!isset($_SESSION['user_num']) || !is_numeric($_SESSION['user_num'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit();
}

//ip검증
function sendErrorResponse($message)
{
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => $message]);
    exit();
}
if (!isset($_SESSION['ip'])) {
    // 세션에 user_ip가 없을 때
    sendErrorResponse('비정상적인 접근입니다.');
} else if ($_SESSION['ip'] !== $_SERVER['REMOTE_ADDR']) {
    // 세션의 user_ip와 현재 클라이언트 IP가 다를 때
    sendErrorResponse('비정상적인 접근입니다.');
}

//GET 요청 처리 - 초기 데이터 로드
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $user_num = $_SESSION['user_num'];
        $stmt = $conn->prepare("SELECT SUM(balance) AS total_assets FROM accounts WHERE user_num = :user_num");
        $stmt->bindParam(':user_num', $user_num, PDO::PARAM_INT); // SQL 인젝션 방지 
        $stmt->execute(); // 쿼리를 실행
        $row = $stmt->fetch(PDO::FETCH_ASSOC); // 실행 결과 가져오기

        $interestRates = [
            '신용대출' => [100000000 => 7.0, 300000000 => 5.5, 500000000 => 3.5, 0 => 7.5],
            '담보대출' => [100000000 => 5.0, 300000000 => 4.5, 500000000 => 4.0, 0 => 5.5],
            '자동차대출' => [100000000 => 4.5, 300000000 => 4.2, 500000000 => 3.8, 0 => 4.8],
            '사업자대출' => [100000000 => 7.0, 300000000 => 6.0, 500000000 => 4.5, 0 => 7.5]
        ];
        $loanDuration = 0;
        if ($totalAssets >= 500000000)
            $loanDuration = 7;
        elseif ($totalAssets >= 300000000)
            $loanDuration = 5;
        elseif ($totalAssets >= 100000000)
            $loanDuration = 4;
        else
            $loanDuration = 3;

        echo json_encode([
            'success' => true,
            'data' => [
                'totalAssets' => $row['total_assets'],
                'interestRates' => $interestRates,
                'loanDuration' => $loanDuration
            ]
        ]);
        exit();
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => '데이터 조회 중 오류가 발생했습니다.']);
        exit();
    }
}

// POST 요청 처리 - 대출 신청
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'CSRF 검증에 실패했습니다.']);
        exit();
    }
    //서버 측 날짜 검증
    $loan_start_date = $_POST['loanStartDate'];
    $today = date('Y-m-d');
    if ($loan_start_date < $today) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '대출 시작일은 오늘 이후의 날짜여야 합니다.']);
        exit();
    }
    try {
        $user_num = $_SESSION['user_num'];
        $loan_type = $_POST['loanType'];
        $loan_amount = $_POST['loanAmount'];
        $interest_rate = $_POST['interestRate'];
        $loan_start_date = $_POST['loanStartDate'];
        $loan_end_date = $_POST['loanEndDate'];
        $loan_status_id = 1;

        // 대출 유형 ID 조회
        $stmt = $conn->prepare("SELECT id FROM loan_types WHERE type = :loanType");
        $stmt->bindParam(':loanType', $loan_type);
        $stmt->execute();
        $loan_type_id = $stmt->fetchColumn();

        if (!$loan_type_id) {
            throw new Exception("유효하지 않은 대출 유형입니다.");
        }

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

        echo json_encode([
            'success' => true,
            'message' => '대출 신청이 성공적으로 완료되었습니다.',
            'loan_id' => $conn->lastInsertId()
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => '대출 신청 중 오류가 발생했습니다.: ' . $e->getMessage()
        ]);
    }
    exit();
}
?>