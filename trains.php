<?php
require_once 'db.php'; // আগে তৈরি করা db.php ফাইল ইনক্লুড করা হচ্ছে

// ডাটাবেস কানেকশন চেক করা
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// সার্চ ফর্ম প্রসেসিং
$searchResults = [];
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$date = $_GET['date'] ?? '';

// SQL Base Query
$sql = "SELECT t.*, c.class_name, c.fare, c.seats_available 
        FROM trains t
        JOIN classes c ON t.id = c.train_id
        WHERE 1 "; // সবসময় true → পরে condition add হবে

$params = [];
$types = "";

// যদি from দেওয়া থাকে
if (!empty($from)) {
    $sql .= " AND t.from_station LIKE ? ";
    $params[] = "%{$from}%";
    $types .= "s";
}

// যদি to দেওয়া থাকে
if (!empty($to)) {
    $sql .= " AND t.to_station LIKE ? ";
    $params[] = "%{$to}%";
    $types .= "s";
}

$sql .= " ORDER BY t.departure_time"; // ডিফল্ট sort

$stmt = $conn->prepare($sql);

// যদি parameter থাকে তাহলে bind করো
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// ডাটা Array তে রাখো
while ($row = $result->fetch_assoc()) {
    $searchResults[] = $row;
}

// সর্টিং প্যারামিটার
$sortBy = $_GET['sort'] ?? 'departure';
switch ($sortBy) {
    case 'fare':
        usort($searchResults, function($a, $b) {
            return $a['fare'] <=> $b['fare'];
        });
        break;
    case 'duration':
        usort($searchResults, function($a, $b) {
            preg_match('/(\d+)h (\d+)m/', $a['duration'], $matchesA);
            preg_match('/(\d+)h (\d+)m/', $b['duration'], $matchesB);
            $totalMinA = intval($matchesA[1]) * 60 + intval($matchesA[2]);
            $totalMinB = intval($matchesB[1]) * 60 + intval($matchesB[2]);
            return $totalMinA <=> $totalMinB;
        });
        break;
    default:
        usort($searchResults, function($a, $b) {
            return strtotime($a['departure_time']) <=> strtotime($b['departure_time']);
        });
}

// পেজিনেশন
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$itemsPerPage = 6;
$totalItems = count($searchResults);
$totalPages = max(1, ceil($totalItems / $itemsPerPage)); 
$startIndex = ($page - 1) * $itemsPerPage;
$currentTrains = array_slice($searchResults, $startIndex, $itemsPerPage);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RailLink - Train Search Results</title>
    <link rel="stylesheet" href="trains.css">
</head>
<body>
    <!-- Header -->
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

    <!-- Main Content -->
    <main>
        <!-- Search Section -->
        <section class="search-section">
            <form class="search-form" id="searchForm">
                <div class="form-group">
                    <label for="from">From</label>
                    <input type="text" id="from" name="from" placeholder="Enter departure station" value="<?= htmlspecialchars($from) ?>">
                </div>
                
                <div class="form-group">
                    <label for="to">To</label>
                    <input type="text" id="to" name="to" placeholder="Enter destination station" value="<?= htmlspecialchars($to) ?>">
                </div>
                
                <div class="form-group">
                    <label for="journeyDate">Journey Date</label>
                    <input type="date" id="journeyDate" name="date" value="<?= htmlspecialchars($date) ?>">
                </div>
                
                <button type="submit" class="search-btn">Search Trains</button>
            </form>
        </section>

        <!-- Results Section -->
        <section class="results-section">
            <div class="results-header">
                <div class="results-count">
                    Showing <?= count($currentTrains) ?> of <?= $totalItems ?> trains
                </div>
                <div class="sort-options">
                    <select id="sortBy" onchange="sortTrains()">
                        <option value="departure" <?= $sortBy == 'departure' ? 'selected' : '' ?>>Sort by Departure Time</option>
                        <option value="fare" <?= $sortBy == 'fare' ? 'selected' : '' ?>>Sort by Fare</option>
                        <option value="duration" <?= $sortBy == 'duration' ? 'selected' : '' ?>>Sort by Duration</option>
                    </select>
                </div>
            </div>

            <div class="train-grid" id="trainGrid">
                <?php foreach ($currentTrains as $train): ?>
                <div class="train-card">
                     <img src="images/<?= $train['image'] ?? 'default.jpg' ?>" alt="<?= $train['train_name'] ?>">
                    <div class="train-info">
                        <div class="train-name"><?= $train['train_name'] ?></div>
                        <div class="train-number"><?= $train['train_number'] ?></div>
                        
                        <div class="timing">
                            <div class="time-item">
                                <div class="time-label">DEPARTURE</div>
                                <div class="time-value"><?= date('h:i A', strtotime($train['departure_time'])) ?></div>
                            </div>
                            <div class="time-item">
                                <div class="time-label">ARRIVAL</div>
                                <div class="time-value"><?= date('h:i A', strtotime($train['arrival_time'])) ?></div>
                            </div>
                            <div class="time-item">
                                <div class="time-label">DURATION</div>
                                <div class="time-value"><?= $train['duration'] ?></div>
                            </div>
                        </div>
                        
                        <div class="classes">
                            <div class="class-item">
                                <div class="class-type"><?= $train['class_name'] ?></div>
                                <div class="class-fare">₹<?= number_format($train['fare']) ?></div>
                                <div class="seat-info">Available: <?= $train['seats_available'] ?></div>
                            </div>
                        </div>
                        
                        <button class="book-btn" onclick="window.location.href='book.php?id=<?= $train['id'] ?>'">Book Now</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <div class="pagination" id="pagination">
                <?php if ($page > 1): ?>
                <button onclick="goToPage(<?= $page - 1 ?>)">Previous</button>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <button <?= $i == $page ? 'class="active"' : '' ?> onclick="goToPage(<?= $i ?>)">
                    <?= $i ?>
                </button>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                <button onclick="goToPage(<?= $page + 1 ?>)">Next</button>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <script>
        function goToPage(page) {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('page', page);
            window.location.href = '?' + urlParams.toString();
        }
        
        function sortTrains() {
            const sortBy = document.getElementById('sortBy').value;
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('sort', sortBy);
            window.location.href = '?' + urlParams.toString();
        }
    </script>
</body>
</html>
