<?php
require_once __DIR__ . "/helpers/response.php";

mysqli_report(MYSQLI_REPORT_OFF);

$host = "localhost";   
$user = "root";        
$pass = "";            
$db   = "db_kafe_pos";     

$koneksi = @mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    sendResponse(
        false,
        "Koneksi database gagal",
        null,
        500
    );
}