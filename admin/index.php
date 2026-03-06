<?php
$title = "Dashboard Admin";
include 'layout/header.php';
include '../database/conn.php';

// Pastikan kolom created_at ada di tabel pesan (untuk chart)
$colCheck = mysqli_query($conn, "SHOW COLUMNS FROM pesan LIKE 'created_at'");
if (mysqli_num_rows($colCheck) == 0) {
    mysqli_query($conn, "ALTER TABLE pesan ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
}

// Data chart: jumlah pesan per hari (7 hari terakhir)
$chartLabels = [];
$chartData = [];
$dayAbbrev = ['Mg', 'Sn', 'Sl', 'Rb', 'Km', 'Jm', 'Sb'];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dayNum = (int) date('w', strtotime($date));
    $chartLabels[] = $dayAbbrev[$dayNum];
    $q = mysqli_query($conn, "SELECT COUNT(*) FROM pesan WHERE DATE(COALESCE(created_at, NOW())) = '" . mysqli_real_escape_string($conn, $date) . "'");
    $chartData[] = (int) ($q ? mysqli_fetch_row($q)[0] : 0);
}
$total_berita = (int) mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM berita"))[0];
$total_agenda = (int) mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM agenda"))[0];
$total_galeri = (int) mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM galeri"))[0];
$total_guru = (int) mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM teachers"))[0];
?>

<!-- Welcome Banner -->
<div class="welcome-banner animate-fade-in-up">
    <i class="fas fa-chart-line welcome-bg"></i>
    <h1>Selamat Datang, <?= $_SESSION['username']; ?>!</h1>
    <p>Ini adalah pusat kontrol website SMP PGRI 3 BOGOR. Anda dapat mengelola berita, galeri, data guru, dan informasi lainnya dari sini.</p>
</div>

<!-- Stats Widgets -->
<div class="stats-grid">
    <div class="stat-card animate-fade-in-up" style="animation-delay: 0.1s;">
        <div class="stat-icon bg-blue">
            <i class="fas fa-newspaper"></i>
        </div>
        <div class="stat-info">
            <h3><?= $total_berita ?></h3>
            <p>Berita Dipublish</p>
        </div>
    </div>

    <div class="stat-card animate-fade-in-up" style="animation-delay: 0.2s;">
        <div class="stat-icon bg-green">
            <i class="fas fa-images"></i>
        </div>
        <div class="stat-info">
            <h3><?= $total_galeri ?></h3>
            <p>Foto Galeri</p>
        </div>
    </div>

    <div class="stat-card animate-fade-in-up" style="animation-delay: 0.3s;">
        <div class="stat-icon bg-purple">
            <i class="fas fa-chalkboard-teacher"></i>
        </div>
        <div class="stat-info">
            <h3><?= $total_guru ?></h3>
            <p>Total Guru</p>
        </div>
    </div>

    <div class="stat-card animate-fade-in-up" style="animation-delay: 0.4s;">
        <div class="stat-icon bg-orange">
            <i class="fas fa-calendar-check"></i>
        </div>
        <div class="stat-info">
            <h3><?= $total_agenda ?></h3>
            <p>Agenda Mendatang</p>
        </div>
    </div>
</div>

<!-- Dashboard Content -->
<div class="dashboard-grid">
    <!-- Chart Section -->
    <div class="card-panel animate-fade-in-up" style="animation-delay: 0.5s;">
        <div class="card-header">
            <h3>Statistik Pesan Masuk</h3>
            <a href="pesan.php" class="btn-sm">Lihat Detail</a>
        </div>
        <canvas id="visitorChart" height="120"></canvas>
    </div>

    <!-- Recent Activity -->
    <div class="card-panel animate-fade-in-up" style="animation-delay: 0.6s;">
        <div class="card-header">
            <h3>Aktivitas Terbaru</h3>
        </div>

        <div class="activity-list">
            <?php
            $q_recent = mysqli_query($conn, "SELECT id, nama, subjek, pesan, created_at FROM pesan WHERE COALESCE(dibaca, 0) = 0 ORDER BY id DESC LIMIT 5");
            if (mysqli_num_rows($q_recent) > 0) {
                while ($act = mysqli_fetch_assoc($q_recent)) {
                    $time_str = date('d M Y, H:i', strtotime($act['created_at']));
            ?>
                    <div class="activity-item" onclick="location.href='pesan.php'" style="cursor: pointer;">
                        <div class="activity-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="activity-content">
                            <h4><?= htmlspecialchars($act['nama']) ?></h4>
                            <p><?= htmlspecialchars(substr($act['subjek'], 0, 40)) ?><?= strlen($act['subjek']) > 40 ? '...' : '' ?></p>
                        </div>
                        <div class="activity-time"><?= $time_str ?></div>
                    </div>
                <?php
                }
            } else {
                ?>
                <div style="text-align: center; padding: 40px; color: #94a3b8;">
                    <i class="fas fa-check-circle" style="font-size: 2rem; margin-bottom: 15px; opacity: 0.3;"></i>
                    <p>Semua pesan sudah dibaca</p>
                </div>
            <?php } ?>
        </div>
    </div>
</div>

<!-- Chart: Jumlah Pesan per Hari -->
<script>
    const ctx = document.getElementById('visitorChart').getContext('2d');
    const visitorChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chartLabels) ?>,
            datasets: [{
                label: 'Pesan Masuk',
                data: <?= json_encode($chartData) ?>,
                fill: true,
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                borderColor: '#0b2d72',
                tension: 0.4,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 2
                    },
                    grid: {
                        display: false
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
</script>

<?php include 'layout/footer.php'; ?>