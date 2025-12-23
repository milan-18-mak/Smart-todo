<?php
session_start();
require 'db.php'; // Make sure this connects to your database

// Dummy user session for demonstration
// In real scenario, you will fetch logged-in user info from session or DB
$_SESSION['username'] = $_SESSION['username'] ?? 'John Doe';
$_SESSION['email'] = $_SESSION['email'] ?? 'john@example.com';
$_SESSION['photo'] = $_SESSION['photo'] ?? 'default.png'; // default profile image

$upload_success = '';
$upload_error = '';

if(isset($_POST['upload_photo'])){
    $target_dir = "uploads/";
    // Ensure the uploads directory exists
    if(!is_dir($target_dir)) mkdir($target_dir, 0777, true);

    $file_name = basename($_FILES["profile_photo"]["name"]);
    // Use time() to ensure unique filename
    $target_file = $target_dir . time() . "_" . $file_name;
    $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

    $check = getimagesize($_FILES["profile_photo"]["tmp_name"]);
    if($check !== false){
        if(move_uploaded_file($_FILES["profile_photo"]["tmp_name"], $target_file)){
            // Only update session photo if upload was successful
            $_SESSION['photo'] = $target_file;
            $upload_success = "Profile photo updated successfully!";
        } else {
            $upload_error = "Sorry, there was an error uploading your file.";
        }
    } else {
        $upload_error = "File is not an image.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile - StudyGenius Pro</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
  body {
    font-family:'Poppins',sans-serif; /* Added for consistency */
    background:#e6f0fb; /* Added for consistency */
    color:#1e3a8a; /* Added for consistency */
    min-height: 100vh;
    display: flex;
    flex-direction: column;
  }
  .navbar { 
      background: linear-gradient(90deg,#1e3a8a,#3b82f6); 
      box-shadow:0 4px 8px rgba(0,0,0,0.2); 
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
  footer {
    width: 100%;
  }
  </style>
</head>
<body>

<?php include 'navbar.php'; ?>


  
  <div class="container my-5">
    <div class="row justify-content-center">
      <div class="col-md-6 text-center">

        <h3 class="mb-4">👤 Your Profile</h3>

        <img src="<?= $_SESSION['photo'] ?>" alt="Profile Photo" class="rounded-circle mb-3" width="150" height="150" style="object-fit:cover;">

        <?php if($upload_success): ?>
          <div class="alert alert-success"><?= $upload_success ?></div>
        <?php endif; ?>
        <?php if($upload_error): ?>
          <div class="alert alert-danger"><?= $upload_error ?></div>
        <?php endif; ?>

        <ul class="list-group mb-3 text-start">
          <li class="list-group-item"><strong>Username:</strong> <?= $_SESSION['username'] ?></li>
          <li class="list-group-item"><strong>Email:</strong> <?= $_SESSION['email'] ?></li>
        </ul>

        <form method="post" enctype="multipart/form-data">
          <div class="mb-3">
            <input type="file" class="form-control" name="profile_photo" required>
          </div>
          <button type="submit" name="upload_photo" class="btn btn-primary">Upload Photo</button>
        </form>

        <a href="index.php" class="btn btn-secondary mt-3">⬅ Back to Home</a>

      </div>
    </div>
  </div>

  
  
<?php include 'footer.html'; ?>


  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>