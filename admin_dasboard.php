<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}
include 'db.php';

// Approve song if requested
if (isset($_GET['approve']) && is_numeric($_GET['approve'])) {
    $song_id = intval($_GET['approve']);
    $conn->query("UPDATE songs SET approved = 1 WHERE id = $song_id");
    header("Location: admin_dashboard.php");
    exit();
}

// Handle admin song upload
$upload_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_add_song'])) {
    $artist_id = $_POST['artist_id'];
    $title = $_POST['song_title'];
    if (isset($_FILES['music_file']) && $_FILES['music_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['music_file']['tmp_name'];
        $fileName = uniqid() . '_' . basename($_FILES['music_file']['name']);
        $dest_path = 'uploads/' . $fileName;
        if (!is_dir('uploads')) {
            mkdir('uploads', 0777, true);
        }
        if (move_uploaded_file($fileTmpPath, $dest_path)) {
            $stmt = $conn->prepare("INSERT INTO songs (artist_id, title, file_path, approved) VALUES (?, ?, ?, 1)");
            $stmt->bind_param("iss", $artist_id, $title, $dest_path);
            if ($stmt->execute()) {
                $upload_message = '<div class="alert alert-success mt-2">Song added and approved successfully!</div>';
            } else {
                $upload_message = '<div class="alert alert-danger mt-2">Database error. Song not added.</div>';
            }
            $stmt->close();
        } else {
            $upload_message = '<div class="alert alert-danger mt-2">File upload failed.</div>';
        }
    } else {
        $upload_message = '<div class="alert alert-danger mt-2">Please select a valid MP3 file.</div>';
    }
}

// Fetch users
$users = $conn->query("SELECT id, firstname, lastname, email, role FROM users WHERE role='artist'");
$all_users = $conn->query("SELECT id, firstname, lastname, email, role FROM users");
$pending_songs = $conn->query("SELECT s.id, s.title, u.firstname, u.lastname FROM songs s JOIN users u ON s.artist_id = u.id WHERE s.approved = 0");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - SoundNext</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        .navbar {
            background: #fff;
            border-bottom: 1px solid #dee2e6;
        }
        .navbar-brand {
            font-weight: 600;
            color: #198754 !important;
        }
        .dashboard-section {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            padding: 24px 18px;
            margin-top: 32px;
        }
        .form-label, label {
            font-weight: 500;
        }
        .btn-success, .btn-danger, .btn-primary {
            border-radius: 20px;
            font-weight: 500;
        }
        .table th {
            background: #e9ecef;
        }
        .alert {
            border-radius: 8px;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg mb-2">
    <div class="container">
        <a class="navbar-brand" href="#">SoundNext Admin</a>
        <div class="ms-auto">
            <a href="logout.php" class="btn btn-danger px-4">Logout</a>
        </div>
    </div>
</nav>
<div class="container">
    <div class="dashboard-section">
        <h2 class="mb-4">Admin Dashboard</h2>
        <hr>
        <h5>Manually Add Song (Admin)</h5>
        <?php if ($upload_message) echo $upload_message; ?>
        <form action="" method="POST" enctype="multipart/form-data" class="mb-4">
            <input type="hidden" name="admin_add_song" value="1">
            <div class="row">
                <div class="col-md-4 mb-2">
                    <label for="artist_id" class="form-label">Select Artist</label>
                    <select name="artist_id" class="form-control" required>
                        <option value="" disabled selected>Select Artist</option>
                        <?php while($artist = $users->fetch_assoc()): ?>
                            <option value="<?= $artist['id'] ?>">
                                <?= htmlspecialchars($artist['firstname'].' '.$artist['lastname']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-2">
                    <label for="song_title" class="form-label">Song Title</label>
                    <input type="text" name="song_title" class="form-control" required>
                </div>
                <div class="col-md-4 mb-2">
                    <label for="music_file" class="form-label">Music File (mp3)</label>
                    <input type="file" name="music_file" class="form-control" accept=".mp3" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary mt-2">Add Song</button>
        </form>
        <hr>
        <h5>Manage Users</h5>
        <table class="table table-bordered">
            <thead>
                <tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th></tr>
            </thead>
            <tbody>
                <?php while($user = $all_users->fetch_assoc()): ?>
                <tr>
                    <td><?= $user['id'] ?></td>
                    <td><?= htmlspecialchars($user['firstname'].' '.$user['lastname']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= htmlspecialchars($user['role']) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <hr>
        <h5>Pending Music Uploads</h5>
        <table class="table table-bordered">
            <thead>
                <tr><th>ID</th><th>Title</th><th>Artist</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php while($song = $pending_songs->fetch_assoc()): ?>
                <tr>
                    <td><?= $song['id'] ?></td>
                    <td><?= htmlspecialchars($song['title']) ?></td>
                    <td><?= htmlspecialchars($song['firstname'].' '.$song['lastname']) ?></td>
                    <td>
                        <a href="?approve=<?= $song['id'] ?>" class="btn btn-success btn-sm">Approve</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>