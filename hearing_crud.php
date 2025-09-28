<?php
require_once 'db.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Invalid request to hearing_crud.php'];

// Handle GET requests for fetching all hearings
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_all') {
    $sql = "SELECT HearingID, CaseID, HearingDate, CourtLocation, HearingTime FROM courtHearing";
    $result = $conn->query($sql);
    $hearings = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $hearings[] = $row;
        }
        $response = ['success' => true, 'message' => 'Hearings fetched.', 'data' => $hearings];
    } else {
        $response = ['success' => true, 'message' => 'No hearings found.', 'data' => []];
    }
}
// Handle POST requests for adding or updating hearings
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $caseId = intval($_POST['CaseID'] ?? 0);
        $courtLocation = $conn->real_escape_string($_POST['CourtLocation'] ?? '');
        $hearingDate = $conn->real_escape_string($_POST['HearingDate'] ?? '');
        $hearingTime = $conn->real_escape_string($_POST['HearingTime'] ?? '');

        $sql = "INSERT INTO courtHearing (CaseID, CourtLocation, HearingDate, HearingTime) VALUES ($caseId, '$courtLocation', '$hearingDate', '$hearingTime')";
        if ($conn->query($sql) === TRUE) {
            $response = ['success' => true, 'message' => 'Hearing added successfully!'];
        } else {
            $response = ['success' => false, 'message' => 'Error adding hearing: ' . $conn->error];
        }
    } elseif ($action === 'update') {
        $id = intval($_POST['id'] ?? 0);
        $case_id = intval($_POST['CaseID'] ?? 0);
        $hearing_date = $conn->real_escape_string($_POST['HearingDate'] ?? '');
        $court_location = $conn->real_escape_string($_POST['CourtLocation'] ?? '');
        $hearing_time = $conn->real_escape_string($_POST['HearingTime'] ?? '');

        if ($id > 0) {
            $sql = "UPDATE courthearing SET CaseID = $case_id, HearingDate = '$hearing_date', CourtLocation = '$court_location', HearingTime = '$hearing_time' WHERE HearingID = $id";
            if ($conn->query($sql) === TRUE) {
                $response = ['success' => true, 'message' => 'Hearing updated successfully!'];
            } else {
                $response = ['success' => false, 'message' => 'Error updating hearing: ' . $conn->error];
            }
        } else {
            $response = ['success' => false, 'message' => 'Invalid Hearing ID for update.'];
        }
    } else {
        $response['message'] = 'Unknown POST action.';
    }
}
// Handle DELETE requests for deleting hearings
elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $data = json_decode(file_get_contents("php://input"), true);
    $id = intval($data['id'] ?? 0);

    if ($id > 0) {
        $sql = "DELETE FROM courthearing WHERE HearingID = $id";
        if ($conn->query($sql) === TRUE) {
            $response = ['success' => true, 'message' => 'Hearing deleted successfully!'];
        } else {
            $response = ['success' => false, 'message' => 'Error deleting hearing: ' . $conn->error];
        }
    } else {
        $response = ['success' => false, 'message' => 'Invalid Hearing ID for deletion.'];
    }
}

echo json_encode($response);
$conn->close();
?>