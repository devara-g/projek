<?php include 'header.php'; ?>

<section class="structure-hero">
    <div class="floating-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>
    <h1>Guru & Staff Pengajar</h1>
    <p>Tenaga Pendidik SMP PGRI 3 BOGOR</p>
    <?php include 'wave.php'; ?>
</section>

<section class="guru-content">
    <?php
    // Koneksi ke database
    include '../database/conn.php';

    if ($conn->connect_error) {
        die("Koneksi gagal: " . $conn->connect_error);
    }

    // Ambil data dari database, urut berdasarkan category dan sort_order
    $query = "SELECT * FROM teachers ORDER BY 
        FIELD(category, '7', '8', '9', 'mapel'),
        sort_order ASC";
    $result = $conn->query($query);

    // Check if there are any teachers
    if ($result->num_rows === 0) {
        echo '
        <div class="empty-state">
            <i class="fas fa-user-tie"></i>
            <h3>Data Guru Belum Tersedia</h3>
            <p>Mohon maaf, data guru dan staff pengajar sedang dalam perbaikan.</p>
        </div>
        ';
    } else {
        // Grouping data berdasarkan category
        $guru = [];
        while ($row = $result->fetch_assoc()) {
            $guru[$row['category']][] = $row;
        }

        // Mapping category ke nama display dengan icon
        $categoryInfo = [
            '7' => ['name' => 'Wali Kelas 7', 'icon' => 'fa-user-graduate', 'color' => '#e74c3c'],
            '8' => ['name' => 'Wali Kelas 8', 'icon' => 'fa-user-graduate', 'color' => '#3498db'],
            '9' => ['name' => 'Wali Kelas 9', 'icon' => 'fa-user-graduate', 'color' => '#2ecc71'],
            'mapel' => ['name' => 'Guru Mata Pelajaran', 'icon' => 'fa-chalkboard-teacher', 'color' => '#9b59b6']
        ];

        // Loop setiap kategori
        foreach ($categoryInfo as $category => $info) {
            if (isset($guru[$category])) {
                echo '
                <div class="guru-section">
                    <div class="section-header">
                        <div class="header-icon" style="background: ' . $info['color'] . '">
                            <i class="fas ' . $info['icon'] . '"></i>
                        </div>
                        <h2>' . $info['name'] . '</h2>
                        <span class="teacher-count">' . count($guru[$category]) . ' Orang</span>
                    </div>
                    <div class="guru-grid">
                ';

                foreach ($guru[$category] as $guruData) {
                    // Handle photo - use default if not exists
                    $photoPath = '../upload/img/' . $guruData['photo_filename'];
                    $defaultPhoto = 'https://ui-avatars.com/api/?name=' . urlencode($guruData['name']) . '&background=random&color=fff&size=200';
                    $photoSrc = file_exists($photoPath) ? $photoPath : $defaultPhoto;

                    echo '
                        <div class="guru-card">
                            <div class="card-image">
                                <img src="' . $photoSrc . '" alt="' . htmlspecialchars($guruData['name']) . '" onerror="this.src=\'' . $defaultPhoto . '\'">
                                <div class="card-overlay">
                                    <a href="mailto:' . (isset($guruData['email']) ? $guruData['email'] : '') . '" class="contact-btn" title="Kirim Email">
                                        <i class="fas fa-envelope"></i>
                                    </a>
                                </div>
                            </div>
                            <div class="card-info">
                                <h3 class="guru-name">' . htmlspecialchars($guruData['name']) . '</h3>
                                <p class="guru-nip">
                                    <i class="fas fa-id-card"></i>
                                    ' . (!empty($guruData['nip']) ? htmlspecialchars($guruData['nip']) : '-') . '
                                </p>
                                ' . (isset($guruData['subject']) && !empty($guruData['subject']) ? '
                                <p class="guru-mapel">
                                    <i class="fas fa-book"></i>
                                    ' . htmlspecialchars($guruData['subject']) . '
                                </p>
                                ' : '') . '
                            </div>
                        </div>
                    ';
                }

                echo '
                    </div>
                </div>
                ';
            }
        }
    }

    $conn->close();
    ?>
</section>

<style>
    /* Main Content */
    .guru-content {
        padding: 50px 20px;
        max-width: 1400px;
        margin: 0 auto;
        background: #f8f9fa;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 80px 20px;
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
    }

    .empty-state i {
        font-size: 4rem;
        color: #bdc3c7;
        margin-bottom: 20px;
    }

    .empty-state h3 {
        color: #2c3e50;
        font-size: 1.5rem;
        margin-bottom: 10px;
    }

    .empty-state p {
        color: #7f8c8d;
    }

    /* Section Styles */
    .guru-section {
        margin-bottom: 60px;
    }

    .section-header {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 30px;
        padding-bottom: 15px;
        border-bottom: 3px solid #3498db;
    }

    .header-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 1.3rem;
    }

    .section-header h2 {
        color: #2c3e50;
        font-size: 1.8rem;
        font-weight: 600;
        flex: 1;
    }

    .teacher-count {
        background: #ecf0f1;
        color: #7f8c8d;
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 500;
    }

    /* Grid Layout */
    .guru-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 25px;
    }

    /* Card Styles */
    .guru-card {
        background: #fff;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .guru-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    }

    .card-image {
        position: relative;
        height: 220px;
        overflow: hidden;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .card-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.4s ease;
    }

    .guru-card:hover .card-image img {
        transform: scale(1.1);
    }

    .card-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.4);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .guru-card:hover .card-overlay {
        opacity: 1;
    }

    .contact-btn {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: #fff;
        color: #3498db;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        text-decoration: none;
        transform: translateY(20px);
        transition: all 0.3s ease;
    }

    .guru-card:hover .contact-btn {
        transform: translateY(0);
    }

    .contact-btn:hover {
        background: #3498db;
        color: #fff;
    }

    .card-info {
        padding: 20px;
        text-align: center;
    }

    .guru-name {
        color: #2c3e50;
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 10px;
        line-height: 1.4;
        min-height: 52px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .guru-nip,
    .guru-mapel {
        color: #7f8c8d;
        font-size: 0.9rem;
        margin: 5px 0;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .guru-nip i,
    .guru-mapel i {
        color: #3498db;
        font-size: 0.85rem;
    }

    .guru-mapel {
        color: #9b59b6;
        font-weight: 500;
    }

    .guru-mapel i {
        color: #9b59b6;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .section-header {
            flex-wrap: wrap;
        }

        .section-header h2 {
            font-size: 1.4rem;
        }

        .guru-grid {
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }

        .card-image {
            height: 180px;
        }
    }

    @media (max-width: 480px) {
        .guru-grid {
            grid-template-columns: 1fr;
            max-width: 300px;
            margin: 0 auto;
        }
    }
</style>

<?php include 'footer.php'; ?>