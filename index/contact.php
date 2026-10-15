<?php
// Start session
session_start();

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="contact.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">E-vote</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link active" href="#">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Join Us</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Contact Us Section -->
    <section class="contact-us-section py-5">
        <div class="container text-center">
            <h1>Contact Us</h1>
            <p>We’d love to hear from you! Fill out the form below or reach us through the provided contact details.</p>

            <!-- Contact Form -->
            <form action="#" method="post" class="contact-form mx-auto">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="mb-3">
                    <input type="text" name="name" placeholder="Your Name" required class="form-control">
                </div>
                <div class="mb-3">
                    <input type="email" name="email" placeholder="Your Email Address" required class="form-control">
                </div>
                <div class="mb-3">
                    <textarea name="message" placeholder="Your Message" rows="4" required class="form-control"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Send Message</button>
            </form>

            <!-- Additional Contact Details -->
            <div class="contact-details mt-4">
                <p><strong>Email:</strong> support@evote.com</p>
                <p><strong>Phone:</strong> +1 (555) 123-4567</p>
                <p><strong>Address:</strong> 123 Main St, New York, NY 10001</p>

                <!-- Social Media Links -->
                <div class="social-links mt-3">
                    <a href="#" class="social-icon"><i class="fa fa-facebook"></i></a>
                    <a href="#" class="social-icon"><i class="fa fa-twitter"></i></a>
                    <a href="#" class="social-icon"><i class="fa fa-instagram"></i></a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer bg-dark text-light text-center py-3">
        &copy; 2025 E-vote Platform. All Rights Reserved.
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
