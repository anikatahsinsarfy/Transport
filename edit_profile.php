<?php
session_start();
require_once 'db.php';

// Check login
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user info
$stmt = $conn->prepare("SELECT full_name,email,phone,address,image,created_at FROM users WHERE id=?");
$stmt->bind_param("i",$user_id);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();

// Handle profile update
$success = false;
$error_msg = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $full_name = $_POST['full_name'] ?? '';
    $email     = $_POST['email'] ?? '';
    $phone     = $_POST['phone'] ?? '';
    $address   = $_POST['address'] ?? '';
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $image_path = $user['image']; // existing path

    // Handle image upload
    if(isset($_FILES['image']) && $_FILES['image']['error']===0){
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = 'uploads/image_'.$user_id.'_'.time().'.'.$ext;
        if(move_uploaded_file($_FILES['image']['tmp_name'], $filename)){
            $image_path = $filename;
        }
    }

    if(!empty($new_password)){
        $stmt = $conn->prepare("SELECT password FROM users WHERE id=?");
        $stmt->bind_param("i",$user_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if(!password_verify($current_password, $row['password'])){
            $error_msg = "Current password is incorrect.";
        } elseif($new_password !== $confirm_password){
            $error_msg = "New password and confirm password do not match.";
        } else {
            $hashed_pass = password_hash($new_password,PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET full_name=?,email=?,phone=?,address=?,image=?,password=? WHERE id=?");
            $stmt->bind_param("ssssssi",$full_name,$email,$phone,$address,$image_path,$hashed_pass,$user_id);
            $stmt->execute();
            $stmt->close();
            $success = true;
        }
    } else {
        $stmt = $conn->prepare("UPDATE users SET full_name=?,email=?,phone=?,address=?,image=? WHERE id=?");
        $stmt->bind_param("sssssi",$full_name,$email,$phone,$address,$image_path,$user_id);
        $stmt->execute();
        $stmt->close();
        $success = true;
    }

    // Redirect to dashboard with flash
    if($success){
    // 🔹 Update session variables
    $_SESSION['full_name'] = $full_name;
    $_SESSION['email'] = $email;
    $_SESSION['phone'] = $phone;
    $_SESSION['address'] = $address;

    $_SESSION['flash_success'] = "Profile updated successfully!";
    header("Refresh:0.5; url=dashboard.php");
    exit();
}

}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Profile</title>
<link rel="stylesheet" href="edit_profile.css">
<style>
.image-preview img{width:120px;height:120px;border-radius:50%;object-fit:cover;}
.flash-msg{background:#4caf50;color:#fff;padding:10px;text-align:center;margin-bottom:10px;border-radius:5px;}
</style>
</head>
<body>
<div class="profile-container">
    <h1>Edit Profile</h1>

    <?php if($error_msg) echo '<div class="flash-msg" style="background:red;">'.$error_msg.'</div>'; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="image-upload">
            <label>Profile Image</label>
            <div class="image-preview">
                <img id="preview" src="<?= htmlspecialchars($user['image'] ?: 'uploads/default.jpg') ?>" alt="Profile Image">
            </div>
            <input type="file" name="image" id="imageInput">
        </div>

        <label>Full Name</label>
        <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>

        <label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>

        <label>Phone</label>
        <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" required>

        <label>Address</label>
        <textarea name="address" required><?= htmlspecialchars($user['address']) ?></textarea>

        <label>Current Password (for change)</label>
        <input type="password" name="current_password">

        <label>New Password</label>
        <input type="password" name="new_password">

        <label>Confirm New Password</label>
        <input type="password" name="confirm_password">

        <button type="submit">Update Profile</button>
    </form>
</div>

<script>
// Preview image on file select
document.getElementById('imageInput').addEventListener('change', function(e){
    const reader = new FileReader();
    reader.onload = function(){
        document.getElementById('preview').src = reader.result;
    }
    reader.readAsDataURL(e.target.files[0]);
});
</script>
</body>
</html>
