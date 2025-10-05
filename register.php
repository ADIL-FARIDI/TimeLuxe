<?php
// Your backend logic remains the same. It's the engine.
include("php/db.php");

$error = null;
$success = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Basic validation to ensure fields are not empty
    if (empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } else {
        // Check if email already exists
        $sql_check = "SELECT id FROM users WHERE email = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $error = "An account with this email already exists.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql_insert = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("sss", $username, $email, $hashed_password);

            if ($stmt_insert->execute()) {
                // Instead of a jarring alert, we'll redirect with a success flag
                header("Location: login.php?registration=success");
                exit();
            } else {
                $error = "Registration failed. Please try again later.";
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membership Ledger | TimeLuxe Auctions</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0e0e0e;
            --card-bg: #1A1A1A;
            --primary-gold: #c0a060;
            --text-color: #EAEAEA;
            --text-light: #999;
            --error-red: #B71C1C;
            --font-serif: 'Cormorant Garamond', serif;
            --font-sans: 'Inter', sans-serif;
            --border-dark: #2a2a2a;
        }

        body {
            margin: 0;
            font-family: var(--font-sans);
            background-color: var(--bg-color);
            color: var(--text-color);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px 0;
            overflow-x: hidden;
            background-image: radial-gradient(circle, rgba(18, 18, 18, 0.8) 0%, rgba(18, 18, 18, 1) 75%), url('https://images.unsplash.com/photo-1610603114859-c7003b743758?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1770&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        .register-vault {
            width: 100%;
            max-width: 420px;
            padding: 40px;
            background: rgba(10, 10, 10, 0.6);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-dark);
            border-radius: 8px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
            text-align: center;
            opacity: 0;
            /* Hidden for GSAP animation */
        }

        .logo {
            width: 80px;
            margin-bottom: 20px;
        }

        h2 {
            font-family: var(--font-serif);
            font-size: 2.8rem;
            color: var(--text-color);
            margin-bottom: 30px;
            font-weight: 700;
        }

        .error-message {
            color: white;
            background-color: rgba(183, 28, 28, 0.3);
            border: 1px solid var(--error-red);
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            display: <?php echo $error ? 'block' : 'none'; ?>;
        }

        .form-group {
            position: relative;
            margin-bottom: 25px;
        }

        .form-input {
            width: 100%;
            padding: 14px 15px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid #333;
            border-radius: 4px;
            color: var(--text-color);
            font-family: var(--font-sans);
            font-size: 1rem;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            box-sizing: border-box;
        }

        .form-label {
            position: absolute;
            top: 15px;
            left: 15px;
            color: var(--text-light);
            pointer-events: none;
            transition: all 0.3s ease;
        }

        .form-input:focus+.form-label,
        .form-input:not(:placeholder-shown)+.form-label {
            top: -10px;
            left: 10px;
            font-size: 0.8rem;
            background-color: var(--card-bg);
            padding: 0 5px;
            color: var(--primary-gold);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-gold);
            box-shadow: 0 0 15px -5px var(--primary-gold);
        }

        .submit-btn {
            width: 100%;
            padding: 15px;
            font-size: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--bg-color);
            background: var(--primary-gold);
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease, box-shadow 0.3s ease;
        }

        .submit-btn:hover {
            background-color: #d4b57a;
            box-shadow: 0 0 15px rgba(192, 160, 96, 0.6);
            transform: translateY(-2px);
        }

        .submit-btn:active {
            transform: scale(0.98) translateY(0);
        }

        .extra-links {
            margin-top: 25px;
            font-size: 0.9rem;
            color: var(--text-light);
        }

        .extra-links a {
            color: var(--primary-gold);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .extra-links a:hover {
            color: #fff;
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="register-vault">
        <img src="./assets/logo-no-bg-W.png" alt="TimeLuxe Monogram" class="logo">
        <h2>Create Your Account</h2>

        <div class="error-message">
            <?php echo $error ? htmlspecialchars($error) : ''; ?>
        </div>

        <form method="POST" action="register.php">
            <div class="form-group">
                <input type="text" id="username" name="username" class="form-input" placeholder=" " required>
                <label for="username" class="form-label">Username</label>
            </div>
            <div class="form-group">
                <input type="email" id="email" name="email" class="form-input" placeholder=" " required>
                <label for="email" class="form-label">Email Address</label>
            </div>
            <div class="form-group">
                <input type="password" id="password" name="password" class="form-input" placeholder=" " required>
                <label for="password" class="form-label">Password</label>
            </div>
            <button type="submit" class="submit-btn">Establish Membership</button>
        </form>

        <div class="extra-links">
            <span>Already a member? <a href="login.php">Access Vault</a></span>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.10.4/gsap.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tl = gsap.timeline();

            tl.to('.register-vault', {
                    opacity: 1,
                    y: 0,
                    duration: 0.8,
                    ease: 'power3.out'
                })
                .from('.logo, h2, .form-group, .submit-btn, .extra-links', {
                    opacity: 0,
                    y: 20,
                    stagger: 0.1,
                    duration: 0.6,
                    ease: 'power2.out'
                }, "-=0.5");
        });
    </script>
</body>

</html>