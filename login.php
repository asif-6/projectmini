<?php

include 'db.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $role = $_POST['role'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Fixed admin credentials
    $admin_username = 'admin@soundnext.com';
    $admin_password = 'Admin@123'; // Change this to your desired password

    if ($role === 'admin') {
        if ($email === $admin_username && $password === $admin_password) {
            $_SESSION['user_id'] = 0; // No DB id for admin
            $_SESSION['firstname'] = 'Admin';
            $_SESSION['lastname'] = '';
            $_SESSION['role'] = 'admin';
            header("Location: admin_dasboard.php");
            exit();
        } else {
            echo "Invalid admin credentials.";
        }
    } 
    else {
        // Normal user login
        $stmt = $conn->prepare("SELECT id, password, firstname, lastname, role FROM users WHERE email = ? AND role = ?");
        $stmt->bind_param("ss", $email, $role);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($id, $hashed_password, $firstname, $lastname, $db_role);
            $stmt->fetch();
            if (password_verify($password, $hashed_password)) {
                $_SESSION['user_id'] = $id;
                $_SESSION['firstname'] = $firstname;
                $_SESSION['lastname'] = $lastname;
                $_SESSION['role'] = $db_role;
                if ($db_role === 'artist') {
                    header("Location: artist_dashboard.php");
                } elseif ($db_role === 'listener') {
                    header("Location: index.php");
                } else {
                    header("Location: index.php");
                }
                exit();
            } else {
                echo "Invalid password.";
            }
        } else {
            echo "Invalid email or role.";
        }
        $stmt->close();
        $conn->close();
    }
}
?>