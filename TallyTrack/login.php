<?php
    include 'controllerLogin&User.php';
    
    $result = $conn->query("SELECT user_username FROM `inv_user`");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TallyTrack | Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-green: #15803d;
            --link-blue: #2563eb;
            --dark-navy: #0f172a;
            --slate-500: #64748b;
            --glass-white: rgba(255, 255, 255, 0.95);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            height: 100vh;
            display: flex;
            overflow: hidden;
            background-color: #f8fafc;
        }

        .split-container {
            display: flex;
            width: 100%;
            height: 100%;
        }

        /* --- Left Side: Branding & Visuals --- */
        .brand-side {
            flex: 1;
            background: linear-gradient(135deg, var(--dark-navy) 0%, #1e293b 50%, #14532d 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 10%;
            color: white;
            position: relative;
        }

        .brand-side::after {
            content: '';
            position: absolute;
            top: 0; right: 0; bottom: 0; left: 0;
            background: radial-gradient(circle at 70% 30%, rgba(37, 99, 235, 0.1), transparent 50%);
            pointer-events: none;
        }

        .brand-side h1 {
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            letter-spacing: -2px;
            z-index: 1;
        }

        .brand-side p {
            font-size: 1.1rem;
            color: #94a3b8;
            max-width: 400px;
            line-height: 1.6;
            z-index: 1;
        }

        /* --- Right Side: Centered Login Card --- */
        .login-side {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            background-image: radial-gradient(#e2e8f0 1px, transparent 1px);
            background-size: 30px 30px;
        }

        .login-card {
            background: var(--glass-white);
            width: 100%;
            max-width: 440px;
            padding: 3rem;
            border-radius: 24px;
            box-shadow: 0 20px 40px -10px rgba(15, 23, 42, 0.1);
            text-align: center;
            border: 1px solid white;
        }

        /* Logo Box with TallyTrack.jpg */
        .logo-box {
            width: 64px;
            height: 64px;

            border-radius: 14px;
            margin: 0 auto 1.5rem;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .login-card h2 {
            font-size: 2rem;
            color: var(--dark-navy);
            margin-bottom: 0.5rem;
        }

        .login-card .subtitle {
            color: var(--slate-500);
            margin-bottom: 2rem;
            font-size: 0.95rem;
        }

        /* Input Controls */
        .input-group { text-align: left;
            margin-bottom: 1.2rem;
        }

        .input-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--dark-navy);
            margin-bottom: 8px; }

        .field-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 48px;
            color: var(--dark-navy);
        }

        .field-wrapper {
            display: flex;
            align-items: center;
            background: #fff;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            transition: var(--transition);
        }

        .field-wrapper:focus-within {
            border-color: var(--link-blue);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.08);
        }

        .field-wrapper i.icon {
            padding-left: 16px;
            color: var(--slate-500); }
        
        .field-wrapper input {
            flex: 1;
            padding: 14px 16px;
            border: none;
            outline: none;
            background: transparent;
            font-size: 1rem;
            color: var(--dark-navy);
        }

        /* Password Specific (Blue tint from image_1acc18) */
        .password-field .field-wrapper {
            background-color: #eff6ff;
        }
        .password-field input {
            color: var(--link-blue);
        }

        .toggle-btn {
            background: none;
            border: none;
            padding-right: 16px;
            cursor: pointer;
            color: var(--slate-500);
        }

        .btn-signin {
            width: 100%;
            padding: 16px;
            background: var(--primary-green);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-signin:hover {
            background: #166534;
            transform: translateY(-1px);
            box-shadow: 0 10px 20px -5px rgba(21, 128, 61, 0.3);
        }

        .brand-header {
            margin-bottom: auto;
            font-weight: 700;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .brand-footer {
            margin-top: auto;
            font-size: 0.8rem;
            color: #64748b;
        }

        @media (max-width: 900px) {
            .brand-side { display: none; }
        }

        .flash-message {
            padding: 14px 18px;
            margin-bottom: 20px;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.3s ease-out;
            border: 1px solid transparent;
        }

        /* Danger / Error */
        .flash-message.danger {
            background: #fee2e2;
            color: #991b1b;
            border-color: #fca5a5;
        }

        /* Success */
        .flash-message.success {
            background: #dcfce7;
            color: #166534;
            border-color: #86efac;
        }

        /* Info */
        .flash-message.info {
            background: #dbeafe;
            color: #1e40af;
            border-color: #93c5fd;
        }

        /* Animation */
        @keyframes slideDown {
            from {
                transform: translateY(-10px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body>

    <div class="split-container">

        <section class="brand-side">
            <div class="brand-header">

                <img src="img/TallyTrack 2.0.png" alt="TallyTrack Logo" style="height: 50px; width: 50px; object-fit: contain; border-radius: 8px;"> 
                TallyTrack
            </div>
            
            <div>
                <h1>Inventory Management System for Small Retail Stores</h1>
                <p>A specialized workspace for stock management, real-time tracking, and seamless point-of-sale operations.</p>
            </div>

            <div class="brand-footer">
                © 2026 TallyTrack — Final Project in Advanced Database Systems
            </div>
        </section>

        <!-- Right Side: The Sign-In Form -->
        <section class="login-side">
            <div class="login-card">
                <div class="logo-box">

                    <img src="img/TallyTrack 2.0.png" alt="TallyTrack Logo">
                </div>

                <h2>Welcome back</h2>
                <p class="subtitle">Sign in to TallyTrack to continue</p>

                <?php if (isset($_SESSION['message'])): ?>
                    <div class="flash-message <?php echo $_SESSION['msg_type']; ?>">
                        <?php echo $_SESSION['message']; ?>
                    </div>
                <?php endif; ?>
                <form method="POST" id="loginForm" action="controllerLogin&User.php">
                    <div class="input-group">
                        <label>Username</label>
                        <div class="field-wrapper">
                            <div class="field-icon">
                                <i data-lucide="user" size="20"></i>
                            </div>
                            <input type="text" name="username" placeholder="username" required>
                        </div>
                    </div>

                    <div class="input-group password-field">
                        <label>Password</label>
                        <div class="field-wrapper">
                            <div class="field-icon">
                                <i data-lucide="lock" size="20"></i>
                            </div>
                            <input type="password" name="password" id="passwordInput" placeholder="password" required>
                            <button type="button" class="toggle-btn" onclick="togglePass()">
                                <i data-lucide="eye" id="eyeIcon" size="20"></i>
                            </button>
                        </div>
                    </div>

                    <div class="actions"> 
                        
                    </div>

                    <button type="submit" class="btn-signin" name="login">Sign in</button>
                </form>

                <p style="margin-top: 2rem; font-size: 0.8rem; color: var(--slate-500);">
                    © 2026 TallyTrack · Inventory Management
                </p>
            </div>
        </section>
    </div>

    
    <?php
        unset($_SESSION['message']);
        unset($_SESSION['msg_type']);
    ?>


    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {

            // init icons ONCE after DOM loads
            lucide.createIcons();

            const existingUsernames = [
                <?php
                    $usernames = [];

                    while ($row = $result->fetch_assoc()) {
                        $usernames[] = "'" . addslashes($row['user_username']) . "'";
                    }

                    echo implode(",", $usernames);
                ?>
            ];

            const form = document.getElementById('loginForm');
            form.addEventListener('submit', (e) => { 
                const usernameInput = form.querySelector('input[name="username"]').value.trim();

                if (!existingUsernames.includes(usernameInput)) {
                    e.preventDefault();

                    alert("Username not found!");
                    return;
                }
            });

        });

        // password toggle
        function togglePass() {
            const input = document.getElementById('passwordInput');
            const icon = document.getElementById('eyeIcon');

            if (input.type === 'password') {
                input.type = 'text';
                icon.setAttribute('data-lucide', 'eye-off');
            } else {
                input.type = 'password';
                icon.setAttribute('data-lucide', 'eye');
            }

            lucide.createIcons();
        }
    </script>
</body>
</html>