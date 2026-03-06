<?php
include 'database/conn.php';
$res = mysqli_query($conn, "SELECT * FROM admin LIMIT 1");
$fields = mysqli_fetch_fields($res);
foreach ($fields as $field) {
    echo $field->name . ",";
}
?>
