<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'artist') {
    header("Location: index.php");
    exit();
}
include 'db.php';

// Get playlist ID and check ownership
$playlist_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];

// Fetch playlist info and check if it belongs to the user
$pl_stmt = $conn->prepare("SELECT name FROM playlists WHERE id = ? AND user_id = ?");
$pl_stmt->bind_param("ii", $playlist_id, $user_id);
$pl_stmt->execute();
$pl_stmt->store_result();
if ($pl_stmt->num_rows == 0) {
    echo "<div style='color:red;padding:20px;'>Playlist not found or access denied.</div>";
    exit();
}
$pl_stmt->bind_result($playlist_name);
$pl_stmt->fetch();
$pl_stmt->close();

// Handle add song to playlist
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_song_to_playlist'])) {
    $song_id = intval($_POST['song_id']);
    // Prevent duplicate
    $check = $conn->prepare("SELECT id FROM playlist_songs WHERE playlist_id = ? AND song_id = ?");
    $check->bind_param("ii", $playlist_id, $song_id);
    $check->execute();
    $check->store_result();
    if ($check->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO playlist_songs (playlist_id, song_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $playlist_id, $song_id);
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">Song added to playlist!</div>';
        } else {
            $message = '<div class="alert alert-danger">Could not add song.</div>';
        }
        $stmt->close();
    } else {
        $message = '<div class="alert alert-warning">Song already in playlist.</div>';
    }
    $check->close();
}

// Handle remove song from playlist
if (isset($_GET['remove_song']) && is_numeric($_GET['remove_song'])) {
    $song_id = intval($_GET['remove_song']);
    $stmt = $conn->prepare("DELETE FROM playlist_songs WHERE playlist_id = ? AND song_id = ?");
    $stmt->bind_param("ii", $playlist_id, $song_id);
    $stmt->execute();
    $stmt->close();
    header("Location: artist_playlist_view.php?id=$playlist_id");
    exit();
}

// Fetch all approved songs (for adding)
$songs = $conn->query("SELECT s.id, s.title, s.file_path, u.firstname, u.lastname FROM songs s JOIN users u ON s.artist_id = u.id WHERE s.approved = 1");

// Fetch songs in this playlist
$playlist_songs = $conn->prepare("SELECT s.id, s.title, s.file_path, u.firstname, u.lastname
    FROM playlist_songs ps
    JOIN songs s ON ps.song_id = s.id
    JOIN users u ON s.artist_id = u.id
    WHERE ps.playlist_id = ?");
$playlist_songs->bind_param("i", $playlist_id);
$playlist_songs->execute();
$playlist_songs_result = $playlist_songs->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Playlist: <?= htmlspecialchars($playlist_name) ?> - SoundNext</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #181818;
            color: #fff;
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        .container {
            margin-top: 40px;
        }
        .playlist-header {
            background: #222;
            border-radius: 16px;
            padding: 24px 18px;
            margin-bottom: 32px;
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
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            gap: 18px;
        }
        .song-title {
            font-size: 1.15rem;
            font-weight: 600;
            color: #fff;
            max-width: 320px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .song-artist {
            color: #1db954;
            font-size: 1rem;
            margin-left: 0;
            margin-top: 2px;
            max-width: 320px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        audio {
            width: 320px;
            min-width: 120px;
            max-width: 260px;
            margin-left: 20px;
        }
        .btn-danger, .btn-success {
            border-radius: 20px;
            font-weight: 500;
        }
        .form-select, .form-control {
            background: #222;
            color: #fff;
            border-radius: 8px;
            border: 1.5px solid #222;
        }
        .form-select:focus, .form-control:focus {
            background: #222;
            color: #fff;
            border-color: #1db954;
            box-shadow: 0 0 0 0.2rem rgba(29,185,84,0.15);
        }
    </style>
</head>
<body>
<div class="container">
    <div class="playlist-header">
        <h2>Playlist: <?= htmlspecialchars($playlist_name) ?></h2>
        <a href="artist_dashboard.php" class="btn btn-secondary btn-sm mt-2">Back to Dashboard</a>
        <?= $message ?>
        <form method="POST" class="row g-2 align-items-center mt-3">
            <div class="col-auto">
                <label for="song_id" class="col-form-label">Add Song:</label>
            </div>
            <div class="col-auto">
                <select name="song_id" id="song_id" class="form-select" required>
                    <option value="" disabled selected>Select a song</option>
                    <?php
                    // Show only songs not already in playlist
                    $in_playlist = [];
                    $playlist_songs->execute();
                    $playlist_songs_result = $playlist_songs->get_result();
                    while ($row = $playlist_songs_result->fetch_assoc()) {
                        $in_playlist[] = $row['id'];
                    }
                    $songs->data_seek(0);
                    while ($song = $songs->fetch_assoc()):
                        if (in_array($song['id'], $in_playlist)) continue;
                    ?>
                        <option value="<?= $song['id'] ?>">
                            <?= htmlspecialchars($song['title']) ?> (by <?= htmlspecialchars($song['firstname'].' '.$song['lastname']) ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" name="add_song_to_playlist" class="btn btn-success">Add</button>
            </div>
        </form>
    </div>
    <h4 class="mb-3">Songs in this Playlist</h4>
    <ul class="song-list">
        <?php
        $playlist_songs->execute();
        $playlist_songs_result = $playlist_songs->get_result();
        if ($playlist_songs_result->num_rows == 0): ?>
            <li>No songs in this playlist yet.</li>
        <?php else:
            while($song = $playlist_songs_result->fetch_assoc()): ?>
            <li>
                <span>
                    <span class="song-title"><?= htmlspecialchars($song['title']) ?></span>
                    <span class="song-artist">by <?= htmlspecialchars($song['firstname'].' '.$song['lastname']) ?></span>
                </span>
                <audio controls>
                    <source src="<?= htmlspecialchars($song['file_path']) ?>" type="audio/mpeg">
                    Your browser does not support the audio element.
                </audio>
                <a href="?id=<?= $playlist_id ?>&remove_song=<?= $song['id'] ?>" class="btn btn-danger btn-sm"
                   onclick="return confirm('Remove this song from playlist?');">Remove</a>
            </li>
        <?php endwhile; endif; ?>
    </ul>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Only one audio plays at a time
document.addEventListener('play', function(e){
    if(e.target.tagName === 'AUDIO'){
        let audios = document.getElementsByTagName('audio');
        for(let i = 0, len = audios.length; i < len; i++){
            if(audios[i] !== e.target){
                audios[i].pause();
                audios[i].currentTime = 0;
            }
        }
    }
}, true);
</script>
</body>
</html>