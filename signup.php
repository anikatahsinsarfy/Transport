<?php
include 'db.php'; // your db.php must return $conn connection

$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['fullName']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    $confirmPassword = trim($_POST['confirmPassword']);

    // check password match
    if ($password !== $confirmPassword) {
        $msg = "Passwords do not match!";
    } else {
        // hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // check email already exist
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $msg = "Email already exists!";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, password) VALUES (?,?,?,?)");
            $stmt->bind_param("ssss", $fullName, $email, $phone, $hashedPassword);

            if ($stmt->execute()) {
                header("Location: login.php?success=1");
                exit;
            } else {
                $msg = "Something went wrong. Please try again!";
            }
            $stmt->close();
        }
        $check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RailLink - Sign Up</title>
  <link rel="stylesheet" href="signup.css">
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
    <h1 class="auth-title">Create Your Account</h1>

    <?php if ($msg): ?>
      <p style="color:red; text-align:center;"><?php echo $msg; ?></p>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label for="fullName">Full Name</label>
        <input type="text" name="fullName" id="fullName" placeholder="Enter your full name" required>
      </div>

      <div class="form-group">
        <label for="email">Email Address</label>
        <input type="email" name="email" id="email" placeholder="Enter your email address" required>
      </div>

      <div class="form-group">
        <label for="phone">Phone Number</label>
        <input type="tel" name="phone" id="phone" placeholder="Enter your phone number" required>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" name="password" id="password" placeholder="Create a strong password" required>
      </div>

      <div class="form-group">
        <label for="confirmPassword">Confirm Password</label>
        <input type="password" name="confirmPassword" id="confirmPassword" placeholder="Re-enter your password" required>
      </div>

      <div class="terms-checkbox">
        <input type="checkbox" id="terms" required>
        <label for="terms">I agree to the Terms and Conditions and Privacy Policy</label>
      </div>

      <button type="submit" class="btn btn-primary">Sign Up</button>
    </form>

    <div class="auth-footer">
      Already have an account? <a href="login.php">Log in here</a>
    </div>
  </div>
</main>
</body>
</html>
