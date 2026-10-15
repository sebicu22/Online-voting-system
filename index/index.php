<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Voting Platform</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

    <link rel="stylesheet" href="style.css">

</head>
<body>

    <!-- nav bar -->

    <nav class="menu">
        <h1>
            E-vote
        </h1>

        <button id="toggleList"><i class="fa fa-bars" style="font-size:24px; color: white;" ></i></i></button>
        <ul id="responsiveList">
            <li><a href="index.php">Home</a></li>
            <li><a href="joinus.php">Join Us</a></li>
            <li><a href="contact.php">Contact Us</a></li>
            <li><button><a href="../login/login.php">Login</a></button></li>
        </ul>
    </nav>

    <!-- First section -->

    <section class = "intro">
    <div class="intro-content">
            <h1>Secure Online Voting Platform</h1>
            <p>Conduct elections with transparency and integrity</p>
            <button style="background: #3498db; color: white; padding: 1rem 2rem; border: none; border-radius: 5px; cursor: pointer;">Start Voting</button>
        </div>
    </section>

    <!-- Features Section  -->

    <section class = "features">
    
    <div class="card">
            <i class="fa fa-lock" aria-hidden="true"></i>
            <h3>End-to-End Encryption</h3>
            <p>Secure your votes with military-grade encryption</p>
        </div>
        
        <div class="card">
            <i class="fas fa-ballot" aria-hidden="true"></i>
            <h3>Transparent Results</h3>
            <p>Real-time results with audit trails</p>
        </div>
        
        <div class="card">
            <div class = "device">
            <i class="fa fa-mobile" aria-hidden="true"></i>
            <i class="fa fa-tablet" aria-hidden="true"></i>
            </div>
            <h3>Mobile-Optimized</h3>
            <p>Vote from any device</p>
        </div>
    </section>
    

    <!-- footer bar -->

    <footer class="footer">
    <div class="container">
        <div class="row">
            <!-- About Section -->
            <div class="col-md-3">
                <h4 class="footer-title">About Us</h4>
                <p>Secure, transparent, and accessible voting solutions for organizations worldwide.</p>
                <div class="social-links">
                    <a href="#" class="social-icon"><i class="fa fa-facebook"></i></a>
                    <a href="#" class="social-icon"><i class="fa fa-twitter"></i></a>
                    <a href="#" class="social-icon"><i class="fa fa-instagram"></i></a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-md-3">
                <h4 class="footer-title">Quick Links</h4>
                <ul class="footer-links">
                    <li><a href="/how-it-works">How It Works</a></li>
                    <li><a href="/pricing">Pricing</a></li>
                    <li><a href="/contact">Contact Us</a></li>
                    <li><a href="/terms">Terms of Service</a></li>
                </ul>
            </div>

            <!-- Legal -->
            <div class="col-md-3">
                <h4 class="footer-title">Legal</h4>
                <ul class="footer-links">
                    <li><a href="/privacy">Privacy Policy</a></li>
                    <li><a href="/security">Security Measures</a></li>
                    <li><a href="/accessibility">Accessibility Statement</a></li>
                </ul>
            </div>

            <!-- Contact -->
            <div class="col-md-3">
                <h4 class="footer-title">Get in Touch</h4>
                <p>support@evote.com</p>
                <p>+256 703014441</p>
                <p>123 Kabusu rd, Kampala, kla 345</p>
            </div>
        </div>

        <div class="copyright">
            <p>&copy; 2025 Evote.com. All rights reserved.</p>
        </div>
    </div>
</footer>






<script>

    // toggler

    document.getElementById('toggleList').addEventListener('click', function() {
  var list = document.getElementById('responsiveList');
  if (list.style.display === 'none' || list.style.display === '') {
    list.style.display = 'block';
  } else {
    list.style.display = 'none';
  }
});

</script>
</body>
</html>