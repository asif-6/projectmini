<?php
session_start();
session_unset();
session_destroy();
header("Location: index1.html"); // or your login page
exit();
?>