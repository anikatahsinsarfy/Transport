<?php
session_start();
require_once 'db.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get booking_id from URL
$booking_id = $_GET['booking_id'] ?? null;
if (!$booking_id) {
    echo "Booking ID not provided.";
    exit;
}

// Fetch booking info with passenger details
$stmt = $conn->prepare("
    SELECT b.*, t.train_name
    FROM bookings b
    JOIN trains t ON b.train_id = t.id
    WHERE b.id = ? AND b.user_id = ?
");
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (!$bookings) {
    echo "No booking found for this ID.";
    exit;
}

// Fetch payment info
$stmt2 = $conn->prepare("
    SELECT * FROM payments 
    WHERE user_id = ? AND train_id = ? 
    ORDER BY created_at DESC LIMIT 1
");
$train_id = $bookings[0]['train_id'];
$stmt2->bind_param("ii", $user_id, $train_id);
$stmt2->execute();
$payment = $stmt2->get_result()->fetch_assoc();
$stmt2->close();

// Generate PNR
$pnr = 'RLK-' . str_pad($payment['id'], 9, '0', STR_PAD_LEFT);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>RailLink - Booking Confirmed</title>
<link rel="stylesheet" href="booking_successful.css">
</head>
<body>
<header>
<div class="header-container">
    <div class="logo"><img src="logo.jpg" alt="RailLink Logo"></div>
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
<div class="success-container">
    <div class="success-icon">✓</div>
    <h1 class="success-title">Booking Confirmed Successfully!</h1>
    <p class="success-subtitle">Your train ticket has been successfully booked. You can download your ticket below or check your dashboard for more details.</p>
    
    <div class="ticket-card">
        <div class="ticket-header">
            <h2 class="ticket-title">Ticket Details</h2>
            <div class="ticket-id">PNR: <?= htmlspecialchars($pnr) ?></div>
        </div>
        
        <div class="ticket-details">
            <?php foreach ($bookings as $b): ?>
                <div class="detail-item">
                    <div class="detail-icon train-icon">🚂</div>
                    <div class="detail-content">
                        <div class="detail-label">Train</div>
                        <div class="detail-value"><?= htmlspecialchars($b['train_name']); ?> (<?= htmlspecialchars($b['class_name']); ?>)</div>
                    </div>
                </div>

                <div class="detail-item">
                    <div class="detail-icon seat-icon">💺</div>
                    <div class="detail-content">
                        <div class="detail-label">Seat</div>
                        <div class="detail-value"><?= htmlspecialchars($b['seat_number'] . ' (' . $b['fare'] . '₹)'); ?></div>
                    </div>
                </div>

                <div class="detail-item">
                    <div class="detail-icon person-icon">👤</div>
                    <div class="detail-content">
                        <div class="detail-label">Passenger</div>
                        <div class="detail-value">
                            <?= htmlspecialchars($b['passenger_name'] ?: 'N/A') ?>, 
                            <?= htmlspecialchars($b['passenger_gender'] ?: 'N/A') ?>, 
                            Age <?= htmlspecialchars($b['passenger_age'] ?: 'N/A') ?>
                        </div>
                    </div>
                </div>

                <div class="detail-item">
                    <div class="detail-icon seat-pref-icon">🪑</div>
                    <div class="detail-content">
                        <div class="detail-label">Seat Preference</div>
                        <div class="detail-value"><?= htmlspecialchars($b['seat_preference'] ?: 'None') ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="action-buttons">
        <button class="btn btn-secondary" onclick="printTicket()">🖨️ Print Ticket</button>
        <button class="btn btn-outline" onclick="viewDashboard()">👤 View in Dashboard</button>
    </div>
    
    <a href="dashboard.php" class="dashboard-link">Go to Dashboard</a>
</div>
</main>

<script>
function printTicket() {
    window.location.href = 'ticket.php?booking_id=<?= $booking_id ?>';
}

function viewDashboard() {
    window.location.href = 'dashboard.php';
}
</script>
</body>
</html>
