<?php
require_once 'db.php';
mysqli_query($conn,"UPDATE notifications SET is_read=1");
echo "ok";
?>
