<?php
session_start();
session_unset();
session_destroy();
// Artık login.php'ye yönlendiriyor
header("Location: login.php");
exit();
?>