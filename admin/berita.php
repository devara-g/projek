<?php
session_start();
include '../database/conn.php';

$total_berita = (int) mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM berita"))[0];

// PROSES EDIT - UPDATE DATA
if (isset($_POST['edit'])) {
    $id = (int) $_POST['id'];
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    $tanggal = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $penulis = mysqli_real_escape_string($conn, $_POST['penulis']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);

    // Cek apakah upload file baru
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        $file_type = $_FILES['foto']['type'];
        $file_size = $_FILES['foto']['size'];

        if (in_array($file_type, $allowed_types) && $file_size <= 5 * 1024 * 1024) {
            // Hapus foto lama
            $query_foto_lama = mysqli_query($conn, "SELECT foto FROM berita WHERE id = $id");
            $data_foto_lama = mysqli_fetch_assoc($query_foto_lama);
            if (!empty($data_foto_lama['foto'])) {
                $file_path = '../' . $data_foto_lama['foto'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }

            // Upload foto baru
            $target_dir = '../upload/img/';
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $filename = time() . '_' . uniqid() . '.' . $extension;
            $upload_path = $target_dir . $filename;

            if (move_uploaded_file($_FILES['foto']['tmp_name'], $upload_path)) {
                $foto = 'upload/img/' . $filename;
                // Update dengan foto baru
                $sql = "UPDATE berita SET 
                        judul = '$judul', 
                        kategori = '$kategori', 
                        tanggal = '$tanggal', 
                        penulis = '$penulis', 
                        deskripsi = '$deskripsi', 
                        foto = '$foto' 
                        WHERE id = $id";
            } else {
                $error = "Gagal mengupload file";
            }
        } else {
            $error = "File tidak valid (max 5MB, format: JPG, PNG, GIF)";
        }
    } else {
        // Update tanpa foto
        $sql = "UPDATE berita SET 
                judul = '$judul', 
                kategori = '$kategori', 
                tanggal = '$tanggal', 
                penulis = '$penulis', 
                deskripsi = '$deskripsi' 
                WHERE id = $id";
    }

    if (!isset($error)) {
        if (mysqli_query($conn, $sql)) {
            header("Location: berita.php?edit=1");
            exit;
        } else {
            $error = "Gagal mengupdate berita: " . mysqli_error($conn);
        }
    }
}

// PROSES HAPUS - LANGSUNG DI SINI
if (isset($_GET['aksi']) && $_GET['aksi'] == 'hapus' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];

    // Ambil data foto dulu untuk dihapus filenya
    $query_foto = mysqli_query($conn, "SELECT foto FROM berita WHERE id = $id");
    $data_foto = mysqli_fetch_assoc($query_foto);

    if ($data_foto) {
        // Hapus file foto jika ada
        if (!empty($data_foto['foto'])) {
            $file_path = '../' . $data_foto['foto'];
            if (file_exists($file_path)) {
                unlink($file_path); // Hapus file dari server
            }
        }

        // Hapus data dari database
        $sql = "DELETE FROM berita WHERE id = $id";
        if (mysqli_query($conn, $sql)) {
            $pesan = "Berita berhasil dihapus!";
            $pesan_type = "success";
        } else {
            $pesan = "Gagal menghapus berita: " . mysqli_error($conn);
            $pesan_type = "error";
        }
    }

    // Redirect ke halaman yang sama tanpa parameter GET
    header("Location: berita.php?hapus=" . ($pesan_type == 'success' ? '1' : '0'));
    exit;
}

if (isset($_POST['submit'])) {
    // Sanitasi input
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    $tanggal = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $penulis = mysqli_real_escape_string($conn, $_POST['penulis']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);

    // Handle file upload
    $foto = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        $file_type = $_FILES['foto']['type'];
        $file_size = $_FILES['foto']['size'];

        // Validasi tipe file
        if (in_array($file_type, $allowed_types)) {
            // Validasi ukuran file (max 5MB)
            if ($file_size <= 5 * 1024 * 1024) {
                // Buat folder jika belum ada
                $target_dir = '../upload/img/';
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }

                // Buat nama file unik
                $extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
                $filename = time() . '_' . uniqid() . '.' . $extension;
                $upload_path = $target_dir . $filename;

                if (move_uploaded_file($_FILES['foto']['tmp_name'], $upload_path)) {
                    // Simpan path relatif untuk database
                    $foto = 'upload/img/' . $filename;
                } else {
                    $error = "Gagal mengupload file";
                }
            } else {
                $error = "Ukuran file maksimal 5MB";
            }
        } else {
            $error = "Tipe file harus JPG, PNG, atau GIF";
        }
    }

    if (!isset($error)) {
        // Query INSERT sesuai struktur tabel
        $sql = "INSERT INTO berita (judul, kategori, tanggal, penulis, deskripsi, foto) 
                VALUES ('$judul', '$kategori', '$tanggal', '$penulis', '$deskripsi', '$foto')";

        if (mysqli_query($conn, $sql)) {
            header("Location: berita.php?success=1");
            exit;
        } else {
            $error = "Gagal menambahkan berita: " . mysqli_error($conn);
        }
    }
}

