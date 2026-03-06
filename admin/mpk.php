<?php
$title = "Manajemen Data MPK";
include '../database/conn.php';

// Proses CRUD
$message = '';
$messageType = '';

// Tangkap pesan dari redirect
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'updated') {
        $message = "Data MPK berhasil diupdate!";
        $messageType = "success";
    }
}

// HANDLE TAMBAH DATA
if (isset($_POST['action']) && $_POST['action'] == 'add') {
    $nama = $conn->real_escape_string($_POST['nama']);
    $position = $conn->real_escape_string($_POST['position']);
    $kelas = $conn->real_escape_string($_POST['kelas']);
    $category = $conn->real_escape_string($_POST['category']);

    // Handle upload foto
    $photo_filename = '';
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $photo_filename = uniqid() . '.' . $ext;
        $upload_path = '../upload/img/' . $photo_filename;

        if (move_uploaded_file($_FILES['foto']['tmp_name'], $upload_path)) {
            // Foto berhasil diupload
        }
    }

    // Dapatkan sort_order terakhir untuk category ini
    $result = $conn->query("SELECT MAX(sort_order) as max_sort FROM mpk WHERE category = '$category'");
    $row = $result->fetch_assoc();
    $sort_order = ($row['max_sort'] ?? 0) + 1;

    $sql = "INSERT INTO mpk (category, name, position, kelas, photo_filename, sort_order) 
            VALUES ('$category', '$nama', '$position', '$kelas', '$photo_filename', $sort_order)";

    if ($conn->query($sql)) {
        $message = "Data MPK berhasil ditambahkan!";
        $messageType = "success";
    } else {
        $message = "Error: " . $conn->error;
        $messageType = "error";
    }
}

// HANDLE EDIT DATA
if (isset($_POST['action']) && $_POST['action'] == 'edit') {
    $id = $_POST['id'];
    $nama = $conn->real_escape_string($_POST['nama']);
    $position = $conn->real_escape_string($_POST['position']);
    $kelas = $conn->real_escape_string($_POST['kelas']);
    $category = $conn->real_escape_string($_POST['category']);

    // Cek apakah ada upload foto baru
    $foto_sql = "";
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        // Hapus foto lama
        $result = $conn->query("SELECT photo_filename FROM mpk WHERE id = $id");
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

    $sql = "UPDATE mpk SET 
            category = '$category',
            name = '$nama',
            position = '$position',
            kelas = '$kelas'
            $foto_sql
            WHERE id = $id";

    if ($conn->query($sql)) {
        header("Location: mpk.php?msg=updated");
        exit;
    } else {
        $message = "Error: " . $conn->error;
        $messageType = "error";
    }
}

// HANDLE HAPUS DATA
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    // Hapus foto
    $result = $conn->query("SELECT photo_filename FROM mpk WHERE id = $id");
    $data = $result->fetch_assoc();
    if ($data['photo_filename'] && file_exists('../upload/img/' . $data['photo_filename'])) {
        unlink('../upload/img/' . $data['photo_filename']);
    }

    if ($conn->query("DELETE FROM mpk WHERE id = $id")) {
        $message = "Data MPK berhasil dihapus!";
        $messageType = "success";
    } else {
        $message = "Error: " . $conn->error;
        $messageType = "error";
    }
}

// AMBIL DATA UNTUK EDIT
$editData = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $result = $conn->query("SELECT * FROM mpk WHERE id = $id");
    $editData = $result->fetch_assoc();
}

// AMBIL SEMUA DATA
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$sql = "SELECT * FROM mpk";
if ($search) {
    $sql .= " WHERE name LIKE '%$search%' OR position LIKE '%$search%' OR kelas LIKE '%$search%'";
}
$sql .= " ORDER BY FIELD(category, 'ketua', 'waket', 'sekretaris', 'bendahara', 'anggota'), sort_order ASC";
$result = $conn->query($sql);

