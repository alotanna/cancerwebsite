<?php

$servername = 'localhost';
$username = 'root';
$password = '';
$dbname = 'cancer_website';
$conn = mysqli_connect($servername,$username, $password,$dbname) or die('Unable to connect');

if($conn-> connect_error){
    die('connection failed');
}else{
    //do nothing
    //echo 'connection successful';
}
?>