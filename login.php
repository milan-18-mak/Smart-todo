<?php
session_start();
require 'db.php';

$error = '';

if(isset($_POST['login'])){
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if($user && password_verify($password, $user['password'])){
        $_SESSION['user_logged_in'] = true;
        $_SESSION['username'] = $user['username'];
        header('Location: index.php');
        exit;
    } else {
        $error = "Invalid username or password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login - StudyGenius Pro</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="font-family:'Poppins',sans-serif; background:#e6f0fb; display:flex; justify-content:center; align-items:center; height:100vh; color:#1e3a8a;">

<div class="card p-4" style="background:#d0e7ff; border-radius:15px; width:350px;">
    <h3 class="text-center mb-3" style="color:#1e3a8a;">📘 StudyGenius Pro Login</h3>
    <?php if($error){ echo '<p class="text-center" style="color:#ef4444;">'.$error.'</p>'; } ?>
    <form method="POST">
        <input type="text" name="username" placeholder="Username" class="form-control mb-2" required>
        <input type="password" name="password" placeholder="Password" class="form-control mb-3" required>
        <button type="submit" name="login" class="btn" style="background:#2563eb; color:#fff; width:100%;">Login</button>
    </form>
    <p class="text-center mt-3">Don't have account? <a href="register.php" style="color:#3b82f6; text-decoration:none;">Register</a></p>
</div>

</body>
</html>
