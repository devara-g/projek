<?php
include "../database/conn.php";

// Pastikan tabel galeri ada
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE 'galeri'");
if (mysqli_num_rows($checkTable) == 0) {
    mysqli_query($conn, "CREATE TABLE galeri (
        id INT AUTO_INCREMENT PRIMARY KEY,
        judul VARCHAR(255) NOT NULL,
        kategori VARCHAR(100) NOT NULL,
        deskripsi TEXT,
        foto VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
}

// Pastikan kolom deskripsi ada (jika tabel sudah terlanjur dibuat tanpa deskripsi)
$colCheck = mysqli_query($conn, "SHOW COLUMNS FROM galeri LIKE 'deskripsi'");
if (mysqli_num_rows($colCheck) == 0) {
    mysqli_query($conn, "ALTER TABLE galeri ADD COLUMN deskripsi TEXT AFTER kategori");
}

// Pastikan kolom kategori cukup besar untuk menampung nilai (Fix Data Truncated)
mysqli_query($conn, "ALTER TABLE galeri MODIFY COLUMN kategori VARCHAR(100) NOT NULL");

// Proses Hapus
if (isset($_GET['aksi']) && $_GET['aksi'] == 'hapus' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    // Hapus file fisik
    $q = mysqli_query($conn, "SELECT foto FROM galeri WHERE id = $id");
    $data = mysqli_fetch_assoc($q);
    if ($data && !empty($data['foto'])) {
        $path = "../" . $data['foto'];
        if (file_exists($path)) unlink($path);
    }

    mysqli_query($conn, "DELETE FROM galeri WHERE id = $id");
    header("Location: galeri.php?status=success&message=Foto berhasil dihapus");
    exit;
}

// Proses Simpan (Tambah/Edit)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['aksi'])) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    $deskripsi = isset($_POST['deskripsi']) ? mysqli_real_escape_string($conn, $_POST['deskripsi']) : '';
    $aksi = $_POST['aksi'];

    $foto = "";
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $newName = "galeri_" . time() . "_" . uniqid() . "." . $ext;
        $target = "../upload/img/" . $newName;

        if (!file_exists("../upload/img/")) mkdir("../upload/img/", 0777, true);

        if (move_uploaded_file($_FILES['foto']['tmp_name'], $target)) {
            $foto = "upload/img/" . $newName;

            // Hapus foto lama jika edit
            if ($aksi == 'edit' && $id > 0) {
                $q = mysqli_query($conn, "SELECT foto FROM galeri WHERE id = $id");
                $old = mysqli_fetch_assoc($q);
                if ($old && !empty($old['foto'])) {
                    $oldPath = "../" . $old['foto'];
                    if (file_exists($oldPath)) unlink($oldPath);
                }
            }
        }
    }

    if ($aksi == 'tambah') {
        $sql = "INSERT INTO galeri (judul, kategori, deskripsi, foto) VALUES ('$judul', '$kategori', '$deskripsi', '$foto')";
    } else {
        if ($foto != "") {
            $sql = "UPDATE galeri SET judul='$judul', kategori='$kategori', deskripsi='$deskripsi', foto='$foto' WHERE id=$id";
        } else {
            $sql = "UPDATE galeri SET judul='$judul', kategori='$kategori', deskripsi='$deskripsi' WHERE id=$id";
        }
    }

    if (mysqli_query($conn, $sql)) {
        header("Location: galeri.php?status=success&message=Data berhasil disimpan");
    } else {
        header("Location: galeri.php?status=error&message=Gagal menyimpan data");
    }
    exit;
}

$title = "Manajemen Galeri";
include 'layout/header.php';
?>