// Ambil data untuk edit jika ada parameter edit
$edit_data = null;
if (isset($_GET['edit_id'])) {
    $edit_id = (int) $_GET['edit_id'];
    $query_edit = mysqli_query($conn, "SELECT * FROM berita WHERE id = $edit_id");
    $edit_data = mysqli_fetch_assoc($query_edit);
}
?>
<?php
$title = "Manajemen Berita";
include 'layout/header.php';
?>



<div class="admin-page-container">
    <?php if (isset($_GET['success']) || (isset($_GET['edit']) && $_GET['edit'] == '1') || (isset($_GET['hapus']) && $_GET['hapus'] == '1')): ?>
        <div class="alert-container" id="alertContainer">
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <div class="alert-content">
                    <div class="alert-title">Berhasil!</div>
                    <div class="alert-message">
                        <?php
                        if (isset($_GET['success'])) echo "Berita berhasil ditambahkan!";
                        elseif (isset($_GET['edit'])) echo "Berita berhasil diperbarui!";
                        elseif (isset($_GET['hapus'])) echo "Berita berhasil dihapus!";
                        ?>
                    </div>
                </div>
                <div class="alert-close" onclick="this.closest('.alert-container').remove()">
                    <i class="fas fa-times"></i>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ((isset($_GET['hapus']) && $_GET['hapus'] == '0') || isset($error)): ?>
        <div class="alert-container" id="alertContainer">
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <div class="alert-content">
                    <div class="alert-title">Gagal!</div>
                    <div class="alert-message">
                        <?php
                        if (isset($error)) echo $error;
                        else echo "Gagal menghapus berita!";
                        ?>
                    </div>
                </div>
                <div class="alert-close" onclick="this.closest('.alert-container').remove()">
                    <i class="fas fa-times"></i>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="header-panel">
        <div>
            <h1>Manajemen Berita</h1>
            <p style="color: var(--gray);">Kelola artikel, pengumuman, dan berita terbaru sekolah.</p>
        </div>
        <button class="btn-add" onclick="openAddForm()">
            <i class="fas fa-plus"></i> Berita Baru
        </button>
    </div>

    <!-- Toolbar & Search -->
    <div class="card-panel stagger-item stagger-2" style="margin-bottom: 2rem; border-radius: 20px;">
        <div class="table-header" style="border-bottom: none; padding-bottom: 0; margin-bottom: 0;">
            <h3><i class="fas fa-list-ul" style="margin-right: 10px; color: var(--accent);"></i> Daftar Berita</h3>
            <div style="display: flex; gap: 10px; align-items: center;">
                <div class="header-search">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Cari berita..." value="">
                </div>
                <button onclick="resetSearch()" class="btn-cancel" style="padding: 10px 15px; width: auto; margin:0;">Reset</button>
            </div>
        </div>
    </div>

    <!-- News Grid Output -->
    <div class="news-grid stagger-item stagger-2" id="newsGrid">

        <?php
        $sql = "SELECT * FROM berita ORDER BY tanggal DESC";
        $result = mysqli_query($conn, $sql);
        if (mysqli_num_rows($result) > 0) {
            $delay = 0;
            while ($row = mysqli_fetch_assoc($result)) {
                $delay += 0.05;

                // Set badge color based on category
                $badge_class = 'badge-primary';
                if (strtolower($row['kategori']) == 'prestasi') {
                    $badge_class = 'badge-success';
                } elseif (strtolower($row['kategori']) == 'pengumuman') {
                    $badge_class = 'badge-warning';
                }
        ?>
                <div class="content-card" style="animation-delay: <?= $delay ?>s;">
                    <?php if ($row['foto'] && file_exists('../' . $row['foto'])): ?>
                        <img src="../<?= htmlspecialchars($row['foto']) ?>" class="card-img-top" alt="Thumbnail">
                    <?php else: ?>
                        <div class="card-img-top" style="background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%); display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-newspaper" style="font-size: 3rem; color: #94a3b8;"></i>
                        </div>
                    <?php endif; ?>

                    <div class="card-body">
                        <div class="card-category">
                            <span class="badge <?= $badge_class ?>" style="border-radius: 6px; padding: 4px 10px; font-size: 0.75rem;"><?= htmlspecialchars($row['kategori']) ?></span>
                            <span style="color: var(--slate-400); font-weight: 500; font-size: 0.8rem; display: flex; align-items: center; gap: 5px;">
                                <i class="far fa-calendar-alt"></i> <?= date('d/m/Y', strtotime($row['tanggal'])) ?>
                            </span>
                        </div>

                        <h3 class="card-title"><?= htmlspecialchars($row['judul']) ?></h3>

                        <div class="card-desc">
                            <?= htmlspecialchars(strip_tags(substr($row['deskripsi'], 0, 150))) . (strlen($row['deskripsi']) > 150 ? '...' : '') ?>
                        </div>

                        <div class="card-footer">
                            <div class="card-author" style="font-size: 0.85rem; color: var(--slate-500); display: flex; align-items: center; gap: 6px;">
                                <div style="width: 24px; height: 24px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.7rem;">
                                    <i class="fas fa-user"></i>
                                </div>
                                <?= htmlspecialchars($row['penulis']) ?: 'Admin' ?>
                            </div>

                            <div class="card-actions">
                                <button class="btn-card-action" title="Edit" onclick="openEditForm(<?= htmlspecialchars(json_encode($row)) ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-card-action delete" title="Hapus" onclick="showDeleteAlert(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['judul'])) ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php
            }
        } else {
            ?>
            <div class="empty-grid-state">
                <i class="fas fa-newspaper empty-icon"></i>
                <h3>Belum ada berita</h3>
                <p style="color: var(--gray);">Silakan tambahkan berita atau artikel baru.</p>
                <div style="margin-top: 1.5rem;">
                    <button class="btn-add" onclick="openAddForm()">
                        <i class="fas fa-plus"></i> Artikel Baru
                    </button>
                </div>
            </div>
        <?php } ?>
    </div>
