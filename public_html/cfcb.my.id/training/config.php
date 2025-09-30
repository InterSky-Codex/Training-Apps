<?php
$host = 'localhost';
$username = 'cfcy1736_root';
$password = '@Harris2025';
$database = 'cfcy1736_training';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}