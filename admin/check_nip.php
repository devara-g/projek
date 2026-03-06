<?php
include '../database/conn.php';

if (isset($_POST['nip'])) {
    $nip = $conn->real_escape_string($_POST['nip']);
    $action = isset($_POST['action']) ? $_POST['action'] : 'add';
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    
    $sql = "SELECT id FROM teachers WHERE nip = '$nip'";
    
    // Jika mode edit, exclude current ID
    if ($action == 'edit' && $id > 0) {
        $sql .= " AND id != $id";
    }
    
    $result = $conn->query($sql);
    
    echo json_encode([
        'exists' => $result->num_rows > 0
    ]);
}
?>