<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

include("db.php");

$error = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $brand = $_POST['brand']; // New Field
    $start_price = $_POST['start_price'];
    $end_time = $_POST['end_time'];

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image_name = time() . '_' . basename($_FILES['image']['name']);
        $image_tmp = $_FILES['image']['tmp_name'];
        $upload_dir = __DIR__ . "/../uploads/";
        $image_path = $upload_dir . $image_name;

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        if (move_uploaded_file($image_tmp, $image_path)) {
            $query = "INSERT INTO auctions (title, description, brand, start_price, end_time, image, status) VALUES (?, ?, ?, ?, ?, ?, 'open')";
            $stmt = $conn->prepare($query);
            // The new 's' in the bind_param is for the brand string
            $stmt->bind_param("sssdis", $title, $description, $brand, $start_price, $end_time, $image_name);

            if ($stmt->execute()) {
                $feedback_message = "New auction created successfully!";
                header("Location: admin.php?feedback=" . urlencode($feedback_message));
                exit();
            } else {
                $error = "Database error: Could not create the auction.";
            }
        } else {
            $error = "Image upload failed. Check folder permissions.";
        }
    } else {
        $error = "An image is required for the auction.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Timepiece | TimeLuxe Auctions</title>
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
            background-image: radial-gradient(circle, rgba(18, 18, 18, 0.8) 0%, rgba(18, 18, 18, 1) 75%), url('../assets/web/bg.png');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 40px;
            background-color: rgba(10, 10, 10, 0.7);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-dark);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-logo {
            height: 40px;
        }

        .header-title {
            font-family: var(--font-serif);
            font-size: 1.8rem;
            color: var(--text-color);
            margin: 0;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .btn-view-auctions {
            font-size: .8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-decoration: none;
            color: var(--primary-gold);
            padding: 8px 16px;
            border: 1px solid var(--primary-gold);
            border-radius: 20px;
            transition: all .3s ease;
        }

        .btn-view-auctions:hover {
            background-color: var(--primary-gold);
            color: var(--bg-color);
            box-shadow: 0 0 10px rgba(192, 160, 96, 0.5);
        }

        .logout-btn {
            font-size: .8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-decoration: none;
            color: var(--text-light);
            padding: 8px 16px;
            border: 1px solid #333;
            border-radius: 20px;
            transition: all .3s ease;
        }

        .logout-btn:hover {
            background-color: var(--error-red);
            border-color: var(--error-red);
            color: #fff;
        }

        .admin-container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 40px;
            animation: fadeIn .8s ease-out;
        }

        .ledger-panel {
            background: rgba(10, 10, 10, 0.6);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-dark);
            border-radius: 8px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .section-title {
            font-family: var(--font-serif);
            font-size: 2.8rem;
            margin: 0;
            color: var(--primary-gold);
        }

        .form-label {
            display: block;
            font-weight: 600;
            font-size: .9rem;
            color: var(--text-light);
            margin-bottom: 8px;
        }

        .form-input,
        .form-textarea {
            width: 100%;
            padding: 12px 15px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid #333;
            border-radius: 4px;
            color: var(--text-color);
            font-family: var(--font-sans);
            font-size: 1rem;
            transition: border-color .3s ease, box-shadow .3s ease;
            box-sizing: border-box;
            margin-bottom: 20px;
        }

        .form-textarea {
            resize: vertical;
            min-height: 120px;
        }

        .form-input:focus,
        .form-textarea:focus {
            outline: 0;
            border-color: var(--primary-gold);
            box-shadow: 0 0 15px -5px var(--primary-gold);
        }

        .form-actions {
            display: flex;
            justify-content: flex-start;
            gap: 15px;
            margin-top: 20px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            font-size: .9rem;
            font-weight: 600;
            text-decoration: none;
            border-radius: 5px;
            transition: all .3s ease, box-shadow .3s ease;
            cursor: pointer;
            border: none;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .btn-primary {
            background-color: var(--primary-gold);
            color: var(--bg-color);
        }

        .btn-primary:hover {
            background-color: #d4b57a;
            box-shadow: 0 0 15px rgba(192, 160, 96, 0.6);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: #222;
            color: var(--text-light);
        }

        .btn-secondary:hover {
            background-color: #333;
            color: #fff;
            transform: translateY(-2px);
        }

        .error-message {
            color: #fff;
            background-color: rgba(183, 28, 28, 0.5);
            border: 1px solid var(--error-red);
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: .9rem;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px)
            }

            to {
                opacity: 1;
                transform: translateY(0)
            }
        }
    </style>
</head>

<body>
    <header class="admin-header">
        <a href="admin.php"><img src="../assets/logo-no-bg-W.png" alt="TimeLuxe Monogram" class="header-logo"></a>
        <h1 class="header-title">Control Panel</h1>
        <div class="header-actions">
            <a href="../auctions.php" class="btn-view-auctions">View Live Site</a>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </header>

    <main class="admin-container">
        <div class="ledger-panel">
            <div class="section-header">
                <h2 class="section-title">Add New Timepiece</h2>
            </div>

            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <label for="title" class="form-label">Auction Title (Model Name)</label>
                <input type="text" id="title" name="title" class="form-input" required>

                <label for="brand" class="form-label">Brand</label>
                <input type="text" id="brand" name="brand" class="form-input" required>

                <label for="description" class="form-label">Description</label>
                <textarea id="description" name="description" class="form-textarea" required></textarea>

                <label for="start_price" class="form-label">Starting Price (₹)</label>
                <input type="number" id="start_price" name="start_price" step="100" class="form-input" required>

                <label for="end_time" class="form-label">End Time</label>
                <input type="datetime-local" id="end_time" name="end_time" class="form-input" required>

                <label for="image" class="form-label">Watch Image</label>
                <input type="file" id="image" name="image" class="form-input" accept="image/*" required>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Create Auction</button>
                    <a href="admin.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </main>
</body>

</html>