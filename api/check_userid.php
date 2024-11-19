<?php
include "../dbconn.php";

header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
$userid = $data['userid'];

$sql = "SELECT * FROM users WHERE userid='$userid'";
$stmt = $conn->prepare($sql);
$stmt->execute();

$response = array();
$response['exists'] = ($stmt->rowCount() > 0);

echo json_encode($response);

$conn = null;
exit();
?>
