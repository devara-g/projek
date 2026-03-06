<?php
$title = "Manajemen Data Kepsek";
include '../database/conn.php';

// Proses CRUD
$message = '';
$messageType = '';

// Tangkap pesan dari redirect
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'updated') {
        $message = "Data Kepala Sekolah berhasil diupdate!";
        $messageType = "success";
    }
}

// =============================================
// DAFTAR NILAI ENUM SESUAI DATABASE
// enum('kepsek dan wakasek', 'tata usaha', 'sekre', 'wakil ketua')
// =============================================
$positionOptions = [
    'kepsek dan wakasek' => 'Kepsek dan Wakasek',
    'tata usaha'         => 'Tata Usaha',
    'sekre'              => 'Sekretaris',
    'wakil ketua'        => 'Wakil Ketua',
];

// HANDLE TAMBAH DATA
if (isset($_POST['action']) && $_POST['action'] == 'add') {
    $nama     = $conn->real_escape_string($_POST['nama']);
    $nip      = $conn->real_escape_string($_POST['nip']);
    $position = $conn->real_escape_string($_POST['position']);

    // Handle upload foto
    $photo_filename = '';
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $photo_filename = uniqid() . '.' . $ext;
        $upload_path = '../upload/img/' . $photo_filename;
        move_uploaded_file($_FILES['foto']['tmp_name'], $upload_path);
    }

    $sql = "INSERT INTO kepsek (name, nip, position, photo_filename) 
            VALUES ('$nama', '$nip', '$position', '$photo_filename')";

    if ($conn->query($sql)) {
        $message = "Data Kepala Sekolah berhasil ditambahkan!";
        $messageType = "success";
    } else {
        if ($conn->errno == 1062) {
            $message = "NIP <strong>" . htmlspecialchars($_POST['nip']) . "</strong> sudah terdaftar! Gunakan NIP yang berbeda.";
            $messageType = "warning";
        } else {
            $message = "Error: " . $conn->error;
            $messageType = "error";
        }
    }
}

// HANDLE EDIT DATA
if (isset($_POST['action']) && $_POST['action'] == 'edit') {
    $id       = (int) $_POST['id'];
    $nama     = $conn->real_escape_string($_POST['nama']);
    $nip      = $conn->real_escape_string($_POST['nip']);
    $position = $conn->real_escape_string($_POST['position']);

    // Cek apakah ada upload foto baru
    $foto_sql = "";
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        // Hapus foto lama
        $result = $conn->query("SELECT photo_filename FROM kepsek WHERE id = $id");
        $old = $result->fetch_assoc();
        if ($old['photo_filename'] && file_exists('../upload/img/' . $old['photo_filename'])) {
            unlink('../upload/img/' . $old['photo_filename']);
        }

        // Upload foto baru
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $photo_filename = uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['foto']['tmp_name'], '../upload/img/' . $photo_filename);
        $foto_sql = ", photo_filename = '$photo_filename'";
    }

    $sql = "UPDATE kepsek SET 
            name     = '$nama',
            nip      = '$nip',
            position = '$position'
            $foto_sql
            WHERE id = $id";

    if ($conn->query($sql)) {
        header("Location: kepsek.php?msg=updated");
        exit;
    } else {
        if ($conn->errno == 1062) {
            $message = "NIP <strong>" . htmlspecialchars($_POST['nip']) . "</strong> sudah terdaftar! Gunakan NIP yang berbeda.";
            $messageType = "warning";
        } else {
            $message = "Error: " . $conn->error;
            $messageType = "error";
        }
    }
}

// HANDLE HAPUS DATA
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];

    // Hapus foto
    $result = $conn->query("SELECT photo_filename FROM kepsek WHERE id = $id");
    $data = $result->fetch_assoc();
    if ($data['photo_filename'] && file_exists('../upload/img/' . $data['photo_filename'])) {
        unlink('../upload/img/' . $data['photo_filename']);
    }

    if ($conn->query("DELETE FROM kepsek WHERE id = $id")) {
        $message = "Data Kepala Sekolah berhasil dihapus!";
        $messageType = "success";
    } else {
        $message = "Error: " . $conn->error;
        $messageType = "error";
    }
}

// AMBIL DATA UNTUK EDIT
$editData = null;
if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    $result = $conn->query("SELECT * FROM kepsek WHERE id = $id");
    $editData = $result->fetch_assoc();
}

