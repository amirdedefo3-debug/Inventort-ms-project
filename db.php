<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "inventory_db";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("<div style='color:red; font-family:sans-serif; padding:20px;'>
        <h3>Database Connection Failed</h3>
        <p>" . mysqli_connect_error() . "</p>
        <p>Make sure XAMPP MySQL is running and the database <b>inventory_db</b> exists.</p>
    </div>");
}

mysqli_set_charset($conn, "utf8");
?>
