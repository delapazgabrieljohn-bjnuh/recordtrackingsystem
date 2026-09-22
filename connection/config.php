<?php

function connection(){
    $host = "localhost";
    $user = "root";
    $pass = "";
    $dbname = "recordtrackingsystem";

    $con = new mysqli($host,$user,$pass,$dbname);
    if ($con->connect_error) {
        echo $con->connect_error;
    }else{
        return $con;
    }
}

?>