<?php
$conn = mysqli_connect("localhost", "root", "", "p3p2");

if (mysqli_connect_errno()) {
    echo "database gagal" . mysqli_connect_error();
    exit();
}
