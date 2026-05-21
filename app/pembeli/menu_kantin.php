<?php
$id = (int)($_GET['id'] ?? 0);
header("Location: kantin_detail.php" . ($id > 0 ? "?id=$id" : ""));
exit();
?>
