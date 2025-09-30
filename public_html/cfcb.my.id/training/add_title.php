<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'];
    $title = $_POST['title'];

    $stmt = $conn->prepare("INSERT INTO admin_titles (user_id, title) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $title);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Title added successfully!";
    } else {
        $_SESSION['error'] = "Error adding title.";
    }

    header("Location: admin_dashboard.php");
    exit();
}