// Mapping category ke nama display
$categoryNames = [
    'ketua' => 'Ketua & Wakil Ketua',
    'waket' => 'Wakil Ketua',
    'sekretaris' => 'Sekretaris',
    'bendahara' => 'Bendahara',
    'anggota' => 'Anggota'
];

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
            <h1>Data MPK</h1>
            <p style="color: var(--gray);">Kelola informasi kepengurusan MPK SMP PGRI 3 BOGOR.</p>
        </div>
        <button class="btn-add" onclick="openAddForm()">
            <i class="fas fa-plus"></i> Tambah Data
        </button>
    </div>

    <!-- Toolbar & Search -->
    <div class="card-panel stagger-item stagger-2" style="margin-bottom: 2rem; border-radius: 20px;">
        <div class="table-header" style="border-bottom: none; padding-bottom: 0; margin-bottom: 0; box-shadow: none; border: none; padding: 0;">
            <h3><i class="fas fa-user-tie" style="margin-right: 10px; color: var(--accent);"></i> Daftar Pengurus MPK</h3>
            <form method="GET" style="display: flex; gap: 10px; align-items: center;">
                <div class="header-search">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" placeholder="Cari nama, posisi, atau kelas..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <button type="submit" class="btn-submit" style="padding: 10px 15px; width: auto; margin:0;">
                    <i class="fas fa-search"></i>
                </button>
                <?php if ($search): ?>
                    <a href="mpk.php" class="btn-cancel" style="padding: 10px 15px; text-decoration:none;">Reset</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- MPK Card Grid Layout -->
    <div class="teacher-grid">
        <?php if ($result->num_rows > 0): ?>
            <?php
            $delay = 0;
            while ($row = $result->fetch_assoc()):
                $delay += 0.05;
            ?>
                <div class="teacher-card" style="animation-delay: <?php echo $delay; ?>s;">
                    <div class="teacher-img-wrapper">
                        <?php if ($row['photo_filename'] && file_exists('../upload/img/' . $row['photo_filename'])): ?>
                            <img src="../upload/img/<?php echo $row['photo_filename']; ?>" class="teacher-img">
                        <?php else: ?>
                            <div class="teacher-initial">
                                <?php echo strtoupper(substr($row['name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="teacher-info">
                        <h3><?php echo htmlspecialchars($row['name']); ?></h3>

                        <?php if ($row['position']): ?>
                            <span class="teacher-nip">
                                <i class="fas fa-briefcase" style="margin-right: 5px; opacity: 0.7;"></i>
                                <?php echo htmlspecialchars($row['position']); ?>
                            </span>
                        <?php endif; ?>

                        <?php if ($row['kelas']): ?>
                            <span class="teacher-nip">
                                <i class="fas fa-school" style="margin-right: 5px; opacity: 0.7;"></i>
                                <?php echo htmlspecialchars($row['kelas']); ?>
                            </span>
                        <?php endif; ?>

                        <div class="teacher-role">
                            <span class="badge badge-<?php echo $row['category']; ?>">
                                <?php echo $categoryNames[$row['category']] ?? $row['category']; ?>
                            </span>
                        </div>
                    </div>

                    <div class="teacher-actions">
                        <a href="?edit=<?php echo $row['id']; ?><?php echo $search ? '&search=' . $search : ''; ?>" class="btn-card-action" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="javascript:void(0)" onclick="openDeleteModal('<?php echo $row['id']; ?>', '<?php echo htmlspecialchars(addslashes($row['name'])); ?>', '<?php echo htmlspecialchars($row['position']); ?>', '<?php echo $row['category']; ?>')" class="btn-card-action delete" title="Hapus">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-grid-state">
                <i class="fas fa-user-tie empty-icon"></i>
                <h3>Data Tidak Ditemukan</h3>
                <p style="color: var(--gray);">Belum ada data MPK atau pencarian tidak sesuai.</p>
                <div style="margin-top: 1.5rem;">
                    <button class="btn-add" onclick="openAddForm()">
                        <i class="fas fa-plus"></i> Tambah MPK Baru
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
                <?php echo $editData ? 'Edit Data MPK' : 'Tambah Data MPK'; ?>
            </h2>
            <div class="btn-close-modal" onclick="closeForm()"><i class="fas fa-times"></i></div>
        </div>
        <form action="" method="POST" enctype="multipart/form-data" id="mpkForm" style="display: contents;">
            <input type="hidden" name="action" value="<?php echo $editData ? 'edit' : 'add'; ?>" id="action">
            <?php if ($editData): ?>
                <input type="hidden" name="id" value="<?php echo $editData['id']; ?>" id="mpkId">
            <?php endif; ?>

            <div class="modal-body">
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama" id="nama" required placeholder="Masukkan nama lengkap" value="<?php echo $editData ? htmlspecialchars($editData['name']) : ''; ?>">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Kategori</label>
                        <select name="category" id="category" required>
                            <option value="">Pilih Kategori</option>
                            <option value="ketua" <?php echo ($editData && $editData['category'] == 'ketua') ? 'selected' : ''; ?>>Ketua & Wakil Ketua</option>
                            <option value="waket" <?php echo ($editData && $editData['category'] == 'waket') ? 'selected' : ''; ?>>Wakil Ketua</option>
                            <option value="sekretaris" <?php echo ($editData && $editData['category'] == 'sekretaris') ? 'selected' : ''; ?>>Sekretaris</option>
                            <option value="bendahara" <?php echo ($editData && $editData['category'] == 'bendahara') ? 'selected' : ''; ?>>Bendahara</option>
                            <option value="anggota" <?php echo ($editData && $editData['category'] == 'anggota') ? 'selected' : ''; ?>>Anggota</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Jabatan</label>
                        <input type="text" name="position" id="position" required placeholder="Contoh: Ketua MPK, Sekretaris 1" value="<?php echo $editData ? htmlspecialchars($editData['position']) : ''; ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Kelas</label>
                    <input type="text" name="kelas" id="kelas" required placeholder="Contoh: XII TKR A" value="<?php echo $editData ? htmlspecialchars($editData['kelas']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label>Foto Profil</label>
                    <?php if ($editData && $editData['photo_filename']): ?>
                        <div id="existingPhotoWrapper" style="margin-bottom: 10px; text-align: center;">
                            <img src="../upload/img/<?php echo $editData['photo_filename']; ?>" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary-light);">
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
        <p class="warning-text">Anda akan menghapus data MPK berikut:</p>
        <div class="highlight-text" id="deleteMpkInfo">
            <p><i class="fas fa-user"></i> <strong id="deleteMpkName"></strong></p>
            <p><i class="fas fa-briefcase"></i> <span id="deleteMpkPosition"></span></p>
            <p><i class="fas fa-tag"></i> <span id="deleteMpkCategory"></span></p>
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
        // Reset semua input text/select/file
        document.querySelectorAll('#formOverlay input:not([type="hidden"]), #formOverlay select').forEach(el => {
            el.value = '';
        });

        // Reset hidden fields untuk mode "add"
        document.querySelector('input[name="action"]').value = 'add';

        // Hapus input hidden "id" jika ada
        const idInput = document.querySelector('input[name="id"]');
        if (idInput) idInput.remove();

        // Sembunyikan foto existing
        const existingPhotoWrapper = document.getElementById('existingPhotoWrapper');
        if (existingPhotoWrapper) existingPhotoWrapper.style.display = 'none';

        // Reset preview foto
        document.getElementById('imagePreview').style.display = 'none';
        document.getElementById('fileName').style.display = 'none';
        document.getElementById('fileName').textContent = '';

        // Reset error state
        document.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));
        document.querySelectorAll('.error-message').forEach(el => {
            el.classList.remove('show');
            el.textContent = '';
        });

        // Update judul dan tombol
        document.getElementById('formTitle').innerHTML = '<i class="fas fa-user-plus"></i> Tambah Data MPK';
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

        // Hapus parameter edit dari URL tanpa reload
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

    // Close modal jika klik di luar form
    document.getElementById('formOverlay').addEventListener('click', function(e) {
        if (e.target === this) closeForm();
    });

    // Auto hide alert setelah 5 detik
    setTimeout(function() {
        const alertContainer = document.getElementById('alertContainer');
        if (alertContainer) {
            alertContainer.style.transition = 'opacity 0.5s ease';
            alertContainer.style.opacity = '0';
            setTimeout(() => alertContainer.remove(), 500);
        }
    }, 5000);

    // Delete Modal Functions
    let deleteUrl = '';

    function openDeleteModal(id, name, position, category) {
        // Set MPK info
        document.getElementById('deleteMpkName').textContent = name;
        document.getElementById('deleteMpkPosition').textContent = position || '-';

        // Map category to display name
        const categoryNames = {
            'ketua': 'Ketua & Wakil Ketua',
            'waket': 'Wakil Ketua',
            'sekretaris': 'Sekretaris',
            'bendahara': 'Bendahara',
            'anggota': 'Anggota'
        };
        document.getElementById('deleteMpkCategory').textContent = categoryNames[category] || category;

        // Set delete URL with current search parameters
        const urlParams = new URLSearchParams(window.location.search);
        const searchParam = urlParams.get('search') ? '&search=' + urlParams.get('search') : '';
        deleteUrl = '?delete=' + id + searchParam;
        document.getElementById('confirmDeleteBtn').href = deleteUrl;

        // Show modal
        document.getElementById('deleteModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';

        // Add animation class to modal
        const modal = document.getElementById('deleteModal');
        modal.style.animation = 'none';
        modal.offsetHeight; // Trigger reflow
        modal.style.animation = 'slideUp 0.4s cubic-bezier(0.34, 1.56, 0.64, 1)';
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').style.display = 'none';
        document.body.style.overflow = '';
    }

    // Add loading state when delete is clicked
    document.addEventListener('DOMContentLoaded', function() {
        // Pindahkan modal ke body agar fixed relative ke viewport
        const deleteModalOverlay = document.getElementById('deleteModal');
        const formOverlay = document.getElementById('formOverlay');
        if (deleteModalOverlay) document.body.appendChild(deleteModalOverlay);
        if (formOverlay) document.body.appendChild(formOverlay);

        const confirmBtn = document.getElementById('confirmDeleteBtn');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', function(e) {
                // Add loading class
                this.classList.add('loading');
                this.innerHTML = '<i class="fas fa-spinner"></i> Menghapus...';

                // Disable button to prevent double click
                this.style.pointerEvents = 'none';
            });
        }
    });

    // Close modal when clicking outside
    document.getElementById('deleteModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeDeleteModal();
        }
    });

    // Close modal with ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeDeleteModal();
        }
    });
</script>

<?php include 'layout/footer.php'; ?>
