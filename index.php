 <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Q-RIOUS | Welcome</title>
    <style>
        /* 1. UNIVERSAL STYLES */
        * {
            cursor: url('https://cur.cursors-4u.net/games/gam-4/gam373.cur'), auto !important;
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Courier New', monospace;
            background-color: #f5e6e0;
            background-image: linear-gradient(#e8d6cf 1px, transparent 1px),
                              linear-gradient(90deg, #e8d6cf 1px, transparent 1px);
            background-size: 30px 30px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
            overflow-x: hidden;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        /* 2. HEADER SECTION */
        .header-container {
            text-align: center;
            margin-bottom: 30px;
            animation: float 4s ease-in-out infinite;
            z-index: 10;
            width: 100%;
        }

        .logo-box {
            background: #e3a693;
            color: white;
            padding: 15px 40px;
            font-size: clamp(24px, 8vw, 45px);
            font-weight: bold;
            letter-spacing: clamp(5px, 2vw, 15px);
            box-shadow: 8px 8px 0 #c98f7a;
            text-transform: uppercase;
            display: inline-block;
        }

        .subtitle {
            margin-top: 10px;
            font-size: clamp(10px, 3vw, 16px);
            color: #5d4037;
            font-weight: bold;
            letter-spacing: 2px;
            text-transform: uppercase;
            background: rgba(255, 255, 255, 0.5);
            padding: 5px 15px;
            display: inline-block;
            border-radius: 5px;
        }

        /* 3. RESPONSIVE NOTEBOOK */
        .book-wrapper {
            display: flex;
            width: 100%;
            max-width: 900px;
            min-height: 480px;
            position: relative;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            border: 10px solid #e3a693; 
            border-radius: 20px;
            background-color: #e3a693;
            animation: float 6s ease-in-out infinite;
            transition: all 0.3s ease;
        }

        .page {
            flex: 1;
            background-color: #fefcf9; 
            background-image: linear-gradient(#e8eef0 1.5px, transparent 1.5px);
            background-size: 100% 30px; 
            padding: 40px 20px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            position: relative;
        }

        .left-page {
            border-radius: 10px 0 0 10px;
            border-right: 1px solid rgba(0,0,0,0.05);
            padding-right: 40px;
        }

        .right-page {
            border-radius: 0 10px 10px 0;
            border-left: 1px solid rgba(0,0,0,0.05);
            padding-left: 40px;
        }

        .spine-spring {
            width: 35px;
            background: #fefcf9;
            display: flex;
            flex-direction: column;
            justify-content: space-around;
            align-items: center;
            z-index: 5;
        }

        .spine-spring::before {
            content: "";
            width: 20px;
            height: 90%;
            background-image: repeating-linear-gradient(
                to bottom,
                #b07a66, #b07a66 12px, 
                transparent 12px, transparent 24px 
            );
            border-radius: 10px;
        }

        .label {
            font-size: 12px;
            color: #c98f7a;
            margin-bottom: 20px;
            letter-spacing: 3px;
            text-transform: uppercase;
            font-weight: bold;
            background: #fefcf9;
            padding: 0 5px;
        }

        .btn {
            width: 85%;
            max-width: 250px;
            padding: 18px;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
            text-align: center;
            border: 3px solid #c98f7a;
            letter-spacing: 2px;
            text-transform: uppercase;
            transition: 0.3s ease;
            margin-bottom: 10px;
        }

        .btn-login {
            background: #e3a693;
            color: white;
            box-shadow: 5px 5px 0 #c98f7a;
        }

        .btn-register {
            background: white;
            color: #c98f7a;
            box-shadow: 5px 5px 0 #e8d6cf;
        }

        .btn:active { transform: scale(0.95); }

        @media (max-width: 768px) {
            body { justify-content: flex-start; padding-top: 40px; }
            .book-wrapper { flex-direction: column; height: auto; animation: none; }
            .spine-spring { width: 100%; height: 30px; flex-direction: row; padding: 0 20px; }
            .spine-spring::before {
                width: 90%;
                height: 15px;
                background-image: repeating-linear-gradient(
                    to right,
                    #b07a66, #b07a66 12px, 
                    transparent 12px, transparent 24px 
                );
            }
            .page { width: 100%; border-radius: 0; padding: 50px 20px; }
            .left-page { border-radius: 0; border-right: none; border-bottom: 1px solid rgba(0,0,0,0.1); }
            .right-page { border-radius: 0 0 10px 10px; border-left: none; }
        }

        .decor-circle {
            position: absolute;
            width: 150px;
            height: 150px;
            border: 8px double #e8d6cf;
            border-radius: 50%;
            z-index: -1;
            opacity: 0.3;
        }
    </style>
</head>
<body>

    <div class="decor-circle" style="top: -20px; left: -20px;"></div>
    <div class="decor-circle" style="bottom: -20px; right: -20px; border-color: #e3a693;"></div>

    <div class="header-container">
        <div class="logo-box">Q - R I O U S</div>
        <br>
        <div class="subtitle">Online Quiz Management System</div>
    </div>

    <div class="book-wrapper">
        <div class="page left-page">
            <p class="label">Management Portal</p>
            <p style="font-size: 11px; color: #5d4037; margin-bottom: 15px; text-align: center;">Admin & Instructor Access</p>
            <a href="login.php" class="btn btn-login">Staff Login</a>
        </div>

        <div class="spine-spring"></div>

        <div class="page right-page">
            <p class="label">Knowledge Quest</p>
            <p style="font-size: 11px; color: #5d4037; margin-bottom: 15px; text-align: center;">Participant & Student Area</p>
            <a href="login.php" class="btn btn-login" style="background-color: #c98f7a;">Join Quiz</a>
            <a href="register.php" class="btn btn-register">New Account</a>
        </div>
    </div>

</body>
</html>

