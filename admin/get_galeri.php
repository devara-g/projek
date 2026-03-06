<?php
include "../database/conn.php";

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $query = mysqli_query($conn, "SELECT * FROM galeri WHERE id = $id");

    if ($row = mysqli_fetch_assoc($query)) {
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'ID tidak diberikan']);
}
