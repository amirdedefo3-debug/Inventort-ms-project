<?php
require_once '../includes/auth.php';
require_admin();
require_once '../db.php';
$id=intval($_GET['id']??0);
if($id>0){
    $p=mysqli_fetch_assoc(mysqli_query($conn,"SELECT name FROM products WHERE id=$id"));
    if($p){mysqli_query($conn,"DELETE FROM products WHERE id=$id");log_activity($conn,"Deleted Product",$p['name']);}
}
header("Location: products.php?msg=deleted"); exit;
