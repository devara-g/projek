<?php
session_start();
$title = "Pesan Masuk";
include '../database/conn.php';

$pesan_msg = '';
$pesan_type = '';

// Hapus pesan
if (isset($_GET['aksi']) && $_GET['aksi'] == 'hapus' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    if ($id > 0) {
        $stmt = mysqli_prepare($conn, "DELETE FROM pesan WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        if (mysqli_stmt_execute($stmt)) {
            $pesan_msg = 'Pesan berhasil dihapus.';
            $pesan_type = 'success';
        } else {
            $pesan_msg = 'Gagal menghapus pesan.';
            $pesan_type = 'error';
        }
        mysqli_stmt_close($stmt);
    }
}

// Tandai pesan dibaca
if (isset($_GET['aksi']) && $_GET['aksi'] == 'baca' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    if ($id > 0) {
        $stmt = mysqli_prepare($conn, "UPDATE pesan SET dibaca = 1 WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        if (mysqli_stmt_execute($stmt)) {
            $pesan_msg = 'Pesan ditandai telah dibaca.';
            $pesan_type = 'success';
        }
        mysqli_stmt_close($stmt);
    }
}

// Edit pesan
if (isset($_POST['edit_pesan']) && isset($_POST['id'])) {
    $id = (int) $_POST['id'];
    $nama = mysqli_real_escape_string($conn, $_POST['nama'] ?? '');
    $email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
    $subjek = mysqli_real_escape_string($conn, $_POST['subjek'] ?? '');
    $pesan_text = mysqli_real_escape_string($conn, $_POST['pesan'] ?? '');

    if ($id > 0 && $nama && $email && $subjek && $pesan_text) {
        $stmt = mysqli_prepare($conn, "UPDATE pesan SET nama = ?, email = ?, subjek = ?, pesan = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ssssi", $nama, $email, $subjek, $pesan_text, $id);
        if (mysqli_stmt_execute($stmt)) {
            $pesan_msg = 'Pesan berhasil diperbarui.';
            $pesan_type = 'success';
        } else {
            $pesan_msg = 'Gagal memperbarui pesan.';
            $pesan_type = 'error';
        }
        mysqli_stmt_close($stmt);
    }
}

// Hitung statistik pesan dari database
$total_pesan = (int) mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM pesan"))[0];
$belum_dibaca = (int) mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM pesan WHERE COALESCE(dibaca, 0) = 0"))[0];
$sudah_dibaca = (int) mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM pesan WHERE dibaca = 1"))[0];

include 'layout/header.php';
?>

