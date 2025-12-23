<?php
session_start();
require 'db.php'; // Reuse your database connection

if (!isset($conn) || !($conn instanceof PDO)) {
    die("Database connection failed.");
}

// 1. Determine Sorting/Filtering (Leveraging the 'sort_by' parameter)
$sort_by = $_GET['sort_by'] ?? 'id';

if ($sort_by === 'date') {
    // Report is ordered by deadline (Date-Wise view)
    $order_by = 'deadline ASC';
    $report_name_suffix = 'DateWise';
} else {
    // Report is ordered by ID (Show All view)
    $order_by = 'id DESC';
    $report_name_suffix = 'AllMissions';
}

// 2. Fetch Data
// Select only necessary columns for the CSV report
$query = "SELECT id, mission_name, subject, priority, deadline, status, progress, created_at FROM missions ORDER BY {$order_by}";
$stmt = $conn->query($query);
$missions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Set Headers for CSV Download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="StudyGenius_Report_' . $report_name_suffix . '_' . date('Ymd') . '.csv"');

// 4. Create output stream
$output = fopen('php://output', 'w');

// Set CSV column headers
fputcsv($output, ['ID', 'Mission', 'Subject', 'Priority', 'Deadline', 'Created At', 'Status', 'Progress (%)']);

// 5. Output Data Rows
if (!empty($missions)) {
    foreach ($missions as $row) {
        
        // Use the actual progress value
        $progress_value = $row['progress'] . '%';
        
        // Output the data row to the CSV file
        fputcsv($output, [
            $row['id'], 
            $row['mission_name'], 
            $row['subject'], 
            $row['priority'], 
            $row['deadline'], 
            $row['created_at'],
            $row['status'], 
            $progress_value
        ]);
    }
} else {
    fputcsv($output, ['No missions found for this report.']);
}

fclose($output);
exit;
?>