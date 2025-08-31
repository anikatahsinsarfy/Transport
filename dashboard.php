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
$stmt = $conn->prepare("SELECT full_name,email,phone,address,created_at,image FROM users WHERE id=?");
$stmt->bind_param("i",$user_id);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();

// Fetch recent bookings
$stmt = $conn->prepare("SELECT b.id as booking_id, t.train_name, t.train_number, t.from_station, t.to_station, t.departure_time, t.arrival_time, b.status 
                        FROM bookings b 
                        JOIN trains t ON b.train_id = t.id 
                        WHERE b.user_id=? 
                        ORDER BY b.id DESC LIMIT 5");
$stmt->bind_param("i",$user_id);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch recent activities (last 5 actions)
$stmt = $conn->prepare("SELECT description, created_at FROM activities WHERE user_id=? ORDER BY id DESC LIMIT 5");
$stmt->bind_param("i",$user_id);
$stmt->execute();
$activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Flash message handling
$flash_message = '';
if(isset($_SESSION['flash_success'])){
    $flash_message = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>RailLink - Dashboard</title>
<link rel="stylesheet" href="dashboard.css">
<style>
/* Flash message style */
#flashMessage {
    position: fixed;
    top: 20px;
    right: 20px;
    background: #4CAF50;
    color: #fff;
    padding: 10px 20px;
    border-radius: 5px;
    display: none;
    z-index: 999;
}
</style>
</head>
<body>
<header>
    <div class="header-container">
        <div class="logo">
            <img src="logo.jpg" alt="RailLink Logo">
        </div>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="trains.php">Book Ticket</a></li>
                <li><a href="my_bookings.php">My Bookings</a></li>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="signup.php">Signup</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>

<!-- Flash message -->
<?php if($flash_message): ?>
    <div id="flashMessage"><?= htmlspecialchars($flash_message) ?></div>
<?php endif; ?>

<main>
<div class="dashboard-container">

    <!-- User Profile Card -->
    <div class="card profile-card">
        <div class="avatar">
            <img src="<?= !empty($user['image']) ? htmlspecialchars($user['image']) : 'uploads/default.jpg' ?>" 
                 alt="Profile Picture" style="width:100px; height:100px; border-radius:50%;">
        </div>
        <div class="user-info">
            <h2 class="user-name"><?= htmlspecialchars($user['full_name']) ?></h2>
            <p class="user-email">Email: <?= htmlspecialchars($user['email']) ?></p>
            <p class="user-phone">Phone: <?= htmlspecialchars($user['phone']) ?></p>
            <p class="user-address">Address: <?= htmlspecialchars($user['address']) ?></p>
            <p class="user-date">Member Since: <?= date("d M Y", strtotime($user['created_at'])) ?></p>
            <a href="edit_profile.php" class="edit-profile-btn">Edit Profile</a>
        </div>
    </div>

    <!-- Recent Bookings Card -->
    <div class="card bookings-card">
        <h2 class="card-title">Recent Bookings</h2>
        <?php if(!empty($bookings)): ?>
            <?php foreach($bookings as $b): ?>
            <div class="booking-item">
                <div class="booking-icon">🚂</div>
                <div class="booking-details">
                    <div class="booking-train"><?= htmlspecialchars($b['train_name']) ?> (<?= htmlspecialchars($b['train_number']) ?>)</div>
                    <div class="booking-route"><?= htmlspecialchars($b['from_station']) ?> to <?= htmlspecialchars($b['to_station']) ?></div>
                    <div class="booking-date"><?= date("d M Y • h:i A", strtotime($b['departure_time'])) ?></div>
                </div>
                <div class="booking-status <?= $b['status']=='booked'?'status-in-progress':'status-completed' ?>"><?= ucfirst($b['status']) ?></div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No recent bookings found.</p>
        <?php endif; ?>
    </div>

    <!-- Recent Activities Card -->
    <div class="card activities-card">
        <h2 class="card-title">Recent Activity</h2>
        <?php if(!empty($activities)): ?>
            <?php foreach($activities as $a): ?>
            <div class="activity-item">
                <div class="activity-icon">🎫</div>
                <div class="activity-content">
                    <div class="activity-title"><?= htmlspecialchars($a['description']) ?></div>
                    <div class="activity-time"><?= date("d M Y • h:i A", strtotime($a['created_at'])) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No recent activity found.</p>
        <?php endif; ?>
    </div>

    <!-- Quick Links Card -->
    <div class="card quick-links-card">
        <h2 class="card-title">Quick Links</h2>
        <a href="my_bookings.php" class="quick-link">
            <div class="quick-link-icon">📋</div>
            <span class="quick-link-text">My Bookings</span>
        </a>
        <a href="trains.php" class="quick-link">
            <div class="quick-link-icon">🎫</div>
            <span class="quick-link-text">Book New Ticket</span>
        </a>
        <a href="edit_profile.php" class="quick-link">
            <div class="quick-link-icon">✏️</div>
            <span class="quick-link-text">Edit Profile</span>
        </a>
    </div>

</div>
</main>

<script>
// Show flash message for 0.5 sec
window.addEventListener('DOMContentLoaded', (event) => {
    const flash = document.getElementById('flashMessage');
    if(flash){
        flash.style.display = 'block';
        setTimeout(() => { flash.style.display = 'none'; }, 500);
    }
});
</script>
</body>
</html>
