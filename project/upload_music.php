<?php
// filepath: c:\xampp\htdocs\project\upload_music.php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'artist') {
    header("Location: index.php");
    exit();
}
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $artist_id = $_SESSION['user_id'];
    $title = trim($_POST['song_title']);

    // Check file upload
    if (isset($_FILES['music_file']) && $_FILES['music_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['music_file']['tmp_name'];
        $fileName = uniqid() . '_' . basename($_FILES['music_file']['name']);
        $dest_path = 'uploads/' . $fileName;

        // Ensure uploads directory exists
        if (!is_dir('uploads')) {
            mkdir('uploads', 0777, true);
        }

        // Only allow mp3 files
        $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if ($fileType !== 'mp3') {
            header("Location: artist_dashboard.php?msg=invalidtype");
            exit();
        }

        if (move_uploaded_file($fileTmpPath, $dest_path)) {
            // Insert into DB as pending (approved = 0)
            $stmt = $conn->prepare("INSERT INTO songs (artist_id, title, file_path, approved) VALUES (?, ?, ?, 0)");
            $stmt->bind_param("iss", $artist_id, $title, $dest_path);
            if ($stmt->execute()) {
                header("Location: artist_dashboard.php?msg=success");
            } else {
                header("Location: artist_dashboard.php?msg=dberror");
            }
            $stmt->close();
        } else {
            header("Location: artist_dashboard.php?msg=uploadfail");
        }
    } else {
        header("Location: artist_dashboard.php?msg=nofile");
    }
    exit();
} else {
    header("Location: artist_dashboard.php");
    exit();
}
?>