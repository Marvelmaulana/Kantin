<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Kantin Kita</title>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
    
    <style>
        :root {
            --primary-red: #C53011; /* Warna merah sesuai tombol di gambar */
            --bg-light: #FFF8F6;    /* Warna background krem tipis */
            --input-bg: #FEE8E1;    /* Warna background input sesuai gambar */
            --text-black: #1A1A1A;
            --text-gray: #666666;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', 'Segoe UI', sans-serif; }

        body {
            background-color: var(--bg-light);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        /* HEADER LOGO (Kantin Kita) */
        .top-nav {
            position: absolute;
            top: 20px;
            width: 100%;
            max-width: 400px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .top-nav .brand { color: var(--primary-red); font-weight: 800; font-size: 20px; }
        .top-nav .help { color: var(--primary-red); text-decoration: none; font-size: 14px; font-weight: bold; }

        /* CARD */
        .login-card {
            background: white;
            width: 100%;
            max-width: 400px;
            padding: 50px 35px;
            border-radius: 40px; /* Lengkungan besar sesuai gambar */
            box-shadow: 0 10px 40px rgba(197, 48, 17, 0.05);
            text-align: left; /* Teks rata kiri sesuai gambar */
        }

        h1 { font-size: 32px; color: var(--text-black); margin-bottom: 8px; font-weight: 700; }
        .subtitle { color: var(--text-gray); font-size: 15px; margin-bottom: 35px; }

        /* INPUT STYLE */
        .input-group { margin-bottom: 20px; position: relative; }

        .input-group label {
            display: block;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1px;
            margin-bottom: 8px;
            color: var(--text-black);
            text-transform: uppercase;
        }

        .input-group input {
            width: 100%;
            padding: 18px 20px 18px 50px;
            border-radius: 20px; /* Sesuai gambar */
            border: none;
            background: var(--input-bg);
            outline: none;
            font-size: 15px;
            color: var(--text-black);
        }

        .input-group .icon {
            position: absolute;
            left: 18px;
            top: 40px;
            color: var(--text-gray);
            font-size: 22px;
            opacity: 0.6;
        }

        /* FORGOT PASSWORD */
        .forgot-pass {
            float: right;
            color: var(--primary-red);
            text-decoration: none;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            margin-top: -5px;
        }

        /* ROLE SELECTOR (Custom) */
        .role-selector {
            display: flex;
            background: #F0F0F0;
            padding: 4px;
            border-radius: 15px;
            margin: 25px 0;
            gap: 4px;
        }

        .role-btn {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 12px;
            background: none;
            cursor: pointer;
            font-size: 12px;
            font-weight: 700;
            color: var(--text-gray);
            transition: 0.2s;
        }

        .role-btn.active {
            background: white;
            color: var(--primary-red);
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        /* BUTTON LOGIN */
        .btn-login {
            width: 100%;
            padding: 18px;
            border: none;
            border-radius: 50px; /* Tombol lonjong sesuai gambar */
            background: var(--primary-red);
            color: white;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 8px 20px rgba(197, 48, 17, 0.3);
            margin-top: 10px;
        }

        .btn-login:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        /* FOOTER */
        .footer-link { 
            margin-top: 25px; 
            font-size: 14px; 
            text-align: center; 
            color: var(--text-gray); 
        }
        .footer-link a { color: var(--primary-red); text-decoration: none; font-weight: bold; }
        
        .clearfix::after { content: ""; clear: both; display: table; }
    </style>
</head>
<body>

    <div class="top-nav">
        <div class="brand">Kantin Kita</div>
        <a href="#" class="help">Help</a>
    </div>

    <div class="login-card">
        <h1>Welcome Back</h1>
        <p class="subtitle">Please enter your details to sign in.</p>

        <form action="proses.php" method="POST">
            <div class="input-group">
                <label>Email or Username</label>
                <span class="material-symbols-outlined icon">alternate_email</span>
                <input type="text" name="user_input" placeholder="name@example.com" required>
            </div>

            <div class="input-group clearfix">
                <label>Password</label>
                <a href="#" class="forgot-pass">Forgot Password?</a>
                <span class="material-symbols-outlined icon">lock</span>
                <input type="password" name="password" placeholder="••••••••" required>
                <span class="material-symbols-outlined" style="position:absolute; right:18px; top:40px; font-size:20px; opacity:0.5;">visibility</span>
            </div>

            <label style="font-size:11px; font-weight:800; color:var(--text-black);">MASUK SEBAGAI</label>
            <input type="hidden" name="role" id="role_value" value="pembeli">
            <div class="role-selector">
                <button type="button" class="role-btn active" onclick="setRole('pembeli', this)">Pembeli</button>
                <button type="button" class="role-btn" onclick="setRole('penjual', this)">Penjual</button>
                <button type="button" class="role-btn" onclick="setRole('admin', this)">Admin</button>
            </div>

            <button type="submit" name="login_btn" class="btn-login">Login</button>
        </form>

        <div class="footer-link">
            Don't have an account?  <a href="daftar.php">Register</a>
        </div>
    </div>

    <script>
        function setRole(role, element) {
            document.getElementById('role_value').value = role;
            let buttons = document.querySelectorAll('.role-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            element.classList.add('active');
        }
    </script>

</body>
</html>