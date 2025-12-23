<?php
session_start();
require 'db.php';

if(!isset($_SESSION['user_logged_in'])){
    header('Location: login.php');
    exit;
}

// Fetch all missions for dashboard
$stmt = $conn->prepare("SELECT * FROM missions ORDER BY deadline ASC");
$stmt->execute();
$missions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Define standard styles for consistent theme appearance
$navbar_styles = 'background: linear-gradient(90deg,#1e3a8a,#3b82f6); box-shadow:0 4px 8px rgba(0,0,0,0.2);';
$footer_styles = 'background: linear-gradient(90deg,#1e3a8a,#3b82f6); box-shadow:0 -4px 8px rgba(0,0,0,0.1);';
$body_styles = "font-family:'Poppins',sans-serif; background:#e6f0fb; color:#1e3a8a;";
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - StudyGenius Pro</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    <?= $body_styles ?>
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}
.navbar .nav-link { 
    color:#fff !important; 
    margin-right:10px; 
    transition:0.3s; 
}
.navbar .nav-link:hover { 
    color:#e0f2fe !important; 
    transform:translateY(-1px); 
}
main {
    flex: 1; /* This makes main grow to fill space */
}
</style>
</head>
<body>

<?php 
// If 'navbar.php' is not available, uncomment the code block below
/*
<nav class="navbar navbar-expand-lg sticky-top" style="<?= $navbar_styles ?>">
    <div class="container">
        <a class="navbar-brand fs-4" href="index.php" style="color:#fff !important;">📘 StudyGenius Pro</a>
        <button class="navbar-toggler bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">🏠 Home</a></li>
                <li class="nav-item"><a class="nav-link" href="calendar.php">📅 Calendar</a></li>
                <li class="nav-item"><a class="nav-link" href="reports.php">📊 Reports</a></li>
                <li class="nav-item"><a class="nav-link" href="profile.php">👤 Profile</a></li>
                <li class="nav-item"><a class="nav-link" href="settings.php">⚙ Settings</a></li>
            </ul>
            <div class="d-flex ms-lg-3">
                <a href="index.php#add" class="btn btn-sm me-2" style="background:#3b82f6; color:#fff;">+ Add Mission</a>
                <a href="logout.php" class="btn btn-sm" style="background:#ef4444; color:#fff;">Logout</a>
            </div>
        </div>
    </div>
</nav>
*/
include 'navbar.php'; 
?>

<main class="container my-5">
    <h3 class="mb-4">🏠 Dashboard - All Missions</h3>

    <?php if(count($missions) > 0): ?>
        <div class="row row-cols-1 row-cols-md-2 g-3">
            <?php foreach($missions as $m): ?>
                <div class="col">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($m['mission_name']) ?></h5>
                            <p class="card-text mb-1"><strong>Subject:</strong> <?= htmlspecialchars($m['subject']) ?></p>
                            <p class="card-text mb-1"><strong>Priority:</strong> <span class="badge bg-info"><?= htmlspecialchars($m['priority']) ?></span></p>
                            <p class="card-text mb-1"><strong>Deadline:</strong> <?= htmlspecialchars($m['deadline']) ?></p>
                            <p class="card-text mb-0"><strong>Status:</strong> <span class="badge bg-primary"><?= htmlspecialchars($m['status']) ?></span></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="text-center">No missions added yet.</p>
    <?php endif; ?>
</main>

<footer class="mt-5 text-center text-white py-4" 
        style="<?= $footer_styles ?>">
    <div class="container">
        <div class="row align-items-center">
            
            <div class="col-md-6 mb-2 mb-md-0 text-md-start">
                <h5 class="fw-bold">📘 StudyGenius Pro</h5>
                <p class="mb-0" style="font-size:14px;">Your Smart Study & Productivity Dashboard</p>
            </div>

            <div class="col-md-6 text-md-end">
                <p class="mb-0" style="font-size:14px;">© <?= date('Y'); ?> StudyGenius Pro. All rights reserved.</p>
                <p style="font-size:13px; opacity:0.8; margin-top: 5px;">Made with ❤️ by the StudyGenius Hackops Team</p>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>