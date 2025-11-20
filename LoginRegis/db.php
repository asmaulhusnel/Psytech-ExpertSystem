<?php
$host = "localhost";
$user = "root"; // sesuaikan dengan username database kamu
$pass = "";     // sesuaikan password database kamu
$db = "psytech"; // ganti dengan nama database kamu

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>