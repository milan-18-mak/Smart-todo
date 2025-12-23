<?php
session_start();
require 'db.php'; // Make sure this file has a valid $conn = new PDO(...)

$errors = [];

if (isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $cpassword = $_POST['cpassword'];

    // Profile photo upload
    $photo = 'default.png';
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['name'] != '') {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_name = basename($_FILES["profile_photo"]["name"]);
        $target_file = $target_dir . time() . "_" . $file_name;
        $check = getimagesize($_FILES["profile_photo"]["tmp_name"]);

        if ($check !== false) {
            if (move_uploaded_file($_FILES["profile_photo"]["tmp_name"], $target_file)) {
                $photo = $target_file;
            } else {
                $errors[] = "Error uploading profile photo.";
            }
        } else {
            $errors[] = "Uploaded file is not a valid image.";
        }
    }

    // Validation
    if (!$username) $errors[] = 'Username is required';
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters long';
    if ($password !== $cpassword) $errors[] = 'Passwords do not match';

    // Check for existing user
    if (!$errors) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $errors[] = 'Username or Email already exists';
        }
    }

    // Insert into database
    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, photo) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $email, $hash, $photo]);

        $_SESSION['user_logged_in'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;
        $_SESSION['photo'] = $photo;

        header('Location: index.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register - StudyGenius Pro</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="font-family:'Poppins',sans-serif; background:#e6f0fb; display:flex; justify-content:center; align-items:center; height:100vh; color:#1e3a8a;">

<div class="card p-4" style="background:#d0e7ff; border-radius:15px; width:400px;">
    <h3 class="text-center mb-3" style="color:#1e3a8a;">📘 StudyGenius Pro Register</h3>

    <?php if ($errors): ?>
        <ul style="color:#ef4444;">
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <input type="text" name="username" placeholder="Username" class="form-control mb-2" required>
        <input type="email" name="email" placeholder="Email" class="form-control mb-2" required>
        <input type="password" name="password" placeholder="Password" class="form-control mb-2" required>
        <input type="password" name="cpassword" placeholder="Confirm Password" class="form-control mb-2" required>
        <input type="file" name="profile_photo" class="form-control mb-3">
        <button type="submit" name="register" class="btn" style="background:#2563eb; color:#fff; width:100%;">Register</button>
    </form>

    <p class="text-center mt-3">
        Already have an account?
        <a href="login.php" style="color:#3b82f6; text-decoration:none;">Login</a>
    </p>
</div>

</body>
</html>
