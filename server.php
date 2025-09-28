<?php
require_once 'db.php'; // Your database connection file
header('Content-Type: application/json');
error_reporting(E_ALL); // Display all errors during development
ini_set('display_errors', 1);

$response = ['success' => false, 'message' => 'Invalid action.', 'data' => []];

// Function to sanitize input (important for security)
function sanitize_input($conn, $data) {
    if (is_string($data)) {
        return $conn->real_escape_string(trim($data));
    }
    return $data;
}

// Check for 'action' in GET, or 'insightType' in POST/GET
// Standardize to always check GET for action
if (isset($_GET['action'])) {
    $action = $_GET['action'];
} else {
    // No valid action provided
    echo json_encode($response);
    exit();
}

switch ($action) {
    case 'get_dashboard_counts':
        $counts = [];

        // Total Clients
        $sql_clients = "SELECT COUNT(*) AS totalClients FROM Client";
        $result_clients = $conn->query($sql_clients);
        $counts['clients'] = $result_clients ? $result_clients->fetch_assoc()['totalClients'] : 0;

        // Total Lawyers
        $sql_lawyers = "SELECT COUNT(*) AS totalLawyers FROM Lawyer";
        $result_lawyers = $conn->query($sql_lawyers);
        $counts['totalLawyers'] = $result_lawyers ? $result_lawyers->fetch_assoc()['totalLawyers'] : 0;

        // Active Cases (Pending or Ongoing)
        $sql_active_cases = "SELECT COUNT(*) AS activeCases FROM CaseInfo WHERE Status IN ('Pending', 'Ongoing')";
        $result_active_cases = $conn->query($sql_active_cases);
        $counts['activeCases'] = $result_active_cases ? $result_active_cases->fetch_assoc()['activeCases'] : 0;

        // Cases Won Percentage (assuming 'Closed' means won, or you can add a 'Result' field)
        $sql_closed_cases = "SELECT COUNT(*) AS closedCases FROM CaseInfo WHERE Status = 'Closed'";
        $result_closed_cases = $conn->query($sql_closed_cases);
        $closedCases = $result_closed_cases ? $result_closed_cases->fetch_assoc()['closedCases'] : 0;

        $sql_total_cases = "SELECT COUNT(*) AS totalCases FROM CaseInfo";
        $result_total_cases = $conn->query($sql_total_cases);
        $totalCases = $result_total_cases ? $result_total_cases->fetch_assoc()['totalCases'] : 0;

        $counts['casesWonPercentage'] = $totalCases > 0 ? round(($closedCases / $totalCases) * 100, 1) : 0;

        $response = ['success' => true, 'message' => 'Dashboard counts fetched.', 'data' => $counts];
        break;

    case 'search_cases':
        $case_no = sanitize_input($conn, $_GET['case_no'] ?? '');
        $status = sanitize_input($conn, $_GET['status'] ?? '');
        $case_type = sanitize_input($conn, $_GET['case_type'] ?? '');

        $sql = "SELECT ci.*, c.Name AS ClientName, l.Name AS LawyerName
                FROM CaseInfo ci
                LEFT JOIN Client c ON ci.ClientID = c.ClientID
                LEFT JOIN Lawyer l ON ci.LawyerID = l.LawyerID
                WHERE 1=1";
        if ($case_no) {
            $sql .= " AND ci.CaseID = '$case_no'";
        }
        if ($status && $status !== 'All') {
            $sql .= " AND ci.Status = '$status'";
        }
        if ($case_type && $case_type !== 'All') {
            $sql .= " AND ci.CaseType = '$case_type'";
        }

        $result = $conn->query($sql);
        $cases = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                foreach ($row as $key => $value) {
                    $row[$key] = $value === null ? 'Null' : $value;
                }
                $cases[] = $row;
            }
            $response = ['success' => true, 'message' => 'Cases found.', 'data' => $cases];
        } else {
            $response = ['success' => false, 'message' => 'Error executing search cases query: ' . $conn->error, 'data' => []];
        }
        break;

    case 'get_detailed_case_info':
        $sql = "SELECT
                    ci.CaseID,
                    ci.CaseType,
                    cl.Name AS ClientName,
                    l.Name AS LawyerName
                FROM
                    CaseInfo AS ci
                INNER JOIN
                    Client AS cl ON ci.ClientID = cl.ClientID
                INNER JOIN
                    Lawyer AS l ON ci.LawyerID = l.LawyerID";

        $result = $conn->query($sql);
        $cases_with_names = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $cases_with_names[] = $row;
            }
            $response = ['success' => true, 'message' => 'Detailed case information with client and lawyer names fetched.', 'data' => $cases_with_names];
        } else {
            $response = ['success' => false, 'message' => 'Error fetching detailed case information: ' . $conn->error, 'data' => []];
        }
        break;

    case 'search_hearings_by_court':
        $courtFilter = sanitize_input($conn, $_GET['court_filter'] ?? '');
        $hearingDate = sanitize_input($conn, $_GET['hearing_date'] ?? '');


        $sql = "SELECT
            h.HearingID,
            h.CaseID,
            h.CourtLocation,
            h.HearingDate AS HearingDate,
            h.HearingTime AS HearingTime,
            c.CaseType,
            c.Status,
            c.CaseType AS CaseName
        FROM CourtHearing h
        JOIN CaseInfo c ON h.CaseID = c.CaseID
        WHERE 1=1";

        if (!empty($courtFilter) && $courtFilter !== 'All') {
            $sql .= " AND h.CourtLocation LIKE '%" . $courtFilter . "%'";
        }
        if (!empty($hearingDate)) {
            $sql .= " AND h.HearingDate = '" . $hearingDate . "'";
        }

        $result = $conn->query($sql);
        $hearings = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                foreach ($row as $key => $value) {
                    $row[$key] = $value === null ? 'Null' : $value;
                }
                $hearings[] = $row;
            }
            $response = ['success' => true, 'message' => 'Hearings found.', 'data' => $hearings];
        } else {
            $response = ['success' => false, 'message' => 'Error executing search hearings query: ' . $conn->error, 'data' => []];
        }
        break;

    case 'search_witnesses_and_evidence':
        $witnessName = sanitize_input($conn, $_GET['witness_name'] ?? '');
        $evidenceType = sanitize_input($conn, $_GET['evidence_type'] ?? '');

        // Comprehensive query to get all related witness, evidence, case, client, and lawyer info
        $sql = "SELECT
                    ci.CaseID,
                    ci.CaseType,
                    ci.Status AS CaseStatus,
                    cl.Name AS ClientName,
                    l.Name AS LawyerName,
                    GROUP_CONCAT(DISTINCT w.Name ORDER BY w.Name SEPARATOR '; ') AS WitnessesNames,
                    GROUP_CONCAT(DISTINCT w.Contact ORDER BY w.Name SEPARATOR '; ') AS WitnessesContacts,
                    -- Assuming 'Testimony' field exists in Witness table
                    GROUP_CONCAT(DISTINCT w.Testimony ORDER BY w.Name SEPARATOR ' || ') AS WitnessesTestimonies,
                    GROUP_CONCAT(DISTINCT e.EvidenceType ORDER BY e.EvidenceType SEPARATOR '; ') AS EvidenceTypes,
                    GROUP_CONCAT(DISTINCT e.Description ORDER BY e.Description SEPARATOR ' || ') AS EvidenceDescriptions
                FROM
                    CaseInfo AS ci
                LEFT JOIN
                    Client AS cl ON ci.ClientID = cl.ClientID
                LEFT JOIN
                    Lawyer AS l ON ci.LawyerID = l.LawyerID
                LEFT JOIN
                    Witness AS w ON ci.CaseID = w.CaseID
                LEFT JOIN
                    Evidence AS e ON ci.CaseID = e.CaseID
                WHERE 1=1";

        $conditions = [];

        if (!empty($witnessName)) {
            // Subquery to find CaseIDs that have a witness matching the name
            $conditions[] = "ci.CaseID IN (SELECT DISTINCT CaseID FROM Witness WHERE Name LIKE '%" . $witnessName . "%')";
        }
        if (!empty($evidenceType) && $evidenceType !== 'All') {
            // Subquery to find CaseIDs that have evidence matching the type
            $conditions[] = "ci.CaseID IN (SELECT DISTINCT CaseID FROM Evidence WHERE EvidenceType LIKE '%" . $evidenceType . "%')";
        }

        if (empty($conditions)) {
            // No filters provided, return a message and empty data
            $response = ['success' => true, 'message' => 'Please provide search criteria for witnesses or evidence.', 'data' => []];
            echo json_encode($response);
            exit();
        } else if (count($conditions) === 2) {
            // Both witness name and evidence type are provided, combine conditions with AND
            $sql .= " AND (" . implode(" AND ", $conditions) . ")";
        } else {
            // Only one of witness name or evidence type is provided, use that single condition
            $sql .= " AND " . $conditions[0];
        }

        $sql .= " GROUP BY ci.CaseID"; // Group by case to aggregate witness/evidence info per case
        $sql .= " ORDER BY ci.CaseID"; // Order for consistent results

        $result = $conn->query($sql);
        $data = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                foreach ($row as $key => $value) {
                    $row[$key] = $value === null ? 'Null' : $value;
                }
                $data[] = $row;
            }
            $response = ['success' => true, 'message' => 'Witness and Evidence search results fetched.', 'data' => $data];
        } else {
            $response = ['success' => false, 'message' => 'Error executing witness/evidence search query: ' . $conn->error, 'data' => []];
        }
        break;

    case 'get_distinct_evidence_types':
        $sql = "SELECT DISTINCT EvidenceType FROM Evidence ORDER BY EvidenceType";
        $result = $conn->query($sql);
        $evidenceTypes = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $evidenceTypes[] = $row;
            }
            $response = ['success' => true, 'message' => 'Distinct evidence types fetched.', 'data' => $evidenceTypes];
        } else {
            $response = ['success' => false, 'message' => 'Error fetching distinct evidence types: ' . $conn->error, 'data' => []];
        }
        break;

    case 'get_distinct_firms':
        $sql = "SELECT DISTINCT Firm FROM Lawyer WHERE Firm IS NOT NULL AND Firm != '' ORDER BY Firm";
        $result = $conn->query($sql);
        $firms = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $firms[] = $row;
            }
            $response = ['success' => true, 'message' => 'Distinct firms fetched.', 'data' => $firms];
        } else {
            $response = ['success' => false, 'message' => 'Error fetching distinct firms: ' . $conn->error, 'data' => []];
        }
        break;

    case 'get_distinct_specializations':
        $sql = "SELECT DISTINCT Specialization FROM Lawyer WHERE Specialization IS NOT NULL AND Specialization != '' ORDER BY Specialization";
        $result = $conn->query($sql);
        $specializations = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {

                $specializations[] = $row;
            }
            $response = ['success' => true, 'message' => 'Distinct specializations fetched.', 'data' => $specializations];
        } else {
            $response = ['success' => false, 'message' => 'Error fetching distinct specializations: ' . $conn->error, 'data' => []];
        }
        break;

    case 'get_distinct_client_addresses':
        $sql = "SELECT DISTINCT Address FROM Client WHERE Address IS NOT NULL AND Address != '' ORDER BY Address";
        $result = $conn->query($sql);
        $addresses = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $addresses[] = $row;
            }
            $response = ['success' => true, 'message' => 'Distinct client addresses fetched.', 'data' => $addresses];
        } else {
            $response = ['success' => false, 'message' => 'Error fetching distinct client addresses: ' . $conn->error, 'data' => []];
        }
        break;

    case 'searchLawyers':
        $lawyer_name = sanitize_input($conn, $_GET['lawyer_name'] ?? '');
        $lawyer_firm = sanitize_input($conn, $_GET['lawyer_firm'] ?? '');
        $lawyer_specialization = sanitize_input($conn, $_GET['lawyer_specialization'] ?? '');

        $sql = "SELECT
                    l.LawyerID,
                    l.Name AS LawyerName,
                    l.Firm,
                    l.Specialization,
                    l.Email AS LawyerEmail,
                    l.PhoneNumber,
                    GROUP_CONCAT(DISTINCT ci.CaseType SEPARATOR ', ') AS HandledCaseTypes,
                    COUNT(DISTINCT ci.CaseID) AS TotalCasesHandled,
                    GROUP_CONCAT(DISTINCT CONCAT(cl.Name, ' (CaseID: ', ci.CaseID, ')') SEPARATOR '; ') AS AssociatedClientsAndCases
                FROM
                    Lawyer AS l
                LEFT JOIN
                    CaseInfo AS ci ON l.LawyerID = ci.LawyerID
                LEFT JOIN
                    Client AS cl ON ci.ClientID = cl.ClientID
                WHERE 1=1";

        if (!empty($lawyer_name)) {
            $sql .= " AND l.Name LIKE '%" . $lawyer_name . "%'";
        }
        // Add conditions for dropdowns, handling 'All' or empty string as no filter
        if (!empty($lawyer_firm) && $lawyer_firm !== 'All') {
            $sql .= " AND l.Firm = '" . $lawyer_firm . "'";
        }
        if (!empty($lawyer_specialization) && $lawyer_specialization !== 'All') {
            $sql .= " AND l.Specialization = '" . $lawyer_specialization . "'";
        }

        $sql .= " GROUP BY l.LawyerID";
        $sql .= " ORDER BY l.Name"; // Add an order for consistent results

        $result = $conn->query($sql);
        $data = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                foreach ($row as $key => $value) {
                    $row[$key] = $value === null ? 'Null' : $value;
                }
                $data[] = $row;
            }
            $response = ['success' => true, 'message' => 'Lawyer search results fetched.', 'data' => $data];
        } else {
            $response = ['success' => false, 'message' => 'Error fetching lawyer information: ' . $conn->error, 'data' => []];
        }
        break;

    case 'searchClients':
        $client_name = sanitize_input($conn, $_GET['client_name'] ?? '');
        $client_address = sanitize_input($conn, $_GET['client_address'] ?? '');

        $sql = "SELECT
                    cl.ClientID,
                    cl.Name AS ClientName,
                    cl.Email AS ClientEmail,
                    cl.Phone AS ClientPhone,
                    cl.Address AS ClientAddress,
                    GROUP_CONCAT(DISTINCT ci.CaseType SEPARATOR ', ') AS AssociatedCaseTypes,
                    COUNT(DISTINCT ci.CaseID) AS TotalAssociatedCases,
                    GROUP_CONCAT(DISTINCT CONCAT(l.Name, ' (CaseID: ', ci.CaseID, ')') SEPARATOR '; ') AS AssociatedLawyersAndCases
                FROM
                    Client AS cl
                LEFT JOIN
                    CaseInfo AS ci ON cl.ClientID = ci.ClientID
                LEFT JOIN
                    Lawyer AS l ON ci.LawyerID = l.LawyerID
                WHERE 1=1";

        if (!empty($client_name)) {
            $sql .= " AND cl.Name LIKE '%" . $client_name . "%'";
        }
        // Corrected condition for client_address to use LIKE for partial matching
        if (!empty($client_address) && $client_address !== 'All') {
            $sql .= " AND cl.Address LIKE '%" . $client_address . "%'"; // Changed from '=' to 'LIKE' with wildcards
        }

        $sql .= " GROUP BY cl.ClientID";
        $sql .= " ORDER BY cl.Name"; // Add an order for consistent results

        $result = $conn->query($sql);
        $data = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                foreach ($row as $key => $value) {
                    $row[$key] = $value === null ? 'Null' : $value;
                }
                $data[] = $row;
            }
            $response = ['success' => true, 'message' => 'Client search results fetched.', 'data' => $data];
        } else {
            $response = ['success' => false, 'message' => 'Error fetching client information: ' . $conn->error, 'data' => []];
        }
        break;

    // --- DIRECT INSIGHT CASES ---
    case 'top-lawyers':
        $query = 'SELECT
                        l.LawyerID,
                        l.Name AS LawyerName,
                        l.Firm,
                        l.Specialization,
                        l.Email,
                        l.PhoneNumber,
                        COUNT(DISTINCT CASE WHEN ci.Status = "Closed" THEN ci.CaseID END) AS TotalClosedCases,
                        COUNT(DISTINCT CASE WHEN ci.Status IN ("Pending", "Ongoing") THEN ci.CaseID END) AS TotalCurrentCases,
                        COUNT(DISTINCT ci.CaseID) AS TotalCasesHandled,
                        GROUP_CONCAT(DISTINCT ch.CourtLocation ORDER BY ch.CourtLocation SEPARATOR ", ") AS CourtLocations
                    FROM
                        Lawyer AS l
                    LEFT JOIN
                        CaseInfo AS ci ON l.LawyerID = ci.LawyerID
                    LEFT JOIN
                        CourtHearing AS ch ON ci.CaseID = ch.CaseID
                    GROUP BY
                        l.LawyerID, l.Name, l.Firm, l.Specialization, l.Email, l.PhoneNumber
                    ORDER BY
                        TotalCurrentCases DESC,TotalClosedCases DESC, LawyerName ASC
                    LIMIT 20';
        break;
    case 'unassigned-clients':
        $query = 'SELECT
                        c.ClientID,
                        c.Name AS ClientName,
                        c.Phone,
                        c.Email,
                        c.Address
                    FROM
                        Client c
                    LEFT JOIN
                        CaseInfo ci ON c.ClientID = ci.ClientID
                    WHERE
                        ci.LawyerID IS NULL OR ci.LawyerID = ""
                    GROUP BY
                        c.ClientID, c.Name, c.Phone, c.Email
                    ORDER BY
                        ClientName ASC';
        break;
    case 'recent-hearings':
        $query = 'SELECT
                        ch.HearingID,
                        ch.HearingDate,
                        ch.CourtLocation,
                        ch.HearingTime,
                        ci.CaseID,
                        ci.CaseType,
                        l.Name AS LawyerName,
                        l.Firm
                    FROM
                        CourtHearing ch
                    JOIN
                        CaseInfo ci ON ch.CaseID = ci.CaseID
                    LEFT JOIN
                        Lawyer l ON ci.LawyerID = l.LawyerID
                    ORDER BY
                        ch.HearingDate DESC';
        break;
    case 'all-records-view':
        $query = 'SELECT
                        cl.ClientID,
                        cl.Name AS ClientName,
                        ci.CaseID,
                        ci.CaseType,
                        ci.Status,
                        l.LawyerID,
                        l.Name AS LawyerName,
                        l.Firm,
                        ch.HearingID,
                        ch.HearingDate,
                        ch.CourtLocation,
                        ch.HearingTime,
                        w.WitnessID,
                        w.Name AS WitnessName,
                        e.EvidenceID,
                        e.Description AS EvidenceDescription
                    FROM
                        Client cl
                    LEFT JOIN
                        CaseInfo ci ON cl.ClientID = ci.ClientID
                    LEFT JOIN
                        Lawyer l ON ci.LawyerID = l.LawyerID
                    LEFT JOIN
                        CourtHearing ch ON ci.CaseID = ch.CaseID
                    LEFT JOIN
                        Witness w ON ci.CaseID = w.CaseID
                    LEFT JOIN
                        Evidence e ON ci.CaseID = e.CaseID
                    ORDER BY
                        cl.ClientID, ci.CaseID, ch.HearingDate';
        break;
    case 'crime-increase-by-city':
        // REMEMBER TO REPLACE YourCrimeDataTable AND DateColumn WITH YOUR ACTUAL TABLE AND COLUMN NAMES
        $query = 'SELECT
    Address AS City,
    CaseType AS MostFrequentCaseType,
    CaseCount
