<?php
session_start();
require 'db.php';

// --- SECURITY CHECK ADDED ---
if(!isset($_SESSION['user_logged_in'])){
    header('Location: login.php');
    exit;
}
// ----------------------------

$username = $_SESSION['username'] ?? '';
$success = $errors = [];

if(isset($_POST['update_password'])){
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    // Get current password hash
    $stmt = $conn->prepare("SELECT password FROM users WHERE username=?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$user){
        $errors[] = "User not found.";
    } elseif(!password_verify($current, $user['password'])){
        $errors[] = "Current password is incorrect.";
    } elseif(strlen($new) < 6) {
        $errors[] = "New password must be at least 6 characters.";
    } elseif($new !== $confirm){
        $errors[] = "Passwords do not match.";
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password=? WHERE username=?");
        $stmt->execute([$hash, $username]);
        $success[] = "Password updated successfully!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>⚙️ Settings - StudyGenius Pro</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body {
        font-family: 'Poppins', sans-serif;
        background: #e6f0fb;
        color: #1e3a8a;
    }
</style>
</head>
<body>

<?php include 'navbar.php';?>

<div class="d-flex justify-content-center align-items-center" style="min-height: 80vh;">
    <div class="card p-4" style="background:#d0e7ff; border-radius:15px; max-width:500px; width:100%;">
        <h3 class="text-center mb-3">⚙️ Settings</h3>
        <?php if($errors){ echo '<ul class="text-danger">'; foreach($errors as $e){ echo "<li>$e</li>"; } echo '</ul>'; } ?>
        <?php if($success){ echo '<ul class="text-success">'; foreach($success as $s){ echo "<li>$s</li>"; } echo '</ul>'; } ?>
        <form method="POST">
            <input type="password" name="current_password" placeholder="Current Password" class="form-control mb-2" required>
            <input type="password" name="new_password" placeholder="New Password" class="form-control mb-2" required>
            <input type="password" name="confirm_password" placeholder="Confirm New Password" class="form-control mb-3" required>
            <button type="submit" name="update_password" class="btn btn-primary w-100">Update Password</button>
        </form>
    </div>
</div>

<?php include 'footer.html'; ?>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>