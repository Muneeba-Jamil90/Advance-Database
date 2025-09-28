<?php
/**
 * information.php
 *
 * This file displays an overview of lawyers, including:
 * - Their name and the firm they belong to.
 * - The number of cases they have successfully closed.
 * - The number of cases they are currently actively working on.
 * - A list of their unique clients.
 *
 * The data is sorted by the number of closed cases in descending order,
 * effectively showing the "top lawyers" based on this metric.
 *
 * Assumes the following database table structure:
 * - 'lawyer' table: lawyer_id (PK), lawyer_name, firm_name
 * - 'caseinfo' table: case_id (PK), lawyer_id (FK), client_id (FK), case_status (e.g., 'Closed', 'Active')
 * - 'client' table: client_id (PK), client_name
 *
 * Requires a 'db.php' file in the same directory for database connection.
 */

// Include the database connection file.
// This file is expected to establish a MySQLi connection and store it in the $conn variable.
require_once 'db.php';

// Check if the database connection was successful.
if (!$conn) {
    // If connection failed, terminate the script and display an error message.
    die("Database connection failed: " . mysqli_connect_error());
}

// Initialize an empty array to store aggregated lawyer data.
// The key will be lawyer_id, and the value will be an associative array of their details.
$lawyers_data = [];

// --- Step 1: Fetch all lawyers and their associated firms ---
// This query retrieves basic information for all lawyers from the 'lawyer' table.
$lawyers_query = "SELECT lawyer_id, lawyer_name, firm_name FROM lawyer ORDER BY lawyer_name ASC";
$lawyers_result = mysqli_query($conn, $lawyers_query);

// Check if the query for lawyers was successful and returned any rows.
if ($lawyers_result && mysqli_num_rows($lawyers_result) > 0) {
    // Iterate over each lawyer row fetched from the database.
    while ($row = mysqli_fetch_assoc($lawyers_result)) {
        // Populate the $lawyers_data array with initial lawyer information.
        // Initialize 'closed_cases', 'active_cases', and 'clients' counts/arrays to zero/empty.
        $lawyers_data[$row['lawyer_id']] = [
            'name' => $row['lawyer_name'],
            'firm' => $row['firm_name'],
            'closed_cases' => 0,
            'active_cases' => 0,
            'clients' => [] // Stores unique client names for this lawyer
        ];
    }
}

// --- Step 2: Fetch all case information and associated client names ---
// This query joins 'caseinfo' and 'client' tables to get case status and client names
// for all cases, linking them back to the lawyer via 'lawyer_id'.
$cases_query = "
    SELECT
        ci.lawyer_id,      -- The ID of the lawyer handling the case
        ci.case_status,    -- The current status of the case (e.g., 'Closed', 'Active')
        c.client_name      -- The name of the client associated with the case
    FROM
        caseinfo ci        -- Alias 'caseinfo' as 'ci'
    JOIN
        client c ON ci.client_id = c.client_id -- Join with 'client' table on client_id
";
$cases_result = mysqli_query($conn, $cases_query);

// Check if the query for cases was successful and returned any rows.
if ($cases_result && mysqli_num_rows($cases_result) > 0) {
    // Iterate over each case row fetched from the database.
    while ($case_row = mysqli_fetch_assoc($cases_result)) {
        $lawyer_id = $case_row['lawyer_id'];
        $case_status = $case_row['case_status'];
        $client_name = $case_row['client_name'];

        // Ensure the lawyer exists in our $lawyers_data array before processing their cases.
        if (isset($lawyers_data[$lawyer_id])) {
            // Increment closed cases count if the case status is 'Closed'.
            // Make sure 'Closed' matches the exact string in your database.
            if ($case_status == 'Closed') {
                $lawyers_data[$lawyer_id]['closed_cases']++;
            }
            // Increment active cases count if the case status is 'Active'.
            // Make sure 'Active' matches the exact string in your database.
            elseif ($case_status == 'Active') {
                $lawyers_data[$lawyer_id]['active_cases']++;
            }

            // Add the client's name to the lawyer's client list if it's not already there.
            if (!in_array($client_name, $lawyers_data[$lawyer_id]['clients'])) {
                $lawyers_data[$lawyer_id]['clients'][] = $client_name;
            }
        }
    }
}

// --- Step 3: Sort lawyers by their closed cases to find "top lawyers" ---
// uasort() sorts an array by its values using a user-defined comparison function.
// Here, we sort in descending order based on 'closed_cases'.
uasort($lawyers_data, function($a, $b) {
    // The spaceship operator (<=>) returns -1, 0, or 1 if $a is less than, equal to, or greater than $b.
    // We use $b <=> $a for descending order.
    return $b['closed_cases'] <=> $a['closed_cases'];
});

?>