<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "inventory_db";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("<div style='color:red; font-family:Arial; padding:20px;'>
        <h2>Database Connection Failed</h2>
        <p>" . mysqli_connect_error() . "</p>
        <p>Please make sure XAMPP MySQL is running and the database <strong>inventory_db</strong> exists.</p>
        <p><a href='setup.php'>Click here to run setup</a></p>
    </div>");
}

mysqli_set_charset($conn, "utf8");
?>
