<?php
session_start();
require 'db.php'; // Reuse your database connection

if (!isset($conn) || !($conn instanceof PDO)) {
    die("Database connection failed. Please check db.php.");
}

// ----------------------------------------------------
// ✅ NEW: AJAX HANDLER FOR DRAG & DROP DEADLINE UPDATE
// ----------------------------------------------------
if (isset($_POST['mission_id']) && isset($_POST['new_deadline'])) {
    $mission_id = (int)$_POST['mission_id'];
    $new_deadline = $_POST['new_deadline']; // Format YYYY-MM-DD

    // 1. Prepare the new deadline (assuming time component is '23:59:59')
    $new_deadline_datetime = $new_deadline . ' 23:59:59'; 

    try {
        $stmt = $conn->prepare("UPDATE missions SET deadline = ? WHERE id = ?");
        $success = $stmt->execute([$new_deadline_datetime, $mission_id]);

        if ($success) {
            // Send a success response back to the JavaScript
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'Mission deadline updated successfully.']);
            exit;
        } else {
            // Send an error response
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Database update failed.']);
            exit;
        }
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}
// ----------------------------------------------------
// END: AJAX HANDLER
// ----------------------------------------------------


// ----------------- Mission Data Fetch -----------------
// ... (rest of your existing PHP data fetching logic remains here)
$stmt = $conn->query("
    SELECT 
        id, mission_name, subject, priority, deadline 
    FROM missions
    WHERE deadline IS NOT NULL
");
$missions_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ... (rest of your existing PHP logic for $missions_by_date)
$missions_by_date = [];
foreach ($missions_raw as $mission) {
    // Extract date component (e.g., '2025-10-09')
    $date_key = substr($mission['deadline'], 0, 10);
    
    // Initialize array for the date if it doesn't exist
    if (!isset($missions_by_date[$date_key])) {
        $missions_by_date[$date_key] = [];
    }
    
    // Add mission details to the corresponding date
    $missions_by_date[$date_key][] = [
        'id' => $mission['id'],
        'name' => htmlspecialchars($mission['mission_name']),
        'subject' => htmlspecialchars($mission['subject']),
        'priority' => $mission['priority'],
    ];
}

// Convert the structured PHP array to a JSON string for JavaScript
$missions_json = json_encode($missions_by_date);


// ----------------- Calendar Grid Generation -----------------
// ... (rest of your existing PHP calendar navigation logic)
$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');
$dateObj = DateTime::createFromFormat('Y-m-d', "$year-$month-01");

// Determine names and navigation
$month_name = $dateObj->format('F');

// Calculate next month/year
$dateObj->modify('+1 month'); 
$next_month = $dateObj->format('m');
$next_year = $dateObj->format('Y');

// Calculate previous month/year
$dateObj->modify('-2 month'); 
$prev_month = $dateObj->format('m');
$prev_year = $dateObj->format('Y');

// Reset to current month for grid calculation
$dateObj->modify('+1 month'); 
$first_day_of_month = $dateObj->format('w'); // 0=Sun, 6=Sat
$days_in_month = $dateObj->format('t');
$day_counter = 1;

// Go back to the correct year/month string for display
$dateObj = DateTime::createFromFormat('Y-m-d', "$year-$month-01");
$display_month_year = $dateObj->format('F Y');

// Helper function to generate the calendar days (Modified for Drag & Drop)
function generate_calendar_grid($first_day, $days_in_month, $month, $year) {
    global $missions_by_date;
    $output = '';
    $current_day = 1;
    $day_of_week_counter = 0;
    
    $output .= '<tr>';
    
    // Fill in leading empty days
    for ($i = 0; $i < $first_day; $i++) {
        $output .= '<td class="empty-day"></td>';
        $day_of_week_counter++;
    }

    // Fill in days of the month (DROP TARGET MODIFIED)
    while ($current_day <= $days_in_month) {
        if ($day_of_week_counter == 7) {
            $output .= '</tr><tr>';
            $day_of_week_counter = 0;
        }

        $date_key = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-" . str_pad($current_day, 2, '0', STR_PAD_LEFT);
        $missions_on_day = $missions_by_date[$date_key] ?? [];
        $mission_count = count($missions_on_day);
        $is_today = ($date_key === date('Y-m-d')) ? 'today' : '';
        
        // ADD DRAG & DROP HANDLERS TO THE CELL
        $output .= "<td 
            class='calendar-day $is_today' 
            data-date='$date_key'
            ondragover='allowDrop(event)'
            ondrop='dropMission(event)'
        >";
        $output .= "<span class='day-number'>" . $current_day . "</span>";
        
        // Missions Container for D&D
        $output .= "<div class='mission-buttons-container' data-date='$date_key'>";

        // Render each mission button individually for drag/drop
        foreach ($missions_on_day as $mission) {
            $priority_class = strtolower($mission['priority']);
            
            // ADD DRAG ATTRIBUTES TO THE MISSION BUTTON
            $output .= "<button 
                class='mission-drag-btn mission-priority-$priority_class' 
                id='mission-{$mission['id']}' 
                data-mission-id='{$mission['id']}'
                data-date='$date_key'
                draggable='true'
                ondragstart='dragStart(event)'
            >
                {$mission['name']}
            </button>";
        }

        if ($mission_count > 0) {
            // Kept the original count button for compatibility/modal trigger if desired,
            // but the individual buttons above are the primary drag targets.
            // You can choose to remove this if you only want the individual missions.
            // For now, I'll remove the original count button to only show the drag buttons.
        } else {
             // Display a hidden drop area if no missions are present for visual clarity
             $output .= "<div class='empty-drop-area'></div>";
        }
        
        $output .= "</div>"; // End mission-buttons-container
        $output .= '</td>';
        
        $current_day++;
        $day_of_week_counter++;
    }

    // Fill in trailing empty days
    while ($day_of_week_counter > 0 && $day_of_week_counter < 7) {
        $output .= '<td class="empty-day"></td>';
        $day_of_week_counter++;
    }
    
    $output .= '</tr>';
    
    return $output;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>📅 StudyGenius Pro Calendar</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
<style>
/* ... (Your existing styles) ... */
body { font-family:'Poppins',sans-serif; background:#e6f0fb; color:#1e3a8a; margin:0; padding:0; }
.navbar { background: linear-gradient(90deg,#1e3a8a,#3b82f6); box-shadow:0 4px 8px rgba(0,0,0,0.2); }
.calendar-container { background: #fff; border-radius: 15px; padding: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
.calendar-table { width: 100%; table-layout: fixed; border-collapse: collapse; }
.calendar-table th { text-align: center; padding: 10px 0; color: #1e3a8a; font-weight: bold; }
.calendar-day, .empty-day { 
    border: 1px solid #cce4ff; 
    height: 120px; 
    vertical-align: top; 
    padding: 5px; 
    cursor: default; /* Changed from pointer for the cell itself */
    position: relative; 
}
.empty-day { background-color: #f7faff; }
.day-number { font-size: 1.2em; font-weight: bold; color: #3b82f6; display: block; margin-bottom: 5px; }
.calendar-day.today { background-color: #ffe0b2; border-color: #ff9800; }

/* ------------------------------------- */
/* NEW DRAG & DROP STYLES */
/* ------------------------------------- */
.mission-buttons-container {
    width: 100%;
    /* Prevents the container from collapsing when empty, maintaining drop area */
    min-height: 50px; 
}

.mission-drag-btn {
    display: block;
    background-color: #2563eb;
    color: #fff;
    border: none;
    border-radius: 5px;
    padding: 3px 8px;
    font-size: 0.85em;
    cursor: grab; /* Indicates it's draggable */
    width: 100%;
    margin-top: 2px;
    text-align: left;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

/* Priority Colors for Drag Buttons */
.mission-priority-high { background-color: #ef4444 !important; } /* Red */
.mission-priority-medium { background-color: #3b82f6 !important; } /* Blue */
.mission-priority-low { background-color: #ffc107 !important; } /* Yellow */

/* Visual feedback when dragging */
.dragging {
    opacity: 0.4;
    border: 2px dashed #1e3a8a;
}

/* Visual feedback for a valid drop zone */
.calendar-day.drop-target {
    background-color: #cce4ff; 
    border: 2px dashed #1e3a8a;
}
/* ------------------------------------- */

/* Modal Styling */
/* ... (Your existing modal styles) ... */
</style>
</head>
<body>


<?php include 'navbar.php'; ?>



<div class="container mt-4">
    <div class="calendar-container">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="calendar.php?month=<?= $prev_month ?>&year=<?= $prev_year ?>" class="btn btn-outline-primary">&lt;</a>
            <h2 class="mb-0"><?= $display_month_year ?></h2>
            <a href="calendar.php?month=<?= $next_month ?>&year=<?= $next_year ?>" class="btn btn-outline-primary">&gt;</a>
        </div>
        
        <table class="calendar-table">
            <thead>
                <tr>
                    <th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th>
                </tr>
            </thead>
            <tbody>
                <?= generate_calendar_grid($first_day_of_month, $days_in_month, $month, $year) ?>
            </tbody>
        </table>
        
    </div>
</div>

<div id="missionListModal" class="mission-list-modal">
    </div>


<?php include 'footer.html'; ?>


<script>
// 1. Pass PHP data to JavaScript
const MISSIONS_BY_DATE = <?= $missions_json ?>;

// ----------------------------------------------------
// ✅ NEW: DRAG AND DROP JAVASCRIPT FUNCTIONS
// ----------------------------------------------------

/**
 * Executes when the drag operation starts on a mission button.
 * Stores the mission ID and its current date.
 * @param {DragEvent} event 
 */
function dragStart(event) {
    const missionId = event.target.getAttribute('data-mission-id');
    const missionDate = event.target.getAttribute('data-date');
    
    // Store the mission ID for the drop handler to retrieve
    event.dataTransfer.setData("text/plain", missionId);
    
    // Optional: Visual feedback
    event.target.classList.add('dragging');
}

/**
 * Executes continuously while a draggable element is dragged over a valid drop target.
 * Must call preventDefault() to allow the drop.
 * @param {DragEvent} event 
 */
function allowDrop(event) {
    event.preventDefault();
    // Optional: Add visual feedback to the drop target cell
    if (event.target.classList.contains('calendar-day')) {
        event.target.classList.add('drop-target');
    }
}

/**
 * Executes when a draggable element leaves a drop target.
 * Cleans up the visual feedback.
 * @param {DragEvent} event 
 */
function dragLeave(event) {
    // Optional: Remove visual feedback from the drop target cell
    if (event.target.classList.contains('calendar-day')) {
        event.target.classList.remove('drop-target');
    }
}

/**
 * Executes when a draggable element is dropped onto a drop target.
 * Performs the AJAX update and DOM manipulation.
 * @param {DragEvent} event 
 */
function dropMission(event) {
    event.preventDefault();
    
    // 1. Get Mission ID
    const missionId = event.dataTransfer.getData("text/plain");
    const draggedMission = document.getElementById(`mission-${missionId}`);
    
    if (!draggedMission) return;
    
    // Remove dragging class immediately
    draggedMission.classList.remove('dragging');

    // 2. Determine the Drop Target Cell (find the nearest 'calendar-day' parent)
    let dropTargetCell = event.target;
    while (dropTargetCell && !dropTargetCell.classList.contains('calendar-day')) {
        dropTargetCell = dropTargetCell.parentElement;
    }

    if (!dropTargetCell) return;
    
    // Remove drop-target class
    dropTargetCell.classList.remove('drop-target');

    // 3. Get New Deadline Date
    const newDeadline = dropTargetCell.getAttribute('data-date');
    const oldDeadline = draggedMission.getAttribute('data-date');

    if (newDeadline === oldDeadline) {
        console.log("Mission dropped on the same date. No action needed.");
        return;
    }

    // 4. Perform AJAX/Fetch call to update the database
    $.ajax({
        url: 'calendar.php', // Posting back to itself
        type: 'POST',
        data: {
            mission_id: missionId,
            new_deadline: newDeadline
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                console.log(`Mission ${missionId} moved to ${newDeadline}.`);
                
                // 5. Update the DOM (move the button)
                const newContainer = dropTargetCell.querySelector('.mission-buttons-container');
                if (newContainer) {
                    newContainer.appendChild(draggedMission);
                    // Update the data-date attribute on the button
                    draggedMission.setAttribute('data-date', newDeadline);
                }
                
                // OPTIONAL: Refresh the page to update mission counts and modal data
                // For a production app, you'd update the MISSIONS_BY_DATE object here instead of reloading.
                window.location.reload(); 

            } else {
                alert('Error updating mission date: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            alert('Failed to update mission deadline. Check server logs.');
            console.error("AJAX Error:", status, error);
            // Move the mission back to its original location (requires tracking the old container, simpler to just reload)
            window.location.reload(); 
        }
    });
}

// ----------------------------------------------------
// ----------------------------------------------------


$(document).ready(function() {
    // Existing: Event listener for all mission count buttons
    // NOTE: Since we changed the button structure, this click listener might need adjustment
    // if you still want to use the modal. I'm removing the old mission-count-btn and adding 
    // a click handler for the new mission-drag-btn to open the modal.

    // New: Handle mission button click to open the modal (optional, depends on your design)
    $('.mission-drag-btn').on('click', function() {
        // You would typically open an edit modal here, 
        // but for now, we'll keep the original modal structure for reference
        alert("Mission Clicked! ID: " + $(this).data('mission-id') + ". Consider opening an edit modal here.");
    });
    
    // NEW: Add the dragleave handler to calendar cells for cleanup
    document.querySelectorAll('.calendar-day').forEach(cell => {
        cell.addEventListener('dragleave', dragLeave);
    });
});

// Existing: Modal Functions (Assuming you'll re-implement or remove the old modal logic)
// ... (Your existing modal functions: displayMissions, closeModal) ...

// Function to close the modal
function closeModal() {
    $('#missionListModal').css('display', 'none');
}

// Close the modal if the user clicks anywhere outside of it
window.onclick = function(event) {
    const modal = document.getElementById('missionListModal');
    if (event.target == modal) {
        modal.style.display = "none";
    }
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>