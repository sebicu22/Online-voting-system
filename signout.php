<?php
session_start();
session_unset();
session_destroy();
header('Location: ../Evote platform/login/login.php');
exit();
?>