<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle cancel booking request
if(isset($_POST['cancel_booking'])){
    $cancel_id = intval($_POST['cancel_booking']);
    $stmt = $conn->prepare("UPDATE bookings SET status='cancelled' WHERE id=? AND user_id=?");
    $stmt->bind_param("ii",$cancel_id,$user_id);
    $stmt->execute();
    $stmt->close();
    $_SESSION['flash_success'] = "Booking cancelled successfully!";
    header("Location: my_bookings.php");
    exit();
}

// Fetch user bookings
$stmt = $conn->prepare("SELECT b.*, t.train_name, t.train_number, t.from_station, t.to_station, t.departure_time, t.arrival_time 
                        FROM bookings b 
                        JOIN trains t ON b.train_id = t.id 
                        WHERE b.user_id=? ORDER BY b.booking_date DESC");
$stmt->bind_param("i",$user_id);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>RailLink - My Bookings</title>
<link rel="stylesheet" href="my_bookings.css">
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
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </div>
</header>

<main>
<h1 class="page-title">My Bookings</h1>

<?php if(isset($_SESSION['flash_success'])): ?>
    <div class="flash-success" id="flash-msg"><?= $_SESSION['flash_success'] ?></div>
    <script>
        setTimeout(()=>{ document.getElementById('flash-msg').style.display='none'; }, 1500);
    </script>
<?php unset($_SESSION['flash_success']); endif; ?>

<div class="bookings-list">
    <?php if(!empty($bookings)): ?>
        <?php foreach($bookings as $b): ?>
        <div class="booking-card">
            <div class="booking-header">
                <div class="booking-train"><?= htmlspecialchars($b['train_name'] . " (" . $b['train_number'] . ")") ?></div>
                <div class="booking-status <?= $b['status']=='booked'?'status-confirmed':'status-cancelled' ?>">
                    <?= ucfirst($b['status']) ?>
                </div>
            </div>
            <div class="booking-details">
                <div class="detail-item">
                    <div class="detail-icon">🚂</div>
                    <div>
                        <div class="detail-label">Route</div>
                        <div class="detail-value"><?= htmlspecialchars($b['from_station'] . " → " . $b['to_station']) ?></div>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-icon">📅</div>
                    <div>
                        <div class="detail-label">Date</div>
                        <div class="detail-value"><?= date("d F Y", strtotime($b['booking_date'])) ?></div>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-icon">🕐</div>
                    <div>
                        <div class="detail-label">Departure</div>
                        <div class="detail-value"><?= date("h:i A", strtotime($b['departure_time'])) ?></div>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-icon">💺</div>
                    <div>
                        <div class="detail-label">Coach / Seat</div>
                        <div class="detail-value"><?= htmlspecialchars($b['coach'] . " / " . $b['seat_number']) ?></div>
                    </div>
                </div>
            </div>
            <div class="booking-actions">
                <a href="ticket.php?booking_id=<?= $b['id'] ?>" class="action-btn btn-primary">View Ticket</a>
                <?php if($b['status']=='booked'): ?>
                    <form method="post" style="display:inline;">
                        <button type="submit" name="cancel_booking" value="<?= $b['id'] ?>" class="action-btn btn-danger">
                            Cancel Ticket
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No bookings found.</p>
    <?php endif; ?>
</div>
</main>
</body>
</html>
