<?php
include 'auth.php';

if (!is_admin()) {
    header("Location: dashboard.php?admin=denied");
    exit();
}
?>
