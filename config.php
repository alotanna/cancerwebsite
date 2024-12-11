<?php

$servername = 'localhost';
$username = 'austine.iheji';
$password = 'Benedict12*';
$dbname = 'webtech_fall2024_austine_iheji';
$conn = mysqli_connect($servername,$username, $password, $dbname) or die('Unable to connect');

if($conn-> connect_error){
    die('connection failed');
}else{
    //do nothing
    //echo 'connection successful';
}
?>