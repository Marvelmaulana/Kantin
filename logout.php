<?php
session_start();
$_SESSION = [];
session_unset();
session_destroy();

header("Location: app/auth/login.php");
exit();
?>