</div>

<!-- Modal Form for Add/Edit -->
<div class="modal-overlay" id="formOverlay">
    <div class="modal-card">
        <div class="modal-header">
            <h2 id="formTitle"><i class="fas fa-newspaper"></i> Tambah Berita</h2>
            <div class="btn-close-modal" onclick="closeForm()"><i class="fas fa-times"></i></div>
        </div>
        <form id="beritaForm" action="" method="POST" enctype="multipart/form-data" style="display: contents;">
            <div class="modal-body">
                <input type="hidden" name="id" id="beritaId">

                <div class="form-group">
                    <label>Judul Berita <span style="color: red;">*</span></label>
                    <input type="text" name="judul" id="judul" required placeholder="Contoh: Kunjungan Industri 2024">
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Kategori <span style="color: red;">*</span></label>
                        <select name="kategori" id="kategori" required>
                            <option value="Pengumuman">Pengumuman</option>
                            <option value="Prestasi">Prestasi</option>
                            <option value="Kegiatan">Kegiatan</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tanggal Publish <span style="color: red;">*</span></label>
                        <input type="date" name="tanggal" id="tanggal" required value="<?= date('Y-m-d') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Penulis/Sumber</label>
                    <input type="text" name="penulis" id="penulis" placeholder="Contoh: Admin / Kesiswaan">
                </div>

                <div class="form-group">
                    <label>Konten Berita <span style="color: red;">*</span></label>
                    <textarea name="deskripsi" id="deskripsi" rows="6" required placeholder="Tuliskan isi berita di sini..."></textarea>
                </div>

                <div class="form-group">
                    <label>Foto Berita</label>
                    <div class="file-upload-wrapper">
                        <input type="file" name="foto" id="fotoInput" class="file-upload-input" onchange="previewFile(this)">
                        <div class="file-upload-label">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <span>Pilih foto atau tarik ke sini</span>
                            <p style="font-size: 0.8rem; margin-top: 5px;">Foto landscape disarankan (Maks. 5MB)</p>
                        </div>
                    </div>
                    <div id="fileNameDisplay" class="file-name"></div>
                    <div id="imagePreview" class="image-preview">
                        <img src="" id="previewImg" alt="Preview">
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeForm()">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="submit" name="submit" id="submitBtn" class="btn-submit">
                    <i class="fas fa-save"></i> <span id="btnText">Publish Berita</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="delete-modal-overlay" id="deleteModal" onclick="if(event.target===this) hideDeleteAlert()">
    <div class="delete-modal-card" onclick="event.stopPropagation()">
        <div class="delete-icon">
            <i class="fas fa-trash-alt"></i>
        </div>
        <h3>Hapus Berita?</h3>
        <p id="deleteAlertMessage">Yakin ingin menghapus berita "<span id="deleteNewsTitle"></span>"? Tindakan ini tidak dapat dibatalkan.</p>
        <div class="delete-actions">
            <button type="button" class="btn-cancel" onclick="hideDeleteAlert()">Batal</button>
            <a href="#" class="btn-confirm-delete" id="confirmDeleteBtn">
                <i class="fas fa-trash"></i> Hapus
            </a>
        </div>
    </div>
