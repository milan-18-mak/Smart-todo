<?php
// index.php
session_start();
// NOTE: Make sure your db.php establishes a PDO connection named $conn
require 'db.php';

if (!isset($conn) || !($conn instanceof PDO)) {
    die("Database connection failed. Please check db.php.");
}

// ----------------------------------------------------
// CORE LOGIC: Deadline-Driven Automatic Progress Function
// ----------------------------------------------------
function calculate_auto_progress($start_date, $end_date, $is_completed) {
    if ($is_completed === 'Completed') {
        return 100;
    }
    $current_timestamp = time();
    $start_timestamp = strtotime($start_date);
    $end_timestamp = strtotime($end_date);
    
    if ($current_timestamp <= $start_timestamp || $end_timestamp <= $start_timestamp) {
        return 0;
    }
    
    if ($current_timestamp > $end_timestamp) {
        return 100; 
    }
    
    $total_duration = $end_timestamp - $start_timestamp;
    $elapsed_duration = $current_timestamp - $start_timestamp;
    $raw_progress = ($elapsed_duration / $total_duration) * 100;
    return min(100, floor($raw_progress));
}

// ----------------------------------------------------
// ✅ PHP HANDLER: Auto-Complete Past Deadlines (Database Update)
// ----------------------------------------------------
$current_date_time = date('Y-m-d H:i:s');
$stmt = $conn->prepare("
    UPDATE missions 
    SET status = 'Completed', progress = 100 
    WHERE status = 'Pending' 
    AND deadline < ? 
");
$stmt->execute([date('Y-m-d')]);

// ----------------- Add Mission Handler -----------------
if(isset($_POST['add_mission'])){
    $mission = $_POST['mission'];
    $subject = $_POST['subject'];
    $priority = $_POST['priority'];
    $deadline = $_POST['deadline'];
    $status = 'Pending';
    $progress = 0; 
    $created_at = date('Y-m-d H:i:s'); 

    $stmt = $conn->prepare("
        INSERT INTO missions (mission_name, subject, priority, deadline, status, progress, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$mission, $subject, $priority, $deadline, $status, $progress, $created_at]);

    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// ----------------- Mark Complete Handler -----------------
if(isset($_GET['complete'])){
    $id = $_GET['complete'];
    
    $stmt = $conn->prepare("UPDATE missions SET status='Completed', progress=100 WHERE id=?");
    $stmt->execute([$id]);
    
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// ----------------- Delete Mission Handler -----------------
if(isset($_GET['delete'])){
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM missions WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// ----------------- Update Mission Handler -----------------
if(isset($_POST['update_mission'])){
    $id = $_POST['id'];
    $mission = $_POST['mission'];
    $subject = $_POST['subject'];
    $priority = $_POST['priority'];
    $deadline = $_POST['deadline'];
    $status = $_POST['status'];
    
    $progress_update = ($status == 'Completed') ? 100 : $conn->query("SELECT progress FROM missions WHERE id=$id")->fetchColumn();
    
    $stmt = $conn->prepare("UPDATE missions SET mission_name=?, subject=?, priority=?, deadline=?, status=?, progress=? WHERE id=?");
    $stmt->execute([$mission, $subject, $priority, $deadline, $status, $progress_update, $id]);
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// ----------------- Fetch Missions (With Sorting) -----------------
$current_sort = $_GET['sort_by'] ?? 'id'; 

if ($current_sort === 'date') {
    $stmt = $conn->query("SELECT id, mission_name, subject, priority, deadline, status, progress, created_at FROM missions ORDER BY deadline ASC");
} else {
    $stmt = $conn->query("SELECT id, mission_name, subject, priority, deadline, status, progress, created_at FROM missions ORDER BY id DESC");
}
$missions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ----------------- Stats & Chart Data -----------------
$total = $conn->query("SELECT COUNT(*) FROM missions")->fetchColumn();
$completed = $conn->query("SELECT COUNT(*) FROM missions WHERE status='Completed'")->fetchColumn();
$pending = $conn->query("SELECT COUNT(*) FROM missions WHERE status='Pending'")->fetchColumn();

$low = $conn->query("SELECT COUNT(*) FROM missions WHERE priority='Low'")->fetchColumn();
$medium = $conn->query("SELECT COUNT(*) FROM missions WHERE priority='Medium'")->fetchColumn();
$high = $conn->query("SELECT COUNT(*) FROM missions WHERE priority='High'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>📘 StudyGenius Pro</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script> 
<style>
/* --- Core Theme --- */
body { font-family:'Poppins',sans-serif; background:#e6f0fb; color:#1e3a8a; margin:0; padding:0; display:flex; flex-direction:column; min-height:100vh;}
.navbar { background: linear-gradient(90deg,#1e3a8a,#3b82f6); box-shadow:0 4px 8px rgba(0,0,0,0.2); }
.navbar .nav-link { color:#fff !important; margin-right:10px; transition:0.3s; }
.navbar .nav-link:hover { color:#e0f2fe !important; transform:translateY(-1px); }
.navbar-brand { color:#fff !important; font-weight:700; }
.card { border:none; border-radius:15px; transition:all 0.3s ease; }
.card:hover { box-shadow:0 8px 20px rgba(0,0,0,0.15); }
/* --- Table & Badges --- */
.table thead tr { background:#3b82f6; color:#fff; font-weight:bold; }
.table tbody tr:hover { background:#cce4ff; }
.badge-priority-high { background-color: #ef4444 !important; } /* Red */
.badge-priority-medium { background-color: #3b82f6 !important; } /* Blue */
.badge-priority-low { background-color: #ffc107 !important; } /* Yellow */
.badge { font-weight:bold; }
/* --- Modal & Progress --- */
.modal-content { border-radius:15px; }
.progress-bar { transition: all 0.6s ease; font-weight:bold; text-align:center; }
h4,h5 { font-weight:bold; }
/* Chart Container Styling for Aspect Ratio */
.chart-container { 
    height: 350px; 
    position: relative;
    padding: 15px;
}
/* New Button Style for Quick Actions */
.btn-quick-complete {
    background-color: #22c55e;
    color: white;
    border-radius: 5px;
    padding: 5px 10px;
}
</style>
</head>
<body>


<?php
// 🔔 Reminder & Warning Feature
$reminders = [];
$warnings = [];
$today = date('Y-m-d');

foreach ($missions as $m) {
    $deadline = $m['deadline'];
    $status = $m['status'];
    $mission = htmlspecialchars($m['mission_name']);
    
    // One day before deadline → Reminder
    if ($status == 'Pending' && $deadline == date('Y-m-d', strtotime('+1 day'))) {
        $reminders[] = "⚠️ Reminder: Tomorrow is the deadline for <b>{$mission}</b>.";
    }

    // One day after deadline → Warning
    if ($status == 'Pending' && $deadline == date('Y-m-d', strtotime('-1 day'))) {
        $warnings[] = "❌ Warning: <b>{$mission}</b> is overdue! Complete it soon.";
    }
}
?>

<!-- 🔔 Display Reminders & Warnings -->
<?php if(!empty($reminders) || !empty($warnings)): ?>
    <div class="mb-3">
        <?php foreach($reminders as $r): ?>
            <div class="alert alert-warning mb-2" style="background:#fff3cd; color:#856404; border-left:5px solid #ffb300;">
                <?= $r ?>
            </div>
        <?php endforeach; ?>
        <?php foreach($warnings as $w): ?>
            <div class="alert alert-danger mb-2" style="background:#f8d7da; color:#721c24; border-left:5px solid #dc3545;">
                <?= $w ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include 'navbar.php'; ?>

<main style="flex:1;">
<div class="container mt-4">

    <div class="row text-center mb-4 g-3">
        <div class="col-md-4">
            <div class="card p-3" style="background:#3b82f6; color:#fff;">
                <h5>Total Missions</h5>
                <h2><?= $total ?></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3" style="background:#2563eb; color:#fff;">
                <h5>Completed</h5>
                <h2><?= $completed ?></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3" style="background:#60a5fa; color:#fff;">
                <h5>Pending</h5>
                <h2><?= $pending ?></h2>
            </div>
        </div>
    </div>

    <div class="card p-4 mb-4" id="add" style="background:#d0e7ff;">
        <h4 class="mb-3">Add New Mission</h4>
        <form method="POST" class="row g-3">
            <div class="col-md-3"><input type="text" name="mission" placeholder="Mission" class="form-control" required></div>
            <div class="col-md-2"><input type="text" name="subject" placeholder="Subject" class="form-control" required></div>
            <div class="col-md-2">
                <select name="priority" class="form-select">
                    <option value="Low">Low</option>
                    <option value="Medium" selected>Medium</option>
                    <option value="High">High</option>
                </select>
            </div>
            <div class="col-md-3"><input type="date" name="deadline" class="form-control" required></div>
            <div class="col-md-2"><button type="submit" name="add_mission" class="btn" style="background:#2563eb; color:#fff; width:100%;">Add</button></div>
        </form>
    </div>

    <div class="card p-4 mb-4" style="background:#d0e7ff;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>All Missions</h4>
            <div class="d-flex align-items-center">
                <input 
                    type="text" 
                    id="subjectSearch" 
                    class="form-control form-control-sm me-3 ms-auto" 
                    placeholder="Search by Subject..." 
                    style="max-width: 200px;"
                >
                
                <a href="index.php" class="btn btn-secondary btn-sm  <?php echo ($current_sort === 'id') ? 'active' : ''; ?>">Show All (Latest)</a>
                <a href="index.php?sort_by=date" class="btn btn-primary btn-sm <?php echo ($current_sort === 'date') ? 'active' : ''; ?>">📅 Show Date-Wise</a>
                
                <a href="generate_report.php?sort_by=<?php echo $current_sort; ?>" class="btn btn-dark btn-sm ms-2">⬇️ Download Report</a>
            </div>
        </div>
        <table class="table table-hover" id="missionTable">
            <thead>
                <tr>
                    <th>#</th><th>Mission</th><th>Subject</th><th>Priority</th><th>Deadline</th><th>Status</th><th>Progress</th><th>Actions</th>
                </tr>
            </thead>
            <tbody id="missionTableBody">
            <?php foreach($missions as $m){ 
                $progress = calculate_auto_progress($m['created_at'] ?? date('Y-m-d'), $m['deadline'], $m['status']);
                $priority_class = 'badge-priority-' . strtolower($m['priority']);
            ?>
                <tr>
                    <td><?= $m['id'] ?></td>
                    <td><?= htmlspecialchars($m['mission_name']) ?></td>
                    <td class="mission-subject"><?= htmlspecialchars($m['subject']) ?></td>
                    <td><span class="badge <?= $priority_class ?>"><?= $m['priority'] ?></span></td>
                    <td><?= $m['deadline'] ?></td>
                    <td>
                        <?php if($m['status']=="Completed"){ ?>
                            <span class="badge bg-success">Completed</span>
                        <?php } else { ?>
                            <span class="badge bg-info">Pending</span>
                        <?php } ?>
                    </td>
                    <td>
                        <div class="progress" style="height:20px; border-radius:10px;">
                            <div class="progress-bar 
                                <?php 
                                    if($progress == 100) echo 'bg-success';
                                    elseif($progress >= 75) echo 'bg-warning';
                                    else echo 'bg-primary'; 
                                ?>" 
                                role="progressbar" 
                                style="width: <?= $progress ?>%;"
                                aria-valuenow="<?= $progress ?>"
                                aria-valuemin="0"
                                aria-valuemax="100"
                            >
                                <?= $progress ?>%
                            </div>
                        </div>
                    </td>
                    
                    <td>
                        <?php if($m['status'] !== "Completed"){ ?>
                            <a href="?complete=<?= $m['id'] ?>" 
                               class="btn btn-sm btn-quick-complete me-2" 
                               title="Mark as Completed" 
                               onclick="return confirm('Mark \'<?= htmlspecialchars($m['mission_name']) ?>\' as COMPLETE?')"
                            >
                                ✓ Done
                            </a>
                        <?php } else { ?>
                            <button class="btn btn-sm btn-secondary me-2" disabled>✓ Done</button>
                        <?php } ?>

                        <a href="?delete=<?= $m['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this mission? This cannot be undone.')">Delete</a>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>

    <div class="row mb-4 g-3">
        <div class="col-md-6">
            <div class="card p-3" style="background:#d0e7ff;">
                <h5 class="text-center">Status Distribution</h5>
                <div class="chart-container">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card p-3" style="background:#d0e7ff;">
                <h5 class="text-center">Priority Distribution</h5>
                <div class="chart-container">
                    <canvas id="priorityChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
</main>

<div id="updateModal" class="modal" style="display:none; position:fixed; z-index:100; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.3);">
  <div class="modal-dialog">
    <div class="modal-content p-4" style="background:#d0e7ff; border-radius:15px;">
      <div class="modal-header border-0">
        <h4 class="modal-title">Edit Mission Details (Hidden Functionality)</h4>
        <button type="button" class="btn-close" onclick="closeModal()" aria-label="Close"></button>
      </div>
      <form method="POST">
        <input type="hidden" name="id" id="modal_id">
        <input type="text" name="mission" id="modal_mission" class="form-control mb-2" placeholder="Mission" required>
        <input type="text" name="subject" id="modal_subject" class="form-control mb-2" placeholder="Subject" required>
        <select name="priority" id="modal_priority" class="form-select mb-2">
            <option value="Low">Low</option><option value="Medium">Medium</option><option value="High">High</option>
        </select>
        <input type="date" name="deadline" id="modal_deadline" class="form-control mb-2" required>
        <select name="status" id="modal_status" class="form-select mb-2">
            <option value="Pending">Pending</option><option value="Completed">Completed</option>
        </select>
        <button type="submit" name="update_mission" class="btn btn-primary w-100">Update Mission</button>
      </form>
    </div>
  </div>
</div>


<?php include 'footer.html'; ?>


<script>
// --- Client-Side Subject Search Logic ---
$(document).ready(function() {
    $("#subjectSearch").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $("#missionTableBody tr").filter(function() {
            var subjectText = $(this).children('td').eq(2).text().toLowerCase();
            $(this).toggle(subjectText.indexOf(value) > -1)
        });
    });
});


// --- Modal Functions ---
function openModal(id, mission, subject, priority, deadline, status){
    // NOTE: This uses Bootstrap 5 JS, which should be included in your bundle.
    var updateModal = new bootstrap.Modal(document.getElementById('updateModal'));
    document.getElementById('modal_id').value = id;
    document.getElementById('modal_mission').value = mission;
    document.getElementById('modal_subject').value = subject;
    document.getElementById('modal_priority').value = priority;
    document.getElementById('modal_deadline').value = deadline;
    document.getElementById('modal_status').value = status;
    updateModal.show();
}
function closeModal(){
    // NOTE: This requires Bootstrap 5 JS to be loaded.
    var updateModal = bootstrap.Modal.getInstance(document.getElementById('updateModal'));
    if (updateModal) {
        updateModal.hide();
    }
}

// --- Chart Rendering ---
// 1. Status Distribution (Pie Chart)
new Chart(document.getElementById('statusChart'), {
    type:'pie',
    data:{
        labels:['Completed','Pending'],
        datasets:[{ 
            data:[<?= $completed ?>,<?= $pending ?>], 
            backgroundColor:['#22c55e','#3b82f6'], 
            hoverOffset: 4
        }]
    },
    options:{
        responsive: true,
        maintainAspectRatio: false, 
        plugins:{ 
            legend:{ 
                position: 'top', 
                labels: {
                    usePointStyle: true, 
                    padding: 20
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.label || '';
                        if (label) {
                            label += ': ';
                        }
                        let total = <?= $completed ?> + <?= $pending ?>;
                        let value = context.parsed;
                        let percentage = ((value / total) * 100).toFixed(1) + '%';
                        return label + value + ' (' + percentage + ')';
                    }
                }
            }
        } 
    }
});

// 2. Priority Distribution (Bar Chart)
new Chart(document.getElementById('priorityChart'), {
    type:'bar',
    data:{
        labels:['High','Medium','Low'],
        datasets:[{ 
            data:[<?= $high ?>,<?= $medium ?>,<?= $low ?>], 
            backgroundColor:['#ef4444','#3b82f6','#ffc107'],
            borderRadius: 5
        }]
    },
    options:{ 
        responsive: true,
        maintainAspectRatio: false, 
        plugins:{ 
            legend:{ 
                display:false 
            } 
        },
        scales: {
            y: {
                beginAtZero: true,  
                title: {
                    display: true,
                    text: 'Count'
                },
                ticks: {
                    callback: function(value) { if (value % 1 === 0) { return value; } }
                }
            },
            x: {
                grid: {
                    display: false 
                }
            }
        }
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
