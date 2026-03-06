<?php
session_start();

include '../database/conn.php';

// Periksa koneksi database
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

if (isset($_POST['submit'])) {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $subjek = trim($_POST['subjek']);
    $pesan = trim($_POST['pesan']);

    // Validasi input dasar
    if (empty($nama) || empty($email) || empty($subjek) || empty($pesan)) {
        $_SESSION['message_type'] = 'error';
        $_SESSION['message'] = 'Semua field harus diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message_type'] = 'error';
        $_SESSION['message'] = 'Format email tidak valid.';
    } else {
        // Gunakan prepared statement untuk mencegah SQL injection
        $stmt = $conn->prepare("INSERT INTO pesan (nama, email, subjek, pesan) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssss", $nama, $email, $subjek, $pesan);
            if ($stmt->execute()) {
                $_SESSION['message_type'] = 'success';
                $_SESSION['message'] = 'Pesan berhasil dikirim.';
            } else {
                $_SESSION['message_type'] = 'error';
                $_SESSION['message'] = 'Pesan gagal dikirim. Error: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['message_type'] = 'error';
            $_SESSION['message'] = 'Gagal mempersiapkan query. Error: ' . $conn->error;
        }
    }
}

include 'header.php'; ?>

<section class="about-hero">
    <div class="floating-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>
    <h1>Kontak Kami</h1>
    <p>Hubungi SMP PGRI 3 BOGOR</p>
    <?php include 'wave.php'; ?>
</section>

<?php if (isset($_SESSION['message'])): ?>
    <div class="message-alert <?= $_SESSION['message_type'] ?>">
        <i class="fas fa-<?= $_SESSION['message_type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <span><?= htmlspecialchars($_SESSION['message']) ?></span>
    </div>
    <?php
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
    ?>
<?php endif; ?>

<section class="contact-section">
    <div class="contact-wrapper">
        <!-- Contact Information -->
        <div class="contact-info-card" data-delay="0s">
            <div class="contact-header">
                <h2>Informasi Kontak</h2>
                <p>Jangan ragu untuk menghubungi kami melalui saluran resmi berikut.</p>
            </div>

            <div class="contact-details">
                <div class="contact-item" data-delay="0.2s">
                    <div class="icon-box">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="item-content">
                        <h3>Alamat</h3>
                        <p>Jalan Raya Ciomas No.308 Ciomas Rahayu, Jl. Raya Ciomas, Pasirmulya, <br>Kec. Ciomas, Kabupaten Bogor, Jawa Barat 16610</p>
                    </div>
                </div>

                <div class="contact-item" data-delay="0.4s">
                    <div class="icon-box">
                        <i class="fas fa-phone-alt"></i>
                    </div>
                    <div class="item-content">
                        <h3>Telepon</h3>
                        <p>(0251) 1234-5678<br>(0251) 1234-5679</p>
                    </div>
                </div>

                <div class="contact-item" data-delay="0.6s">
                    <div class="icon-box">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="item-content">
                        <h3>Email</h3>
                        <p>info@smppgri3bogor.sch.id<br>humas@smppgri3bogor.sch.id</p>
                    </div>
                </div>
            </div>

            <div class="social-links-contact" data-delay="0.8s">
                <h3>Ikuti Kami</h3>
                <div class="social-icons">
                    <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
        </div>

        <!-- Contact Form -->
        <div class="contact-form-card" data-delay="0.3s">
            <div class="form-header">
                <h2>Kirim Pesan</h2>
                <p>Kami akan segera membalas pesan Anda.</p>
            </div>
            <form action="kontak.php" method="POST" id="contactForm">
                <div class="form-grid">
                    <div class="form-group">
                        <input type="text" name="nama" class="form-control" placeholder="Nama Lengkap" id="nama" required>
                    </div>
                    <div class="form-group">
                        <input type="email" name="email" class="form-control" placeholder="Email Anda" id="email" required>
                    </div>
                    <div class="form-group full">
                        <input type="text" name="subjek" class="form-control" placeholder="Subjek Pesan" id="subjek" required>
                    </div>
                    <div class="form-group full">
                        <textarea name="pesan" class="form-control" rows="5" placeholder="Tulis pesan Anda disini..." id="pesan" required></textarea>
                    </div>
                </div>
                <button type="submit" name="submit" id="submit" class="btn-send">
                    Kirim Pesan <i class="fas fa-paper-plane"></i>
                </button>
            </form>
        </div>
    </div>
</section>

