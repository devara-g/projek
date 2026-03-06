<?php include 'header.php'; ?>

<section class="gallery-hero">
    <div class="floating-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>
    <h1>Agenda Sekolah</h1>
    <p>Jadwal kegiatan SMP PGRI 3 BOGOR</p>
    <?php include 'wave.php'; ?>
</section>

<section class="news-content">
    <?php
    include '../database/conn.php';
    $data = mysqli_query($conn, "SELECT * FROM agenda ORDER BY tanggal DESC");

    if (mysqli_num_rows($data) > 0) {
        echo '<div class="news-grid">';
        foreach ($data as $a) {
            $foto_path = (!empty($a['foto']) && file_exists('../' . $a['foto'])) ? '../' . $a['foto'] : 'assets/img/no-image.jpg';
            echo '
            <div class="news-card">
                <img src="' . $foto_path . '" alt="' . $a['judul'] . '" class="news-image">
                <div class="news-info">
                    <h3 class="news-title">' . $a['judul'] . '</h3>
                    <div class="news-meta" style="margin-bottom: 1rem; color: var(--gray);">
                        <span><i class="fas fa-calendar-alt"></i> ' . date('d M Y', strtotime($a['tanggal'])) . '</span><br>
                        <span><i class="fas fa-map-marker-alt"></i> ' . htmlspecialchars($a['lokasi']) . '</span>
                    </div>
                    <p class="news-excerpt">' . nl2br(htmlspecialchars($a['deskripsi'])) . '</p>
                </div>
            </div>
            ';
        }
        echo '</div>';
    } else {
        echo '
        <div class="empty-state" style="text-align: center; padding: 80px 20px; background: #fff; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.08);">
            <i class="fas fa-calendar-times" style="font-size: 4rem; color: #bdc3c7; margin-bottom: 20px;"></i>
            <h3 style="color: #2c3e50; font-size: 1.5rem; margin-bottom: 10px;">Agenda Kosong</h3>
            <p style="color: #7f8c8d;">Belum ada agenda sekolah yang tersedia.</p>
        </div>
        ';
    }
    ?>
</section>

<?php include 'footer.php'; ?>