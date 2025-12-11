<?php

// Timezone Indonesia
date_default_timezone_set('Asia/Jakarta');

// Start session sekali saja
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// koneksi ke database
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '123'; // isi kalau kamu pakai password
$DB_NAME = 'sistem_bookingruangan';

$db = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($db->connect_error) {
    die("Koneksi database gagal: " . $db->connect_error);
}

// (Optional) fungsi helper kecil
function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}
