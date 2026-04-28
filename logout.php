<?php
// logout.php
require_once __DIR__ . '/db_connect.php';
session_destroy();
header('Location: login.php');
exit;