<div class="admin-page-container">
    <?php if (isset($_GET['status'])) : ?>
        <div class="alert-container">
            <div class="alert alert-<?= $_GET['status'] == 'success' ? 'success' : 'error' ?>">
                <i class="fas <?= $_GET['status'] == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                <div class="alert-content">
                    <div class="alert-title"><?= $_GET['status'] == 'success' ? 'Berhasil!' : 'Gagal!' ?></div>
                    <div class="alert-message">
                        <?= htmlspecialchars($_GET['message']) ?>
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
            <h1>Manajemen Galeri</h1>
            <p style="color: var(--gray);">Kelola foto dokumentasi kegiatan dan prestasi sekolah.</p>
        </div>
        <button class="btn-add" onclick="openAddForm()">
            <i class="fas fa-plus"></i> Tambah Foto
        </button>
    </div>

    <!-- Toolbar & Search -->


    <div class="table-card">
        <div class="table-header">
            <h3>Daftar Foto</h3>
            <div class="header-search">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Cari foto...">
            </div>
        </div>
        <div style="overflow-x: auto;">
            <table id="galeriTable">
                <thead>
                    <tr>
                        <th width="50">No</th>
                        <th width="120">Foto</th>
                        <th>Judul/Keterangan</th>
                        <th>Kategori</th>
                        <th width="100">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $res = mysqli_query($conn, "SELECT * FROM galeri ORDER BY id DESC");
                    $no = 1;
                    while ($row = mysqli_fetch_assoc($res)) :
                        $cat_class = '';
                        $kat_db = strtolower($row['kategori']);
                        if ($kat_db == 'kegiatan sekolah') {
                            $cat_class = 'category-school';
                            $display_kat = 'Kegiatan Sekolah';
                        } elseif ($kat_db == 'prestasi siswa') {
                            $cat_class = 'category-achievement';
                            $display_kat = 'Prestasi Siswa';
                        } else {
                            $cat_class = 'category-extracurricular';
                            $display_kat = 'Kegiatan Eskul';
                        }
                    ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td>
                                <?php if (!empty($row['foto']) && file_exists('../' . $row['foto'])) : ?>
                                    <img src="../<?= $row['foto'] ?>" class="data-thumb" alt="Foto">
                                <?php else : ?>
                                    <div class="no-thumb"><i class="fas fa-image"></i></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-weight: 600; color: var(--primary);"><?= htmlspecialchars($row['judul']) ?></div>
                                <?php if (!empty($row['deskripsi'])) : ?>
                                    <div style="font-size: 0.8rem; color: #64748b; margin-top: 4px; display: -webkit-box; -webkit-line-clamp: 2; line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                        <?= htmlspecialchars($row['deskripsi']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge <?= $cat_class ?>"><?= $display_kat ?></span></td>
                            <td>
                                <div class="action-btns">
                                    <button class="btn-icon btn-icon-edit" title="Edit"
                                        onclick='openEditForm(<?= json_encode($row) ?>)'>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-icon btn-icon-delete" title="Hapus"
                                        onclick="showDeleteModal(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['judul'])) ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    <?php if (mysqli_num_rows($res) == 0) : ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px; color: #94a3b8;">
                                <i class="fas fa-images" style="font-size: 3rem; display: block; margin-bottom: 10px; opacity: 0.3;"></i>
                                Belum ada foto galeri.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Form -->
<div class="modal-overlay" id="formOverlay">
    <div class="modal-card">
        <div class="modal-header">
            <h2 id="formTitle"><i class="fas fa-plus-circle"></i> Tambah Foto</h2>
            <div class="btn-close-modal" onclick="closeForm()">
                <i class="fas fa-times"></i>
            </div>
        </div>
        <form id="galeriForm" action="" method="POST" enctype="multipart/form-data" style="display: contents;">
            <div class="modal-body">
                <input type="hidden" name="aksi" id="formAksi" value="tambah">
                <input type="hidden" name="id" id="galeriId" value="">

                <div class="form-group">
                    <label>Judul Galeri <span style="color: red;">*</span></label>
                    <input type="text" name="judul" id="judul" required placeholder="Contoh: Upacara Bendera">
                </div>

                <div class="form-group">
                    <label>Kategori & Lokasi <span style="color: red;">*</span></label>
                    <select name="kategori" id="kategori" required>
                        <option value="Kegiatan Sekolah">Lingkungan Sekolah</option>
                        <option value="Prestasi Siswa">Prestasi Siswa</option>
                        <option value="Ekstrakurikuler">Kegiatan Ekskul</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Deskripsi Singkat</label>
                    <textarea name="deskripsi" id="deskripsi" rows="3" placeholder="Ceritakan tentang foto ini..."></textarea>
                </div>

                <div class="form-group">
                    <label>Pilih File Foto</label>
                    <div class="file-upload-wrapper">
                        <input type="file" name="foto" id="foto" class="file-upload-input" onchange="previewImage(this)">
                        <div class="file-upload-label" id="uploadLabelArea">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <span>Klik atau seret foto ke sini</span>
                            <p style="font-size: 0.8rem; margin-top: 5px;">Format: JPG, PNG, GIF (Maks. 10MB)</p>
                        </div>
                    </div>
                </div>

                <div id="imagePreview" class="image-preview" style="display: none; margin-top: 15px; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0;">
                    <img src="" id="previewImg" style="width: 100%; height: auto; display: block;">
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeForm()">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="submit" class="btn-submit" id="submitBtn">
                    <i class="fas fa-save"></i> <span id="btnText">Simpan Foto</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Hapus -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-card" style="max-width: 400px;">
        <div class="modal-header">
            <h2><i class="fas fa-exclamation-triangle" style="color: #ef4444; margin-right: 10px;"></i> Konfirmasi Hapus</h2>
            <div class="btn-close-modal" onclick="closeDeleteForm()">
                <i class="fas fa-times"></i>
            </div>
        </div>
        <div class="modal-body" style="text-align: center; padding: 2rem 1.5rem;">
            <p id="deleteMessage" style="font-size: 1.1rem; color: #64748b; margin-bottom: 2rem;">
                Yakin ingin menghapus data ini?<br>Tindakan ini tidak dapat dibatalkan.
            </p>
            <div style="display: flex; gap: 1rem; justify-content: center;">
                <button type="button" class="btn-cancel" onclick="closeDeleteForm()">
                    <i class="fas fa-times"></i> Batal
                </button>
                <a href="#" id="deleteConfirmBtn" class="btn-delete">
                    <i class="fas fa-trash"></i> Hapus
                </a>
            </div>
        </div>
    </div>
