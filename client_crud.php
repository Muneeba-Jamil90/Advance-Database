<?php
ob_start(); // Prevent any premature output

require_once 'db.php'; // Include DB connection

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set headers for CORS and JSON
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Default response
$response = ['success' => false, 'message' => 'Invalid request to client_crud.php'];

// Check DB connection
if ($conn->connect_error) {
    $response = ['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error];
    ob_end_clean();
    echo json_encode($response);
    exit();
}

// GET: Fetch all clients
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'get_all') {
    $sql = "SELECT ClientID, Name, Email, Phone, Address FROM Client";
    $result = $conn->query($sql);
    $clients = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $clients[] = $row;
        }
        $response = ['success' => true, 'message' => 'Clients fetched successfully.', 'data' => $clients];
        $result->free();
    } else {
        error_log("Client GET Error: " . $conn->error);
        $response = ['success' => false, 'message' => 'Error fetching clients: ' . $conn->error];
    }
}

// POST: Add or update client
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $name = $conn->real_escape_string(trim($_POST['Name'] ?? ''));
    $email = $conn->real_escape_string(trim($_POST['Email'] ?? ''));
    $phone = $conn->real_escape_string(trim($_POST['Phone'] ?? ''));
    $address = $conn->real_escape_string(trim($_POST['Address'] ?? ''));

    if (empty($name) || empty($email) || empty($phone) || empty($address)) {
        $response = ['success' => false, 'message' => 'All fields are required.'];
    } elseif ($action === 'add') {
        $sql = "INSERT INTO Client (Name, Email, Phone, Address) VALUES ('$name', '$email', '$phone', '$address')";
        if ($conn->query($sql) === TRUE) {
            $response = ['success' => true, 'message' => 'Client added successfully!', 'clientID' => $conn->insert_id];
        } else {
            error_log("Client ADD Error: " . $conn->error);
            $response = ['success' => false, 'message' => 'Error adding client: ' . $conn->error];
        }
    } elseif ($action === 'update') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            $response = ['success' => false, 'message' => 'Invalid Client ID.'];
        } else {
            $sql = "UPDATE Client SET Name = '$name', Email = '$email', Phone = '$phone', Address = '$address' WHERE ClientID = $id";
            if ($conn->query($sql) === TRUE) {
                $response = ['success' => true, 'message' => 'Client updated successfully!'];
            } else {
                error_log("Client UPDATE Error: " . $conn->error);
                $response = ['success' => false, 'message' => 'Error updating client: ' . $conn->error];
            }
        }
    } else {
        $response['message'] = 'Unknown POST action.';
    }
}

// DELETE: Delete client
elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $data = json_decode(file_get_contents("php://input"), true);
    $id = intval($data['id'] ?? 0);

    if ($id <= 0) {
        $response = ['success' => false, 'message' => 'Invalid Client ID for deletion.'];
    } else {
        $sql = "DELETE FROM Client WHERE ClientID = $id";
        if ($conn->query($sql) === TRUE) {
            if ($conn->affected_rows > 0) {
                $response = ['success' => true, 'message' => 'Client deleted successfully!'];
            } else {
                $response = ['success' => false, 'message' => 'Client not found.'];
            }
        } else {
            error_log("Client DELETE Error: " . $conn->error);
            $response = ['success' => false, 'message' => 'Error deleting client: ' . $conn->error];
        }
    }
}

// Final JSON output
ob_end_clean();
echo json_encode($response);
$conn->close();
exit();