<!-- Modal Konfirmasi Kirim Pesan -->
<div class="confirm-modal-overlay" id="confirmModal" onclick="if(event.target===this) closeConfirmModal()">
    <div class="confirm-modal" onclick="event.stopPropagation()">
        <div class="confirm-modal-icon">
            <i class="fas fa-paper-plane"></i>
        </div>
        <h3>Kirim Pesan?</h3>
        <p>Apakah Anda yakin ingin mengirim pesan ini? Kami akan segera memproses dan membalas pesan Anda.</p>
        <div class="confirm-modal-btns">
            <button type="button" class="confirm-btn-cancel" onclick="closeConfirmModal()">Batal</button>
            <button type="button" class="confirm-btn-ok" onclick="submitContactForm()">
                <i class="fas fa-check"></i> Ya, Kirim
            </button>
        </div>
    </div>
</div>

<style>
    .confirm-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(11, 45, 114, 0.6);
        backdrop-filter: blur(8px);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        padding: 20px;
    }

    .confirm-modal-overlay.show {
        display: flex;
    }

    .confirm-modal {
        background: white;
        border-radius: 20px;
        padding: 36px;
        max-width: 420px;
        width: 100%;
        text-align: center;
        box-shadow: 0 25px 70px rgba(0, 0, 0, 0.2);
        animation: confirmModalIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    @keyframes confirmModalIn {
        from {
            opacity: 0;
            transform: scale(0.9) translateY(20px);
        }

        to {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }

    .confirm-modal-icon {
        width: 70px;
        height: 70px;
        background: linear-gradient(135deg, #0b2d72 0%, #0992c2 100%);
        color: white;
        font-size: 1.8rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 24px;
        box-shadow: 0 10px 30px rgba(11, 45, 114, 0.3);
    }

    .confirm-modal h3 {
        font-size: 1.35rem;
        color: var(--primary);
        margin: 0 0 12px;
        font-weight: 700;
    }

    .confirm-modal p {
        color: #64748b;
        font-size: 0.95rem;
        margin: 0 0 28px;
        line-height: 1.6;
    }

    .confirm-modal-btns {
        display: flex;
        gap: 12px;
        justify-content: center;
        flex-wrap: wrap;
    }

    .confirm-btn-cancel {
        padding: 12px 28px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.95rem;
        cursor: pointer;
        border: 2px solid #e2e8f0;
        background: white;
        color: #64748b;
        transition: all 0.3s ease;
    }

    .confirm-btn-cancel:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
        color: #475569;
    }

    .confirm-btn-ok {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 28px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.95rem;
        cursor: pointer;
        border: none;
        background: var(--gradient-primary);
        color: white;
        box-shadow: 0 8px 25px rgba(11, 45, 114, 0.35);
        transition: all 0.3s ease;
    }

    .confirm-btn-ok:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 35px rgba(11, 45, 114, 0.45);
    }
</style>
<!-- Recent Feedback Section -->
<section class="feedback-section" data-aos="fade-up">
    <div class="container">
        <div class="section-title-wrapper" style="text-align: center; margin-bottom: 40px;">
            <span class="section-badge" style="background: rgba(11, 45, 114, 0.1); color: var(--primary); padding: 8px 20px; border-radius: 50px; font-weight: 700; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 2px;">Suara Pengunjung</span>
            <h2 style="font-size: 2.5rem; color: var(--primary); font-weight: 800; margin-top: 15px;">Pesan Terbaru</h2>
            <p style="color: #64748b; max-width: 600px; margin: 10px auto;">Apa yang mereka katakan tentang SMP PGRI 3 BOGOR.</p>
        </div>

        <div class="feedback-ticker-container">
            <div class="feedback-ticker-wrapper">
                <div class="feedback-ticker-track">
                    <?php
                    $q_feedback = mysqli_query($conn, "SELECT nama, pesan, created_at FROM pesan ORDER BY id DESC LIMIT 15");
                    if (mysqli_num_rows($q_feedback) > 0) {
                        // Loop twice for seamless infinite scroll
                        $feedbacks = [];
                        while ($fb = mysqli_fetch_assoc($q_feedback)) {
                            $feedbacks[] = $fb;
                        }

                        // Output messages twice to create infinite loop effect
                        for ($i = 0; $i < 2; $i++) {
                            foreach ($feedbacks as $fb) {
                    ?>
                                <div class="feedback-item">
                                    <div class="feedback-header">
                                        <div class="user-avatar-mini">
                                            <i class="fas fa-user-circle"></i>
                                        </div>
                                        <div class="user-meta">
                                            <h4><?= htmlspecialchars($fb['nama']) ?></h4>
                                            <span><?= date('d M Y', strtotime($fb['created_at'])) ?></span>
                                        </div>
                                        <div class="quote-icon">
                                            <i class="fas fa-quote-right"></i>
                                        </div>
                                    </div>
                                    <div class="feedback-body">
                                        <p>"<?= htmlspecialchars(substr($fb['pesan'], 0, 150)) ?><?= strlen($fb['pesan']) > 150 ? '...' : '' ?>"</p>
                                    </div>
                                </div>
                    <?php
                            }
                        }
                    } else {
                        echo '<p style="text-align:center; color:#94a3b8; padding: 40px;">Belum ada pesan terbaru.</p>';
                    }
                    ?>
                </div>
            </div>

            <!-- Gradient Overlays for better depth -->
            <div class="ticker-overlay-top"></div>
            <div class="ticker-overlay-bottom"></div>
        </div>
    </div>
</section>

<style>
    .feedback-section {
        padding: 80px 0;
        background: radial-gradient(circle at 50% 50%, #f1f5f9 0%, #ffffff 100%);
        overflow: hidden;
    }

    .feedback-ticker-container {
        position: relative;
        max-width: 800px;
        margin: 0 auto;
        height: 500px;
        /* Fixed height for scroll */
        background: white;
        border-radius: 30px;
        box-shadow: 0 30px 60px rgba(0, 0, 0, 0.05);
        border: 1px solid rgba(226, 232, 240, 0.8);
        overflow: hidden;
    }

    .feedback-ticker-wrapper {
        padding: 40px;
        height: 100%;
        overflow: hidden;
    }

    .feedback-ticker-track {
        display: flex;
        flex-direction: column;
        gap: 25px;
        animation: tickerScroll 40s linear infinite;
    }

    .feedback-ticker-track:hover {
        animation-play-state: paused;
    }

    @keyframes tickerScroll {
        0% {
            transform: translateY(0);
        }

        100% {
            transform: translateY(-50%);
        }

        /* Scroll through the first set */
    }

    .feedback-item {
        background: #f8fafc;
        padding: 25px;
        border-radius: 20px;
        border: 1px solid #e2e8f0;
        transition: all 0.3s ease;
    }

    .feedback-item:hover {
        transform: scale(1.02);
        background: white;
        box-shadow: 0 15px 30px rgba(11, 45, 114, 0.05);
        border-color: #3b82f6;
    }

    .feedback-header {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 15px;
        position: relative;
    }

    .user-avatar-mini {
        font-size: 2.2rem;
        color: #3b82f6;
        opacity: 0.8;
    }

    .user-meta h4 {
        font-size: 1rem;
        color: var(--primary);
        font-weight: 700;
        margin: 0;
    }

    .user-meta span {
        font-size: 0.75rem;
        color: #94a3b8;
        font-weight: 500;
    }

    .quote-icon {
        margin-left: auto;
        font-size: 1.2rem;
        color: rgba(59, 130, 246, 0.15);
    }

    .feedback-body p {
        color: #475569;
        font-size: 0.95rem;
        line-height: 1.6;
        font-style: italic;
        margin: 0;
    }

    /* Gradients for smooth fade */
    .ticker-overlay-top,
    .ticker-overlay-bottom {
        position: absolute;
        left: 0;
        right: 0;
        height: 80px;
        pointer-events: none;
        z-index: 5;
    }

    .ticker-overlay-top {
        top: 0;
        background: linear-gradient(to bottom, white 0%, transparent 100%);
    }

    .ticker-overlay-bottom {
        bottom: 0;
        background: linear-gradient(to top, white 0%, transparent 100%);
    }

    @media (max-width: 768px) {
        .feedback-ticker-container {
            height: 400px;
            margin: 0 20px;
        }

        .feedback-ticker-wrapper {
            padding: 20px;
        }
    }
</style>

<section class="map-section">
    <div class="map-wrapper" data-delay="0.5s">
        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2539.0293709443768!2d106.76799617233665!3d-6.6017412933921005!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e69c5a76272bded%3A0x6062ff519c374276!2sSMP%20PGRI%203%20BOGOR!5e1!3m2!1sid!2sid!4v1770558972453!5m2!1sid!2sid" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
    </div>
</section>

<?php include 'footer.php'; ?>