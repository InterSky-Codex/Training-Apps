<?php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'cfcy1736_training';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}