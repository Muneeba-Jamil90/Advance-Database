<?php
require_once 'db.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Error fetching case types.', 'data' => []];

$sql = "SELECT DISTINCT CaseType FROM CaseInfo ORDER BY CaseType ASC";
$result = $conn->query($sql);
$case_types = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $case_types[] = $row;
    }
    $response = ['success' => true, 'message' => 'Distinct case types fetched.', 'data' => $case_types];
} else {
    $response = ['success' => true, 'message' => 'No distinct case types found.', 'data' => []];
}

echo json_encode($response);
$conn->close();
?>