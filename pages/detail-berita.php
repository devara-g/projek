<?php
$id = isset($_GET['id']) ? $_GET['id'] : 1;
include '../database/conn.php';
$sql = "SELECT * FROM berita WHERE id = $id";
$result = mysqli_query($conn, $sql);
$berita = mysqli_fetch_assoc($result);

if (!$berita["id"]) {
    header("Location: berita.php");
    exit();
}


?>
<?php include 'header.php'; ?>

<section class="news-hero" style="padding-top: 6rem;">
    <div class="floating-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>
    <h1>Detail Berita</h1>
    <p>Informasi lengkap seputar sekolah</p>
    <?php include 'wave.php'; ?>
</section>

<section class="news-detail">

    <a href="berita.php" class="back-btn"><i class="fas fa-arrow-left"></i> Kembali ke Berita</a>

    <div class="news-detail-header">
        <span class="news-date" style="color: var(--accent);"><?php echo ucfirst($berita['kategori']); ?></span>
        <h1><?php echo $berita['judul']; ?></h1>
        <div class="news-detail-meta">
            <span><i class="fas fa-calendar-alt"></i> <?php echo date('d M Y', strtotime($berita['tanggal'])); ?></span>
            <span><i class="fas fa-user"></i> <?php echo $berita['penulis']; ?></span>
        </div>
    </div>

    <?php
    // Cek apakah foto ada atau tidak
    if (!empty($berita['foto']) && file_exists('../' . $berita['foto'])) {
        $foto_path = '../' . $berita['foto'];
        $alt_text = $berita['judul'];
    } else {
        $foto_path = 'assets/img/no-image.jpg';
        $alt_text = 'Tidak ada gambar';
    }
    ?>

    <img src="<?php echo $foto_path; ?>" alt="<?php echo $alt_text; ?>" class="news-detail-image">

    <div class="news-detail-content">
        <?php echo nl2br($berita['deskripsi']); ?>
    </div>

</section>

<?php include 'footer.php'; ?>