// AMBIL SEMUA DATA
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$sql = "SELECT * FROM kepsek";
if ($search) {
    $sql .= " WHERE name LIKE '%$search%' OR nip LIKE '%$search%' OR position LIKE '%$search%'";
}
// ORDER BY sesuai urutan logis jabatan
$sql .= " ORDER BY FIELD(position, 'kepsek dan wakasek', 'wakil ketua', 'sekre', 'tata usaha'), id ASC";
$result = $conn->query($sql);

include 'layout/header.php';
?>

<div class="admin-page-container">
    <!-- Alert Message -->
    <?php if ($message): ?>
        <div class="alert-container" id="alertContainer">
            <div class="alert alert-<?php echo $messageType; ?>">
                <i class="fas <?php
                    if ($messageType == 'success') echo 'fa-check-circle';
                    elseif ($messageType == 'warning') echo 'fa-exclamation-triangle';
                    else echo 'fa-exclamation-circle';
                ?>"></i>
                <div class="alert-content">
                    <div class="alert-title">
                        <?php
                        if ($messageType == 'success') echo 'Berhasil!';
                        elseif ($messageType == 'warning') echo 'Peringatan!';
                        else echo 'Gagal!';
                        ?>
                    </div>
                    <div class="alert-message">
                        <?php echo $message; ?>
                    </div>
                </div>
                <div class="alert-close" onclick="this.closest('.alert-container').remove()">
                    <i class="fas fa-times"></i>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="header-panel stagger-item stagger-1">
        <div>
            <h1>Data Kepala Sekolah</h1>
            <p style="color: var(--gray);">Kelola informasi Kepala Sekolah SMP PGRI 3 BOGOR.</p>
        </div>
        <button class="btn-add" onclick="openAddForm()">
            <i class="fas fa-plus"></i> Tambah Data
        </button>
    </div>

    <!-- Toolbar & Search -->
    <div class="card-panel stagger-item stagger-2" style="margin-bottom: 2rem; border-radius: 20px;">
        <div class="table-header" style="border-bottom: none; padding-bottom: 0; margin-bottom: 0; box-shadow: none; border: none; padding: 0;">
            <h3><i class="fas fa-school" style="margin-right: 10px; color: var(--accent);"></i> Daftar Kepala Sekolah</h3>
            <form method="GET" style="display: flex; gap: 10px; align-items: center;">
                <div class="header-search">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" placeholder="Cari nama atau NIP..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <button type="submit" class="btn-submit" style="padding: 10px 15px; width: auto; margin:0;">
                    <i class="fas fa-search"></i>
                </button>
                <?php if ($search): ?>
                    <a href="kepsek.php" class="btn-cancel" style="padding: 10px 15px; text-decoration:none;">Reset</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Kepsek Card Grid Layout -->
    <div class="teacher-grid">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php
            $delay = 0;
            while ($row = $result->fetch_assoc()):
                $delay += 0.05;
                // Tampilkan label yang lebih rapi untuk card
                $posLabel = $positionOptions[$row['position']] ?? ucwords($row['position']);
            ?>
                <div class="teacher-card" style="animation-delay: <?php echo $delay; ?>s;">
                    <div class="teacher-img-wrapper">
                        <?php if ($row['photo_filename'] && file_exists('../upload/img/' . $row['photo_filename'])): ?>
                            <img src="../upload/img/<?php echo htmlspecialchars($row['photo_filename']); ?>" class="teacher-img">
                        <?php else: ?>
                            <div class="teacher-initial">
                                <?php echo strtoupper(substr($row['name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="teacher-info">
                        <h3><?php echo htmlspecialchars($row['name']); ?></h3>

                        <?php if ($row['nip']): ?>
                            <span class="teacher-nip">
                                <i class="fas fa-id-badge" style="margin-right: 5px; opacity: 0.7;"></i>
                                <?php echo htmlspecialchars($row['nip']); ?>
                            </span>
                        <?php endif; ?>

                        <div class="teacher-role">
                            <span class="badge badge-kepsek">
                                <?php echo htmlspecialchars($posLabel); ?>
                            </span>
                        </div>
                    </div>

                    <div class="teacher-actions">
                        <a href="?edit=<?php echo $row['id']; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="btn-card-action" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="javascript:void(0)" onclick="openDeleteModal('<?php echo $row['id']; ?>', '<?php echo htmlspecialchars(addslashes($row['name'])); ?>', '<?php echo htmlspecialchars(addslashes($row['nip'])); ?>', '<?php echo htmlspecialchars(addslashes($posLabel)); ?>')" class="btn-card-action delete" title="Hapus">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-grid-state">
                <i class="fas fa-school empty-icon"></i>
                <h3>Data Tidak Ditemukan</h3>
                <p style="color: var(--gray);">Belum ada data Kepala Sekolah atau pencarian tidak sesuai.</p>
                <div style="margin-top: 1.5rem;">
                    <button class="btn-add" onclick="openAddForm()">
                        <i class="fas fa-plus"></i> Tambah Kepala Sekolah
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Form -->
<div class="modal-overlay" id="formOverlay" style="display: <?php echo $editData ? 'flex' : 'none'; ?>;">
    <div class="modal-card">
        <div class="modal-header">
            <h2 id="formTitle">
                <i class="fas <?php echo $editData ? 'fa-user-edit' : 'fa-user-plus'; ?>"></i>
                <?php echo $editData ? 'Edit Data Kepala Sekolah' : 'Tambah Data Kepala Sekolah'; ?>
            </h2>
            <div class="btn-close-modal" onclick="closeForm()"><i class="fas fa-times"></i></div>
        </div>
        <form action="" method="POST" enctype="multipart/form-data" id="kepsekForm" style="display: contents;">
            <input type="hidden" name="action" value="<?php echo $editData ? 'edit' : 'add'; ?>" id="action">
            <?php if ($editData): ?>
                <input type="hidden" name="id" value="<?php echo $editData['id']; ?>" id="kepsekId">
            <?php endif; ?>

            <div class="modal-body">
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama" id="nama" required placeholder="Masukkan nama lengkap"
                           value="<?php echo $editData ? htmlspecialchars($editData['name']) : ''; ?>">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>NIP</label>
                        <input type="text" name="nip" id="nip" placeholder="Contoh: 196708151992031002"
                               value="<?php echo $editData ? htmlspecialchars($editData['nip']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Jabatan</label>
                        <select name="position" id="position" required>
                            <option value="">Pilih Jabatan</option>
                            <?php foreach ($positionOptions as $val => $label): ?>
                                <option value="<?php echo htmlspecialchars($val); ?>"
                                    <?php echo ($editData && $editData['position'] === $val) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Foto Profil</label>
                    <?php if ($editData && $editData['photo_filename'] && file_exists('../upload/img/' . $editData['photo_filename'])): ?>
                        <div id="existingPhotoWrapper" style="margin-bottom: 10px; text-align: center;">
                            <img src="../upload/img/<?php echo htmlspecialchars($editData['photo_filename']); ?>"
                                 style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary-light);">
                            <p style="font-size: 0.75rem; color: var(--gray); margin-top: 3px;">Foto saat ini</p>
                        </div>
                    <?php else: ?>
                        <div id="existingPhotoWrapper" style="display:none;"></div>
                    <?php endif; ?>
                    <div class="file-upload-wrapper">
                        <input type="file" name="foto" class="file-upload-input" id="fotoInput" onchange="previewFile(this)" accept="image/*">
                        <div class="file-upload-label">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <span>Klik atau seret foto ke sini</span>
                            <span class="file-name" id="fileName"></span>
                        </div>
                    </div>
                </div>
                <div id="imagePreview" style="display: none; margin-top: 15px; border-radius: 50%; width: 80px; height: 80px; overflow: hidden; border: 2px solid #e2e8f0; margin-left: auto; margin-right: auto;">
                    <img src="" id="previewImg" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeForm()">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="submit" class="btn-submit" id="submitBtn">
                    <i class="fas fa-save"></i>
                    <span id="btnText"><?php echo $editData ? 'Update' : 'Simpan'; ?></span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="delete-modal-overlay" id="deleteModal">
    <div class="delete-modal-card" id="deleteModalCard">
        <div class="delete-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h3>Hapus Data?</h3>
        <p class="warning-text">Anda akan menghapus data Kepala Sekolah berikut:</p>
        <div class="highlight-text" id="deleteKepsekInfo">
            <p><i class="fas fa-user"></i> <strong id="deleteKepsekName"></strong></p>
            <p><i class="fas fa-id-card"></i> NIP: <span id="deleteKepsekNip"></span></p>
            <p><i class="fas fa-tag"></i> <span id="deleteKepsekCategory"></span></p>
        </div>
        <p style="color: #94a3b8; font-size: 0.9rem; margin: 8px 0 0;">
            <i class="fas fa-info-circle"></i> Tindakan ini tidak dapat dibatalkan
        </p>
        <div class="delete-actions">
            <button class="btn-cancel" onclick="closeDeleteModal()">
                <i class="fas fa-times"></i> Batal
            </button>
            <a href="#" class="btn-confirm-delete" id="confirmDeleteBtn">
                <i class="fas fa-trash"></i> Hapus
            </a>
        </div>
    </div>
</div>

<script>
    function openAddForm() {
        document.querySelectorAll('#formOverlay input:not([type="hidden"]), #formOverlay select').forEach(el => {
            el.value = '';
        });

        document.querySelector('input[name="action"]').value = 'add';

        const idInput = document.querySelector('input[name="id"]');
        if (idInput) idInput.remove();

        const existingPhotoWrapper = document.getElementById('existingPhotoWrapper');
        if (existingPhotoWrapper) existingPhotoWrapper.style.display = 'none';

        document.getElementById('imagePreview').style.display = 'none';
        document.getElementById('fileName').style.display = 'none';
        document.getElementById('fileName').textContent = '';

        document.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));
        document.querySelectorAll('.error-message').forEach(el => {
            el.classList.remove('show');
            el.textContent = '';
        });

        document.getElementById('formTitle').innerHTML = '<i class="fas fa-user-plus"></i> Tambah Data Kepala Sekolah';
        document.getElementById('btnText').innerText = 'Simpan';

        // Tampilkan overlay dengan bounce animation
        const overlay = document.getElementById('formOverlay');
        const modalCard = overlay.querySelector('.modal-card');
        modalCard.style.animation = 'none';
        modalCard.offsetHeight; // Trigger reflow
        modalCard.style.animation = 'editModalIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1)';
        
        overlay.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeForm() {
        document.getElementById('formOverlay').style.display = 'none';
        document.body.style.overflow = '';

        const url = new URL(window.location);
        url.searchParams.delete('edit');
        window.history.replaceState({}, '', url);
    }

    function previewFile(input) {
        const file = input.files[0];
        if (file) {
            document.getElementById('fileName').textContent = file.name;
            document.getElementById('fileName').style.display = 'block';

            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('imagePreview').style.display = 'block';
                document.getElementById('previewImg').src = e.target.result;
            }
            reader.readAsDataURL(file);
        }
    }

    document.getElementById('formOverlay').addEventListener('click', function(e) {
        if (e.target === this) closeForm();
    });

    setTimeout(function() {
        const alertContainer = document.getElementById('alertContainer');
        if (alertContainer) {
            alertContainer.style.transition = 'opacity 0.5s ease';
            alertContainer.style.opacity = '0';
            setTimeout(() => alertContainer.remove(), 500);
        }
    }, 5000);

    let deleteUrl = '';

    function openDeleteModal(id, name, nip, position) {
        document.getElementById('deleteKepsekName').textContent = name;
        document.getElementById('deleteKepsekNip').textContent = nip || '-';
        document.getElementById('deleteKepsekCategory').textContent = position || '-';

        const urlParams = new URLSearchParams(window.location.search);
        const searchParam = urlParams.get('search') ? '&search=' + encodeURIComponent(urlParams.get('search')) : '';
        deleteUrl = '?delete=' + id + searchParam;
        document.getElementById('confirmDeleteBtn').href = deleteUrl;

        document.getElementById('deleteModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';

        const modal = document.getElementById('deleteModal');
        modal.style.animation = 'none';
        modal.offsetHeight;
        modal.style.animation = 'slideUp 0.4s cubic-bezier(0.34, 1.56, 0.64, 1)';
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').style.display = 'none';
        document.body.style.overflow = '';
    }

    document.addEventListener('DOMContentLoaded', function() {
        const deleteModalOverlay = document.getElementById('deleteModal');
        const formOverlay = document.getElementById('formOverlay');
        if (deleteModalOverlay) document.body.appendChild(deleteModalOverlay);
        if (formOverlay) document.body.appendChild(formOverlay);

        const confirmBtn = document.getElementById('confirmDeleteBtn');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', function(e) {
                this.classList.add('loading');
                this.innerHTML = '<i class="fas fa-spinner"></i> Menghapus...';
                this.style.pointerEvents = 'none';
            });
        }
    });

    document.getElementById('deleteModal').addEventListener('click', function(e) {
        if (e.target === this) closeDeleteModal();
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeDeleteModal();
    });
</script>

<?php include 'layout/footer.php'; ?>