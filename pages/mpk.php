<?php
include 'header.php';
include '../database/conn.php';

// Fetch data from mpk table
$result = $conn->query("SELECT * FROM mpk ORDER BY FIELD(category, 'ketua', 'waket', 'sekretaris', 'bendahara', 'anggota'), sort_order ASC");

// Group data by category
$mpkData = [
    'ketua' => [],
    'waket' => [],
    'sekretaris' => [],
    'bendahara' => [],
    'anggota' => []
];

while ($row = $result->fetch_assoc()) {
    $mpkData[$row['category']][] = $row;
}

// Section configuration
$sections = [
    'ketua' => ['title' => 'Ketua & Wakil Ketua MPK', 'positions' => ['ketua', 'waket'], 'icon' => 'fa-crown', 'color' => '#e74c3c'],
    'sekretaris' => ['title' => 'Sekretaris', 'positions' => ['sekretaris'], 'icon' => 'fa-clipboard', 'color' => '#9b59b6'],
    'bendahara' => ['title' => 'Bendahara', 'positions' => ['bendahara'], 'icon' => 'fa-wallet', 'color' => '#2ecc71'],
    'anggota' => ['title' => 'Anggota MPK', 'positions' => ['anggota'], 'icon' => 'fa-vote-yea', 'color' => '#3498db']
];

// Get total count for each section
function getSectionCount($data, $positions)
{
    $count = 0;
    foreach ($positions as $pos) {
        if (isset($data[$pos])) {
            $count += count($data[$pos]);
        }
    }
    return $count;
}
?>

<section class="structure-hero">
    <div class="floating-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>
    <h1>Majelis Perwakilan Kelas (MPK)</h1>
    <p>Kepengurusan MPK SMP PGRI 3 BOGOR Periode 2025/2026</p>
    <?php include 'wave.php'; ?>
</section>

<section class="guru-content">
    <?php
    $hasData = false;
    foreach ($sections as $sectionKey => $section):
        $sectionCount = getSectionCount($mpkData, $section['positions']);
        if ($sectionCount == 0) continue;
        $hasData = true;
    ?>
        <div class="guru-section">
            <div class="section-header">
                <div class="header-icon" style="background: <?php echo $section['color']; ?>">
                    <i class="fas <?php echo $section['icon']; ?>"></i>
                </div>
                <h2><?php echo $section['title']; ?></h2>
                <span class="teacher-count"><?php echo $sectionCount; ?> Orang</span>
            </div>
            <div class="guru-grid">
                <?php foreach ($section['positions'] as $cat): ?>
                    <?php if (isset($mpkData[$cat]) && count($mpkData[$cat]) > 0): ?>
                        <?php foreach ($mpkData[$cat] as $member): ?>
                            <?php
                            $photoPath = !empty($member['photo_filename']) ? '../upload/img/' . $member['photo_filename'] : '';
                            $defaultPhoto = 'https://ui-avatars.com/api/?name=' . urlencode($member['name']) . '&background=random&color=fff&size=200';
                            $photoSrc = !empty($photoPath) && file_exists($photoPath) ? $photoPath : $defaultPhoto;
                            ?>
                            <div class="guru-card">
                                <div class="card-image">
                                    <img src="<?php echo $photoSrc; ?>" alt="<?php echo htmlspecialchars($member['name']); ?>" onerror="this.src='<?php echo $defaultPhoto; ?>'">
                                    <div class="card-overlay">
                                        <a href="mailto:" class="contact-btn" title="Kirim Email">
                                            <i class="fas fa-envelope"></i>
                                        </a>
                                    </div>
                                </div>
                                <div class="card-info">
                                    <h3 class="guru-name"><?php echo htmlspecialchars($member['name']); ?></h3>
                                    <p class="guru-nip">
                                        <i class="fas fa-briefcase"></i>
                                        <?php echo htmlspecialchars($member['position']); ?>
                                    </p>
                                    <p class="guru-mapel">
                                        <i class="fas fa-school"></i>
                                        Kelas <?php echo htmlspecialchars($member['kelas']); ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if (!$hasData): ?>
        <div class="empty-state">
            <i class="fas fa-folder-open"></i>
            <h3>Data Belum Tersedia</h3>
            <p>Data MPK belum tersedia.</p>
        </div>
    <?php endif; ?>
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