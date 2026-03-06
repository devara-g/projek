<?php
include "../database/conn.php";

// Memperbaiki query hapus yang sebelumnya tidak konsisten dan menambahkan redirect
if (isset($_GET['aksi']) && $_GET['aksi'] == 'hapus' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    if ($id > 0) {
        // Hapus foto jika ada
        $query_foto = mysqli_query($conn, "SELECT foto FROM agenda WHERE id = $id");
        $data_foto = mysqli_fetch_assoc($query_foto);
        if (!empty($data_foto['foto'])) {
            $file_path = '../' . $data_foto['foto'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }

        // Menggunakan prepared statement untuk keamanan
        $stmt = mysqli_prepare($conn, "DELETE FROM agenda WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);

        if (mysqli_stmt_execute($stmt)) {
            // Redirect dengan parameter success
            header("Location: agenda.php?status=success&message=Agenda berhasil dihapus");
            exit();
        } else {
            header("Location: agenda.php?status=error&message=Gagal menghapus agenda");
            exit();
        }
        mysqli_stmt_close($stmt);
    }
}

// Menambahkan handler untuk form tambah agenda
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['aksi']) && $_POST['aksi'] == 'tambah') {
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $tanggal = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $waktu = mysqli_real_escape_string($conn, $_POST['waktu']);
    $lokasi = mysqli_real_escape_string($conn, $_POST['lokasi']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
    $status = 'akan datang'; // Default status

    // Handle file upload
    $foto = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        $file_type = $_FILES['foto']['type'];
        $file_size = $_FILES['foto']['size'];

        if (in_array($file_type, $allowed_types) && $file_size <= 5 * 1024 * 1024) {
            $target_dir = '../upload/img/';
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $filename = 'agenda_' . time() . '_' . uniqid() . '.' . $extension;
            $upload_path = $target_dir . $filename;

            if (move_uploaded_file($_FILES['foto']['tmp_name'], $upload_path)) {
                $foto = 'upload/img/' . $filename;
            }
        }
    }

    // Prepared statement untuk INSERT
    $stmt = mysqli_prepare($conn, "INSERT INTO agenda (judul, tanggal, waktu, lokasi, deskripsi, status, foto) VALUES (?, ?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sssssss", $judul, $tanggal, $waktu, $lokasi, $keterangan, $status, $foto);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: agenda.php?status=success&message=Agenda berhasil ditambahkan");
        exit();
    } else {
        $error_message = "Gagal menambahkan agenda: " . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt);
}

// Menambahkan handler untuk form edit agenda
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['aksi']) && $_POST['aksi'] == 'edit') {
    $id = (int) $_POST['id'];
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $tanggal = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $waktu = mysqli_real_escape_string($conn, $_POST['waktu']);
    $lokasi = mysqli_real_escape_string($conn, $_POST['lokasi']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']); // Fix field name from deskripsi
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    // Handle file upload
    $sql_update = "UPDATE agenda SET judul=?, tanggal=?, waktu=?, lokasi=?, deskripsi=?, status=? WHERE id=?";
    $bind_types = "ssssssi";
    $bind_params = [$judul, $tanggal, $waktu, $lokasi, $keterangan, $status, $id];

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        $file_type = $_FILES['foto']['type'];
        $file_size = $_FILES['foto']['size'];

        if (in_array($file_type, $allowed_types) && $file_size <= 5 * 1024 * 1024) {
            // Hapus foto lama
            $query_foto_lama = mysqli_query($conn, "SELECT foto FROM agenda WHERE id = $id");
            $data_foto_lama = mysqli_fetch_assoc($query_foto_lama);
            if (!empty($data_foto_lama['foto'])) {
                $file_path = '../' . $data_foto_lama['foto'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }

            $target_dir = '../upload/img/';
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $filename = 'agenda_' . time() . '_' . uniqid() . '.' . $extension;
            $upload_path = $target_dir . $filename;

            if (move_uploaded_file($_FILES['foto']['tmp_name'], $upload_path)) {
                $foto_new = 'upload/img/' . $filename;
                $sql_update = "UPDATE agenda SET judul=?, tanggal=?, waktu=?, lokasi=?, deskripsi=?,    status=?, foto=? WHERE id=?";
                $bind_types = "sssssssi";
                $bind_params = [$judul, $tanggal, $waktu, $lokasi, $keterangan, $status, $foto_new, $id];
            }
        }
    }

    // Prepared statement untuk UPDATE
    $stmt = mysqli_prepare($conn, $sql_update);
    mysqli_stmt_bind_param($stmt, $bind_types, ...$bind_params);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: agenda.php?status=success&message=Agenda berhasil diperbarui");
        exit();
    } else {
        $error_message = "Gagal memperbarui agenda: " . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt);
}

