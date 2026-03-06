    <!DOCTYPE html>
    <html lang="id">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>SMP PGRI 3 BGR - Sekolah Hebat Indonesia</title>
        <link rel="stylesheet" href="../css/style.css?v=<?= time(); ?>">
        <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
        <link rel="icon" href="../img/p3hd.jpg">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    </head>

    <body>
        <header>
            <nav>
                <a href="../index.php" class="logo">
                    <img src="../img/p3hd.jpg?v=<?= time(); ?>" alt="Logo Sekolah">
                    <span>SMP PGRI 3 BGR</span>
                </a>

                <div class="mobile-menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>

                <ul class="nav-links">
                    <li><a href="../index.php">Beranda</a></li>
                    <li class="dropdown">
                        <a href="#">Tentang <i class="fas fa-chevron-down" style="font-size: 0.7rem; margin-left: 4px;"></i></a>
                        <ul class="dropdown-menu">
                            <li><a href="about.php">Profil Sekolah</a></li>
                            <li><a href="visi-misi.php">Visi & Misi</a></li>
                        </ul>
                    </li>
                    <li class="dropdown">
                        <a href="#">Berita <i class="fas fa-chevron-down" style="font-size: 0.7rem; margin-left: 4px;"></i></a>
                        <ul class="dropdown-menu">
                            <li><a href="berita.php">Berita Terbaru</a></li>
                            <li><a href="agenda.php">Agenda Sekolah</a></li>
                        </ul>
                    </li>
                    <li class="dropdown">
                        <a href="#">Galeri <i class="fas fa-chevron-down" style="font-size: 0.7rem; margin-left: 4px;"></i></a>
                        <ul class="dropdown-menu">
                            <li><a href="galeri.php">Galeri Kegiatan</a></li>
                            <li><a href="fasilitas.php">Fasilitas</a></li>
                        </ul>
                    </li>
                    <li class="dropdown">
                        <a href="#">Struktur <i class="fas fa-chevron-down" style="font-size: 0.7rem; margin-left: 4px;"></i></a>
                        <ul class="dropdown-menu">
                            <li><a href="kesiswaan.php">Kesiswaan</a></li>
                            <li><a href="struktur.php">Kepsek & TU</a></li>
                            <li><a href="osis.php">OSIS</a></li>
                            <li><a href="mpk.php">MPK</a></li>
                            <li><a href="guru.php">Guru & Staff</a></li>
                        </ul>
                    </li>
                    <li><a href="kontak.php">Kontak</a></li>
                </ul>
            </nav>
        </header>

        <a href="../admin/login.php" class="admin-fixed" title="Admin Panel">
            <i class="bx bx-cog"></i>
        </a>

        <script src="../js/main.js?v=<?= time(); ?>"></script>