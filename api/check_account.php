<?php
include "../dbconn.php";
header('Content-Type: application/json');

if (isset($_GET['account_number'])) {
    $account_number = $_GET['account_number'];
    try {
        $sql = "SELECT balance FROM accounts WHERE account_number = :account_number";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":account_number", $account_number);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            echo json_encode(['balance' => $result['balance']]);
        } else {
            echo json_encode(['balance' => 0]);
        }
        $conn = null;
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => '계좌번호가 전달되지 않았습니다.']);
}

exit();
?>