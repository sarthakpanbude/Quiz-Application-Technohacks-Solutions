<?php
session_start();
$_SESSION['role'] = 'ADMIN';
ob_start();
include 'api.php';
$output = ob_get_clean();
echo $output;
