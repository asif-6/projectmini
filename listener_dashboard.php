<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'listener') {
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
    header("Location: listener_dashboard.php");
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

// Fetch user playlists
$playlists = $conn->prepare("SELECT id, name FROM playlists WHERE user_id = ?");
$playlists->bind_param("i", $_SESSION['user_id']);
$playlists->execute();
$playlists_result = $playlists->get_result();

// Fetch playlists with songs for display
$my_playlists = $conn->prepare("SELECT id, name FROM playlists WHERE user_id = ?");
$my_playlists->bind_param("i", $_SESSION['user_id']);
$my_playlists->execute();
$my_playlists_result = $my_playlists->get_result();
$playlists_with_songs = [];
while ($pl = $my_playlists_result->fetch_assoc()) {
    $pl_id = $pl['id'];
    $pl_name = $pl['name'];
    $songs_in_pl = [];
    $res = $conn->query("SELECT s.id, s.title, s.file_path, u.firstname, u.lastname FROM playlist_songs ps JOIN songs s ON ps.song_id = s.id JOIN users u ON s.artist_id = u.id WHERE ps.playlist_id = $pl_id AND s.approved = 1");
    while ($row = $res->fetch_assoc()) {
        $songs_in_pl[] = $row;
    }
    $playlists_with_songs[] = [
        'id' => $pl_id,
        'name' => $pl_name,
        'songs' => $songs_in_pl
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Listener Dashboard - SoundNext</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            min-height: 100vh;
            background-size: cover;
            font-family: 'Segoe UI', Arial, sans-serif;
            color: #fff;
        }
        .navbar {
            background:rgb(0, 0, 0);
            border-bottom: 2px solidrgb(255, 255, 255);
        }
        .navbar-brand {
            color:rgb(255, 255, 255) !important;
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
        .song-list {
            list-style: none;
            padding: 0;
        }
        .song-list li {
            background: rgba(0, 0, 0, 0.85);
            border-radius: 14px;
            margin-bottom: 18px;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 8px rgba(53, 49, 49, 0.09);
            transition: background 0.2s;
            gap: 18px;
        }
        .song-list li:hover {
            background: rgba(45, 45, 45, 0.87);
        }
        .song-info {
            min-width: 0;
            flex: 1 1 0;
            display: flex;
            flex-direction: column;
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
            color:rgb(14, 135, 33);
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
            width: 320px;
            min-width: 120px;
            max-width: 260px;
            margin-left: 20px;
        }
        .playlist-section {
            background: rgba(0, 0, 0, 0.07);
            border-radius: 14px;
            padding: 24px 18px;
            margin-bottom: 32px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }
        .btn-spotify {
            background:rgb(0, 0, 0);
            color: #fff;
            border-radius:2px;
            font-weight: 600;
            padding: 8px 28px;
            border: none;
            transition: background 0.2s;
        }
        .search-bar-top {
            max-width: 50%;
            margin: 20px auto;
        }
        .btn-spotify:hover {
            background:rgb(24, 69, 184);
            color: #fff;
        }
        .playlist-list {
            margin-top: 18px;
        }
        audio::-webkit-media-controls-play-button {
            background-color: #1db954;
            border-radius: 50%;
            transition: background 0.2s;
        }
        .playlist-list li {
            color: #fff;
            background: rgba(0, 0, 0, 0.13);
            border-radius: 10px;
            padding: 8px 14px;
            margin-bottom: 8px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        @media (max-width: 768px) {
            .dashboard-section {
                padding: 12px 4px;
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
            audio {
                width: 50%;
                margin-left: 0;
                margin-top: 8px;
            }
        }
        /* Video background styles */
        #bgvid {
            position: fixed;
            right: 0;
            bottom: 0;
            min-width: 100vw;
            min-height: 100vh;
            width: auto;
            height: auto;
            z-index: -1;
            object-fit: cover;
        }
    </style>
</head>
<body>
<!-- Video Background -->
<video autoplay muted loop id="bgvid">
    <source src="stringing.mp4" type="video/mp4">
    Your browser does not support the video tag.
</video>
<nav class="navbar navbar-expand-lg mb-2">
    <div class="container">
        <a class="navbar-brand" href="#">SoundNext</a>
        <div class="ms-auto">
            <a href="logout.php" class="btn btn-spotify px-4">Logout</a>
        </div>
    </div>
</nav>
<!-- Search Bar at Top -->
 
<div class="search-bar-top">
    <form class="d-flex justify-content-center search-bar" onsubmit="return false;">
        <input class="form-control" type="search" id="mainSongSearch" placeholder="Search for songs or artists..." aria-label="Search">
        <button class="btn btn-primary ms-2" type="button" tabindex="-1">Search</button>
    </form>
</div>
<div class="container">
    <div class="dashboard-section">
        <h2 class="mb-4"><i class="bi bi-music-note-beamed"></i> Welcome, <?= htmlspecialchars($_SESSION['firstname']) ?>!</h2>
        <div class="row">
            <!-- Playlist Section -->
            <div class="col-md-4 playlist-section">
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
                                <a href="playlist_view.php?id=<?= $pl['id'] ?>" target="_blank" style="color:#fff;text-decoration:underline;">
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
            <!-- Songs Section -->
            <div class="col-md-8">
                <?php if ($song_add_message) echo $song_add_message; ?>
                <h4><i class="bi bi-disc"></i> All Songs</h4>
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
                        <span class="song-info">
                            <span class="song-title"><?= htmlspecialchars($song['title']) ?></span>
                            <span class="song-artist">by <?= htmlspecialchars($song['firstname'].' '.$song['lastname']) ?></span>
                        </span>
                        <audio controls>
                            <source src="<?= htmlspecialchars($song['file_path']) ?>" type="audio/mpeg">
                            Your browser does not support the audio element.
                        </audio>
                        <?php if (count($playlists_arr)): ?>
                        <form method="POST" style="display:inline-block; margin-left:10px;">
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
                    </li>
                    <?php endwhile; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

</body>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var searchInput = document.getElementById('mainSongSearch');
    if (!searchInput) return;
    searchInput.addEventListener('input', function() {
        let filter = this.value.trim().toLowerCase();
        let items = document.querySelectorAll('#songList li');
        items.forEach(function(li) {
            let titleElem = li.querySelector('.song-title');
            let artistElem = li.querySelector('.song-artist');
            let title = titleElem ? titleElem.textContent.trim().toLowerCase() : '';
            let artist = artistElem ? artistElem.textContent.trim().toLowerCase() : '';
            if (filter === '' || title.includes(filter) || artist.includes(filter)) {
                li.style.display = '';
            } else {
                li.style.display = 'none';
            }
        });
    });
});
</script>
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
// Hide alert messages after 2 seconds
setTimeout(function() {
    var alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        alert.style.transition = "opacity 0.5s";
        alert.style.opacity = 0;
        setTimeout(function() {
            alert.style.display = "none";
        }, 500);
    });
}, 2000);
// ...existing code...

// ...existing code...