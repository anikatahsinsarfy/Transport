<?php
session_start();
require_once 'db.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

if(!isset($_GET['id'])){
    header("Location: my_bookings.php");
    exit;
}

$booking_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Update booking status to cancelled
$stmt = $conn->prepare("UPDATE bookings SET status='cancelled' WHERE id=? AND user_id=?");
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$stmt->close();

// Redirect back
header("Location: my_bookings.php");
exit;
?>
