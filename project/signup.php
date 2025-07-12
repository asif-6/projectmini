<?php
include 'db.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $role = $_POST['role'];
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if email already exists
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo json_encode([
            "status" => "error",
            "message" => "Email already registered."
        ]);
    } else {
        // Insert new user
        $stmt = $conn->prepare("INSERT INTO users (role, firstname, lastname, email, password) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $role, $firstname, $lastname, $email, $password);
        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;
            $_SESSION['user_id'] = $user_id;
            $_SESSION['firstname'] = $firstname;
            $_SESSION['role'] = $role;
            echo json_encode([
                "status" => "success",
                "role" => $role,
                "message" => "Signup successful."
            ]);
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Signup failed. Please try again."
            ]);
        }
        $stmt->close();
    }
    $check->close();
    $conn->close();
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid request."
    ]);
}
?>