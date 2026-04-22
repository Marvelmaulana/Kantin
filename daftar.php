<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | Kantin Kita</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
    <style>
        :root {
            --primary-red: #C53011; 
            --bg-light: #FFF8F6;    
            --input-bg: #FEE8E1;    
            --text-black: #1A1A1A;
            --text-gray: #666666;
            --link-blue: #2A7E93;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }

        body {
            background-color: var(--bg-light);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        /* CARD CONTAINER */
        .register-card {
            background: white;
            width: 100%;
            max-width: 420px;
            padding: 40px 35px;
            border-radius: 40px;
            box-shadow: 0 10px 40px rgba(197, 48, 17, 0.05);
            position: relative;
        }

        /* HEADER DALAM CARD (Back + Brand) */
        .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 25px;
        }

        .back-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            background: white;
            border-radius: 50%;
            color: var(--primary-red);
            text-decoration: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: 0.2s;
        }

        .back-link:hover { transform: scale(1.1); }
        .back-link span { font-size: 20px; font-weight: bold; }

        .brand-name {
            color: var(--primary-red);
            font-weight: 800;
            font-size: 18px;
        }

        /* TYPOGRAPHY */
        h1 { font-size: 32px; color: var(--text-black); font-weight: 800; margin-bottom: 5px; }
        .subtitle { color: var(--text-gray); font-size: 14px; margin-bottom: 30px; }

        /* FORM STYLE */
        .input-group { margin-bottom: 18px; text-align: left; }
        .input-group label {
            display: block; font-size: 11px; font-weight: 800; 
            margin-bottom: 8px; color: var(--text-black); text-transform: uppercase;
        }

        .input-group input {
            width: 100%; padding: 16px 20px; border-radius: 20px;
            border: none; background: var(--input-bg); outline: none;
            font-size: 14px; color: var(--text-black);
        }

        /* TERMS */
        .terms { 
            display: flex; gap: 10px; font-size: 11px; color: var(--text-gray); 
            margin: 25px 0; align-items: flex-start; line-height: 1.5;
        }
        .terms a { color: var(--link-blue); text-decoration: none; font-weight: bold; }
        .terms input { margin-top: 2px; accent-color: var(--primary-red); }
        
        /* BUTTON REGISTER */
        .btn-register {
            width: 100%; padding: 18px; border: none; border-radius: 50px;
            background: var(--primary-red); color: white; font-size: 18px;
            font-weight: 700; cursor: pointer; 
            box-shadow: 0 8px 20px rgba(197, 48, 17, 0.3);
            margin-bottom: 30px;
        }

        /* FOOTER LOGIN */
        .footer-link { font-size: 14px; text-align: center; color: var(--text-gray); }
        .footer-link a { color: var(--primary-red); text-decoration: none; font-weight: 800; }
    </style>
</head>
<body>

    <div class="register-card">
        <div class="card-header">
            <a href="login.php" class="back-link">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            <div class="brand-name">Kantin Kita</div>
        </div>

        <h1>Create Account</h1>
        <p class="subtitle">Join our curated culinary community today.</p>

        <form action="proses_daftar.php" method="POST">
            <div class="input-group">
                <label>Full Name</label>
                <input type="text" name="username" placeholder="Enter your full name" required>
            </div>
            
            <div class="input-group">
                <label>Email or Phone</label>
                <input type="text" name="email" placeholder="name@example.com" required>
            </div>

            <div class="input-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>

            <div class="terms">
                <input type="checkbox" required>
                <span>By registering, you agree to our <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>.</span>
            </div>

            <button type="submit" name="daftar_btn" class="btn-register">Register</button>
        </form>

        <div class="footer-link">
            Already have an account? <a href="login.php">Login</a>
        </div>
    </div>

</body>
</html>