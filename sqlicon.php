<?php
$db_host = "localhost";
$db_username = "root";
$db_pass = "";
$db_name = "ticket_system";
$con = mysqli_connect($db_host, $db_username, $db_pass, $db_name);

if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}
?>