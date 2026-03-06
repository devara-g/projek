<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("Location: login.php");
    exit;
}

// Get current page name for active menu
$current_page = basename($_SERVER['PHP_SELF']);

// Proses Update Profil Modal
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_profile') {
    include_once '../database/conn.php';
    
    // Pastikan session ID admin tersedia
    // Karena login.php saat ini tidak menyimpan ID admin di session, kita akan update berdasarkan username saat ini
    // Untuk keamanan produksi, disarankan menggunakan $_SESSION['admin_id'] 
    $current_session_username = $_SESSION['username'];
    
    // Dapatkan data admin saat ini untuk verifikasi password
    $q_admin = mysqli_query($conn, "SELECT * FROM admin WHERE username='$current_session_username' OR nama='$current_session_username'");
    
    if($q_admin && mysqli_num_rows($q_admin) > 0) {
        $admin_data = mysqli_fetch_assoc($q_admin);
        $admin_id = $admin_data['id'];
        
        $new_username = mysqli_real_escape_string($conn, $_POST['username']);
        $old_password = $_POST['old_password'];
        $new_password = $_POST['new_password'];
        
        $update_query = "UPDATE admin SET username = '$new_username'";
        $success = true;
        
        // Handle Password Update (jika diisi)
        if (!empty($old_password) || !empty($new_password)) {
            if ($old_password == $admin_data['password']) {
                if(!empty($new_password)) {
                    $new_password_safe = mysqli_real_escape_string($conn, $new_password);
                    $update_query .= ", password = '$new_password_safe'";
                }
            } else {
                $success = false;
                $_SESSION['profile_error'] = "Password lama yang Anda masukkan salah.";
            }
        }
        
        // Handle Foto Upload
        if ($success && isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'svg'];
            $filename = $_FILES['foto']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $new_filename = 'admin_avatar_' . time() . '.' . $ext;
                $dest = '../upload/img/' . $new_filename;
                
                // Buat direktori jika belum ada
                if (!file_exists('../upload/img/')) {
                    mkdir('../upload/img/', 0777, true);
                }
                
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $dest)) {
                    $update_query .= ", foto = '$new_filename'";
                    
                    // Hapus foto lama jika bukan default
                    if (!empty($admin_data['foto']) && file_exists('../upload/img/' . $admin_data['foto'])) {
                        @unlink('../upload/img/' . $admin_data['foto']);
                    }
                    $_SESSION['foto'] = $new_filename;
                } else {
                    $success = false;
                    $_SESSION['profile_error'] = "Gagal mengunggah foto profil.";
                }
            } else {
                $success = false;
                $_SESSION['profile_error'] = "Format foto tidak didukung (gunakan JPG, PNG, atau SVG).";
            }
        }
        
        if ($success) {
            $update_query .= " WHERE id = $admin_id";
            if(mysqli_query($conn, $update_query)) {
                $_SESSION['username'] = $new_username;
                $_SESSION['profile_success'] = "Profil berhasil diperbarui!";
                
                // Redirect untuk refresh halaman
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit;
            } else {
                $_SESSION['profile_error'] = "Terjadi kesalahan database: " . mysqli_error($conn);
            }
        }
    } else {
        $_SESSION['profile_error'] = "Data user tidak ditemukan.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? $title : 'Dashboard Admin'; ?> - SMP PGRI 3 BOGOR</title>
    <link rel="stylesheet" href="../css/style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="css/admin.css?v=<?= time(); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* ===== Sidebar Dropdown - Unified Accordion Style (All Screens) ===== */

        /* Arrow indicator on the toggle link */
        .sidebar-menu .dropdown>a {
            display: flex;
            align-items: center;
        }

        .sidebar-menu .dropdown>a .arrow {
            margin-left: auto;
            font-size: 11px;
            color: #64748b;
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1),
                color 0.3s ease;
            flex-shrink: 0;
        }

        .sidebar-menu .dropdown.show>a .arrow {
            transform: rotate(90deg);
            color: #38bdf8;
        }

        /* Active parent link highlight */
        .sidebar-menu .dropdown.show>a {
            color: #f8fafc;
            background: rgba(56, 189, 248, 0.08);
            border-radius: 14px;
        }

        /* ===== Accordion submenu - works on ALL screen sizes ===== */
        .sidebar-menu .dropdown .dropdown-menu {
            display: block;
            /* must be block for max-height animation */
            position: static;
            /* in-flow, never floating */
            left: unset;
            top: unset;
            transform: none;
            width: auto;

            /* Collapsed state */
            max-height: 0;
            overflow: hidden;
            opacity: 0;
            visibility: hidden;

            /* Sidebar-matching colors */
            background: rgba(255, 255, 255, 0.03);
            border-left: 2px solid rgba(56, 189, 248, 0.2);
            border-radius: 0 0 10px 10px;
            margin: 0 8px 0 16px;
            padding: 0;
            list-style: none;
            box-shadow: none;
            z-index: auto;

            transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1),
                opacity 0.35s ease,
                visibility 0.35s ease,
                margin 0.35s ease,
                padding 0.35s ease;
        }

        /* Expanded state */
        .sidebar-menu .dropdown.show .dropdown-menu {
            max-height: 500px;
            opacity: 1;
            visibility: visible;
            margin-top: 6px;
            margin-bottom: 8px;
            padding: 4px 0;
        }

        /* Submenu items */
        .sidebar-menu .dropdown .dropdown-menu li {
            margin: 2px 0;
            padding: 0;
        }

        .sidebar-menu .dropdown .dropdown-menu li a {
            padding: 9px 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #94a3b8;
            /* Slate 400 - matches sidebar inactive */
            font-size: 0.85rem;
            font-weight: 500;
            text-decoration: none;
            border-left: 2px solid transparent;
            border-radius: 8px;
            margin: 0 4px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sidebar-menu .dropdown .dropdown-menu li a i {
            font-size: 14px;
            width: 18px;
            text-align: center;
            color: #64748b;
            /* Slate 500 - matches sidebar icon color */
            transition: color 0.3s ease, transform 0.3s ease;
        }

        .sidebar-menu .dropdown .dropdown-menu li a:hover {
            background: rgba(56, 189, 248, 0.06);
            color: #f8fafc;
            border-left-color: #38bdf8;
        }

        .sidebar-menu .dropdown .dropdown-menu li a:hover i {
            color: #38bdf8;
            transform: scale(1.1);
        }

        .sidebar-menu .dropdown .dropdown-menu li a.active {
            background: rgba(56, 189, 248, 0.12);
            color: #38bdf8;
            border-left-color: #38bdf8;
            font-weight: 700;
        }

        .sidebar-menu .dropdown .dropdown-menu li a.active i {
            color: #38bdf8;
            filter: drop-shadow(0 0 6px rgba(56, 189, 248, 0.4));
        }
    </style>
</head>

<body>

    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="admin-logo-container" style="overflow: hidden;">
                    <?php if(isset($_SESSION['foto']) && !empty($_SESSION['foto']) && file_exists('../upload/img/' . $_SESSION['foto'])): ?>
                            <img src="../upload/img/<?= htmlspecialchars($_SESSION['foto']) ?>" alt="Admin Avatar" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <i class="fas fa-user"></i>
                        <?php endif; ?>
                </div>
                <div class="sidebar-brand">
                    <h2>SMP PGRI 3 BGR</h2>
                    <span><?= isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Administrator'; ?></span>
                </div>
            </div>

            <div class="sidebar-menu">
                <div class="menu-label">Main Menu</div>
                <ul>
                    <li><a href="index.php" class="<?= $current_page == 'index.php' ? 'active' : ''; ?>"><i class="bx bxs-dashboard"></i> Dashboard</a></li>
                    <li><a href="berita.php" class="<?= $current_page == 'berita.php' ? 'active' : ''; ?>"><i class="bx bxs-news"></i> Berita & Artikel</a></li>
                    <li><a href="agenda.php" class="<?= $current_page == 'agenda.php' ? 'active' : ''; ?>"><i class="bx bxs-calendar-event"></i> Agenda Sekolah</a></li>
                    <li><a href="galeri.php" class="<?= $current_page == 'galeri.php' ? 'active' : ''; ?>"><i class="bx bxs-camera"></i> Galeri Kegiatan</a></li>
                    <li class="dropdown <?= in_array($current_page, ['guru.php', 'osis.php', 'mpk.php', 'kepsek.php']) ? 'active show' : ''; ?>">
                        <a href="javascript:void(0)" class="dropdown-toggle"><i class="bx bxs-user-pin"></i> Data Guru & Staff <i class="fas fa-chevron-right arrow"></i></a>
                        <ul class="dropdown-menu">
                            <li><a href="guru.php" class="<?= $current_page == 'guru.php' ? 'active' : ''; ?>"><i class="fas fa-chalkboard-teacher"></i> Data Guru</a></li>
                            <li><a href="osis.php" class="<?= $current_page == 'osis.php' ? 'active' : ''; ?>"><i class="fas fa-users"></i> Data OSIS</a></li>
                            <li><a href="mpk.php" class="<?= $current_page == 'mpk.php' ? 'active' : ''; ?>"><i class="fas fa-user-tie"></i> Data MPK</a></li>
                            <li><a href="kepsek.php" class="<?= $current_page == 'kepsek.php' ? 'active' : ''; ?>"><i class="fas fa-school"></i> Data Kepsek</a></li>
                        </ul>
                    </li>
                </ul>

                <div class="menu-label">External</div>
                <ul>
                    <li><a href="pesan.php" class="<?= $current_page == 'pesan.php' ? 'active' : ''; ?>"><i class="fas fa-envelope"></i> Pesan Masuk</a></li>
                    <li><a href="../index.php" target="_blank"><i class="fas fa-external-link-alt"></i> Lihat Website</a></li>
                </ul>
            </div>

            <div class="logout-btn">
                <a href="javascript:void(0)" id="logoutBtn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </aside>

        <!-- Main Content - Hapus class page-transition -->
        <main class="main-content">
            <!-- Mobile Sidebar Overlay -->
            <div class="sidebar-overlay" id="sidebarOverlay"></div>

            <!-- Top Bar -->
            <div class="top-bar">
                <button class="toggle-sidebar" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>

                <div class="search-box">
                    <i class="fas fa-search" style="color: var(--gray);"></i>
                    <input type="text" placeholder="Cari data...">
                </div>

                <div class="user-profile" id="btnProfileTop" style="cursor: pointer;">
                    <div class="user-info">
                        <h4><?= isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Administrator'; ?></h4>
                        <p>Super Admin</p>
                    </div>
                    <div class="user-avatar" style="overflow: hidden;">
                        <?php if(isset($_SESSION['foto']) && !empty($_SESSION['foto']) && file_exists('../upload/img/' . $_SESSION['foto'])): ?>
                            <img src="../upload/img/<?= htmlspecialchars($_SESSION['foto']) ?>" alt="Admin Avatar" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <i class="fas fa-user"></i>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php if(isset($_SESSION['profile_success'])): ?>
            <div class="toast-notification success active" style="position: fixed; top: 20px; right: 20px; z-index: 9999; background: #10b981; color: white; padding: 15px 25px; border-radius: 10px; box-shadow: 0 10px 25px rgba(16, 185, 129, 0.4); display: flex; align-items: center; gap: 15px; animation: slideInRight 0.5s ease forwards;">
                <i class="fas fa-check-circle" style="font-size: 1.5rem;"></i>
                <div>
                    <h4 style="margin: 0; font-size: 1rem;">Berhasil!</h4>
                    <p style="margin: 0; font-size: 0.85rem; opacity: 0.9;"><?= $_SESSION['profile_success'] ?></p>
                </div>
                <button onclick="this.parentElement.remove()" style="background: none; border: none; color: white; cursor: pointer; margin-left: 10px;"><i class="fas fa-times"></i></button>
            </div>
            <script>setTimeout(() => { const t = document.querySelector('.toast-notification'); if(t) t.remove(); }, 4000);</script>
            <?php unset($_SESSION['profile_success']); endif; ?>
            
            <?php if(isset($_SESSION['profile_error'])): ?>
            <div class="toast-notification error active" style="position: fixed; top: 20px; right: 20px; z-index: 9999; background: #ef4444; color: white; padding: 15px 25px; border-radius: 10px; box-shadow: 0 10px 25px rgba(239, 68, 68, 0.4); display: flex; align-items: center; gap: 15px; animation: slideInRight 0.5s ease forwards;">
                <i class="fas fa-exclamation-circle" style="font-size: 1.5rem;"></i>
                <div>
                    <h4 style="margin: 0; font-size: 1rem;">Gagal!</h4>
                    <p style="margin: 0; font-size: 0.85rem; opacity: 0.9;"><?= $_SESSION['profile_error'] ?></p>
                </div>
                <button onclick="this.parentElement.remove()" style="background: none; border: none; color: white; cursor: pointer; margin-left: 10px;"><i class="fas fa-times"></i></button>
            </div>
            <script>setTimeout(() => { const t = document.querySelector('.toast-notification'); if(t) t.remove(); }, 5000);</script>
            <?php unset($_SESSION['profile_error']); endif; ?>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const sidebar = document.querySelector('.sidebar');
                    const sidebarToggle = document.getElementById('sidebarToggle');
                    const sidebarOverlay = document.getElementById('sidebarOverlay');

                    if (sidebarToggle && sidebar && sidebarOverlay) {
                        sidebarToggle.addEventListener('click', function() {
                            sidebar.classList.toggle('active');
                            sidebarOverlay.classList.toggle('active');
                            document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
                        });

                        sidebarOverlay.addEventListener('click', function() {
                            sidebar.classList.remove('active');
                            sidebarOverlay.classList.remove('active');
                            document.body.style.overflow = '';
                        });
                    }

                    // Logout Modal Logic
                    const logoutBtn = document.getElementById('logoutBtn');
                    const logoutModal = document.getElementById('logoutModal');
                    const btnCancelLogout = document.getElementById('btnCancelLogout');

                    if (logoutBtn && logoutModal && btnCancelLogout) {
                        logoutBtn.addEventListener('click', function(e) {
                            e.preventDefault();
                            logoutModal.classList.add('active');
                            document.body.style.overflow = 'hidden';
                        });

                        btnCancelLogout.addEventListener('click', function() {
                            logoutModal.classList.remove('active');
                            document.body.style.overflow = '';
                        });

                        logoutModal.addEventListener('click', function(e) {
                            if (e.target === logoutModal) {
                                logoutModal.classList.remove('active');
                                document.body.style.overflow = '';
                            }
                        });
                    }

                    // Profile Modal Logic
                    const btnProfileTop = document.getElementById('btnProfileTop');
                    const profileModal = document.getElementById('profileModal');
                    const btnCloseProfile = document.getElementById('btnCloseProfile');
                    
                    if (btnProfileTop && profileModal && btnCloseProfile) {
                        btnProfileTop.addEventListener('click', function(e) {
                            e.preventDefault();
                            profileModal.classList.add('active');
                            document.body.style.overflow = 'hidden';
                        });

                        btnCloseProfile.addEventListener('click', function() {
                            profileModal.classList.remove('active');
                            document.body.style.overflow = '';
                        });

                        profileModal.addEventListener('click', function(e) {
                            if (e.target === profileModal) {
                                profileModal.classList.remove('active');
                                document.body.style.overflow = '';
                            }
                        });
                    }
                    
                    // Profile Image Preview
                    const modalPhotoInput = document.getElementById('modalFotoUpload');
                    const modalPhotoPreview = document.getElementById('modalFotoPreview');
                    
                    if(modalPhotoInput && modalPhotoPreview) {
                        modalPhotoInput.addEventListener('change', function() {
                            if (this.files && this.files[0]) {
                                const reader = new FileReader();
                                reader.onload = function(e) {
                                    modalPhotoPreview.src = e.target.result;
                                    modalPhotoPreview.style.display = 'block';
                                    const icon = modalPhotoPreview.nextElementSibling;
                                    if(icon) icon.style.display = 'none';
                                }
                                reader.readAsDataURL(this.files[0]);
                            }
                        });
                    }

                    // Dropdown Toggle Logic - Improved
                    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');

                    dropdownToggles.forEach(toggle => {
                        toggle.addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();

                            const dropdown = this.closest('.dropdown');

                            // Close other dropdowns (Accordion logic)
                            document.querySelectorAll('.dropdown.show').forEach(d => {
                                if (d !== dropdown) {
                                    d.classList.remove('show');
                                }
                            });

                            // Toggle current dropdown
                            dropdown.classList.toggle('show');
                        });
                    });

                    // Close dropdown when clicking outside
                    document.addEventListener('click', function(e) {
                        if (!e.target.closest('.sidebar')) {
                            document.querySelectorAll('.dropdown.show').forEach(d => {
                                d.classList.remove('show');
                            });
                        }
                    });

                    // File Upload Logic
                    const fileInputs = document.querySelectorAll('.file-upload-input');
                    fileInputs.forEach(input => {
                        input.addEventListener('change', function() {
                            const fileName = this.files[0] ? this.files[0].name : '';
                            const wrapper = this.closest('.file-upload-wrapper');
                            if (wrapper) {
                                const nameDisplay = wrapper.querySelector('.file-name');
                                const labelSpan = wrapper.querySelector('span:not(.file-name)');
                                const labelIcon = wrapper.querySelector('i');

                                if (fileName) {
                                    if (nameDisplay) {
                                        nameDisplay.textContent = fileName;
                                        nameDisplay.style.display = 'block';
                                    }
                                    if (labelSpan) labelSpan.textContent = 'File terpilih:';
                                    if (labelIcon) labelIcon.style.color = '#15803d';
                                    const label = wrapper.querySelector('.file-upload-label');
                                    if (label) label.style.borderColor = '#15803d';
                                } else {
                                    if (nameDisplay) nameDisplay.style.display = 'none';
                                    if (labelSpan) labelSpan.textContent = 'Klik atau seret file ke sini';
                                    if (labelIcon) labelIcon.style.color = '';
                                    const label = wrapper.querySelector('.file-upload-label');
                                    if (label) label.style.borderColor = '';
                                }
                            }
                        });
                    });
                });

                // Pindahkan modal ke body
                document.addEventListener('DOMContentLoaded', function() {
                    const logoutModal = document.getElementById('logoutModal');
                    if (logoutModal) {
                        document.body.appendChild(logoutModal);
                    }
                });
            </script>

            <!-- Logout Confirmation Modal -->
            <div class="logout-overlay" id="logoutModal">
                <div class="logout-modal">
                    <div class="logout-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h2>Konfirmasi Logout</h2>
                    <p>Apakah Anda yakin ingin keluar dari halaman panel admin? Sesi Anda akan berakhir.</p>
                    <div class="logout-btns">
                        <button class="btn-cancel" id="btnCancelLogout">Batal</button>
                        <a href="logout.php" class="btn-confirm-logout">Ya, Keluar</a>
                    </div>
                </div>
            </div>

            <!-- Profile Settings Modal -->
            <div class="logout-overlay" id="profileModal">
                <div class="logout-modal" style="max-width: 500px; text-align: left; padding: 30px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 15px;">
                        <h2 style="margin: 0; font-size: 1.4rem; color: #0f172a;"><i class="fas fa-user-edit" style="color: #3b82f6; margin-right: 10px;"></i> Edit Profil Admin</h2>
                        <button id="btnCloseProfile" style="background: none; border: none; font-size: 1.2rem; color: #94a3b8; cursor: pointer;"><i class="fas fa-times"></i></button>
                    </div>
                    
                    <form action="" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div style="display: flex; gap: 20px; margin-bottom: 20px;">
                            <div style="width: 100px; height: 100px; border-radius: 15px; background: #f1f5f9; overflow: hidden; position: relative; border: 2px dashed #cbd5e1; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <?php if(isset($_SESSION['foto']) && !empty($_SESSION['foto']) && file_exists('../upload/img/' . $_SESSION['foto'])): ?>
                                    <img id="modalFotoPreview" src="../upload/img/<?= htmlspecialchars($_SESSION['foto']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                    <i class="fas fa-camera" style="position: absolute; display: none; color: #94a3b8; font-size: 1.5rem;"></i>
                                <?php else: ?>
                                    <img id="modalFotoPreview" src="" style="width: 100%; height: 100%; object-fit: cover; display: none;">
                                    <i class="fas fa-camera" style="color: #94a3b8; font-size: 1.5rem;"></i>
                                <?php endif; ?>
                            </div>
                            <div style="flex-grow: 1; display: flex; flex-direction: column; justify-content: center;">
                                <label style="font-size: 0.9rem; font-weight: 600; color: #334155; margin-bottom: 8px;">Foto Profil</label>
                                <div class="file-upload-wrapper" style="height: auto;">
                                    <input type="file" name="foto" id="modalFotoUpload" class="file-upload-input" accept="image/jpeg, image/png, image/svg+xml">
                                    <label class="file-upload-label" style="height: 45px; padding: 0 15px; justify-content: flex-start; gap: 10px;">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <span style="font-size: 0.85rem;">Pilih foto baru...</span>
                                    </label>
                                </div>
                                <small style="color: #94a3b8; font-size: 0.75rem; margin-top: 5px;">Maksimal 2MB (JPG, PNG)</small>
                            </div>
                        </div>

                        <div style="margin-bottom: 20px;">
                            <label style="display: block; font-size: 0.9rem; font-weight: 600; color: #334155; margin-bottom: 8px;">Username Tampilan</label>
                            <div style="position: relative;">
                                <i class="fas fa-user" style="position: absolute; top: 50%; transform: translateY(-50%); left: 15px; color: #94a3b8;"></i>
                                <input type="text" name="username" value="<?= isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : '' ?>" required style="width: 100%; padding: 12px 15px 12px 40px; border: 1px solid #e2e8f0; border-radius: 10px; outline: none; transition: all 0.3s; font-family: 'Poppins', sans-serif;">
                            </div>
                        </div>

                        <div style="padding: 15px; background: rgba(241, 245, 249, 0.5); border-radius: 10px; margin-bottom: 20px; border: 1px solid #e2e8f0;">
                            <h4 style="margin: 0 0 15px 0; font-size: 0.95rem; color: #0f172a;"><i class="fas fa-lock" style="margin-right: 8px; color: #64748b;"></i> Ganti Password (Opsional)</h4>
                            
                            <div style="margin-bottom: 15px;">
                                <label style="display: block; font-size: 0.85rem; color: #64748b; margin-bottom: 5px;">Password Lama</label>
                                <input type="password" name="old_password" style="width: 100%; padding: 10px 15px; border: 1px solid #cbd5e1; border-radius: 8px; outline: none;" placeholder="Kosongkan jika tidak ingin ganti password">
                            </div>
                            
                            <div>
                                <label style="display: block; font-size: 0.85rem; color: #64748b; margin-bottom: 5px;">Password Baru</label>
                                <input type="password" name="new_password" style="width: 100%; padding: 10px 15px; border: 1px solid #cbd5e1; border-radius: 8px; outline: none;" placeholder="Masukkan password baru">
                            </div>
                        </div>

                        <div style="display: flex; justify-content: flex-end; gap: 15px; margin-top: 25px;">
                            <button type="submit" style="background: #3b82f6; color: white; border: none; padding: 10px 25px; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3); transition: all 0.3s;">
                                <i class="fas fa-save"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>