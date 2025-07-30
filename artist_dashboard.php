<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'artist') {
    header("Location: index.php");
    exit();
}
include 'db.php';

// Handle playlist creation
$playlist_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_playlist'])) {
    $playlist_name = trim($_POST['playlist_name']);
    $user_id = $_SESSION['user_id'];
    if ($playlist_name !== '') {
        $stmt = $conn->prepare("INSERT INTO playlists (user_id, name) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $playlist_name);
        if ($stmt->execute()) {
            $playlist_message = '<div class="alert alert-success">Playlist created!</div>';
        } else {
            $playlist_message = '<div class="alert alert-danger">Could not create playlist.</div>';
        }
        $stmt->close();
    }
}

// Handle playlist deletion
if (isset($_POST['delete_playlist']) && isset($_POST['playlist_id'])) {
    $playlist_id = intval($_POST['playlist_id']);
    // Only allow deleting own playlist
    $del = $conn->prepare("DELETE FROM playlists WHERE id = ? AND user_id = ?");
    $del->bind_param("ii", $playlist_id, $_SESSION['user_id']);
    $del->execute();
    $del->close();
    // Delete songs from playlist_songs table as well
    $del_songs = $conn->prepare("DELETE FROM playlist_songs WHERE playlist_id = ?");
    $del_songs->bind_param("i", $playlist_id);
    $del_songs->execute();
    $del_songs->close();
    // Refresh to update the playlist list
    header("Location: artist_dashboard.php");
    exit();
}

// Handle adding song to playlist
$song_add_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_playlist'])) {
    $playlist_id = intval($_POST['playlist_id']);
    $song_id = intval($_POST['song_id']);
    // Prevent duplicate entries
    $check = $conn->prepare("SELECT id FROM playlist_songs WHERE playlist_id = ? AND song_id = ?");
    $check->bind_param("ii", $playlist_id, $song_id);
    $check->execute();
    $check->store_result();
    if ($check->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO playlist_songs (playlist_id, song_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $playlist_id, $song_id);
        if ($stmt->execute()) {
            $song_add_message = '<div class="alert alert-success">Song added to playlist!</div>';
        } else {
            $song_add_message = '<div class="alert alert-danger">Could not add song to playlist.</div>';
        }
        $stmt->close();
    } else {
        $song_add_message = '<div class="alert alert-warning">Song already in playlist.</div>';
    }
    $check->close();
}

// Fetch approved songs
$songs = $conn->query("SELECT s.id, s.title, s.file_path, u.firstname, u.lastname FROM songs s JOIN users u ON s.artist_id = u.id WHERE s.approved = 1");

// Fetch this artist's pending uploads
$pending = $conn->prepare("SELECT id, title FROM songs WHERE artist_id = ? AND approved = 0");
$pending->bind_param("i", $_SESSION['user_id']);
$pending->execute();
$pending_result = $pending->get_result();

