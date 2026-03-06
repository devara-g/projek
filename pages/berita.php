<?php include 'header.php'; ?>

<section class="news-hero">
    <div class="floating-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>
    <h1>Berita Terbaru</h1>
    <p>Informasi terkini dari SMP PGRI 3 BOGOR</p>
    <?php include 'wave.php'; ?>
</section>

<style>
    /* Empty State Styling */
    .empty-state {
        grid-column: 1/-1;
        text-align: center;
        padding: 60px 20px;
        background: linear-gradient(145deg, #ffffff, #f8fafc);
        border-radius: 24px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);
        margin: 20px 0;
        animation: fadeInUp 0.6s ease;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .empty-state-icon {
        width: 120px;
        height: 120px;
        background: linear-gradient(135deg, #e6f0fa, #d1e3f0);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 25px;
        position: relative;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% {
            box-shadow: 0 0 0 0 rgba(9, 146, 194, 0.2);
        }

        70% {
            box-shadow: 0 0 0 15px rgba(9, 146, 194, 0);
        }

        100% {
            box-shadow: 0 0 0 0 rgba(9, 146, 194, 0);
        }
    }

    .empty-state-icon i {
        font-size: 50px;
        color: var(--primary);
    }

    .empty-state h2 {
        font-size: 2rem;
        color: var(--primary);
        margin-bottom: 15px;
        font-weight: 700;
    }

    .empty-state p {
        color: #64748b;
        font-size: 1.1rem;
        max-width: 500px;
        margin: 0 auto 25px;
        line-height: 1.6;
    }

    .empty-state-btn {
        background: linear-gradient(135deg, #0b2d72 0%, #0992c2 100%);
        color: white;
        border: none;
        padding: 14px 30px;
        border-radius: 50px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        transition: 0.3s;
        box-shadow: 0 10px 20px rgba(11, 45, 114, 0.2);
        text-decoration: none;
    }

    .empty-state-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 30px rgba(11, 45, 114, 0.3);
        color: white;
    }

    /* News Grid tetap sama */
    .news-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 30px;
        margin-top: 40px;
    }

    @media (max-width: 768px) {
        .news-grid {
            grid-template-columns: 1fr;
        }

        .empty-state {
            padding: 40px 20px;
        }

        .empty-state h2 {
            font-size: 1.5rem;
        }

        .empty-state-icon {
            width: 100px;
            height: 100px;
        }

        .empty-state-icon i {
            font-size: 40px;
        }
    }
</style>

<section class="news-content">
    <div class="news-grid">
        <?php
        include '../database/conn.php';

        $sql = "SELECT * FROM berita ORDER BY id DESC LIMIT 6";
        $result = mysqli_query($conn, $sql);

        // Cek apakah ada berita
        if (mysqli_num_rows($result) > 0) {
            // Looping berita
            while ($row = mysqli_fetch_assoc($result)) {
                // Cek apakah foto ada atau tidak
                if (!empty($row['foto']) && file_exists('../' . $row['foto'])) {
                    $foto_path = '../' . $row['foto'];
                    $alt_text = $row['judul'];
                } else {
                    $foto_path = 'assets/img/no-image.jpg';
                    $alt_text = 'Tidak ada gambar';
                }

                echo '
                <div class="news-card">
                    <img src="' . $foto_path . '" alt="' . $alt_text . '" class="news-image">
                    <div class="news-info">
                        <span class="news-date"><i class="fas fa-calendar-alt"></i> ' . date('d M Y', strtotime($row['tanggal'])) . ' • <i class="fas fa-tag"></i> ' . ucfirst($row['kategori']) . '</span>
                        <h3 class="news-title">' . $row['judul'] . '</h3>
                        <p class="news-excerpt">' . substr($row['deskripsi'], 0, 150) . '...</p>
                        <a href="detail-berita.php?id=' . $row['id'] . '" class="read-more">Baca Selengkapnya <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
                ';
            }
        } else {
            // Tampilkan pesan jika tidak ada berita
            echo '
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-newspaper"></i>
                </div>
                <h2>Belum Ada Berita</h2>
                <p>Saat ini belum ada berita yang dipublikasikan. Silakan kembali lagi nanti untuk mendapatkan informasi terbaru dari SMP PGRI 3 BOGOR.</p>
                <a href="../index.php" class="empty-state-btn">
                    <i class="fas fa-home"></i> Kembali ke Beranda
                </a>
            </div>
            ';
        }
        ?>
    </div>
</section>

<?php include 'footer.php'; ?>