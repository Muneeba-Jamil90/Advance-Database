<?php
ob_start(); // Prevent premature output

// Error logging setup
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/case_crud_errors.log');
error_reporting(E_ALL);

require_once 'db.php'; // Make sure this file defines $conn (MySQLi connection)

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$response = ['success' => false, 'message' => 'Invalid request to cases_crud.php'];

// Check DB connection
if (!$conn || $conn->connect_error) {
    $response = ['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error];
    echo json_encode($response);
    exit();
}

// GET: Fetch all cases
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'get_all') {
    $sql = "SELECT CaseID, CaseType, Status, FilingDate, ClientID, LawyerID FROM CaseInfo";
    $result = $conn->query($sql);
    $cases = [];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $cases[] = $row;
        }
        $response = ['success' => true, 'message' => 'Cases fetched.', 'data' => $cases];
    } else {
        $response = ['success' => true, 'message' => 'No cases found.', 'data' => []];
    }
}

// POST: Add or update a case
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $caseType = trim($_POST['CaseType'] ?? '');
    $status = trim($_POST['Status'] ?? '');
    $filingDate = trim($_POST['FilingDate'] ?? date('Y-m-d'));
    $clientId = isset($_POST['ClientID']) && $_POST['ClientID'] !== '' ? intval($_POST['ClientID']) : null;
    $lawyerId = isset($_POST['LawyerID']) && $_POST['LawyerID'] !== '' ? intval($_POST['LawyerID']) : null;

    if ($action === 'add') {
        $stmt = $conn->prepare("INSERT INTO CaseInfo (CaseType, Status, FilingDate, ClientID, LawyerID) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sssii", $caseType, $status, $filingDate, $clientId, $lawyerId);
            if ($stmt->execute()) {
                $response = ['success' => true, 'message' => 'Case added successfully!', 'caseID' => $conn->insert_id];
            } else {
                $response = ['success' => false, 'message' => 'Error adding case: ' . $stmt->error];
            }
            $stmt->close();
        } else {
            $response = ['success' => false, 'message' => 'Prepare failed: ' . $conn->error];
        }

    } elseif ($action === 'update') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            $response = ['success' => false, 'message' => 'Invalid Case ID for update.'];
        } else {
            $stmt = $conn->prepare("UPDATE CaseInfo SET CaseType = ?, Status = ?, FilingDate = ?, ClientID = ?, LawyerID = ? WHERE CaseID = ?");
            if ($stmt) {
                $stmt->bind_param("sssiii", $caseType, $status, $filingDate, $clientId, $lawyerId, $id);
                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Case updated successfully!'];
                } else {
                    $response = ['success' => false, 'message' => 'Error updating case: ' . $stmt->error];
                }
                $stmt->close();
            } else {
                $response = ['success' => false, 'message' => 'Prepare failed: ' . $conn->error];
            }
        }
    } else {
        $response['message'] = 'Unknown POST action.';
    }
}

// DELETE: Delete a case
elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $data = json_decode(file_get_contents("php://input"), true);
    $id = intval($data['id'] ?? 0);

    if ($id > 0) {
        $stmt = $conn->prepare("DELETE FROM CaseInfo WHERE CaseID = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $response = ['success' => true, 'message' => 'Case deleted successfully!'];
            } else {
                $response = ['success' => false, 'message' => 'Error deleting case: ' . $stmt->error];
            }
            $stmt->close();
        } else {
            $response = ['success' => false, 'message' => 'Prepare failed: ' . $conn->error];
        }
    } else {
        $response = ['success' => false, 'message' => 'Invalid Case ID for deletion.'];
    }
}

// Output final response
ob_end_clean();
echo json_encode($response);
$conn->close();
exit();
