<?php
// Start session if needed
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SoundNext</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            background:
            url('bg.jpeg') no-repeat center center fixed;
            min-height: 100vh;
            background-size: cover;
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        .navbar {
            background: rgba(34, 34, 34, 0.95);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .navbar-brand, .nav-link {
            color: #fff !important;
            font-weight: 500;
            letter-spacing: 1px;
        }
        .navbar-brand {
            font-size: 1.7rem;
            letter-spacing: 2px;
        }
        .search-bar-top {
            background: #18191a;
            padding: 18px 0 10px 0;
        }
        .search-bar {
            max-width: 500px;
            margin: 0 auto;
        }
        .hero {
            background: linear-gradient(rgba(0,0,0,0.6),rgba(0, 0, 0, 0.7)), url('banner.jpg') no-repeat center center;
            background-size: cover;
            height: 60vh;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            text-shadow: 2px 2px 8px #000;
        }
        .hero h1 {
            font-size: 3rem;
            font-weight: 700;
        }
        .hero p {
            font-size: 1.3rem;
            margin-bottom: 30px;
        }
        .feature-box {
            padding: 30px 20px;
            border-radius: 15px;
            background: rgba(255,255,255,0.95);
            box-shadow: 0 0 18px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
            min-height: 220px;
        }
        .feature-box:hover {
            transform: translateY(-8px) scale(1.03);
            box-shadow: 0 8px 32px rgba(0,0,0,0.18);
        }
        .feature-box h4 {
            color: #0d6efd;
            font-weight: 600;
        }
        .testimonial {
            background: #232526;
            color: #fff;
            border-radius: 12px;
            padding: 25px 20px;
            margin-bottom: 20px;
            box-shadow: 0 0 12px rgba(0,0,0,0.10);
        }
        .testimonial p {
            font-size: 1.1rem;
        }
        .footer-icons a {
            color: #fff;
            font-size: 1.5rem;
            margin: 0 10px;
            transition: color 0.2s;
        }
        .footer-icons a:hover {
            color: #0d6efd;
        }
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2rem;
            }
            .feature-box {
                min-height: 180px;
            }
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="#">SoundNext</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index1.html">Login</a></li>
                <li class="nav-item"><a class="nav-link" href="signup.html">Register</a></li>
            </ul>
        </div>
    </div>
</nav>

<!-- Search Bar at Top 
<div class="search-bar-top">
    <form class="d-flex justify-content-center search-bar" action="search.php" method="GET">
        <input class="form-control" type="search" name="q" placeholder="Search for songs, artists..." aria-label="Search">
        <button class="btn btn-primary ms-2" type="submit">Search</button>
    </form>
</div>-->

<!-- Personalized Welcome -->
<?php if(isset($_SESSION['firstname'])): ?>
    <div class="alert alert-success text-center mb-0 rounded-0">
        Welcome, <?php echo htmlspecialchars($_SESSION['firstname']); ?>!
    </div>
<?php endif; ?>

<!-- Hero Section -->
<section class="hero">
    <div class="text-center">
        <h1>Stream Your Favourite Music</h1>
        <p>Discover songs, create playlists, and follow your favorite artists</p>
        <a href="listener_dashboard.php" class="btn btn-primary mx-2 px-4 py-2">Get Started</a>
        <a href="signup.html" class="btn btn-outline-light mx-2 px-4 py-2">Join as Artist</a>
    </div>
</section>

<!-- Features -->
<section class="py-5">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-6 mb-4">
                <div class="feature-box h-100">
                    <h4><i class="bi bi-headphones"></i> For Listeners</h4>
                    <p>Stream music, build custom playlists, and follow your favorite artists with ease. Enjoy high-quality audio and discover trending tracks every day.</p>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="feature-box h-100">
                    <h4><i class="bi bi-mic"></i> For Artists</h4>
                    <p>Upload your own tracks, grow your fan base, and share your music with the world. Get insights on your listeners and connect with your audience.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials -->
<section class="py-5 bg-dark text-white">
    <div class="container">
        <h3 class="text-center mb-4">What Our Users Say</h3>
        <div class="row">
            <div class="col-md-6">
                <div class="testimonial">
                    <p><i class="bi bi-quote"></i> Best music platform! I found all my favorite tracks and the playlists feature is awesome.</p>
                    <footer class="blockquote-footer text-white">Amit, Listener</footer>
                </div>
            </div>
            <div class="col-md-6">
                <div class="testimonial">
                    <p><i class="bi bi-quote"></i> Easy to upload and share my music. The artist dashboard is super helpful!</p>
                    <footer class="blockquote-footer text-white">Priya, Artist</footer>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="text-center py-4 bg-dark text-white">
    <p class="mb-2">&copy; 2025 SoundNext. All rights reserved.</p>
    <div class="footer-icons mb-2">
        <a href="#"><i class="bi bi-facebook"></i></a>
        <a href="#"><i class="bi bi-twitter"></i></a>
        <a href="#"><i class="bi bi-instagram"></i></a>
    </div>
    <small>Made with <i class="bi bi-music-note-beamed"></i> for music lovers.</small>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>