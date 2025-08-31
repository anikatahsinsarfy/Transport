<?php
session_start();
require_once 'db.php';

// Make sure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get booking ID from URL ?booking_id=xx
if (!isset($_GET['booking_id'])) {
    echo "Booking ID not provided.";
    exit;
}

$booking_id = intval($_GET['booking_id']);
$user_id = $_SESSION['user_id'];

// Fetch booking details with train and user info
$stmt = $conn->prepare("
    SELECT b.*, t.train_name, t.train_number, t.from_station, t.to_station, 
           t.departure_time, t.arrival_time, u.full_name AS passenger_name, u.phone, u.email
    FROM bookings b
    JOIN trains t ON b.train_id = t.id
    JOIN users u ON b.user_id = u.id
    WHERE b.id = ? AND b.user_id = ?
");
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "No booking found.";
    exit;
}

$booking = $result->fetch_assoc();

// Generate PNR dynamically
$pnr = 'RLK-' . str_pad($booking['id'], 9, '0', STR_PAD_LEFT);

// Fare summary (can add service charges/taxes if needed)
$total_fare = $booking['fare'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>RailLink - Ticket Details</title>
<link rel="stylesheet" href="ticket.css">
</head>
<body>
<main>
    <div class="ticket-container">
        <!-- Ticket Header -->
        <div class="ticket-header">
            <h1 class="ticket-title">🚆 Train Ticket</h1>
            <p class="ticket-subtitle">Online Reservation System</p>
        </div>

        <!-- Ticket Details -->
        <div class="ticket-content">
            <div class="ticket-line">
                <div class="ticket-label">PNR No:</div>
                <div class="ticket-value"><?php echo $pnr; ?></div>
            </div>
            
            <div class="ticket-line">
                <div class="ticket-label">Status:</div>
                <div class="ticket-value"><?php echo ucfirst($booking['status']); ?></div>
            </div>
            
            <div class="ticket-line">
                <div class="ticket-label">Train:</div>
                <div class="ticket-value"><?php echo htmlspecialchars($booking['train_name'] . " (Train No: " . $booking['train_number'] . ")"); ?></div>
            </div>
            
            <div class="ticket-line">
                <div class="ticket-label">Route:</div>
                <div class="ticket-value"><?php echo htmlspecialchars($booking['from_station'] . " → " . $booking['to_station']); ?></div>
            </div>
            
            <div class="ticket-line">
                <div class="ticket-label">Date:</div>
                <div class="ticket-value"><?php echo date("d F Y", strtotime($booking['booking_date'])); ?></div>
            </div>
            
            <div class="ticket-line">
                <div class="ticket-label">Class:</div>
                <div class="ticket-value"><?php echo htmlspecialchars($booking['class_name'] ?? 'N/A'); ?></div>
            </div>
            
            <div class="ticket-line">
                <div class="ticket-label">Coach:</div>
                <div class="ticket-value"><?php echo htmlspecialchars($booking['coach']); ?></div>
            </div>
            
            <div class="ticket-line">
                <div class="ticket-label">Seat No:</div>
                <div class="ticket-value"><?php echo htmlspecialchars($booking['seat_number']); ?></div>
            </div>
        </div>

        <!-- Passenger Details -->
        <div class="passenger-section">
            <h2 class="section-title">Passenger Details</h2>
            
            <div class="passenger-detail">
                <div class="passenger-label">Name:</div>
                <div class="passenger-value"><?php echo htmlspecialchars($booking['passenger_name']); ?></div>
            </div>
            
            <div class="passenger-detail">
                <div class="passenger-label">Age:</div>
                <div class="passenger-value"><?php echo htmlspecialchars($booking['passenger_age']); ?></div>
            </div>
            
            <div class="passenger-detail">
                <div class="passenger-label">Gender:</div>
                <div class="passenger-value"><?php echo ucfirst($booking['passenger_gender']); ?></div>
            </div>
            
            <div class="passenger-detail">
                <div class="passenger-label">Seat Preference:</div>
                <div class="passenger-value"><?php echo ucfirst($booking['seat_preference']); ?></div>
            </div>
        </div>

        <!-- Fare Summary -->
        <div class="fare-section">
            <h2 class="section-title">Fare Summary</h2>
            
            <div class="fare-item">
                <div class="fare-label">Ticket Price:</div>
                <div class="fare-value"><?php echo number_format($total_fare, 2); ?> BDT</div>
            </div>
        </div>

        <!-- Travel Information -->
        <div class="ticket-content">
            <div class="ticket-line">
                <div class="ticket-label">Departure:</div>
                <div class="ticket-value"><?php echo date("h:i A", strtotime($booking['departure_time'])); ?></div>
            </div>
            
            <div class="ticket-line">
                <div class="ticket-label">Arrival:</div>
                <div class="ticket-value"><?php echo date("h:i A", strtotime($booking['arrival_time'])); ?></div>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="ticket-content">
            <div class="ticket-line">
                <div class="ticket-label">Contact:</div>
                <div class="ticket-value"><?php echo htmlspecialchars($booking['email']) . " | " . htmlspecialchars($booking['phone']); ?></div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <button class="btn btn-secondary" onclick="window.print()">🖨️ Print Ticket</button>
            <button class="btn btn-secondary" onclick="window.location.href='index.php'">Back To Home</button>
        </div>
    </div>
</main>
</body>
</html>
