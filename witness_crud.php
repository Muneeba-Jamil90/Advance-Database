<?php
require_once 'db.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Invalid request to witness_crud.php'];

// Handle GET requests for fetching all witnesses
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_all') {
    $sql = "SELECT WitnessID, CaseID, Name, Contact, EvidenceID FROM Witness";
    $result = $conn->query($sql);
    $witnesses = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $witnesses[] = $row;
        }
        $response = ['success' => true, 'message' => 'Witnesses fetched.', 'data' => $witnesses];
    } else {
        $response = ['success' => true, 'message' => 'No witnesses found.', 'data' => []];
    }
}
// Handle POST requests for adding or updating witnesses
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = $conn->real_escape_string($_POST['Name'] ?? '');
        $contact = $conn->real_escape_string($_POST['Contact'] ?? '');
        $caseId = intval($_POST['CaseID'] ?? 0);
        $evidenceId = !empty($_POST['EvidenceID']) ? intval($_POST['EvidenceID']) : 'NULL'; // Optional EvidenceID

        $sql = "INSERT INTO Witness (CaseID, Name, Contact, EvidenceID) VALUES ($caseId, '$name', '$contact', $evidenceId)";
        if ($conn->query($sql) === TRUE) {
            $response = ['success' => true, 'message' => 'Witness added successfully!'];
        } else {
            $response = ['success' => false, 'message' => 'Error adding witness: ' . $conn->error];
        }
    } elseif ($action === 'update') {
        $id = intval($_POST['id'] ?? 0);
        $name = $conn->real_escape_string($_POST['Name'] ?? '');
        $contact = $conn->real_escape_string($_POST['Contact'] ?? '');
        $case_id = intval($_POST['CaseID'] ?? 0);
        $evidence_id = !empty($_POST['EvidenceID']) ? intval($_POST['EvidenceID']) : 'NULL';

        if ($id > 0) {
            $sql = "UPDATE Witness SET Name = '$name', Contact = '$contact', CaseID = $case_id, EvidenceID = $evidence_id WHERE WitnessID = $id";
            if ($conn->query($sql) === TRUE) {
                $response = ['success' => true, 'message' => 'Witness updated successfully!'];
            } else {
                $response = ['success' => false, 'message' => 'Error updating witness: ' . $conn->error];
            }
        } else {
            $response = ['success' => false, 'message' => 'Invalid Witness ID for update.'];
        }
    } else {
        $response['message'] = 'Unknown POST action.';
    }
}
// Handle DELETE requests for deleting witnesses
elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $data = json_decode(file_get_contents("php://input"), true);
    $id = intval($data['id'] ?? 0);

    if ($id > 0) {
        $sql = "DELETE FROM Witness WHERE WitnessID = $id";
        if ($conn->query($sql) === TRUE) {
            $response = ['success' => true, 'message' => 'Witness deleted successfully!'];
        } else {
            $response = ['success' => false, 'message' => 'Error deleting witness: ' . $conn->error];
        }
    } else {
        $response = ['success' => false, 'message' => 'Invalid Witness ID for deletion.'];
    }
}

echo json_encode($response);
$conn->close();
?>