<?php
session_start();
include '../database/conn.php';

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Note: In production, consider using prepared statements for security
    $query = mysqli_query($conn, "SELECT * FROM admin WHERE nama = '$username' AND password = '$password'");
    $cek = mysqli_num_rows($query);

    if ($cek > 0) {
        $data = mysqli_fetch_assoc($query);
        $_SESSION['id'] = $data['id'];

        // Use 'nama' as fallback if 'username' field is not updated yet
        // although we will add it shortly in header.php
        $_SESSION['username'] = isset($data['username']) ? $data['username'] : $data['nama'];
        $_SESSION['foto'] = isset($data['foto']) ? $data['foto'] : '';
        $_SESSION['status'] = "login";
        header("Location: index.php");
        exit;
    }
    else {
        $error_msg = "Username atau password salah!";
    }
}

if (isset($_SESSION['status']) && $_SESSION['status'] == "login") {
    header("Location: index.php");
    exit;
}
else if (isset($_SESSION["status"]) && $_SESSION["status"] == "logout") {
    session_destroy();
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - SMP PGRI 3 BOGOR</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" href="../img/p3hd.jpg">
    <style>
        :root {
            --primary: #0f172a;
            --primary-accent: #3b82f6;
            --surface: #ffffff;
            --background: #f1f5f9;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --radius-xl: 32px;
            --radius-lg: 20px;
            --radius-md: 12px;
            --shadow-xl: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
            --font-main: 'Plus Jakarta Sans', sans-serif;
            --font-heading: 'Outfit', sans-serif;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: var(--font-main);
        }

        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #3b82f6 100%);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            overflow-y: auto;
            overflow-x: hidden;
            position: relative;
        }

        .bg-bubbles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
            pointer-events: none;
        }

        .bg-bubbles li {
            position: absolute;
            list-style: none;
            display: block;
            width: 40px;
            height: 40px;
            background-color: rgba(255, 255, 255, 0.1);
            bottom: -160px;
            animation: bubbleFloat 25s infinite;
            transition-timing-function: linear;
            border-radius: 50%;
        }

        .bg-bubbles li:nth-child(1) {
            left: 10%;
        }

        .bg-bubbles li:nth-child(2) {
            left: 20%;
            width: 80px;
            height: 80px;
            animation-delay: 2s;
            animation-duration: 17s;
        }

        .bg-bubbles li:nth-child(3) {
            left: 25%;
            animation-delay: 4s;
        }

        .bg-bubbles li:nth-child(4) {
            left: 40%;
            width: 60px;
            height: 60px;
            animation-duration: 22s;
            background-color: rgba(255, 255, 255, 0.15);
        }

        .bg-bubbles li:nth-child(5) {
            left: 70%;
        }

        .bg-bubbles li:nth-child(6) {
            left: 80%;
            width: 120px;
            height: 120px;
            animation-delay: 3s;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .bg-bubbles li:nth-child(7) {
            left: 32%;
            width: 160px;
            height: 160px;
            animation-delay: 7s;
        }

        .bg-bubbles li:nth-child(8) {
            left: 55%;
            width: 20px;
            height: 20px;
            animation-delay: 15s;
            animation-duration: 40s;
        }

        .bg-bubbles li:nth-child(9) {
            left: 25%;
            width: 10px;
            height: 10px;
            animation-delay: 2s;
            animation-duration: 40s;
            background-color: rgba(255, 255, 255, 0.2);
        }

        .bg-bubbles li:nth-child(10) {
            left: 90%;
            width: 160px;
            height: 160px;
            animation-delay: 11s;
        }

        @keyframes bubbleFloat {
            0% {
                transform: translateY(0);
            }

            100% {
                transform: translateY(-1200px) rotate(600deg);
                opacity: 0;
            }
        }

        .login-page-container {
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            perspective: 1000px;
        }

        .login-card-box {
            display: flex;
            width: 100%;
            max-width: 1100px;
            min-height: 650px;
            background: var(--surface);
            border-radius: var(--radius-xl);
            overflow: hidden;
            box-shadow: var(--shadow-xl);
            animation: cardEnter 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
            border: 1px solid rgba(255, 255, 255, 0.8);
        }

        @keyframes cardEnter {
            from {
                opacity: 0;
                transform: scale(0.9) translateY(30px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        /* Left Side: Form Section */
        .login-side-form {
            width: 45%;
            background: var(--surface);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 4rem 3.5rem;
            position: relative;
            z-index: 10;
        }

        .login-side-form-header {
            margin-bottom: 2.5rem;
        }

        .login-side-form-header h1 {
            font-family: var(--font-heading);
            font-size: 2.25rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .login-side-form-header p {
            color: var(--text-muted);
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .login-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .input-group {
            position: relative;
            margin-bottom: 0.5rem;
        }

        .input-group label {
            position: absolute;
            top: 50%;
            left: 2.75rem;
            transform: translateY(-50%);
            font-size: 0.95rem;
            font-weight: 500;
            color: var(--text-muted);
            transition: all 0.2s ease;
            pointer-events: none;
            z-index: 5;
            background: var(--surface);
            padding: 0 6px;
            border-radius: 4px;
        }

        .input-control:focus+label,
        .input-control:not(:placeholder-shown)+label {
            top: 0;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--primary-accent);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-control {
            width: 100%;
            padding: 0.875rem 1rem;
            padding-left: 2.75rem;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 0.95rem;
            transition: all 0.2s ease;
            color: var(--text-main);
            background: #fdfdfd;
        }

        .input-control:focus {
            outline: none;
            border-color: var(--primary-accent);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }

        .input-icon {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            left: 1.1rem;
            color: var(--text-muted);
            font-size: 1.1rem;
            transition: color 0.2s ease;
            z-index: 6;
        }

        .input-group:focus-within .input-icon {
            color: var(--primary-accent);
        }

        .password-toggle {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            right: 1.1rem;
            color: var(--text-muted);
            cursor: pointer;
            padding: 2px;
            transition: color 0.2s ease;
            z-index: 6;
        }

        .password-toggle:hover {
            color: var(--primary-accent);
        }

        .error-container {
            background: #fef2f2;
            border: 1px solid #fee2e2;
            padding: 0.875rem;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            gap: 10px;
            color: #b91c1c;
            font-size: 0.875rem;
            animation: shake 0.5s ease;
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            25% {
                transform: translateX(-5px);
            }

            75% {
                transform: translateX(5px);
            }
        }

        .btn-submit {
            margin-top: 1rem;
            padding: 1rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .btn-submit:hover {
            background: #1e293b;
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.15);
        }

        .back-link {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 2rem;
            text-decoration: none;
            color: var(--text-muted);
            font-size: 0.875rem;
            font-weight: 500;
            transition: color 0.2s ease;
            gap: 8px;
        }

        .back-link:hover {
            color: var(--primary-accent);
        }

        /* Right Side: Visual Section */
        .login-side-visual {
            width: 55%;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            padding: 4rem;
            overflow: hidden;
        }

        .visual-bg-shape {
            position: absolute;
            background: linear-gradient(135deg, var(--primary-accent), #60a5fa);
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.15;
            pointer-events: none;
        }

        .shape-1 {
            width: 400px;
            height: 400px;
            top: -100px;
            right: -100px;
        }

        .shape-2 {
            width: 300px;
            height: 300px;
            bottom: -50px;
            left: -50px;
        }

        .visual-content {
            position: relative;
            z-index: 5;
            text-align: left;
            max-width: 450px;
        }

        .visual-logo {
            width: 80px;
            height: 80px;
            margin-bottom: 2rem;
            background: white;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            animation: float 6s infinite ease-in-out;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-15px);
            }
        }

        .visual-logo img {
            width: 100%;
            height: auto;
        }

        .visual-content h2 {
            font-family: var(--font-heading);
            font-size: 3rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            background: linear-gradient(to bottom right, #fff, #94a3b8);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .visual-content p {
            font-size: 1.1rem;
            color: #94a3b8;
            line-height: 1.6;
            font-weight: 400;
        }

        .tag-admin {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.2);
            color: #60a5fa;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 1.5rem;
        }

        /* Card specific bubbles */
        .card-bubbles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            overflow: hidden;
            pointer-events: none;
        }

        .card-bubbles li {
            position: absolute;
            list-style: none;
            display: block;
            width: 20px;
            height: 20px;
            background-color: rgba(255, 255, 255, 0.05);
            bottom: -50px;
            animation: cardBubbleFloat 15s infinite;
            transition-timing-function: linear;
            border-radius: 50%;
        }

        .card-bubbles li:nth-child(1) {
            left: 10%;
            width: 30px;
            height: 30px;
            animation-duration: 12s;
        }

        .card-bubbles li:nth-child(2) {
            left: 30%;
            width: 50px;
            height: 50px;
            animation-delay: 2s;
            animation-duration: 18s;
        }

        .card-bubbles li:nth-child(3) {
            left: 70%;
            width: 40px;
            height: 40px;
            animation-delay: 4s;
        }

        .card-bubbles li:nth-child(4) {
            left: 85%;
            width: 25px;
            height: 25px;
            animation-delay: 1s;
            animation-duration: 10s;
        }

        .card-bubbles li:nth-child(5) {
            left: 50%;
            width: 35px;
            height: 35px;
            animation-delay: 6s;
        }

        @keyframes cardBubbleFloat {
            0% {
                transform: translateY(0) scale(1);
                opacity: 0;
            }

            20% {
                opacity: 0.4;
            }

            100% {
                transform: translateY(-500px) rotate(360deg) scale(1.5);
                opacity: 0;
            }
        }

        /* Mobile Responsive */
        @media (max-width: 1024px) {
            .login-card-box {
                max-width: 900px;
                min-height: 600px;
            }

            .login-side-form {
                padding: 3rem;
            }

            .visual-content h2 {
                font-size: 2.5rem;
            }
        }

        @media (max-width: 768px) {
            body {
                padding: 1rem;
                align-items: flex-start;
            }

            .login-card-box {
                flex-direction: column;
                height: auto;
                min-height: unset;
                border-radius: var(--radius-lg);
            }

            .login-side-form {
                width: 100%;
                padding: 3rem 2rem;
                order: 2;
            }

            .login-side-visual {
                width: 100%;
                height: 320px;
                padding: 2rem;
                order: 1;
            }

            .visual-content {
                text-align: center;
                display: flex;
                flex-direction: column;
                align-items: center;
            }

            .visual-content h2 {
                font-size: 2rem;
            }

            .visual-content p {
                font-size: 1rem;
            }

            .visual-logo {
                width: 60px;
                height: 60px;
                margin-bottom: 1.5rem;
            }

            .login-side-form-header h1 {
                font-size: 1.75rem;
            }
        }
    </style>
</head>

<body>

    <ul class="bg-bubbles">
        <li></li>
        <li></li>
        <li></li>
        <li></li>
        <li></li>
        <li></li>
        <li></li>
        <li></li>
        <li></li>
        <li></li>
    </ul>

    <div class="login-page-container">
        <div class="login-card-box">
            <!-- Left: Form Section -->
            <main class="login-side-form">
                <div class="login-side-form-header">
                    <div class="tag-admin">Secure Gateway</div>
                    <h1>Welcome Back</h1>
                    <p>Silakan masukkan akun administrator Anda untuk mengakses dashboard sekolah.</p>
                </div>

                <form method="POST" action="login.php" class="login-form">
                    <?php if (isset($error_msg)): ?>
                        <div class="error-container">
                            <i class="fas fa-circle-exclamation"></i>
                            <span><?= $error_msg; ?></span>
                        </div>
                    <?php
endif; ?>

                    <div class="input-group">
                        <i class="fas fa-user-circle input-icon"></i>
                        <input type="text" name="username" id="username" class="input-control" placeholder=" " required autocomplete="off">
                        <label for="username">Username</label>
                    </div>

                    <div class="input-group">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="password" id="password" class="input-control" placeholder=" " required>
                        <label for="password">Password</label>
                        <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                    </div>

                    <button type="submit" name="login" class="btn-submit">
                        Sign In to Portal
                    </button>
                </form>

                <a href="../index.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Kembali ke Halaman Utama
                </a>
            </main>

            <!-- Right: Visual/Welcome Section -->
            <section class="login-side-visual">
                <div class="visual-bg-shape shape-1"></div>
                <div class="visual-bg-shape shape-2"></div>

                <ul class="card-bubbles">
                    <li></li>
                    <li></li>
                    <li></li>
                    <li></li>
                    <li></li>
                </ul>

                <div class="visual-content">
                    <div class="visual-logo">
                        <img src="../img/p3hd.jpg" alt="SMP PGRI 3 BOGOR">
                    </div>
                    <h2>Control Your Digital web.</h2>
                    <p>Pusat kendali administrasi SMP PGRI 3 BOGOR. Kelola agenda, berita, dan data guru dengan satu platform cerdas.</p>
                </div>
            </section>
        </div>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.querySelector('#togglePassword');
            const password = document.querySelector('#password');

            if (togglePassword) {
                togglePassword.addEventListener('click', function() {
                    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                    password.setAttribute('type', type);

                    // Toggle icon
                    this.classList.toggle('fa-eye');
                    this.classList.toggle('fa-eye-slash');
                });
            }

            // Simple entrance animation
            document.querySelector('.login-side-form').style.opacity = '0';
            document.querySelector('.login-side-form').style.transform = 'translateX(-20px)';

            setTimeout(() => {
                document.querySelector('.login-side-form').style.transition = 'all 0.8s ease-out';
                document.querySelector('.login-side-form').style.opacity = '1';
                document.querySelector('.login-side-form').style.transform = 'translateX(0)';
            }, 100);
        });
    </script>
</body>

</html>