// Fetch artist playlists
$playlists = $conn->prepare("SELECT id, name FROM playlists WHERE user_id = ?");
$playlists->bind_param("i", $_SESSION['user_id']);
$playlists->execute();
$playlists_result = $playlists->get_result();

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
            background: url('bg.jpeg') no-repeat center center fixed;
            min-height: 100vh;
            background-size: cover;
            font-family: 'Segoe UI', Arial, sans-serif;
            color: #fff;
        }
        .navbar {
            background: rgb(0, 0, 0);
        }
        .navbar-brand, .nav-link {
            color: #fff !important;
            font-weight: 700;
            font-size: 2rem;
            letter-spacing: 2px;
        }
        .dashboard-section {
            background: rgba(0, 0, 0, 0.40);
            border-radius: 20px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.18);
            padding: 32px 24px;
            margin-top: 32px;
        }
        .feature-box {
            background: rgba(0, 0, 0, 0.92);
            border-radius: 14px;
            padding: 24px 18px;
            margin-bottom: 32px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            min-height: 220px;
            transition: transform 0.25s, box-shadow 0.25s, background 0.25s;
        }
        .feature-box:hover {
            transform: translateY(-8px) scale(1.03);
            box-shadow: 0 12px 36px rgba(31,38,135,0.18);
            background: rgba(0,0,0,0.98);
        }
        .feature-box h4 {
            color: #ffffffff;
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
        .btn-primary, .btn-outline-primary {
            background: #2857bdff;
            color: #fff;
            border: none;
        }
         .btn-danger
         {
            background: #000000ff;
            border: none;

         }
         .btn-danger:hover {
            background:  #2857bdff;
            color: #fff;
         }
        .btn-primary:hover, .btn-outline-primary:hover {
            background: #2857bdff;
            color: #fff;
        }
        .form-control {
            border-radius: 8px;
            border: 1.5px solid #222;
            box-shadow: 0 1px 4px rgba(13,110,253,0.03);
            padding: 10px 16px;
            background: #222;
            color: #fff;
        }
        .form-control:focus {
            background: #222;
            color: #fff;
            border-color:  #2857bdff;
            box-shadow: 0 0 0 0.2rem rgba(29,185,84,0.15);
        }
        .alert-success {
            background: rgba(0, 0, 0, 0);
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
            color: #fff;
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
            gap: 18px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: background 0.2s;
        }
        .song-list li:hover {
            background: rgba(0, 0, 0, 0.13);
        }
        .song-info {
            flex: 1 1 0;
            min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .song-title {
            font-size: 1.15rem;
            font-weight: 600;
            color: #fff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
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
            max-width: 100%;
            display: block;
        }
        .song-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 260px;
        }
        audio {
            width: 220px;
            min-width: 120px;
            max-width: 260px;
        }
        audio::-webkit-media-controls-play-button {
            background-color: #1db954;
            border-radius: 50%;
            transition: background 0.2s;
        }
        .pending-section {
            background: rgba(0,0,0,0.42);
            border-radius: 14px;
            padding: 18px 18px;
            margin-bottom: 24px;
            box-shadow: 0 2px 8px rgba(30,185,84,0.08);
        }
        .pending-section h5 {
            color: #ffc107;
            font-weight: 600;
        }
        .badge.bg-warning {
            background: #ffc107;
            color: #222;
        }
        .playlist-section {
            background: rgba(0, 0, 0, 0);
            border-radius: 14px;
            padding: 24px 18px;
            margin-bottom: 32px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }
        .playlist-list {
            margin-top: 18px;
        }
        .playlist-list li {
            color: #fff;
            background: rgba(0, 0, 0, 1);
            border-radius: 10px;
            padding: 8px 14px;
            margin-bottom: 8px;
            font-weight: 500;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .all-songs-section {
            background: rgba(0,0,0,0.40);
            border-radius: 18px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.12);
            padding: 32px 24px;
            margin-top: 32px;
        }
        .all-songs-header {
            color: #ffffffff;
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 18px;
            letter-spacing: 1px;
        }
         audio {
            width: 320px;
            min-width: 120px;
            max-width: 260px;
            margin-left: 20px;
        }
        @media (max-width: 991px) {
            .dashboard-section .row > div {
                margin-bottom: 24px;
            }
            .all-songs-section {
                padding: 18px 6px;
            }
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
                gap: 8px;
            }
            .song-title, .song-artist {
                max-width: 90vw;
            }
            .song-actions {
                width: 100%;
                min-width: 0;
                margin-top: 8px;
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            audio {
                width: 100%;
                margin-left: 0;
                margin-top: 8px;
            }
        }
        .bi{
            color: #0d6efd;
            font-size: 1.3rem;
        }
        .feature-box {
            background: rgba(0, 0, 0, 0);
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
            <!-- Playlist Section -->
            <div class="col-md-6 col-lg-5 playlist-section">
                <h4><i class="bi bi-list-ul"></i> Your Playlists</h4>
                <?php if ($playlist_message) echo $playlist_message; ?>
                <form method="POST" class="mb-3">
                    <div class="input-group">
                        <input type="text" name="playlist_name" class="form-control" placeholder="New Playlist Name" required>
                        <button type="submit" name="create_playlist" class="btn btn-spotify">Create</button>
                    </div>
                </form>
                <ul class="playlist-list">
                    <?php while($pl = $playlists_result->fetch_assoc()): ?>
                        <li>
                            <span>
                                <i class="bi bi-music-note-list"></i>
                                <a href="artist_playlist_view.php?id=<?= $pl['id'] ?>" style="color:#fff;text-decoration:underline;">
                                    <?= htmlspecialchars($pl['name']) ?>
                                </a>
                            </span>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this playlist?');">
                                <input type="hidden" name="playlist_id" value="<?= $pl['id'] ?>">
                                <button type="submit" name="delete_playlist" class="btn btn-sm btn-danger ms-2">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </form>
                        </li>
                    <?php endwhile; ?>
                </ul>
            </div>
            <!-- Music Upload (Artist Only) -->
            <div class="col-md-6 col-lg-7 mb-4">
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
        </div>
        <!-- All Songs Section -->
        <div class="all-songs-section mt-4">
            <?php if ($song_add_message) echo $song_add_message; ?>
            <div class="all-songs-header"><i class="bi bi-headphones"></i> All Songs</div>
            <form class="d-flex mb-3" onsubmit="return false;">
                <input class="form-control me-2" type="search" id="mainSongSearch" placeholder="Search for songs, artists..." aria-label="Search" autocomplete="off">
                <button class="btn btn-outline-primary" type="button" tabindex="-1">Search</button>
            </form>
            <ul class="song-list" id="songList">
                <?php
                // Re-fetch playlists for song add dropdown
                $playlists = $conn->prepare("SELECT id, name FROM playlists WHERE user_id = ?");
                $playlists->bind_param("i", $_SESSION['user_id']);
                $playlists->execute();
                $playlists_result2 = $playlists->get_result();
                $playlists_arr = [];
                while($pl = $playlists_result2->fetch_assoc()) {
                    $playlists_arr[] = $pl;
                }
                $songs->data_seek(0); // reset pointer
                while($song = $songs->fetch_assoc()): ?>
                <li>
                    <div class="song-info">
                        <span class="song-title" title="<?= htmlspecialchars($song['title']) ?>">
                            <?= htmlspecialchars($song['title']) ?>
                        </span>
                        <span class="song-artist" title="<?= htmlspecialchars($song['firstname'].' '.$song['lastname']) ?>">
                            by <?= htmlspecialchars($song['firstname'].' '.$song['lastname']) ?>
                        </span>
                    </div>
                    <div class="song-actions">
                        <audio controls>
                            <source src="<?= htmlspecialchars($song['file_path']) ?>" type="audio/mpeg">
                            Your browser does not support the audio element.
                        </audio>
                        <?php if (count($playlists_arr)): ?>
                        <form method="POST" style="display:inline-block;">
                            <input type="hidden" name="song_id" value="<?= $song['id'] ?>">
                            <select name="playlist_id" required style="border-radius:5px;padding:3px 8px;">
                                <option value="" disabled selected>Add to Playlist</option>
                                <?php foreach($playlists_arr as $plopt): ?>
                                    <option value="<?= $plopt['id'] ?>"><?= htmlspecialchars($plopt['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="add_to_playlist" class="btn btn-sm btn-outline-light" style="margin-left:3px;">Add</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </li>
                <?php endwhile; ?>
            </ul>
        </div>
    </div>
</div>
<!-- Bootstrap JS -->
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

// Main top search bar filters songs (robust version)
document.addEventListener('DOMContentLoaded', function() {
    var searchInput = document.getElementById('mainSongSearch');
    if (!searchInput) return;
    searchInput.addEventListener('input', function() {
        let filter = this.value.toLowerCase().trim();
        let items = document.querySelectorAll('#songList > li');
        items.forEach(function(li) {
            let title = li.querySelector('.song-title') ? li.querySelector('.song-title').textContent.toLowerCase() : '';
            let artist = li.querySelector('.song-artist') ? li.querySelector('.song-artist').textContent.toLowerCase() : '';
            if (title.includes(filter) || artist.includes(filter)) {
                li.style.display = '';
            } else {
                li.style.display = 'none';
            }
        });
    });
});
</script>
</body>
</html>