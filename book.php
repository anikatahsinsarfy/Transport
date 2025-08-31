<?php
session_start();
require_once 'db.php';

// Check user login
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'] ?? '';
$user_email = $_SESSION['email'] ?? '';
$user_phone = $_SESSION['phone'] ?? '';
$user_address = $_SESSION['address'] ?? '';

// Get train ID
$train_id = $_GET['id'] ?? 0;

// Fetch train info and classes
$train = null;
$classes = [];
if($train_id){
    $stmt = $conn->prepare("SELECT t.*, c.class_name, c.fare, c.seats_available 
                            FROM trains t 
                            JOIN classes c ON t.id = c.train_id 
                            WHERE t.id=?");
    $stmt->bind_param("i",$train_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while($row = $res->fetch_assoc()){
        $classes[] = $row;
        if(!$train) $train = $row;
    }
}

// Fetch booked seats
$bookedSeats = [];
if($train_id){
    $stmt2 = $conn->prepare("SELECT seat_number FROM bookings WHERE train_id=? AND status='booked'");
    $stmt2->bind_param("i",$train_id);
    $stmt2->execute();
    $res = $stmt2->get_result();
    while($row=$res->fetch_assoc()){
        $bookedSeats[] = $row['seat_number'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>RailLink - Seat Selection</title>
<link rel="stylesheet" href="book.css">
<style>
.seat-grid { margin-top: 20px; }
.seat-row { display: flex; gap: 10px; margin-bottom: 5px; }
.seat { padding: 10px; border-radius: 4px; cursor: pointer; text-align: center; }
.available { background-color: #d4edda; }
.selected { background-color: #007bff; color: white; }
.booked { background-color: #f8d7da; cursor: not-allowed; }
.coach-btn.selected { background-color: #007bff; color: white; }
.passenger-card { border: 1px solid #ccc; padding: 10px; margin-top: 10px; border-radius: 5px; }
.summary-item { display: flex; justify-content: space-between; padding: 5px 0; }
.total-price { font-weight: bold; margin-top: 10px; }
.btn { padding: 8px 15px; border-radius: 5px; cursor: pointer; border: none; }
.btn-add { background-color: #28a745; color: white; margin-top: 10px; }
.btn-remove { background-color: #dc3545; color: white; margin-left: 10px; }
</style>
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
<?php if($train): ?>
<section class="train-details">
    <div class="train-header">
        <h1><?= htmlspecialchars($train['train_name']) ?> (<?= $train['train_number'] ?>)</h1>
        <button onclick="window.location.href='trains.php'">Cancel</button>
    </div>
    <div>From: <?= htmlspecialchars($train['from_station']) ?> | To: <?= htmlspecialchars($train['to_station']) ?></div>
    <div>Departure: <?= date('h:i A', strtotime($train['departure_time'])) ?> | Arrival: <?= date('h:i A', strtotime($train['arrival_time'])) ?></div>
</section>

<form id="bookingForm" action="payment.php" method="POST" onsubmit="return validateForm()">
<input type="hidden" name="train_id" value="<?= $train['id'] ?>">
<input type="hidden" name="train_name" id="train_name" value="<?= htmlspecialchars($train['train_name']) ?>">
<input type="hidden" name="train_class" id="train_class" value="<?= $classes[0]['class_name'] ?>">
<input type="hidden" name="coach" id="coach" value="<?= $classes[0]['class_name'] ?>">
<input type="hidden" name="selected_seats" id="selected_seats">
<input type="hidden" name="fare_per_seat" id="fare_per_seat" value="<?= $classes[0]['fare'] ?>">

<section class="coach-selection">
<h3>Select Class / Coach</h3>
<div class="coach-list">
<?php foreach($classes as $i=>$c): ?>
    <button type="button" class="coach-btn <?= $i==0?'selected':'' ?>" 
        data-class="<?= $c['class_name'] ?>" 
        data-fare="<?= $c['fare'] ?>" 
        data-seats="<?= $c['seats_available'] ?>">
        <?= $c['class_name'] ?>
    </button>
<?php endforeach; ?>
</div>
<div class="coach-status"><?= $classes[0]['class_name'] ?> Selected</div>
</section>

<section class="seat-selection">
<h3>Select Seats (<span class="selected-count">0</span> Selected)</h3>
<div class="seat-grid"></div>
</section>

<section class="passenger-form">
<h3>Passenger Information</h3>
<div class="passengers-section"></div>
</section>

<section class="payment-summary">
<h3>Payment Summary</h3>
<div class="summary-item"><span>Fare per Seat</span><span id="farePerSeat">₹<?= $classes[0]['fare'] ?></span></div>
<div class="summary-item"><span>Number of Seats</span><span id="seatCount">0</span></div>
<div class="summary-item"><span>Taxes & Charges (10%)</span><span id="taxes">₹0</span></div>
<div class="summary-item"><span>Total Fare</span><span id="totalAmount">₹0</span></div>
<div class="total-price" id="totalPrice">Total: ₹0</div>
<button type="submit">Proceed to Payment</button>
</section>

</form>

<script>
const bookedSeats = <?= json_encode($bookedSeats) ?>;
let selectedSeats = new Set();
const userInfo = {
    name: "<?= addslashes($user_name) ?>",
    email: "<?= addslashes($user_email) ?>",
    phone: "<?= addslashes($user_phone) ?>",
    address: "<?= addslashes($user_address) ?>"
};

function generateSeatGrid(className, seatsCount){
    const seatGrid = document.querySelector('.seat-grid');
    seatGrid.innerHTML = '';
    let rowDiv;
    for(let i=1;i<=seatsCount;i++){
        const seatCode = className+'-'+i;
        const cls = bookedSeats.includes(seatCode) ? 'seat booked' : 'seat available';
        if(i % 4 === 1) rowDiv = document.createElement('div'), rowDiv.className='seat-row';
        const seatDiv = document.createElement('div');
        seatDiv.className = cls;
        seatDiv.dataset.seat = seatCode;
        seatDiv.textContent = seatCode;
        rowDiv.appendChild(seatDiv);
        if(i % 4 === 0 || i === seatsCount) seatGrid.appendChild(rowDiv);
    }
    bindSeatClick();
}

function bindSeatClick(){
    document.querySelectorAll('.seat.available').forEach(seat => {
        seat.onclick = function(){
            const code = this.dataset.seat;
            const passengersSection = document.querySelector('.passengers-section');

            if(selectedSeats.has(code)){
                selectedSeats.delete(code);
                this.classList.remove('selected');
                // Remove corresponding passenger form
                const formDiv = document.getElementById('passenger-'+code);
                if(formDiv) passengersSection.removeChild(formDiv);
            } else {
                selectedSeats.add(code);
                this.classList.add('selected');

                // Create passenger form for this seat
                if(!document.getElementById('passenger-'+code)){
                    const formDiv = document.createElement('div');
                    formDiv.className = 'passenger-card';
                    formDiv.id = 'passenger-' + code;
                    formDiv.innerHTML = `
                        <h4>Seat: ${code}</h4>
                        <label>Name: <input type="text" name="passenger_name[${code}]" value="${userInfo.name}" required></label><br>
                        <label>Age: <input type="number" name="passenger_age[${code}]" value="" required></label><br>
                        <label>Gender:
                            <select name="passenger_gender[${code}]" required>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </label><br>
                        <button type="button" class="btn btn-remove" onclick="removePassenger('${code}')">Remove Passenger</button>
                    `;
                    passengersSection.appendChild(formDiv);
                }
            }

            document.querySelector('.selected-count').textContent = selectedSeats.size;
            document.getElementById('selected_seats').value = Array.from(selectedSeats).join(',');
            updatePaymentSummary();
        }
    });
}

function removePassenger(seatCode){
    selectedSeats.delete(seatCode);
    const seatDiv = document.querySelector(`.seat[data-seat='${seatCode}']`);
    if(seatDiv) seatDiv.classList.remove('selected');
    const formDiv = document.getElementById('passenger-'+seatCode);
    if(formDiv) formDiv.remove();

    document.querySelector('.selected-count').textContent = selectedSeats.size;
    document.getElementById('selected_seats').value = Array.from(selectedSeats).join(',');
    updatePaymentSummary();
}

function updatePaymentSummary(){
    const farePerSeat = parseInt(document.getElementById('fare_per_seat').value);
    const count = selectedSeats.size;
    const subtotal = farePerSeat * count;
    const taxes = Math.round(subtotal * 0.1);
    const total = subtotal + taxes;

    document.getElementById('farePerSeat').textContent = '₹' + farePerSeat;
    document.getElementById('seatCount').textContent = count;
    document.getElementById('taxes').textContent = '₹' + taxes;
    document.getElementById('totalAmount').textContent = '₹' + total;
    document.getElementById('totalPrice').textContent = 'Total: ₹' + total;
}

// Coach buttons
document.querySelectorAll('.coach-btn').forEach(btn => {
    btn.onclick = function(){
        document.querySelectorAll('.coach-btn').forEach(b => b.classList.remove('selected'));
        this.classList.add('selected');

        const className = this.dataset.class;
        const fare = parseInt(this.dataset.fare);
        const seatsCount = parseInt(this.dataset.seats);

        document.querySelector('.coach-status').textContent = className + ' Selected';
        document.getElementById('train_class').value = className;
        document.getElementById('coach').value = className;
        document.getElementById('fare_per_seat').value = fare;

        selectedSeats.clear();
        document.getElementById('selected_seats').value = '';
        document.querySelector('.passengers-section').innerHTML = '';
        generateSeatGrid(className, seatsCount);
        updatePaymentSummary();
    }
});

// Initial load
generateSeatGrid('<?= $classes[0]['class_name'] ?>', <?= $classes[0]['seats_available'] ?>);

// Validation
function validateForm(){
    if(selectedSeats.size === 0){
        alert('Please select at least one seat to proceed.');
        return false;
    }
    return true;
}
</script>




<?php else: ?>
<p>Train not found!</p>
<?php endif; ?>
</main>
</body>
</html>
