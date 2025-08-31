<?php
session_start();
require_once 'db.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'] ?? '';
$user_email = $_SESSION['email'] ?? '';
$user_phone = $_SESSION['phone'] ?? '';
$user_address = $_SESSION['address'] ?? '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['pay_now'])) {
    $train_id = $_POST['train_id'] ?? '';
    $train_name = $_POST['train_name'] ?? '';
    $train_class = $_POST['train_class'] ?? '';
    $coach = $_POST['coach'] ?? '';
    $selected_seats = $_POST['selected_seats'] ?? '';
    $fare_per_seat = $_POST['fare_per_seat'] ?? 0;
    $payment_method = $_POST['payment_method'] ?? '';

    $selected_seats_array = array_filter(explode(',', $selected_seats));
    $seat_count = count($selected_seats_array);
    $subtotal = $fare_per_seat * $seat_count;
    $taxes = round($subtotal * 0.1);
    $total_amount = $subtotal + $taxes;

    // Insert into bookings table
    $first_booking_id = null;
    foreach ($selected_seats_array as $index => $seat) {
        // Optional: if you want passenger info inputs, adjust here
        $passenger_name = $user_name;
        $passenger_age = 0;
        $passenger_gender = 'none';
        $seat_preference = 'none';

        $stmt = $conn->prepare("INSERT INTO bookings 
            (user_id, train_id, class_name, coach, seat_number, passenger_name, passenger_age, passenger_gender, seat_preference, fare, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'booked')");
        $stmt->bind_param("iisssiissd",
            $user_id,
            $train_id,
            $train_class,
            $coach,
            $seat,
            $passenger_name,
            $passenger_age,
            $passenger_gender,
            $seat_preference,
            $fare_per_seat
        );
        $stmt->execute();
        if($index === 0) $first_booking_id = $conn->insert_id;
        $stmt->close();
    }

    // Insert into payments table
    $stmt2 = $conn->prepare("INSERT INTO payments (user_id, train_id, amount, payment_method) VALUES (?, ?, ?, ?)");
    $stmt2->bind_param("iids", $user_id, $train_id, $total_amount, $payment_method);
    $stmt2->execute();
    $stmt2->close();

    // Redirect to booking success page
    header("Location: booking_successful.php?booking_id=".$first_booking_id);
    exit();
}

// If page accessed directly, redirect back
if(!isset($_POST['train_id'])){
    header("Location: trains.php");
    exit();
}

// For displaying summary
$train_id = $_POST['train_id'];
$train_name = $_POST['train_name'];
$train_class = $_POST['train_class'];
$coach = $_POST['coach'];
$selected_seats = $_POST['selected_seats'];
$fare_per_seat = $_POST['fare_per_seat'];

$selected_seats_array = array_filter(explode(',', $selected_seats));
$seat_count = count($selected_seats_array);
$subtotal = $fare_per_seat * $seat_count;
$taxes = round($subtotal * 0.1);
$total_amount = $subtotal + $taxes;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>RailLink - Payment</title>
<link rel="stylesheet" href="payment.css">
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
<h1>Complete Your Payment</h1>
<form method="POST">
<input type="hidden" name="train_id" value="<?= htmlspecialchars($train_id) ?>">
<input type="hidden" name="train_name" value="<?= htmlspecialchars($train_name) ?>">
<input type="hidden" name="train_class" value="<?= htmlspecialchars($train_class) ?>">
<input type="hidden" name="coach" value="<?= htmlspecialchars($coach) ?>">
<input type="hidden" name="selected_seats" value="<?= htmlspecialchars($selected_seats) ?>">
<input type="hidden" name="fare_per_seat" value="<?= htmlspecialchars($fare_per_seat) ?>">
<!-- Bill To -->
    <div class="info-card">
        <h2 class="info-title">Bill To</h2>
        <div class="bill-to">
            <div class="bill-item"><span>Name:</span><span><?= htmlspecialchars($user_name) ?></span></div>
            <div class="bill-item"><span>Email:</span><span><?= htmlspecialchars($user_email) ?></span></div>
            <div class="bill-item"><span>Phone:</span><span><?= htmlspecialchars($user_phone ?: 'N/A') ?></span></div>
<div class="bill-item"><span>Address:</span><span><?= htmlspecialchars($user_address ?: 'N/A') ?></span></div>

        </div>
    </div>

<div class="payment-summary">
    <h3>Payment Summary</h3>
    <div class="summary-item"><span>Fare per Seat</span><span>₹<?= $fare_per_seat ?></span></div>
    <div class="summary-item"><span>Number of Seats</span><span><?= $seat_count ?></span></div>
    <div class="summary-item"><span>Taxes & Charges (10%)</span><span>₹<?= $taxes ?></span></div>
    <div class="summary-item"><span>Total Fare</span><span>₹<?= $total_amount ?></span></div>
    <div class="total-price">Total: ₹<?= $total_amount ?></div>
</div>

<div class="payment-methods">
    <h3>Choose Payment Method</h3>
    <?php foreach(['Bkash','Rocket','Nagad','Cash on Station'] as $method): ?>
        <div><input type="radio" name="payment_method" value="<?= $method ?>" required> <?= $method ?></div>
    <?php endforeach; ?>
</div>

<button type="submit" name="pay_now">Pay Now</button>
</form>
</main>
</body>
</html>