<style>
    /* Messaging System Aesthetics */
    .pesan-container {
        padding: 20px;
    }

    .header-panel {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        margin-bottom: 25px;
    }

    .header-panel h1 {
        font-size: 1.8rem;
        color: var(--primary);
        margin: 0;
    }

    /* Stats Cards */
    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 25px;
    }

    .stat-box {
        background: white;
        padding: 20px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        gap: 15px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        transition: transform 0.3s ease;
    }

    .stat-box:hover {
        transform: translateY(-5px);
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .stat-info h3 {
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0;
        color: var(--primary);
    }

    .stat-info p {
        font-size: 0.85rem;
        color: var(--gray);
        margin: 0;
        font-weight: 500;
    }

    /* Message Table */
    .message-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 25px rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }

    .table-header {
        padding: 20px;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th {
        background: #f8fafc;
        padding: 15px 20px;
        text-align: left;
        color: var(--gray);
        font-weight: 700;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    td {
        padding: 18px 20px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }

    .unread-row {
        background: rgba(59, 130, 246, 0.02);
    }

    .unread-row td {
        font-weight: 600;
        color: var(--primary);
    }

    .sender-info {
        display: flex;
        flex-direction: column;
    }

    .sender-name {
        font-size: 0.95rem;
        color: var(--primary);
    }

    .sender-email {
        font-size: 0.8rem;
        color: var(--gray);
    }

    .msg-subject {
        max-width: 250px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Badges */
    .badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .badge-new {
        background: #dcfce7;
        color: #15803d;
    }

    .badge-read {
        background: #ffedd5;
        color: #ea580c;
    }

    /* Actions */
    .btn-action {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        border: none;
        cursor: pointer;
        transition: 0.2s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
    }

    .btn-view {
        background: rgba(59, 130, 246, 0.1);
        color: #3b82f6;
    }

    .btn-view:hover {
        background: #3b82f6;
        color: white;
    }

    .btn-delete {
        background: rgba(244, 63, 94, 0.1);
        color: #f43f5e;
        margin-left: 8px;
    }

    .btn-delete:hover {
        background: #f43f5e;
        color: white;
    }

    .btn-edit {
        background: rgba(245, 158, 11, 0.1);
        color: #f59e0b;
        margin-left: 4px;
    }

    .btn-edit:hover {
        background: #f59e0b;
        color: white;
    }

    /* Alert Styling */
    .alert-msg {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 18px 24px;
        border-radius: 14px;
        margin-bottom: 20px;
        font-weight: 600;
        font-size: 0.95rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        animation: alertSlideIn 0.4s ease-out;
        border-left: 4px solid;
    }

    .alert-msg i {
        font-size: 1.3rem;
        flex-shrink: 0;
    }

    .alert-success {
        background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
        color: #15803d;
        border-left-color: #22c55e;
    }

    .alert-error {
        background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
        color: #dc2626;
        border-left-color: #ef4444;
    }

    @keyframes alertSlideIn {
        from {
            opacity: 0;
            transform: translateY(-12px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Modal Styling */
    .msg-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(8px);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        padding: 20px;
    }

    .msg-modal {
        background: white;
        width: 100%;
        max-width: 700px;
        border-radius: 24px;
        overflow: hidden;
        animation: modalFadeUp 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    @keyframes modalFadeUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .modal-head {
        padding: 30px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }

    .modal-head h2 {
        font-size: 1.5rem;
        color: var(--primary);
        margin: 0;
    }

    .modal-head p {
        margin: 5px 0 0;
        color: var(--gray);
        font-size: 0.9rem;
    }

    .close-modal {
        background: white;
        border: none;
        width: 35px;
        height: 35px;
        border-radius: 50%;
        cursor: pointer;
        color: var(--gray);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: 0.3s;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .close-modal:hover {
        color: #f43f5e;
        transform: rotate(90deg);
    }

    .modal-body {
        padding: 35px;
    }

    .msg-content-box {
        background: #f8fafc;
        padding: 25px;
        border-radius: 16px;
        line-height: 1.7;
        color: var(--text-main);
        font-size: 1rem;
        margin-top: 20px;
        border-left: 4px solid #3b82f6;
    }

    .modal-footer {
        padding: 20px 35px;
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        display: flex;
        justify-content: flex-end;
    }

    .btn-close-text {
        padding: 10px 25px;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        background: white;
        cursor: pointer;
        font-weight: 600;
        transition: 0.3s;
    }

    .btn-close-text:hover {
        background: #f1f5f9;
    }

    /* View Message Modal - Biru Cerah */
    #msgModal.msg-modal-overlay {
        backdrop-filter: blur(12px);
        background: rgba(59, 130, 246, 0.25);
    }

    #msgModal .msg-modal {
        max-width: 520px;
        box-shadow: 0 40px 100px -20px rgba(59, 130, 246, 0.35), 0 0 0 1px rgba(255, 255, 255, 0.5);
        border-radius: 24px;
        overflow: hidden;
        animation: editModalIn 0.45s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    #msgModal .modal-head {
        background: linear-gradient(135deg, #3b82f6 0%, #0ea5e9 100%);
        padding: 28px 32px;
        position: relative;
        overflow: hidden;
    }

    #msgModal .modal-head::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #60a5fa, #38bdf8);
    }

    #msgModal .modal-head h2 {
        display: flex;
        align-items: center;
        gap: 14px;
        font-weight: 700;
        color: white;
        font-size: 1.25rem;
        margin: 0;
    }

    #msgModal .modal-head h2 i {
        width: 40px;
        height: 40px;
        background: rgba(255, 255, 255, 0.25);
        color: white;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        border: 1px solid rgba(255, 255, 255, 0.3);
    }

    #msgModal .modal-head p,
    #msgModal .modal-head #modalEmail {
        color: rgba(255, 255, 255, 0.95) !important;
        margin: 4px 0 0;
        font-size: 0.9rem;
    }

    #msgModal .modal-head #modalEmail {
        color: #dbeafe !important;
    }

    #msgModal .close-modal {
        background: rgba(255, 255, 255, 0.2);
        color: white;
    }

    #msgModal .close-modal:hover {
        background: rgba(255, 255, 255, 0.4);
        color: white;
    }

    #msgModal .modal-body {
        padding: 32px 36px;
        background: linear-gradient(180deg, #eff6ff 0%, #dbeafe 100%);
    }

    #msgModal .msg-content-box {
        background: white;
        padding: 24px;
        border-radius: 16px;
        line-height: 1.7;
        color: #334155;
        font-size: 0.95rem;
        margin-top: 16px;
        border-left: 4px solid #3b82f6;
        box-shadow: 0 2px 12px rgba(59, 130, 246, 0.1);
    }

    #msgModal .modal-footer {
        padding: 24px 36px;
        background: white;
        border-top: 1px solid #e2e8f0;
        gap: 12px;
    }

    #msgModal .btn-close-text {
        border: 2px solid #e2e8f0;
        color: #64748b;
        background: white;
        padding: 12px 24px;
        border-radius: 12px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    #msgModal .btn-close-text:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
    }

    .btn-mark-read {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 12px 24px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.95rem;
        border: none;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.3s ease;
        background: linear-gradient(135deg, #3b82f6 0%, #0ea5e9 100%);
        color: white;
        box-shadow: 0 8px 20px rgba(59, 130, 246, 0.35);
    }

    .btn-mark-read:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 28px rgba(59, 130, 246, 0.45);
        color: white;
    }

    .btn-mark-read i {
        font-size: 1rem;
    }

    /* Edit Modal - Biru Cerah */
    #editModal.msg-modal-overlay {
        backdrop-filter: blur(12px);
        background: rgba(59, 130, 246, 0.25);
        animation: overlayFadeIn 0.3s ease;
    }

    @keyframes overlayFadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    #editModal .msg-modal {
        max-height: 90vh;
        display: flex;
        flex-direction: column;
        max-width: 520px;
        box-shadow: 0 40px 100px -20px rgba(59, 130, 246, 0.35), 0 0 0 1px rgba(255, 255, 255, 0.5);
        border-radius: 24px;
        overflow: hidden;
        animation: editModalIn 0.45s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    @keyframes editModalIn {
        from {
            opacity: 0;
            transform: scale(0.9) translateY(20px);
        }

        to {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }

    #editModal .modal-head {
        background: linear-gradient(135deg, #3b82f6 0%, #0ea5e9 100%);
        padding: 28px 32px;
        position: relative;
        overflow: hidden;
    }

    #editModal .modal-head::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #60a5fa, #38bdf8);
    }

    #editModal .modal-head h2 {
        display: flex;
        align-items: center;
        gap: 14px;
        font-weight: 700;
        color: white;
        font-size: 1.35rem;
        margin: 0;
    }

    #editModal .modal-head h2 i {
        width: 44px;
        height: 44px;
        background: rgba(255, 255, 255, 0.25);
        color: white;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        border: 1px solid rgba(255, 255, 255, 0.3);
    }

    #editModal .close-modal {
        background: rgba(255, 255, 255, 0.2);
        color: white;
    }

    #editModal .close-modal:hover {
        background: rgba(255, 255, 255, 0.4);
        color: white;
    }

    #editModal form {
        display: flex;
        flex-direction: column;
        flex: 1;
        min-height: 0;
        overflow: hidden;
    }

    .edit-modal-scroll {
        overflow-y: auto;
        flex: 1 1 auto;
        min-height: 0;
        padding: 32px 36px;
        background: linear-gradient(180deg, #eff6ff 0%, #dbeafe 100%);
    }

    .edit-modal-scroll .edit-form-group {
        animation: formFieldIn 0.4s ease backwards;
    }

    .edit-modal-scroll .edit-form-group:nth-child(1) {
        animation-delay: 0.05s;
    }

    .edit-modal-scroll .edit-form-group:nth-child(2) {
        animation-delay: 0.1s;
    }

    .edit-modal-scroll .edit-form-group:nth-child(3) {
        animation-delay: 0.15s;
    }

    .edit-modal-scroll .edit-form-group:nth-child(4) {
        animation-delay: 0.2s;
    }

    @keyframes formFieldIn {
        from {
            opacity: 0;
            transform: translateY(12px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    #editModal .modal-footer {
        flex-shrink: 0;
        padding: 24px 36px;
        background: white;
        border-top: 1px solid #e2e8f0;
        gap: 12px;
    }

    .edit-modal-scroll::-webkit-scrollbar {
        width: 6px;
    }

    .edit-modal-scroll::-webkit-scrollbar-track {
        background: rgba(0, 0, 0, 0.05);
        border-radius: 3px;
    }

    .edit-modal-scroll::-webkit-scrollbar-thumb {
        background: linear-gradient(180deg, #60a5fa, #3b82f6);
        border-radius: 3px;
    }

    .edit-modal-scroll::-webkit-scrollbar-thumb:hover {
        background: #2563eb;
    }

    .edit-form-group {
        margin-bottom: 24px;
        position: relative;
    }

    .edit-form-group:last-child {
        margin-bottom: 0;
    }

    .edit-form-group label {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 10px;
        font-weight: 600;
        color: #334155;
        font-size: 0.9rem;
    }

    .edit-form-group label i {
        width: 34px;
        height: 34px;
        background: rgba(59, 130, 246, 0.15);
        color: #3b82f6;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        transition: all 0.3s ease;
    }

    .edit-form-group:focus-within label i {
        background: rgba(59, 130, 246, 0.25);
        color: #2563eb;
        transform: scale(1.05);
    }

    .edit-form-group input,
    .edit-form-group textarea {
        width: 100%;
        padding: 16px 20px;
        border: 2px solid transparent;
        border-radius: 14px;
        font-size: 0.95rem;
        outline: none;
        transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        background: white;
        color: #1e293b;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    .edit-form-group input::placeholder,
    .edit-form-group textarea::placeholder {
        color: #94a3b8;
    }

    .edit-form-group input:hover,
    .edit-form-group textarea:hover {
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.08);
    }

    .edit-form-group input:focus,
    .edit-form-group textarea:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
    }

    .edit-form-group textarea {
        resize: vertical;
        min-height: 130px;
        line-height: 1.6;
    }

    #editModal .btn-close-text {
        padding: 11px 25px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: none;
        background: #f1f5f9;
        color: #475569;
        display: flex;
        align-items: center;
        gap: 10px;
        letter-spacing: 0.3px;
        text-decoration: none;
    }

    #editModal .btn-close-text:hover {
        background: #e2e8f0;
        color: #0f172a;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }

    #editModal .btn-submit {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 14px 28px;
        border-radius: 14px;
        font-weight: 700;
        font-size: 1rem;
        border: none;
        cursor: pointer;
        background: linear-gradient(135deg, #3b82f6 0%, #0ea5e9 100%);
        color: white;
        box-shadow: 0 8px 24px rgba(59, 130, 246, 0.4);
        transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        letter-spacing: 0.3px;
    }

    #editModal .btn-submit i {
        font-size: 1.1rem;
    }

    #editModal .btn-submit:hover {
        transform: translateY(-3px);
        box-shadow: 0 14px 40px rgba(59, 130, 246, 0.5);
        background: linear-gradient(135deg, #2563eb 0%, #0284c7 100%);
    }

    #editModal .btn-submit:active {
        transform: translateY(-1px);
    }

    /* Delete Confirmation Modal */
    .delete-confirm-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(8px);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 10000;
        padding: 20px;
    }

    .delete-confirm-modal {
        background: white;
        border-radius: 20px;
        padding: 32px;
        max-width: 420px;
        width: 100%;
        text-align: center;
        box-shadow: 0 25px 70px rgba(0, 0, 0, 0.2);
        animation: modalFadeUp 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .delete-confirm-icon {
        width: 72px;
        height: 72px;
        background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
        color: #ef4444;
        font-size: 2rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        border: 3px solid rgba(239, 68, 68, 0.2);
    }

    .delete-confirm-modal h3 {
        font-size: 1.25rem;
        color: var(--primary);
        margin: 0 0 10px;
        font-weight: 700;
    }

    .delete-confirm-modal p {
        color: #64748b;
        font-size: 0.95rem;
        margin: 0 0 28px;
        line-height: 1.5;
    }

    .delete-confirm-btns {
        display: flex;
        gap: 12px;
        justify-content: center;
        flex-wrap: wrap;
    }

    .delete-confirm-btns .btn-cancel-delete {
        padding: 11px 25px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: none;
        background: #f1f5f9;
        color: #475569;
        display: flex;
        align-items: center;
        gap: 10px;
        letter-spacing: 0.3px;
        text-decoration: none;
    }

    .delete-confirm-btns .btn-cancel-delete:hover {
        background: #e2e8f0;
        color: #0f172a;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }

    .delete-confirm-btns .btn-confirm-delete {
        padding: 12px 24px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.95rem;
        cursor: pointer;
        transition: all 0.3s ease;
        border: none;
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
        box-shadow: 0 8px 20px rgba(239, 68, 68, 0.35);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .delete-confirm-btns .btn-confirm-delete:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 28px rgba(239, 68, 68, 0.45);
        color: white;
    }

    @media (max-width: 768px) {
        .stat-info p {
            display: none;
        }

        .msg-subject {
            max-width: 150px;
        }

        .edit-modal-scroll {
            padding: 20px;
        }
    }
</style>

<div class="pesan-container">
    <?php if ($pesan_msg): ?>
        <div class="alert-msg alert-<?= $pesan_type ?>">
            <i class="fas fa-<?= $pesan_type === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <span><?= htmlspecialchars($pesan_msg) ?></span>
        </div>
    <?php endif; ?>
    <div class="header-panel stagger-item stagger-1">
        <div>
            <h1>Pesan Masuk</h1>
            <p style="color: var(--gray);">Pantau dan kelola semua feedback dari pengunjung website.</p>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="stats-row stagger-item stagger-2">
        <div class="stat-box">
            <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                <i class="fas fa-inbox"></i>
            </div>
            <div class="stat-info">
                <h3><?= $total_pesan ?></h3>
                <p>Total Pesan</p>
            </div>
        </div>
        <div class="stat-box">
            <div class="stat-icon" style="background: rgba(34, 197, 94, 0.1); color: #22c55e;">
                <i class="fas fa-envelope"></i>
            </div>
            <div class="stat-info">
                <h3><?= $belum_dibaca ?></h3>
                <p>Belum Dibaca</p>
            </div>
        </div>
        <div class="stat-box">
            <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                <i class="fas fa-envelope-open-text"></i>
            </div>
            <div class="stat-info">
                <h3><?= $sudah_dibaca ?></h3>
                <p>Sudah Dibaca</p>
            </div>
        </div>
    </div>

    <!-- Message List -->
    <div class="message-card stagger-item stagger-3">
        <div class="table-header">
            <h3 style="font-weight: 700; color: var(--primary);">Kotak Masuk</h3>
            <div style="position: relative;">
                <i class="fas fa-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 0.9rem;"></i>
                <input type="text" placeholder="Cari pesan..." style="padding: 10px 15px 10px 40px; border: 1.5px solid #e2e8f0; border-radius: 12px; font-size: 0.9rem; outline: none; width: 250px; transition: 0.3s;">
            </div>
        </div>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th style="width: 50px;">Status</th>
                        <th>Pengirim</th>
                        <th>Subjek</th>
                        <th>Waktu</th>
                        <th style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $data = mysqli_query($conn, "SELECT * FROM pesan ORDER BY id DESC") or die(mysqli_error($conn));
                    while ($row = mysqli_fetch_array($data)) {
                        $dibaca = !empty($row['dibaca']);
                    ?>
                        <tr class="<?= $dibaca ? '' : 'unread-row' ?>">
                            <td>
                                <?php if ($dibaca): ?>
                                    <span class="badge badge-read"><i class="fas fa-check-double" style="font-size: 0.7rem;"></i> read</span>
                                <?php else: ?>
                                    <span class="badge badge-new"><i class="fas fa-circle" style="font-size: 6px;"></i> unread</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="sender-info">
                                    <span class="sender-name"><?php echo $row['nama']; ?></span>
                                    <span class="sender-email"><?php echo $row['email']; ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="msg-subject"><?php echo $row['subjek']; ?></div>
                            </td>
                            <td style="font-size: 0.85rem; color: #94a3b8; white-space: nowrap;">
                                <i class="far fa-clock" style="margin-right: 5px;"></i>
                                <?= date('d M Y, H:i', strtotime($row['created_at'])) ?>
                            </td>
                            <td style="text-align: right;">
                                <button class="btn-action btn-view"
                                    data-id="<?= (int)($row['id'] ?? 0) ?>"
                                    data-nama="<?= htmlspecialchars($row['nama']) ?>"
                                    data-email="<?= htmlspecialchars($row['email']) ?>"
                                    data-subjek="<?= htmlspecialchars($row['subjek']) ?>"
                                    data-pesan="<?= htmlspecialchars($row['pesan']) ?>"
                                    data-date="<?= date('d M Y, H:i', strtotime($row['created_at'])) ?>"
                                    onclick="openMessage(this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn-action btn-edit"
                                    data-id="<?= (int)($row['id'] ?? 0) ?>"
                                    data-nama="<?= htmlspecialchars($row['nama']) ?>"
                                    data-email="<?= htmlspecialchars($row['email']) ?>"
                                    data-subjek="<?= htmlspecialchars($row['subjek']) ?>"
                                    data-pesan="<?= htmlspecialchars($row['pesan']) ?>"
                                    onclick="openEdit(this)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn-action btn-delete" data-delete-url="?aksi=hapus&id=<?= (int)($row['id'] ?? 0) ?>" onclick="openDeleteConfirm(this)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php } ?>

                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Message Detail Modal -->
<div class="msg-modal-overlay" id="msgModal">
    <div class="msg-modal">
        <div class="modal-head">
            <div>
                <h2><i class="fas fa-envelope-open-text"></i> <span id="modalSubject">Detail Pesan</span></h2>
                <p id="modalSender">Dari: -</p>
                <div id="modalEmail"></div>
            </div>
            <button class="close-modal" onclick="closeMessage()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div style="font-size: 0.8rem; color: #64748b; display: flex; align-items: center; gap: 8px;">
                <i class="far fa-clock"></i> <span id="modalDate">-</span>
            </div>
            <div class="msg-content-box" id="modalContent">
                Memuat pesan...
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-close-text" onclick="closeMessage()">Tutup</button>
            <a href="#" class="btn-mark-read" id="markReadBtn">
                <i class="fas fa-check-double"></i> Tandai Pesan Telah Dibaca
            </a>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="delete-confirm-overlay" id="deleteModal" onclick="if(event.target===this) closeDeleteConfirm()">
    <div class="delete-confirm-modal" onclick="event.stopPropagation()">
        <div class="delete-confirm-icon">
            <i class="fas fa-trash-alt"></i>
        </div>
        <h3>Hapus Pesan?</h3>
        <p>Yakin ingin menghapus pesan ini? Tindakan ini tidak dapat dibatalkan.</p>
        <div class="delete-confirm-btns">
            <button type="button" class="btn-cancel-delete" onclick="closeDeleteConfirm()">
                <i class="fas fa-times"></i> Batal
            </button>
            <a href="#" class="btn-confirm-delete" id="confirmDeleteBtn">
                <i class="fas fa-trash"></i> Hapus
            </a>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="msg-modal-overlay" id="editModal">
    <div class="msg-modal">
        <div class="modal-head">
            <h2><i class="fas fa-pen"></i> Edit Pesan</h2>
            <button class="close-modal" onclick="closeEdit()"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="id" id="editId">
            <div class="edit-modal-scroll">
                <div class="edit-form-group">
                    <label><i class="fas fa-user"></i> Nama</label>
                    <input type="text" name="nama" id="editNama" required placeholder="Nama pengirim">
                </div>
                <div class="edit-form-group">
                    <label><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" name="email" id="editEmail" required placeholder="alamat@email.com">
                </div>
                <div class="edit-form-group">
                    <label><i class="fas fa-tag"></i> Subjek</label>
                    <input type="text" name="subjek" id="editSubjek" required placeholder="Subjek pesan">
                </div>
                <div class="edit-form-group">
                    <label><i class="fas fa-comment-alt"></i> Isi Pesan</label>
                    <textarea name="pesan" id="editPesan" rows="6" required placeholder="Tulis isi pesan..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-close-text" onclick="closeEdit()">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="submit" name="edit_pesan" class="btn-submit" style="margin: 0 0 0 15px;">
                    <i class="fas fa-save"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openMessage(btn) {
        const d = btn.dataset;
        const id = d.id || '';
        document.getElementById('modalSender').innerText = "Dari: " + (d.nama || '');
        document.getElementById('modalEmail').innerText = d.email || '';
        document.getElementById('modalEmail').style.cssText = 'font-size: 0.9rem; color: #60a5fa !important; font-weight: 500; margin-top: 4px;';
        document.getElementById('modalSubject').innerText = d.subjek || 'Detail Pesan';
        document.getElementById('modalContent').innerText = d.pesan || '';
        document.getElementById('modalDate').innerText = d.date || '-';
        document.getElementById('markReadBtn').href = id ? '?aksi=baca&id=' + id : '#';
        document.getElementById('msgModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeMessage() {
        document.getElementById('msgModal').style.display = 'none';
        document.body.style.overflow = '';
    }

    function openEdit(btn) {
        const d = btn.dataset;
        document.getElementById('editId').value = d.id || '';
        document.getElementById('editNama').value = d.nama || '';
        document.getElementById('editEmail').value = d.email || '';
        document.getElementById('editSubjek').value = d.subjek || '';
        document.getElementById('editPesan').value = d.pesan || '';
        document.getElementById('editModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeEdit() {
        document.getElementById('editModal').style.display = 'none';
        document.body.style.overflow = '';
    }

    function openDeleteConfirm(btn) {
        const url = btn.getAttribute('data-delete-url') || '#';
        document.getElementById('confirmDeleteBtn').href = url;
        document.getElementById('deleteModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeDeleteConfirm() {
        document.getElementById('deleteModal').style.display = 'none';
        document.body.style.overflow = '';
    }

    // Pindahkan modal ke body agar fixed relative ke viewport (bukan main-content yg punya transform)
    document.addEventListener('DOMContentLoaded', function() {
        const editModal = document.getElementById('editModal');
        const msgModal = document.getElementById('msgModal');
        const deleteModal = document.getElementById('deleteModal');
        if (editModal) document.body.appendChild(editModal);
        if (msgModal) document.body.appendChild(msgModal);
        if (deleteModal) document.body.appendChild(deleteModal);
    });

    // Search Interaction
    const searchInput = document.querySelector('input[type="text"]');
    if (searchInput) {
        searchInput.addEventListener('focus', function() {
            this.style.borderColor = '#3b82f6';
            this.style.boxShadow = '0 0 0 4px rgba(59, 130, 246, 0.1)';
        });
        searchInput.addEventListener('blur', function() {
            this.style.borderColor = '#e2e8f0';
            this.style.boxShadow = 'none';
        });
    }
</script>

<?php include 'layout/footer.php'; ?>