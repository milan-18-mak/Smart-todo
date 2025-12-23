<?php
// send_reminders.php - Script to be run daily by a Cron Job or Scheduled Task

// -----------------------------------------------------------------
// 1. CONFIGURATION & SETUP
// -----------------------------------------------------------------
// NOTE: When run via Cron, sessions are not used, and absolute paths are safer.
require __DIR__ . '/db.php'; // Adjust this path if db.php is not in the same directory!

if (!isset($conn) || !($conn instanceof PDO)) {
    // Log the error instead of dying, as this runs in the background
    error_log("Reminder Script: Database connection failed. Please check db.php.");
    exit(1);
}

// ⚠️ IMPORTANT: Replace this with the actual user's email or logic to fetch it.
// Since your missions table doesn't seem to link to a user, we'll use a placeholder.
// In a real app, you would fetch all users and their missions.
$DEFAULT_RECIPIENT_EMAIL = "your_user_email@example.com"; 

// -----------------------------------------------------------------
// 2. DEFINE DATE RANGE FOR REMINDERS (Yesterday, Today, Tomorrow)
// -----------------------------------------------------------------
// Use today's date for reference
$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$yesterday = date('Y-m-d', strtotime('-1 day'));

// Dates we want to check for PENDING missions
$dates_to_check = [
    $yesterday, // Reminder 1 day AFTER deadline (Missed)
    $today,     // Reminder on the deadline day (Final Warning)
    $tomorrow   // Reminder 1 day BEFORE deadline (Heads Up)
];

// -----------------------------------------------------------------
// 3. FETCH PENDING MISSIONS DUE ON THESE DATES
// -----------------------------------------------------------------
// Create placeholders for the SQL IN clause
$placeholders = str_repeat('?,', count($dates_to_check) - 1) . '?';

$stmt = $conn->prepare("
    SELECT id, mission_name, subject, deadline 
    FROM missions 
    WHERE status = 'Pending' 
    AND DATE(deadline) IN ({$placeholders})
");
$stmt->execute($dates_to_check);
$missions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// -----------------------------------------------------------------
// 4. PROCESS AND SEND EMAILS
// -----------------------------------------------------------------
$reminder_count = 0;

foreach ($missions as $mission) {
    $mission_deadline_date = substr($mission['deadline'], 0, 10);
    $relative_time = '';
    $subject_prefix = '';
    $priority_color = '';
    
    // Determine the reminder type and styling
    if ($mission_deadline_date === $tomorrow) {
        $relative_time = 'tomorrow';
        $subject_prefix = '⚠️ URGENT REMINDER (Due Tomorrow)';
        $priority_color = '#3b82f6'; // Blue
    } elseif ($mission_deadline_date === $today) {
        $relative_time = 'today';
        $subject_prefix = '🔥 DEADLINE TODAY!';
        $priority_color = '#ef4444'; // Red
    } elseif ($mission_deadline_date === $yesterday) {
        $relative_time = 'yesterday';
        $subject_prefix = '🚨 MISSED DEADLINE (1 Day Ago)';
        $priority_color = '#ff9800'; // Orange
    } else {
        continue; // Skip if date mismatch (safety check)
    }

    $subject = "{$subject_prefix}: Mission '{$mission['mission_name']}' is due {$relative_time}";
    
    $message = "
        <html>
        <body style='font-family: sans-serif; line-height: 1.6; color: #1e3a8a; background-color: #e6f0fb; padding: 20px; border-radius: 8px;'>
            <div style='max-width: 600px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);'>
                <h3 style='color: {$priority_color}; border-bottom: 2px solid #cce4ff; padding-bottom: 10px;'>StudyGenius Pro Mission Reminder</h3>
                <p>Hello,</p>
                <p>This is a reminder for your mission:</p>
                <table style='border-collapse: collapse; width: 100%; margin: 15px 0;'>
                    <tr><td style='padding: 8px; background-color: #f0f8ff; border: 1px solid #cce4ff;'><strong>Mission:</strong></td><td style='padding: 8px; border: 1px solid #cce4ff;'>{$mission['mission_name']}</td></tr>
                    <tr><td style='padding: 8px; background-color: #f0f8ff; border: 1px solid #cce4ff;'><strong>Subject:</strong></td><td style='padding: 8px; border: 1px solid #cce4ff;'>{$mission['subject']}</td></tr>
                    <tr><td style='padding: 8px; background-color: #f0f8ff; border: 1px solid #cce4ff;'><strong>Deadline:</strong></td><td style='padding: 8px; border: 1px solid #cce4ff; font-weight: bold; color: {$priority_color};'>{$mission_deadline_date} ({$relative_time})</td></tr>
                </table>
                <p style='text-align: center; margin-top: 20px;'>
                    <a href='http://localhost/TODO/index.php' style='display: inline-block; background-color: #2563eb; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Dashboard</a>
                </p>
                <hr style='margin-top: 30px; border-top: 1px solid #cce4ff;'>
                <p style='font-size: 0.8em; color: #60a5fa; text-align: center;'>This is an automated reminder. Please do not reply.</p>
            </div>
        </body>
        </html>
    ";

    // Set mail headers for HTML content
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: StudyGenius Pro <no-reply@yourdomain.com>' . "\r\n"; // ⚠️ Change sender email/domain

    // Send the email
    if (mail($DEFAULT_RECIPIENT_EMAIL, $subject, $message, $headers)) {
        $reminder_count++;
    } else {
        error_log("Reminder Script: Failed to send email for Mission ID {$mission['id']} to {$DEFAULT_RECIPIENT_EMAIL}.");
    }
}

// Log results (useful for debugging Cron)
if ($reminder_count > 0) {
    error_log("Reminder Script: Sent {$reminder_count} reminders on {$today}.");
} else {
    error_log("Reminder Script: No pending missions found for reminders on {$today}.");
}
exit(0);
?>