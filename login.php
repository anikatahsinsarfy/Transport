<?php
session_start();
include 'db.php';

$msg = "";

// Show success msg if redirected from signup
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $msg = "Signup successful! Please log in.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // check if email exists
    $stmt = $conn->prepare("SELECT id, full_name, email, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // verify password
        if (password_verify($password, $user['password'])) {
            // set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            // redirect based on role
            if ($user['role'] === 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: dashboard.php");
            }
            exit;
        } else {
            $msg = "Invalid password!";
        }
    } else {
        $msg = "No account found with that email!";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RailLink - Log In</title>
  <link rel="stylesheet" href="login.css">
</head>
<body>
<header>
  <div class="header-container">
    <div class="logo">
      <img src="logo.jpg" alt="RailLink Logo">
    </div>
    <nav>
     <ul>
<?php if(isset($_SESSION['user_id'])): ?>
    <li><a href="index.php">Home</a></li>
    <li><a href="trains.php">Book Ticket</a></li>
    <li><a href="my_bookings.php">My Bookings</a></li>
    <li><a href="dashboard.php">Dashboard</a></li>
    <li><a href="logout.php">Logout</a></li>
<?php else: ?>
    <li><a href="index.php">Home</a></li>
    <li><a href="trains.php">Book Ticket</a></li>
    <li><a href="my_bookings.php">My Bookings</a></li>
    <li><a href="login.php">Login</a></li>
    <li><a href="signup.php">Signup</a></li>
<?php endif; ?>
</ul>

    </nav>
  </div>
</header>

<main>
  <div class="auth-container">
    <h1 class="auth-title">Welcome Back</h1>

    <?php if ($msg): ?>
      <p style="color: red; text-align:center;"><?php echo $msg; ?></p>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label for="email">Email Address</label>
        <input type="email" name="email" id="email" placeholder="Enter your email address" required>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" name="password" id="password" placeholder="Enter your password" required>
      </div>

      <div class="remember-me">
        <input type="checkbox" id="rememberMe">
        <label for="rememberMe">Remember me</label>
      </div>

      <div class="forgot-password">
        <a href="#">Forgot password?</a>
      </div>

      <button type="submit" class="btn btn-primary">Log In</button>
    </form>

    <div class="auth-footer">
      Don't have an account? <a href="signup.php">Sign up here</a>
    </div>
  </div>
</main>
</body>
</html>
