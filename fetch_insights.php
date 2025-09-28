<?php
require_once 'db.php';
header('Content-Type: application/json');

$insightType = $_POST['insightType'] ?? '';

$query = ''; // Initialize query
// $headingText is not needed in the PHP file anymore as it's handled in JS

switch ($insightType) {
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
                            GROUP_CONCAT(DISTINCT ci.CaseType ORDER BY ci.CaseType SEPARATOR ", ") AS HandledCaseTypes,
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
                        Limit 20';
        break;
    case 'unassigned-clients':
        $query = 'SELECT
                            c.ClientID,
                            c.Name AS ClientName,
                            c.Phone,
                            c.Email
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
        // ** ADD YOUR SQL QUERY FOR CRIME INCREASE BY CITY HERE **
        $query = 'SELECT
                        City,
                        CrimeType,
                        SUM(CASE WHEN DateColumn BETWEEN CURDATE() - INTERVAL 2 MONTH AND CURDATE() - INTERVAL 1 MONTH THEN 1 ELSE 0 END) AS CasesLastMonth,
                        SUM(CASE WHEN DateColumn BETWEEN CURDATE() - INTERVAL 1 MONTH AND CURDATE() THEN 1 ELSE 0 END) AS CasesThisMonth,
                        (SUM(CASE WHEN DateColumn BETWEEN CURDATE() - INTERVAL 1 MONTH AND CURDATE() THEN 1 ELSE 0 END) -
                         SUM(CASE WHEN DateColumn BETWEEN CURDATE() - INTERVAL 2 MONTH AND CURDATE() - INTERVAL 1 MONTH THEN 1 ELSE 0 END)) * 100.0 /
                        NULLIF(SUM(CASE WHEN DateColumn BETWEEN CURDATE() - INTERVAL 2 MONTH AND CURDATE() - INTERVAL 1 MONTH THEN 1 ELSE 0 END), 0) AS PercentageIncrease
                    FROM
                        YourCrimeDataTable -- Replace with your actual crime data table name
                    GROUP BY
                        City, CrimeType
                    ORDER BY
                        PercentageIncrease DESC
                    LIMIT 20';
        break;
    default:
        // This case should be handled by the client-side sending a valid type,
        // but it's good to have a server-side check.
        echo json_encode(['error' => 'Invalid insight type provided.']);
        exit();
}

if (empty($query)) {
    // This check should ideally not be hit if the switch cases are correctly defined
    // and a valid insightType is sent.
    echo json_encode(['error' => 'No SQL query defined for the given insight type.']);
    exit();
}

// Prepare and execute the query
$stmt = $conn->prepare($query);

if ($stmt === false) {
    echo json_encode(['error' => 'Failed to prepare statement: ' . $conn->error]);
    exit();
}

$result = $stmt->execute();

if ($result === false) {
    echo json_encode(['error' => 'Query execution failed: ' . $stmt->error]);
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
$conn->close();

echo json_encode($data);
?>