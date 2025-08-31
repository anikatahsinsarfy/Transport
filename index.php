<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RailLink - Train Ticket Booking</title>
    <link rel="stylesheet" href="index.css">
    <style>
        /* Hero Section */
        .hero {
            text-align: center;
            padding: 120px 20px;
            background: url('hero-bg.jpg') center/cover no-repeat;
            color: white;
            position: relative;
        }

        .hero::after {
            content: '';
            position: absolute;
            inset: 0;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .hero h1 {
            font-size: 3rem;
            margin-bottom: 20px;
        }

        .hero p {
            font-size: 1.2rem;
            margin-bottom: 40px;
        }

        .btn-book {
            padding: 15px 30px;
            font-size: 1.2rem;
            background-color: #ffcc00;
            color: #333;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
            font-weight: bold;
        }

        .btn-book:hover {
            background-color: #e6b800;
        }

        /* Features Section */
        .features {
            padding: 80px 20px;
            background-color: #fff;
            text-align: center;
        }

        .features .section-title {
            font-size: 2rem;
            margin-bottom: 50px;
            color: #1e90ff;
        }

        .feature-cards {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 30px;
        }

        .feature-card {
            background-color: #f4f6f9;
            padding: 30px;
            border-radius: 12px;
            width: 250px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }

        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .feature-card h3 {
            font-size: 1.2rem;
            margin-bottom: 10px;
        }

        .feature-card p {
            font-size: 0.95rem;
        }

      
    </style>
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

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-content">
            <h1>Travel Anywhere, Anytime</h1>
            <p>Experience seamless train travel across India with RailLink. Book your tickets quickly and securely.</p>
            <a href="trains.php" class="btn-book">Book a Ticket</a>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <h2 class="section-title">Why Choose RailLink?</h2>
        <div class="feature-cards">
            <div class="feature-card">
                <div class="feature-icon">⚡</div>
                <h3>Fast Booking</h3>
                <p>Book your train tickets in just a few clicks with our user-friendly interface.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">🔒</div>
                <h3>Secure Payments</h3>
                <p>Your transactions are protected with advanced security measures.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">🕒</div>
                <h3>24/7 Support</h3>
                <p>Our dedicated support team is available round the clock to assist you.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">✈️</div>
                <h3>Wide Network</h3>
                <p>Access trains across thousands of stations nationwide.</p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <p>&copy; 2025 RailLink. All rights reserved.</p>
    </footer>
</body>
</html>
