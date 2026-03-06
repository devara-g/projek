<?php
include "../database/conn.php";

header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    $stmt = mysqli_prepare($conn, "SELECT * FROM agenda WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);

    if ($data) {
        echo json_encode([
            'success' => true,
            'data' => [
                'id' => $data['id'],
                'judul' => $data['judul'],
                'tanggal' => $data['tanggal'],
                'waktu' => $data['waktu'],
                'lokasi' => $data['lokasi'],
                'keterangan' => $data['deskripsi'], // Note: column name is deskripsi in DB
                'status' => $data['status'],
                'foto' => $data['foto']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan']);
    }
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['success' => false, 'message' => 'ID tidak diberikan']);
}
?>