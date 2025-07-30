<?php

session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'artist') {
    header("Location: index.php");
    exit();
}
include 'db.php';

// Fetch approved songs
$songs = $conn->query("SELECT s.id, s.title, s.file_path, u.firstname, u.lastname FROM songs s JOIN users u ON s.artist_id = u.id WHERE s.approved = 1");

// Fetch this artist's pending uploads
$pending = $conn->prepare("SELECT id, title FROM songs WHERE artist_id = ? AND approved = 0");
$pending->bind_param("i", $_SESSION['user_id']);
$pending->execute();
$pending_result = $pending->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Artist Dashboard - SoundNext</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, rgba(35,37,38,0.8) 0%, rgba(65,67,69,0.8) 100%),
                        url('bg.jpeg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        .navbar {
            background: rgba(34, 34, 34, 0.85);
            box-shadow: 0 4px 24px rgba(0,0,0,0.15);
            border-radius: 0 0 16px 16px;
        }
        .navbar-brand, .nav-link {
            color: #fff !important;
            font-weight: 600;
            letter-spacing: 1px;
        }
        .dashboard-section {
            background: rgba(255,255,255,0.22);
            border-radius: 24px;
            box-shadow: 0 8px 32px 0 rgba(31,38,135,0.18);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border: 1px solid rgba(255,255,255,0.24);
            padding: 40px 30px;
            margin-top: 40px;
            animation: fadeIn 1.2s;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px);}
            to { opacity: 1; transform: translateY(0);}
        }
        .feature-box {
            padding: 32px 20px;
            border-radius: 18px;
            background: rgba(255,255,255,0.42);
            box-shadow: 0 4px 24px rgba(31,38,135,0.10);
            min-height: 220px;
            transition: transform 0.25s, box-shadow 0.25s, background 0.25s;
            border: 1px solid rgba(255,255,255,0.18);
            backdrop-filter: blur(8px);
        }
        .feature-box:hover {
            transform: translateY(-8px) scale(1.03);
            box-shadow: 0 12px 36px rgba(31,38,135,0.18);
            background: rgba(255,255,255,0.55);
        }
        .feature-box h4 {
            color: #0d6efd;
            font-weight: 700;
            margin-bottom: 18px;
        }
        .btn-primary, .btn-outline-primary {
            border-radius: 24px;
            font-weight: 600;
            padding: 8px 28px;
            box-shadow: 0 2px 8px rgba(13,110,253,0.08);
            transition: background 0.2s, color 0.2s;
        }
        .btn-primary:hover, .btn-outline-primary:hover {
            background: #0b5ed7;
            color: #fff;
        }
        .form-control {
            border-radius: 16px;
            border: 1.5px solid #e3e6ea;
            box-shadow: 0 1px 4px rgba(13,110,253,0.03);
            padding: 10px 16px;
        }
        .alert-success {
            background: rgba(25,135,84,0.92);
            color: #fff;
            font-size: 1.15rem;
            font-weight: 500;
            border-radius: 0 0 18px 18px;
            border: none;
            margin-bottom: 0;
            margin-top: 24px;
            letter-spacing: 1px;
        }
        ul.list-unstyled li {
            margin-bottom: 10px;
            font-size: 1.08rem;
            color: #333;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .bi {
            color: #0d6efd;
            font-size: 1.3rem;
        }
        .song-list {
            list-style: none;
            padding: 0;
        }
        .song-list li {
            background: rgba(30,30,30,0.85);
            border-radius: 14px;
            margin-bottom: 18px;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 8px rgba(30,185,84,0.08);
            transition: background 0.2s;
        }
        .song-list li:hover {
            background: rgba(29,185,84,0.13);
        }
        .song-title {
            font-size: 1.15rem;
            font-weight: 600;
            color: #222;
        }
        .song-artist {
            color: #0d6efd;
            font-size: 1rem;
            margin-left: 12px;
        }
        audio {
            width: 180px;
            margin-left: 20px;
        }
        .pending-section {
            background: rgba(255,255,255,0.42);
            border-radius: 14px;
            padding: 18px 18px;
            margin-bottom: 24px;
            box-shadow: 0 2px 8px rgba(30,185,84,0.08);
        }
        .pending-section h5 {
            color: #dc3545;
            font-weight: 600;
        }
        @media (max-width: 768px) {
            .dashboard-section {
                padding: 18px 6px;
            }
            .feature-box {
                min-height: 160px;
                padding: 18px 8px;
            }
            .song-list li {
                flex-direction: column;
                align-items: flex-start;
                padding: 14px 8px;
            }
            audio {
                width: 100%;
                margin-left: 0;
                margin-top: 8px;
            }
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg mb-2">
    <div class="container">
        <a class="navbar-brand" href="#">SoundNext</a>
        <div class="ms-auto">
            <a href="logout.php" class="btn btn-danger px-4">Logout</a>
        </div>
    </div>
</nav>

<div class="container">
    <!-- Personalized Welcome -->
    <div class="alert alert-success text-center">
        Welcome, <?php echo htmlspecialchars($_SESSION['firstname']); ?> (Artist)!
    </div>

    <div class="dashboard-section">
        <div class="row">
            <!-- Music Upload (Artist Only) -->
            <div class="col-md-6 mb-4">
                <div class="feature-box h-100">
                    <h4><i class="bi bi-cloud-arrow-up"></i> Upload Your Music</h4>
                    <form action="upload_music.php" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="song_title" class="form-label">Song Title</label>
                            <input type="text" class="form-control" name="song_title" required>
                        </div>
                        <div class="mb-3">
                            <label for="music_file" class="form-label">Music File (mp3)</label>
                            <input type="file" class="form-control" name="music_file" accept=".mp3" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Upload</button>
                    </form>
                    <!-- Pending uploads -->
                    <?php if ($pending_result->num_rows > 0): ?>
                        <div class="pending-section mt-4">
                            <h5><i class="bi bi-hourglass-split"></i> Pending Approval</h5>
                            <ul class="mb-0">
                                <?php while($p = $pending_result->fetch_assoc()): ?>
                                    <li><?= htmlspecialchars($p['title']) ?> <span class="badge bg-warning text-dark">Pending</span></li>
                                <?php endwhile; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Listener Features (Shared) -->
            <div class="col-md-6 mb-4">
                <div class="feature-box h-100">
                    <h4><i class="bi bi-headphones"></i> Listen to All Songs</h4>
                    <form class="d-flex mb-3" action="search.php" method="GET">
                        <input class="form-control me-2" type="search" name="q" placeholder="Search for songs, artists..." aria-label="Search">
                        <button class="btn btn-outline-primary" type="submit">Search</button>
                    </form>
                    <ul class="song-list">
                        <?php while($song = $songs->fetch_assoc()): ?>
                        <li>
                            <span>
                                <span class="song-title"><?= htmlspecialchars($song['title']) ?></span>
                                <span class="song-artist">by <?= htmlspecialchars($song['firstname'].' '.$song['lastname']) ?></span>
                            </span>
                            <audio controls>
                                <source src="<?= htmlspecialchars($song['file_path']) ?>" type="audio/mpeg">
                                Your browser does not support the audio element.
                            </audio>
                        </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            </div>
        </div>
        <!-- You can add more listener/artist features below as needed -->
    </div>
</div>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>