<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Invalid request to evidence_crud.php'];

// Handle GET request: Fetch all evidence
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'get_all') {
    $sql = "SELECT EvidenceID, CaseID, EvidenceType, Description FROM Evidence";
    $result = $conn->query($sql);
    $evidence = [];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $evidence[] = $row;
        }
        $response = ['success' => true, 'message' => 'Evidence fetched.', 'data' => $evidence];
    } else {
        $response = ['success' => true, 'message' => 'No evidence found.', 'data' => []];
    }
}

// Handle POST request: Add or update evidence
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $caseId = intval($_POST['CaseID'] ?? 0);
        $evidenceType = trim($_POST['EvidenceType'] ?? '');
        $description = trim($_POST['Description'] ?? '');

        $stmt = $conn->prepare("INSERT INTO Evidence (CaseID, EvidenceType, Description) VALUES (?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("iss", $caseId, $evidenceType, $description);
            if ($stmt->execute()) {
                $response = ['success' => true, 'message' => 'Evidence added successfully!', 'evidenceID' => $conn->insert_id];
            } else {
                $response = ['success' => false, 'message' => 'Error adding evidence: ' . $stmt->error];
            }
            $stmt->close();
        } else {
            $response = ['success' => false, 'message' => 'Prepare failed: ' . $conn->error];
        }

    } elseif ($action === 'update') {
        $id = intval($_POST['id'] ?? 0);
        $caseId = intval($_POST['CaseID'] ?? 0);
        $evidenceType = trim($_POST['EvidenceType'] ?? '');
        $description = trim($_POST['Description'] ?? '');

        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE Evidence SET CaseID = ?, EvidenceType = ?, Description = ? WHERE EvidenceID = ?");
            if ($stmt) {
                $stmt->bind_param("issi", $caseId, $evidenceType, $description, $id);
                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Evidence updated successfully!'];
                } else {
                    $response = ['success' => false, 'message' => 'Error updating evidence: ' . $stmt->error];
                }
                $stmt->close();
            } else {
                $response = ['success' => false, 'message' => 'Prepare failed: ' . $conn->error];
            }
        } else {
            $response = ['success' => false, 'message' => 'Invalid Evidence ID for update.'];
        }

    } else {
        $response = ['success' => false, 'message' => 'Unknown POST action.'];
    }
}

// Handle DELETE request: Delete evidence
elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $data = json_decode(file_get_contents("php://input"), true);
    $id = intval($data['id'] ?? 0);

    if ($id > 0) {
        $stmt = $conn->prepare("DELETE FROM Evidence WHERE EvidenceID = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $response = ['success' => true, 'message' => 'Evidence deleted successfully!'];
            } else {
                $response = ['success' => false, 'message' => 'Error deleting evidence: ' . $stmt->error];
            }
            $stmt->close();
        } else {
            $response = ['success' => false, 'message' => 'Prepare failed: ' . $conn->error];
        }
    } else {
        $response = ['success' => false, 'message' => 'Invalid Evidence ID for deletion.'];
    }
} else {
    $response = ['success' => false, 'message' => 'Unsupported request method or missing parameters.'];
}

echo json_encode($response);
$conn->close();