</div>

<script>
    // State untuk menyimpan ID yang akan dihapus
    let deleteId = null;

    // Standardized Form Functions
    function openAddForm() {
        const overlay = document.getElementById('formOverlay');
        const form = document.getElementById('beritaForm');
        document.getElementById('formTitle').innerHTML = '<i class="fas fa-newspaper"></i> Tambah Berita Baru';
        document.getElementById('btnText').innerText = 'Publish Berita';
        document.getElementById('submitBtn').name = 'submit';
        form.reset();
        document.getElementById('beritaId').value = '';
        document.getElementById('imagePreview').style.display = 'none';
        document.getElementById('fileNameDisplay').innerText = '';
        
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
        const form = document.getElementById('beritaForm');
        document.getElementById('formTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Berita';
        document.getElementById('btnText').innerText = 'Update Berita';
        document.getElementById('submitBtn').name = 'edit';

        document.getElementById('beritaId').value = data.id || '';
        document.getElementById('judul').value = data.judul || '';
        document.getElementById('kategori').value = data.kategori || '';
        document.getElementById('tanggal').value = data.tanggal || '';
        document.getElementById('penulis').value = data.penulis || '';
        document.getElementById('deskripsi').value = data.deskripsi || '';

        // Preview image if exists
        const preview = document.getElementById('imagePreview');
        const previewImg = document.getElementById('previewImg');
        if (data.foto) {
            previewImg.src = '../' + data.foto;
            preview.style.display = 'block';
        } else {
            preview.style.display = 'none';
        }

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
        // If it was an edit from URL, clear it to clean the state
        if (window.location.search.includes('edit_id')) {
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    }

    function previewFile(input) {
        const file = input.files[0];
        if (file) {
            document.getElementById('fileNameDisplay').innerText = file.name;
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('imagePreview');
                const previewImg = document.getElementById('previewImg');
                previewImg.src = e.target.result;
                preview.style.display = 'block';
            }
            reader.readAsDataURL(file);
        }
    }

    // Scroll hint for mobile
    document.addEventListener('DOMContentLoaded', function() {
        const tableWrapper = document.querySelector('.table-card > div[style*="overflow-x: auto"]');
        if (tableWrapper && window.innerWidth < 768) {
            const hint = document.createElement('div');
            hint.className = 'mobile-scroll-hint';
            hint.style.cssText = 'text-align: center; padding: 10px; color: #94a3b8; font-size: 0.75rem; border-top: 1px solid #f1f5f9; display: none;';
            hint.innerHTML = '<i class="fas fa-arrows-alt-h"></i> Geser tabel untuk melihat detail';
            tableWrapper.parentNode.appendChild(hint);

            if (tableWrapper.scrollWidth > tableWrapper.clientWidth) {
                hint.style.display = 'block';
            }
        }
    });
    // Pindahkan modal ke body agar fixed relative ke viewport (bukan main-content yg punya transform)
    document.addEventListener('DOMContentLoaded', function() {
        const editModal = document.getElementById('editModal');
        const msgModal = document.getElementById('msgModal');
        const deleteModal = document.getElementById('deleteModal');
        if (editModal) document.body.appendChild(editModal);
        if (msgModal) document.body.appendChild(msgModal);
        if (deleteModal) document.body.appendChild(deleteModal);
    });


    // Edit news - Fetch data via AJAX
    // function editNews(id) {
    //     // Redirect ke halaman yang sama dengan parameter edit_id
    //     window.location.href = 'berita.php?edit_id=' + id;
    // }

    // Show custom delete alert
    function showDeleteAlert(id, judul) {
        deleteId = id;
        document.getElementById('deleteNewsTitle').textContent = judul;
        document.getElementById('deleteModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    // Hide delete alert
    function hideDeleteAlert() {
        document.getElementById('deleteModal').style.display = 'none';
        document.body.style.overflow = '';
        deleteId = null;
    }

    // Confirm delete
    document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
        if (deleteId) {
            window.location.href = 'berita.php?aksi=hapus&id=' + deleteId;
        }
    });


    // Search functionality - REAL TIME
    // Search functionality - REAL TIME (Updated for Grid)
    document.getElementById('searchInput')?.addEventListener('keyup', function() {
        const searchValue = this.value.toLowerCase().trim();
        const cards = document.querySelectorAll('.news-grid .content-card');
        let visibleCount = 0;
        let emptyState = document.querySelector('.empty-grid-state');

        cards.forEach(card => {
            const title = card.querySelector('.card-title').textContent.toLowerCase();
            const category = card.querySelector('.card-category .badge').textContent.toLowerCase();
            const desc = card.querySelector('.card-desc').textContent.toLowerCase();
            const author = card.querySelector('.card-author').textContent.toLowerCase();

            if (title.includes(searchValue) || category.includes(searchValue) || desc.includes(searchValue) || author.includes(searchValue)) {
                card.style.display = 'flex'; // Grid items are flex col
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        // Handle empty state visibility
        if (visibleCount === 0) {
            if (!emptyState) {
                const grid = document.getElementById('newsGrid');
                const emptyDiv = document.createElement('div');
                emptyDiv.className = 'empty-grid-state';
                emptyDiv.innerHTML = `
                    <i class="fas fa-search empty-icon"></i>
                    <h3>Tidak ada berita ditemukan</h3>
                    <p style="color: var(--gray);">Coba kata kunci lain atau reset pencarian.</p>
                `;
                grid.appendChild(emptyDiv);
            } else {
                emptyState.style.display = 'flex';
                // Update text if needed, but generic "not found" is fine
                emptyState.querySelector('h3').textContent = 'Tidak ada berita ditemukan';
                emptyState.querySelector('p').textContent = 'Coba kata kunci lain atau reset pencarian.';
                // Hide button if search result is empty
                const btn = emptyState.querySelector('.btn-add');
                if (btn) btn.style.display = 'none';
            }
        } else {
            if (emptyState) {
                // If it was the dynamic search empty state, remove it. 
                // If it was the original empty state (no data at all), keep it hidden? 
                // Actually if visibleCount > 0, we have data, so hide any empty state.
                if (emptyState.innerHTML.includes('fa-search')) {
                    emptyState.remove();
                } else {
                    emptyState.style.display = 'none';
                }
            }
        }
    });

    // Reset search
    function resetSearch() {
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.value = '';
            searchInput.dispatchEvent(new Event('keyup'));
        }
    }

    // Auto-hide alert after 3 seconds
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert-msg');
        alerts.forEach(alert => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        });
    }, 3000);

    // Show form if there are validation errors
    <?php if (isset($error)): ?>
        openAddForm();
    <?php endif; ?>

    // Open edit from URL if parameter exists
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($edit_data): ?>
            openEditForm(<?= json_encode($edit_data) ?>);
        <?php endif; ?>
    });
    // Pindahkan modal ke body agar fixed relative ke viewport
    const deleteModalOverlay = document.getElementById('deleteModalOverlay');
    const formOverlay = document.getElementById('formOverlay');
    if (deleteModalOverlay) document.body.appendChild(deleteModalOverlay);
    if (formOverlay) document.body.appendChild(formOverlay);
</script>

<?php include 'layout/footer.php'; ?>