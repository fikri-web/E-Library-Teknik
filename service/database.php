<?php
    $host = "localhost";
    $username = "root";
    $password = "";
    $database = "db_perpus";

    $db = mysqli_connect($host, $username, $password, $database);
    if($db->connect_error) {
        echo "Koneksi database error";
        die(error);
    }
?>