</div>



<script>
    // Pindahkan modal ke body agar fixed relative ke viewport (bukan main-content yg punya transform)
    document.addEventListener('DOMContentLoaded', function() {
        const editModal = document.getElementById('editModal');
        const msgModal = document.getElementById('msgModal');
        const deleteModal = document.getElementById('deleteModal');
        const formOverlay = document.getElementById('formOverlay');
        if (editModal) document.body.appendChild(editModal);
        if (msgModal) document.body.appendChild(msgModal);
        if (deleteModal) document.body.appendChild(deleteModal);
        if (formOverlay) document.body.appendChild(formOverlay);
    });

    function showDeleteModal(id, judul) {
        document.getElementById('deleteMessage').innerHTML = `Yakin ingin menghapus foto <strong>"${judul}"</strong>?<br>Tindakan ini tidak dapat dibatalkan.`;
        document.getElementById('deleteConfirmBtn').href = `?aksi=hapus&id=${id}`;
        document.getElementById('deleteModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeDeleteForm() {
        document.getElementById('deleteModal').style.display = 'none';
        document.body.style.overflow = '';
    }


    // Standardized Form Functions
    function openAddForm() {
        const overlay = document.getElementById('formOverlay');
        const form = document.getElementById('galeriForm');
        document.getElementById('formTitle').innerHTML = '<i class="fas fa-plus-circle"></i> Tambah Foto Baru';
        document.getElementById('btnText').innerText = 'Simpan Foto';
        document.getElementById('formAksi').value = 'tambah';
        form.reset();
        document.getElementById('galeriId').value = '';
        document.getElementById('imagePreview').style.display = 'none';
        document.getElementById('previewImg').src = ''; // Clear previous image
        document.getElementById('uploadLabelArea').querySelector('span').innerText = 'Klik atau seret foto ke sini'; // Reset upload text
        
        // Bounce animation for modal
        const modalCard = overlay.querySelector('.modal-card');
        modalCard.style.animation = 'none';
        modalCard.offsetHeight;
        modalCard.style.animation = 'editModalIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1)';
        
        overlay.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function openEditForm(data) {
        const overlay = document.getElementById('formOverlay');
        const form = document.getElementById('galeriForm');
        document.getElementById('formTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Foto';
        document.getElementById('btnText').innerText = 'Update Foto';
        document.getElementById('formAksi').value = 'edit';

        document.getElementById('galeriId').value = data.id || '';
        document.getElementById('judul').value = data.judul || '';
        document.getElementById('kategori').value = data.kategori || '';
        document.getElementById('deskripsi').value = data.deskripsi || '';

        // Preview image if exists
        const preview = document.getElementById('imagePreview');
        const previewImg = document.getElementById('previewImg');
        if (data.foto) {
            previewImg.src = '../' + data.foto;
            preview.style.display = 'block';
        } else {
            preview.style.display = 'none';
            previewImg.src = '';
        }
        document.getElementById('uploadLabelArea').querySelector('span').innerText = 'Klik atau seret foto ke sini'; // Reset upload text

        // Bounce animation for modal
        const modalCard = overlay.querySelector('.modal-card');
        modalCard.style.animation = 'none';
        modalCard.offsetHeight;
        modalCard.style.animation = 'editModalIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1)';
        
        overlay.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeForm() {
        document.getElementById('formOverlay').style.display = 'none';
        document.body.style.overflow = '';
    }

    function previewImage(input) {
        const file = input.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('imagePreview');
                const previewImg = document.getElementById('previewImg');
                previewImg.src = e.target.result;
                preview.style.display = 'block';
            }
            reader.readAsDataURL(file);

            // Update label
            const labelArea = document.getElementById('uploadLabelArea');
            if (labelArea) {
                labelArea.querySelector('span').innerText = file.name;
            }
        }
    }

    function showDeleteModal(id, judul) {
        if (confirm('Apakah Anda yakin ingin menghapus foto "' + judul + '"?')) {
            window.location.href = 'galeri.php?aksi=hapus&id=' + id;
        }
    }

    // Search logic
    document.getElementById('searchInput').addEventListener('keyup', function() {
        const value = this.value.toLowerCase();
        const rows = document.querySelectorAll('#galeriTable tbody tr');
        rows.forEach(row => {
            if (row.innerText.toLowerCase().includes(value)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // Close on overlay click
    window.onclick = function(e) {
        if (e.target == document.getElementById('formOverlay')) closeForm();
    }
</script>

<?php include 'layout/footer.php'; ?>