// Menambahkan fungsi untuk mengambil data agenda berdasarkan ID (untuk edit)
function getAgendaById($conn, $id)
{
    $stmt = mysqli_prepare($conn, "SELECT * FROM agenda WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}
?>

<?php
$title = "Manajemen Agenda";
include 'layout/header.php';
?>



<div class="admin-page-container">
    <!-- Tampilkan Notifikasi -->
    <!-- Tampilkan Notifikasi -->
    <?php if (isset($_GET['status'])): ?>
        <div class="alert-container">
            <div class="alert alert-<?php echo $_GET['status'] == 'success' ? 'success' : 'error'; ?>">
                <i class="fas fa-<?php echo $_GET['status'] == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <div class="alert-content">
                    <div class="alert-title"><?php echo $_GET['status'] == 'success' ? 'Berhasil!' : 'Gagal!'; ?></div>
                    <div class="alert-message">
                        <?php echo htmlspecialchars($_GET['message'] ?? ($_GET['status'] == 'success' ? 'Operasi berhasil!' : 'Terjadi kesalahan!')); ?>
                    </div>
                </div>
                <div class="alert-close" onclick="this.closest('.alert-container').remove()">
                    <i class="fas fa-times"></i>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert-container">
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <div class="alert-content">
                    <div class="alert-title">Gagal!</div>
                    <div class="alert-message">
                        <?php echo htmlspecialchars($error_message); ?>
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
            <h1>Manajemen Agenda</h1>
            <p style="color: var(--gray);">Kelola jadwal kegiatan, rapat, dan acara sekolah.</p>
        </div>
        <button class="btn-add" onclick="openAddForm()">
            <i class="fas fa-plus"></i> Tambah Agenda
        </button>
    </div>

    <!-- Toolbar & Search -->
    <div class="card-panel stagger-item stagger-2" style="margin-bottom: 2rem; border-radius: 20px;">
        <div class="table-header" style="border-bottom: none; padding-bottom: 0; margin-bottom: 0;">
            <h3><i class="fas fa-list-ul" style="margin-right: 10px; color: var(--accent);"></i> Jadwal Agenda</h3>
            <div style="display: flex; gap: 10px; align-items: center;">
                <div class="header-search">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Cari agenda..." onkeyup="searchAgenda()">
                </div>
            </div>
        </div>
    </div>

    <!-- Agenda Grid Output -->
    <div class="agenda-grid stagger-item stagger-2" id="agendaGrid">
        <?php
        $no = 1;
        $data = mysqli_query($conn, "SELECT * FROM agenda ORDER BY 
            CASE 
                WHEN tanggal >= CURDATE() THEN 1
                ELSE 2
            END,
            tanggal ASC, waktu ASC") or die(mysqli_error($conn));

        if (mysqli_num_rows($data) > 0) {
            $delay = 0;
            while ($row = mysqli_fetch_array($data)) {
                $delay += 0.05;

                $kat_db = strtolower($row['status']);
                $badge_class = 'badge-success'; // upcoming
                $status_text = $row['status'];

                if ($kat_db == 'hari ini') {
                    $badge_class = 'badge-warning';
                    $status_text = 'Hari Ini';
                } elseif ($kat_db == 'selesai') {
                    $badge_class = 'badge-primary';
                    $status_text = 'Selesai';
                } elseif ($kat_db == 'di batalkan') {
                    $badge_class = 'badge-danger';
                    $status_text = 'Dibatalkan';
                } else {
                    $badge_class = 'badge-success';
                    $status_text = 'Akan Datang';
                }
        ?>
                <div class="content-card" style="animation-delay: <?= $delay ?>s;">
                    <?php if (!empty($row['foto']) && file_exists('../' . $row['foto'])): ?>
                        <img src="../<?= $row['foto'] ?>" class="card-img-top" alt="Agenda">
                    <?php else: ?>
                        <div class="card-img-top" style="background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%); display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-calendar-alt" style="font-size: 3rem; color: #cbd5e1;"></i>
                        </div>
                    <?php endif; ?>

                    <div class="card-body">
                        <div class="card-category">
                            <span class="status-badge <?= $badge_class ?>"><?= htmlspecialchars($status_text) ?></span>
                            <div class="date-badge">
                                <i class="far fa-calendar"></i> <?= date('d M Y', strtotime($row['tanggal'])) ?>
                            </div>
                        </div>

                        <h3 class="card-title" style="margin-top: 10px;"><?= htmlspecialchars($row['judul']) ?></h3>

                        <div class="card-meta">
                            <span><i class="far fa-clock"></i> <?= htmlspecialchars($row['waktu']) ?></span>
                            <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($row['lokasi']) ?></span>
                        </div>

                        <div class="card-desc">
                            <?= htmlspecialchars(substr($row['deskripsi'], 0, 100)) . (strlen($row['deskripsi']) > 100 ? '...' : '') ?>
                        </div>

                        <div class="card-footer">
                            <div style="font-size: 0.8rem; color: var(--slate-400);">
                                #<?= $no++ ?>
                            </div>
                            <div class="card-actions">
                                <button class="btn-card-action" title="Edit" onclick='openEditForm(<?= json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-card-action delete" title="Hapus" onclick="showDeleteModal(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['judul'])) ?>')">
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
                <i class="fas fa-calendar-times empty-icon"></i>
                <h3>Belum ada agenda</h3>
                <p style="color: var(--gray);">Belum ada jadwal kegiatan sekolah saat ini.</p>
                <div style="margin-top: 1.5rem;">
                    <button class="btn-add" onclick="openAddForm()">
                        <i class="fas fa-plus"></i> Tambah Agenda
                    </button>
                </div>
            </div>
        <?php } ?>
    </div>

    <script>
        // Show scroll hint on mobile if table is overflowing
        document.addEventListener('DOMContentLoaded', function() {
            const tableWrapper = document.querySelector('.table-card > div[style*="overflow-x: auto"]');
            const hint = document.querySelector('.mobile-scroll-hint');
            if (tableWrapper && hint && window.innerWidth < 768) {
                if (tableWrapper.scrollWidth > tableWrapper.clientWidth) {
                    hint.style.display = 'block';
                }
            }
        });
        // REAL TIME SEARCH (Updated for Grid)
        function searchAgenda() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase().trim();
            const cards = document.querySelectorAll('.agenda-grid .content-card');
            let visibleCount = 0;
            let emptyState = document.querySelector('.empty-grid-state');

            cards.forEach(card => {
                const title = card.querySelector('.card-title').textContent.toLowerCase();
                const date = card.querySelector('.date-badge').textContent.toLowerCase();
                const location = card.querySelector('.card-meta').textContent.toLowerCase();
                const status = card.querySelector('.status-badge').textContent.toLowerCase();

                if (title.includes(filter) || date.includes(filter) || location.includes(filter) || status.includes(filter)) {
                    card.style.display = 'flex';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            // Handle empty state visibility
            if (visibleCount === 0) {
                if (!emptyState) {
                    const grid = document.getElementById('agendaGrid');
                    const emptyDiv = document.createElement('div');
                    emptyDiv.className = 'empty-grid-state';
                    emptyDiv.innerHTML = `
                    <i class="fas fa-search empty-icon"></i>
                    <h3>Tidak ada agenda ditemukan</h3>
                    <p style="color: var(--gray);">Coba kata kunci lain.</p>
                    <div style="margin-top: 1.5rem;">
                        <button class="btn-add" onclick="openAddForm()">
                            <i class="fas fa-plus"></i> Tambah Agenda
                        </button>
                    </div>
                `;
                    grid.appendChild(emptyDiv);
                } else {
                    emptyState.style.display = 'flex';
                    emptyState.querySelector('h3').textContent = 'Tidak ada agenda ditemukan';
                    emptyState.querySelector('p').textContent = 'Coba kata kunci lain.';
                    const btn = emptyState.querySelector('.btn-add');
                    if (btn) btn.style.display = 'none';
                }
            } else {
                if (emptyState) {
                    if (emptyState.innerHTML.includes('fa-search')) {
                        emptyState.remove();
                    } else {
                        emptyState.style.display = 'none';
                    }
                }
            }
        }

        // Initialization
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-hide alert after 3 seconds
            const alertContainer = document.querySelector('.alert-container');
            if (alertContainer) {
                setTimeout(() => {
                    alertContainer.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    alertContainer.style.opacity = '0';
                    alertContainer.style.transform = 'translateY(-20px)';
                    setTimeout(() => alertContainer.remove(), 500);
                }, 3000);
            }

            // Close modal on outside click
            window.onclick = function(event) {
                const formOverlay = document.getElementById('formOverlay');
                if (event.target == formOverlay) {
                    closeForm();
                }
            }
        });
    </script>

    <div class="modal-overlay" id="formOverlay">
        <div class="modal-card">
            <div class="modal-header">
                <h2 id="formTitle"><i class="fas fa-calendar-alt"></i> Tambah Agenda</h2>
                <div class="btn-close-modal" onclick="closeForm()"><i class="fas fa-times"></i></div>
            </div>
            <form id="agendaForm" action="" method="POST" enctype="multipart/form-data" style="display: contents;">
                <div class="modal-body">
                    <input type="hidden" name="aksi" id="formAksi" value="tambah">
                    <input type="hidden" name="id" id="agendaId" value="">

                    <div class="form-group">
                        <label>Judul Kegiatan <span style="color: red;">*</span></label>
                        <input type="text" name="judul" id="judul" required placeholder="Contoh: Rapat Mingguan">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label>Tanggal Pelaksanaan <span style="color: red;">*</span></label>
                            <input type="date" name="tanggal" id="tanggal" required>
                        </div>
                        <div class="form-group">
                            <label>Waktu</label>
                            <input type="text" name="waktu" id="waktu" placeholder="Contoh: 08:00 - 10:00">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Lokasi</label>
                        <input type="text" name="lokasi" id="lokasi" placeholder="Contoh: Aula Sekolah">
                    </div>

                    <div class="form-group">
                        <label>Keterangan Tambahan</label>
                        <textarea name="keterangan" id="keterangan" rows="4" placeholder="Detail kegiatan..."></textarea>
                    </div>

                    <div class="form-group">
                        <label>Foto Agenda</label>
                        <div class="file-upload-wrapper">
                            <input type="file" name="foto" id="foto" class="file-upload-input" onchange="previewImage(this)">
                            <div class="file-upload-label" id="uploadLabelArea">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Pilih foto atau tarik ke sini</span>
                                <p style="font-size: 0.8rem; margin-top: 5px;">Format: JPG, PNG, GIF (Maks. 5MB)</p>
                            </div>
                        </div>
                        <div id="imagePreview" class="image-preview">
                            <img src="" id="previewImg" alt="Preview">
                        </div>
                    </div>

                    <div class="form-group" id="statusGroup" style="display: none;">
                        <label>Status</label>
                        <select name="status" id="status">
                            <option value="akan datang">Akan Datang</option>
                            <option value="hari ini">Hari Ini</option>
                            <option value="selesai">Selesai</option>
                            <option value="di batalkan">Dibatalkan</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeForm()">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="submit" class="btn-submit" id="submitBtn">
                        <i class="fas fa-save"></i> <span id="btnText">Simpan Agenda</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="delete-modal-overlay" id="deleteModal" onclick="if(event.target===this) closeDeleteForm()">
        <div class="delete-modal-card" onclick="event.stopPropagation()">
            <div class="delete-icon">
                <i class="fas fa-trash-alt"></i>
            </div>
            <h3>Hapus Agenda?</h3>
            <p id="deleteMessage">Yakin ingin menghapus agenda ini? Tindakan ini tidak dapat dibatalkan.</p>
            <div class="delete-actions">
                <button type="button" class="btn-cancel" onclick="closeDeleteForm()">Batal</button>
                <a href="#" class="btn-confirm-delete" id="deleteConfirmBtn">
                    <i class="fas fa-trash"></i> Hapus
                </a>
            </div>
        </div>
    </div>

    <script>
            // Pindahkan modal ke body agar fixed relative ke viewport (bukan main-content yg punya transform)
    document.addEventListener('DOMContentLoaded', function() {
        const editModal = document.getElementById('editModal');
        const msgModal = document.getElementById('msgModal');
        const deleteModal = document.getElementById('deleteModal');
        if (editModal) document.body.appendChild(editModal);
        if (msgModal) document.body.appendChild(msgModal);
        if (deleteModal) document.body.appendChild(deleteModal);
    });

        // State untuk menyimpan ID yang akan dihapus
        let deleteId = null;

        // Standardized Form Functions
        function openAddForm() {
            const overlay = document.getElementById('formOverlay');
            const form = document.getElementById('agendaForm');
            document.getElementById('formTitle').innerHTML = '<i class="fas fa-calendar-plus"></i> Tambah Agenda Baru';
            document.getElementById('btnText').innerText = 'Simpan Agenda';
            document.getElementById('formAksi').value = 'tambah';
            document.getElementById('statusGroup').style.display = 'none';
            form.reset();
            document.getElementById('agendaId').value = '';
            document.getElementById('imagePreview').style.display = 'none';
            
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
            const form = document.getElementById('agendaForm');
            document.getElementById('formTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Agenda';
            document.getElementById('btnText').innerText = 'Update Agenda';
            document.getElementById('formAksi').value = 'edit';
            document.getElementById('statusGroup').style.display = 'block';

            document.getElementById('agendaId').value = data.id || '';
            document.getElementById('judul').value = data.judul || '';
            document.getElementById('tanggal').value = data.tanggal || '';
            document.getElementById('waktu').value = data.waktu || '';
            document.getElementById('lokasi').value = data.lokasi || '';
            document.getElementById('keterangan').value = data.deskripsi || '';
            document.getElementById('status').value = data.status || 'akan datang';

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
        }

        function showDeleteModal(id, judul) {
            deleteId = id;
            document.getElementById('deleteMessage').innerHTML = `Yakin ingin menghapus agenda <strong>"${judul}"</strong>?<br>Tindakan ini tidak dapat dibatalkan.`;
            document.getElementById('deleteConfirmBtn').href = `?aksi=hapus&id=${id}`;
            document.getElementById('deleteModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeDeleteForm() {
            document.getElementById('deleteModal').style.display = 'none';
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

        // REAL TIME SEARCH (Updated for Grid)
        function searchAgenda() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase().trim();
            const cards = document.querySelectorAll('.agenda-grid .content-card');
            let visibleCount = 0;
            let emptyState = document.querySelector('.empty-grid-state');

            cards.forEach(card => {
                const title = card.querySelector('.card-title').textContent.toLowerCase();
                const date = card.querySelector('.date-badge').textContent.toLowerCase();
                const location = card.querySelector('.card-meta').textContent.toLowerCase();
                const status = card.querySelector('.status-badge').textContent.toLowerCase();

                if (title.includes(filter) || date.includes(filter) || location.includes(filter) || status.includes(filter)) {
                    card.style.display = 'flex';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            // Handle empty state visibility
            if (visibleCount === 0) {
                if (!emptyState) {
                    const grid = document.getElementById('agendaGrid');
                    const emptyDiv = document.createElement('div');
                    emptyDiv.className = 'empty-grid-state';
                    emptyDiv.innerHTML = `
                    <i class="fas fa-search empty-icon"></i>
                    <h3>Tidak ada agenda ditemukan</h3>
                    <p style="color: var(--gray);">Coba kata kunci lain.</p>
                `;
                    grid.appendChild(emptyDiv);
                } else {
                    emptyState.style.display = 'flex';
                    emptyState.querySelector('h3').textContent = 'Tidak ada agenda ditemukan';
                    emptyState.querySelector('p').textContent = 'Coba kata kunci lain.';
                    const btn = emptyState.querySelector('.btn-add');
                    if (btn) btn.style.display = 'none';
                }
            } else {
                if (emptyState) {
                    if (emptyState.innerHTML.includes('fa-search')) {
                        emptyState.remove();
                    } else {
                        emptyState.style.display = 'none';
                    }
                }
            }
        }

        // Initialization
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-hide alert after 3 seconds
            const alertContainer = document.querySelector('.alert-container');
            if (alertContainer) {
                setTimeout(() => {
                    alertContainer.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    alertContainer.style.opacity = '0';
                    alertContainer.style.transform = 'translateY(-20px)';
                    setTimeout(() => alertContainer.remove(), 500);
                }, 3000);
            }

            // Close modal on outside click
            window.onclick = function(event) {
                if (event.target == document.getElementById('formOverlay')) closeForm();
                if (event.target == document.getElementById('deleteOverlay')) closeDeleteForm();
            }
        });
        // Pindahkan modal ke body agar fixed relative ke viewport
        const deleteModalOverlay = document.getElementById('deleteModalOverlay');
        const formOverlay = document.getElementById('formOverlay');
        if (deleteModalOverlay) document.body.appendChild(deleteModalOverlay);
        if (formOverlay) document.body.appendChild(formOverlay);
    </script>

    <?php include 'layout/footer.php'; ?>