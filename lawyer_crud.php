<?php
require_once 'db.php';
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$response = ['success' => false, 'message' => 'Invalid request to lawyer_crud.php'];

if ($conn->connect_error) {
    $response = ['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error];
    echo json_encode($response);
    exit();
}

// GET
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_all') {
    $sql = "SELECT LawyerID, Name, Firm, Specialization, Email, PhoneNumber FROM Lawyer";
    $result = $conn->query($sql);
    $lawyers = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $lawyers[] = $row;
        }
        $response = ['success' => true, 'message' => 'Lawyers fetched successfully.', 'data' => $lawyers];
        $result->free();
    } else {
        $response = ['success' => false, 'message' => 'Error fetching lawyers: ' . $conn->error];
    }
}

// POST
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    file_put_contents("log.txt", "POST: " . json_encode($_POST) . PHP_EOL, FILE_APPEND);

    $name = trim($_POST['Name'] ?? '');
    $firm = trim($_POST['Firm'] ?? '');
    $specialization = trim($_POST['Specialization'] ?? '');
    $email = trim($_POST['Email'] ?? '');
    // Remove $address as it's not part of the lawyer form
    // $address = trim($_POST['Address'] ?? ''); 
    $phone = trim($_POST['PhoneNumber'] ?? '');

    if ($action === 'add') {
        // Corrected: Removed $address from the required fields check
        if ($name && $firm && $specialization && $email && $phone) { 
            // Corrected: Removed 'Address' column from INSERT statement and 's' from bind_param types
            $stmt = $conn->prepare("INSERT INTO Lawyer (Name, Firm, Specialization, Email, PhoneNumber) VALUES (?, ?, ?, ?, ?)");
            if ($stmt) {
                // Corrected: Removed $address from bind_param
                $stmt->bind_param("sssss", $name, $firm, $specialization, $email, $phone); 
                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Lawyer added successfully!', 'lawyerID' => $conn->insert_id];
                } else {
                    $response = ['success' => false, 'message' => 'Insert error: ' . $stmt->error];
                }
                $stmt->close();
            } else {
                $response = ['success' => false, 'message' => 'Insert prepare failed: ' . $conn->error];
            }
        } else {
            // Corrected: Removed 'Address' from the error message
            $response = ['success' => false, 'message' => 'All fields (Name, Firm, Specialization, Email, PhoneNumber) are required.'];
        }
    } elseif ($action === 'update') {
        $id = intval($_POST['id'] ?? 0);
        // Corrected: Removed $address from the required fields check
        if ($id > 0 && $name && $firm && $specialization && $email && $phone) { 
            // Corrected: Removed 'Address' column from UPDATE statement and 's' from bind_param types
            $stmt = $conn->prepare("UPDATE Lawyer SET Name = ?, Firm = ?, Specialization = ?, Email = ?, PhoneNumber = ? WHERE LawyerID = ?");
            if ($stmt) {
                // Corrected: Removed $address from bind_param
                $stmt->bind_param("sssssi", $name, $firm, $specialization, $email, $phone, $id); 
                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Lawyer updated successfully!'];
                } else {
                    $response = ['success' => false, 'message' => 'Update error: ' . $stmt->error];
                }
                $stmt->close();
            } else {
                $response = ['success' => false, 'message' => 'Update prepare failed: ' . $conn->error];
            }
        } else {
            $response = ['success' => false, 'message' => 'Missing or invalid fields for update.'];
        }
    } else {
        $response['message'] = 'Unknown POST action.';
    }
}

// DELETE
elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $data = json_decode(file_get_contents("php://input"), true);
    file_put_contents("log.txt", "DELETE: " . json_encode($data) . PHP_EOL, FILE_APPEND);
    
    $id = intval($data['id'] ?? 0);
    if ($id > 0) {
        $stmt = $conn->prepare("DELETE FROM Lawyer WHERE LawyerID = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $response = ['success' => true, 'message' => 'Lawyer deleted successfully!'];
            } else {
                $response = ['success' => false, 'message' => 'Delete error: ' . $stmt->error];
            }
            $stmt->close();
        } else {
            $response = ['success' => false, 'message' => 'Delete prepare failed: ' . $conn->error];
        }
    } else {
        $response = ['success' => false, 'message' => 'Invalid ID for deletion.'];
    }
}

echo json_encode($response);
$conn->close();
?>