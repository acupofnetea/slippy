<?php
session_start();
// Hapus semua session variables
$_SESSION = array();
// Hancurkan session
session_destroy();
// Redirect ke landing page
header("Location: index.php");
exit;
?>