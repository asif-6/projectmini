<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'listener') {
    header("Location: index.php");
    exit();
}
include 'db.php';

$playlist_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch playlist info
$stmt = $conn->prepare("SELECT name FROM playlists WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $playlist_id, $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($playlist_name);
$stmt->fetch();
$stmt->close();

if (!$playlist_name) {
    echo "<h2 style='color:red'>Playlist not found or access denied.</h2>";
    exit;
}

// Fetch songs in playlist
$songs = $conn->query("SELECT s.title, s.file_path, u.firstname, u.lastname FROM playlist_songs ps JOIN songs s ON ps.song_id = s.id JOIN users u ON s.artist_id = u.id WHERE ps.playlist_id = $playlist_id AND s.approved = 1");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($playlist_name) ?> - Playlist</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #181818; color: #fff; font-family: 'Segoe UI', Arial, sans-serif; }
        .container { margin-top: 40px; }
        .song-list { list-style: none; padding: 0; }
        .song-list li {
            background: rgba(30,30,30,0.85);
            border-radius: 14px;
            margin-bottom: 18px;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
        }
        .song-title {
            font-size: 1.15rem;
            font-weight: 600;
            color: #fff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 320px;
            display: block;
        }
        .song-artist {
            color: #1db954;
            font-size: 1rem;
            margin-left: 0;
            margin-top: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 320px;
            display: block;
        }
        audio {
            width: 180px;
            min-width: 120px;
            max-width: 260px;
            margin-left: 20px;
        }
    </style>
</head>
<body>
<div class="container">
    <h2><?= htmlspecialchars($playlist_name) ?> <span style="font-size:1rem;color:#aaa;">(Playlist)</span></h2>
    <ul class="song-list">
        <?php if ($songs->num_rows == 0): ?>
            <li>No songs in this playlist.</li>
        <?php else: while($song = $songs->fetch_assoc()): ?>
            <li>
                <span>
                    <span class="song-title"><?= htmlspecialchars($song['title']) ?></span>
                    <span class="song-artist">by <?= htmlspecialchars($song['firstname'].' '.$song['lastname']) ?></span>
                </span>
                <audio controls>
                    <source src="<?= htmlspecialchars($song['file_path']) ?>" type="audio/mpeg">
                </audio>
            </li>
        <?php endwhile; endif; ?>
    </ul>
<a href="listener_dashboard.php" class="btn btn-secondary mb-3">&larr; Back to Dashboard</a>
</div>
</body>
</html>