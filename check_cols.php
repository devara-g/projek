<?php
include 'database/conn.php';
$res = mysqli_query($conn, 'SHOW COLUMNS FROM admin');
while ($row = mysqli_fetch_assoc($res)) {
    echo $row['Field'] . ", ";
}
?>
