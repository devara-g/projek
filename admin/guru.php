<?php
$title = "Manajemen Guru & Staff";
include '../database/conn.php';

// Proses CRUD
$message = '';
$messageType = '';

// Tangkap pesan dari redirect
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'updated') {
        $message = "Data guru berhasil diupdate!";
        $messageType = "success";
    }
}

// HANDLE TAMBAH DATA
if (isset($_POST['action']) && $_POST['action'] == 'add') {
    $nama = $conn->real_escape_string($_POST['nama']);
    $nip = $conn->real_escape_string($_POST['nip']);
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
    $result = $conn->query("SELECT MAX(sort_order) as max_sort FROM teachers WHERE category = '$category'");
    $row = $result->fetch_assoc();
    $sort_order = ($row['max_sort'] ?? 0) + 1;

    $sql = "INSERT INTO teachers (category, name, nip, photo_filename, sort_order) 
            VALUES ('$category', '$nama', '$nip', '$photo_filename', $sort_order)";

    if ($conn->query($sql)) {
        $message = "Data guru berhasil ditambahkan!";
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
    $id = $_POST['id'];
    $nama = $conn->real_escape_string($_POST['nama']);
    $nip = $conn->real_escape_string($_POST['nip']);
    $category = $conn->real_escape_string($_POST['category']);

    // Cek apakah ada upload foto baru
    $foto_sql = "";
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        // Hapus foto lama
        $result = $conn->query("SELECT photo_filename FROM teachers WHERE id = $id");
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

    $sql = "UPDATE teachers SET 
            category = '$category',
            name = '$nama',
            nip = '$nip'
            $foto_sql
            WHERE id = $id";

    if ($conn->query($sql)) {
        header("Location: guru.php?msg=updated");
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
    $id = $_GET['delete'];

    // Hapus foto
    $result = $conn->query("SELECT photo_filename FROM teachers WHERE id = $id");
    $data = $result->fetch_assoc();
    if ($data['photo_filename'] && file_exists('../upload/img/' . $data['photo_filename'])) {
        unlink('../upload/img/' . $data['photo_filename']);
    }

    if ($conn->query("DELETE FROM teachers WHERE id = $id")) {
        $message = "Data guru berhasil dihapus!";
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
    $result = $conn->query("SELECT * FROM teachers WHERE id = $id");
    $editData = $result->fetch_assoc();
}

// AMBIL SEMUA DATA
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$sql = "SELECT * FROM teachers";
if ($search) {
    $sql .= " WHERE name LIKE '%$search%' OR nip LIKE '%$search%'";
}
$sql .= " ORDER BY FIELD(category, '7', '8', '9', 'mapel'), sort_order ASC";
$result = $conn->query($sql);

// Mapping category ke nama display
$categoryNames = [
    '7' => 'Wali Kelas 7',
    '8' => 'Wali Kelas 8',
    '9' => 'Wali Kelas 9',
    'mapel' => 'Guru Mata Pelajaran'
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
            <h1>Data Guru & Staff</h1>
            <p style="color: var(--gray);">Kelola informasi tenaga pendidik dan kependidikan.</p>
        </div>
        <button class="btn-add" onclick="openAddForm()">
            <i class="fas fa-plus"></i> Tambah Data
        </button>
    </div>

    <!-- Output Table -->
    <!-- Toolbar & Search -->
    <div class="card-panel stagger-item stagger-2" style="margin-bottom: 2rem; border-radius: 20px;">
        <div class="table-header" style="border-bottom: none; padding-bottom: 0; margin-bottom: 0; box-shadow: none; border: none; padding: 0;">
            <h3><i class="fas fa-list-ul" style="margin-right: 10px; color: var(--accent);"></i> Daftar Guru & Staff</h3>
            <form method="GET" style="display: flex; gap: 10px; align-items: center;">
                <div class="header-search">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" placeholder="Cari nama atau NIP..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <button type="submit" class="btn-submit" style="padding: 10px 15px; width: auto; margin:0;"><i class="fas fa-search"></i></button>
                <?php if ($search): ?>
                    <a href="guru.php" class="btn-cancel" style="padding: 10px 15px; text-decoration:none;">Reset</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Teacher Grid Layout -->
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

                        <?php if ($row['nip']): ?>
                            <span class="teacher-nip">
                                <i class="fas fa-id-badge" style="margin-right: 5px; opacity: 0.7;"></i>
                                <?php echo htmlspecialchars($row['nip']); ?>
                            </span>
                        <?php endif; ?>

                        <div class="teacher-role">
                            <span class="badge <?php echo ($row['category'] == 'mapel') ? 'badge-secondary' : 'badge-primary'; ?>">
                                <?php echo $categoryNames[$row['category']]; ?>
                            </span>
                        </div>
                    </div>

                    <div class="teacher-actions">
                        <a href="?edit=<?php echo $row['id']; ?><?php echo $search ? '&search=' . $search : ''; ?>" class="btn-card-action" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="javascript:void(0)" onclick="openDeleteModal('<?php echo $row['id']; ?>', '<?php echo htmlspecialchars(addslashes($row['name'])); ?>', '<?php echo htmlspecialchars($row['nip']); ?>', '<?php echo $row['category']; ?>')" class="btn-card-action delete" title="Hapus">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-grid-state">
                <i class="fas fa-users empty-icon"></i>
                <h3>Data Tidak Ditemukan</h3>
                <p style="color: var(--gray);">Belum ada data guru atau pencarian tidak sesuai.</p>
                <div style="margin-top: 1.5rem;">
                    <button class="btn-add" onclick="openAddForm()">
                        <i class="fas fa-plus"></i> Tambah Guru Baru
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
                <i class="fas <?php echo $editData ? 'fa-user-edit' : 'fa-user-tie'; ?>"></i>
                <?php echo $editData ? 'Edit Data Guru' : 'Input Guru/Staff'; ?>
            </h2>
            <div class="btn-close-modal" onclick="closeForm()"><i class="fas fa-times"></i></div>
        </div>
        <form action="" method="POST" enctype="multipart/form-data" id="teacherForm" style="display: contents;">
            <input type="hidden" name="action" value="<?php echo $editData ? 'edit' : 'add'; ?>" id="action">
            <?php if ($editData): ?>
                <input type="hidden" name="id" value="<?php echo $editData['id']; ?>" id="teacherId">
            <?php endif; ?>

            <div class="modal-body">
                <div class="form-group">
                    <label>Nama Lengkap & Gelar</label>
                    <input type="text" name="nama" id="nama" required placeholder="Contoh: Siti Aminah, S.Pd" value="<?php echo $editData ? htmlspecialchars($editData['name']) : ''; ?>">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>NIP / NUPTK</label>
                        <input type="text" name="nip" id="nip" placeholder="Masukkan nomor identitas" value="<?php echo $editData ? htmlspecialchars($editData['nip']) : ''; ?>" onkeyup="checkNIP(this.value)" onblur="checkNIP(this.value)">
                        <div class="error-message" id="nipError"></div>
                    </div>
                    <div class="form-group">
                        <label>Kategori</label>
                        <select name="category" id="category" required>
                            <option value="">Pilih Kategori</option>
                            <option value="7" <?php echo ($editData && $editData['category'] == '7') ? 'selected' : ''; ?>>Wali Kelas 7</option>
                            <option value="8" <?php echo ($editData && $editData['category'] == '8') ? 'selected' : ''; ?>>Wali Kelas 8</option>
                            <option value="9" <?php echo ($editData && $editData['category'] == '9') ? 'selected' : ''; ?>>Wali Kelas 9</option>
                            <option value="mapel" <?php echo ($editData && $editData['category'] == 'mapel') ? 'selected' : ''; ?>>Guru Mata Pelajaran</option>
                        </select>
                    </div>
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
    <div class="delete-modal-card" id="deleteModal">
        <div class="delete-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h3>Hapus Data?</h3>
        <p class="warning-text">Anda akan menghapus data guru berikut:</p>
        <div class="highlight-text" id="deleteTeacherInfo">
            <p><i class="fas fa-user"></i> <strong id="deleteTeacherName"></strong></p>
            <p><i class="fas fa-id-card"></i> NIP: <span id="deleteTeacherNip"></span></p>
            <p><i class="fas fa-tag"></i> <span id="deleteTeacherCategory"></span></p>
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
    let nipValid = true;
    let currentNip = '<?php echo $editData ? $editData['nip'] : ''; ?>';
    let checkTimeout;

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

        // Reset validasi NIP
        nipValid = true;
        currentNip = '';
        document.getElementById('submitBtn').disabled = false;

        // Update judul dan tombol
        document.getElementById('formTitle').innerHTML = '<i class="fas fa-user-tie"></i> Input Guru/Staff';
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

    function checkNIP(nip) {
        // Clear previous timeout
        clearTimeout(checkTimeout);

        // Get action and ID for edit mode
        const action = document.getElementById('action').value;
        const teacherId = document.getElementById('teacherId') ? document.getElementById('teacherId').value : null;

        // Get elements
        const nipInput = document.getElementById('nip');
        const errorElement = document.getElementById('nipError');
        const submitBtn = document.getElementById('submitBtn');

        // Remove previous error states
        nipInput.classList.remove('input-error');
        errorElement.classList.remove('show');

        // If NIP is empty, consider it valid (but required will handle it)
        if (!nip.trim()) {
            nipValid = true;
            submitBtn.disabled = false;
            return;
        }

        // If NIP is same as current in edit mode, skip validation
        if (action === 'edit' && nip === currentNip) {
            nipValid = true;
            submitBtn.disabled = false;
            return;
        }

        // Show loading state
        submitBtn.disabled = true;

        // Set timeout to avoid too many requests
        checkTimeout = setTimeout(() => {
            // Make AJAX request to check NIP
            fetch('check_nip.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'nip=' + encodeURIComponent(nip) + '&action=' + action + '&id=' + (teacherId || '')
                })
                .then(response => response.json())
                .then(data => {
                    if (data.exists) {
                        // NIP already exists
                        nipInput.classList.add('input-error');
                        errorElement.textContent = 'NIP ' + nip + ' sudah terdaftar! Gunakan NIP yang berbeda.';
                        errorElement.classList.add('show');
                        nipValid = false;
                        submitBtn.disabled = true;
                    } else {
                        // NIP is available
                        nipValid = true;
                        submitBtn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // If error occurs, allow submission (server will handle it)
                    nipValid = true;
                    submitBtn.disabled = false;
                });
        }, 500); // Wait 500ms after user stops typing
    }

    // Form submission validation
    document.getElementById('teacherForm').addEventListener('submit', function(e) {
        if (!nipValid) {
            e.preventDefault();
            // Show alert that NIP is duplicate
            const nipInput = document.getElementById('nip');
            nipInput.focus();

            // Create temporary alert
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert-container';
            alertDiv.id = 'tempAlert';
            alertDiv.innerHTML = `
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div class="alert-content">
                        <div class="alert-title">Peringatan!</div>
                        <div class="alert-message">NIP sudah terdaftar! Silakan gunakan NIP yang berbeda.</div>
                    </div>
                    <div class="alert-close" onclick="this.closest('.alert-container').remove()">
                        <i class="fas fa-times"></i>
                    </div>
                </div>
            `;
            document.body.appendChild(alertDiv);

            // Auto remove after 5 seconds
            setTimeout(() => {
                const tempAlert = document.getElementById('tempAlert');
                if (tempAlert) {
                    tempAlert.style.transition = 'opacity 0.5s ease';
                    tempAlert.style.opacity = '0';
                    setTimeout(() => tempAlert.remove(), 500);
                }
            }, 5000);
        }
    });

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

    function openDeleteModal(id, name, nip, category) {
        // Set teacher info
        document.getElementById('deleteTeacherName').textContent = name;
        document.getElementById('deleteTeacherNip').textContent = nip || '-';

        // Map category to display name
        const categoryNames = {
            '7': 'Wali Kelas 7',
            '8': 'Wali Kelas 8',
            '9': 'Wali Kelas 9',
            'mapel': 'Guru Mata Pelajaran'
        };
        document.getElementById('deleteTeacherCategory').textContent = categoryNames[category] || category;

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
        // (bukan main-content yg punya transform dari page-transition)
        const deleteModalOverlay = document.getElementById('deleteModal');
        const formOverlay = document.getElementById('formOverlay');
        if (deleteModalOverlay) document.body.appendChild(deleteModal);
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