FROM (
    SELECT
        cl.Address,
        ci.CaseType,
        COUNT(ci.CaseID) AS CaseCount,
        ROW_NUMBER() OVER (PARTITION BY cl.Address ORDER BY COUNT(ci.CaseID) DESC) as rn
    FROM
        CaseInfo ci
    INNER JOIN
        Client cl ON ci.ClientID = cl.ClientID
    GROUP BY
        cl.Address, ci.CaseType
) AS RankedCases
WHERE rn = 1
ORDER BY
    Address, CaseCount DESC;';
        break;
    // --- END DIRECT INSIGHT CASES ---

    default:
        $response = ['success' => false, 'message' => 'Invalid action specified.', 'data' => []];
        break;
}

// Common logic for executing insight queries (now applied to all direct insight actions)
if (isset($query) && !empty($query)) { // Check if a query was defined by one of the new cases
    $stmt = $conn->prepare($query);

    if ($stmt === false) {
        $response = ['success' => false, 'message' => 'Failed to prepare statement for ' . $action . ' insight: ' . $conn->error, 'data' => []];
        echo json_encode($response);
        exit();
    }

    $result = $stmt->execute();

    if ($result === false) {
        $response = ['success' => false, 'message' => $action . ' insight query execution failed: ' . $stmt->error, 'data' => []];
        echo json_encode($response);
        exit();
    }

    $resultSet = $stmt->get_result();

    $data = [];
    if ($resultSet) {
        while ($row = $resultSet->fetch_assoc()) {
            
            $data[] = $row;
        }
        $resultSet->free();
    }

    $stmt->close();
    $response = ['success' => true, 'message' => ucfirst(str_replace('-', ' ', $action)) . ' data fetched successfully.', 'data' => $data];
}


echo json_encode($response);
$conn->close();
?>