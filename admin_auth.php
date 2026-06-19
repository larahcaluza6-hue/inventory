<?php
include 'auth.php';

if (!is_admin()) {
    header("Location: products.php?admin=denied");
    exit();
}
?>
