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
    $start_price = $_POST['start_price'];
    $end_time = $_POST['end_time'];

    // --- Robust Image Upload Handling ---
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image_name = $_FILES['image']['name'];
        $image_tmp = $_FILES['image']['tmp_name'];
        // It's safer to store uploads outside the web root, but for this structure, we'll go up one level to the main `uploads` folder.
        $upload_dir = __DIR__ . "/../uploads/";
        $image_path = $upload_dir . basename($image_name);

        // Create uploads directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        if (move_uploaded_file($image_tmp, $image_path)) {
            // Insert auction into database
            $query = "INSERT INTO auctions (title, description, start_price, end_time, image, status) VALUES (?, ?, ?, ?, ?, 'open')";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssdss", $title, $description, $start_price, $end_time, $image_name);

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
        /*
            DESIGNER'S NOTES - Add Auction Form

            This form must match the refined aesthetic of the Control Panel.

            1.  CONSISTENT STYLING: All styles are inherited or recreated from the admin panel. The background, header, and fonts provide a seamless transition.
            2.  FORM AS A MODULE: The form is presented within a single `.ledger-container`, making it feel like a dedicated, important task.
            3.  INPUT PRECISION: Form inputs adopt the style of the Login Vault for a cohesive feel—dark, clean, with a gold focus state that provides clear, elegant feedback.
        */

        :root {
            --bg-color: #121212;
            --card-bg: #1A1A1A;
            --primary-gold: #c0a060;
            --text-color: #EAEAEA;
            --text-light: #999;
            --error-red: #B71C1C;
            --font-serif: 'Cormorant Garamond', serif;
            --font-sans: 'Inter', sans-serif;
        }

        body {
            margin: 0;
            font-family: var(--font-sans);
            background-color: var(--bg-color);
            color: var(--text-color);
            background-image: radial-gradient(circle, rgba(18, 18, 18, 0.7) 0%, rgba(18, 18, 18, 1) 80%), url('../assets/web/bg.png');
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
            border-bottom: 1px solid #2a2a2a;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-logo {
            height: 40px;
        }

        .header-title {
            font-family: var(--font-serif);
            font-size: 1.5rem;
            color: var(--text-color);
        }

        .logout-btn {
            font-family: var(--font-sans);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-decoration: none;
            color: var(--text-light);
            padding: 8px 16px;
            border: 1px solid #333;
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background-color: var(--error-red);
            border-color: var(--error-red);
            color: white;
        }

        .admin-container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 40px;
        }

        .ledger-container {
            background: rgba(10, 10, 10, 0.6);
            backdrop-filter: blur(10px);
            border: 1px solid #2a2a2a;
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
        }

        .form-label {
            display: block;
            font-weight: 600;
            font-size: 0.9rem;
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
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            box-sizing: border-box;
            margin-bottom: 20px;
        }

        .form-textarea {
            resize: vertical;
            min-height: 120px;
        }

        .form-input:focus,
        .form-textarea:focus {
            outline: none;
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
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary {
            background-color: var(--primary-gold);
            color: var(--bg-color);
        }

        .btn-primary:hover {
            background-color: #d4b57a;
        }

        .btn-secondary {
            background-color: #222;
            color: var(--text-light);
        }

        .btn-secondary:hover {
            background-color: #333;
            color: white;
        }

        .error-message {
            color: white;
            background-color: rgba(183, 28, 28, 0.5);
            border: 1px solid var(--error-red);
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
    </style>
</head>

<body>
    <header class="admin-header">
        <img src="../assets/logo-no-bg-W.png" alt="TimeLuxe Monogram" class="header-logo">
        <h1 class="header-title">Control Panel</h1>
        <a href="logout.php" class="logout-btn">Logout</a>
    </header>

    <main class="admin-container">
        <div class="ledger-container">
            <div class="section-header">
                <h2 class="section-title">Add New Timepiece</h2>
            </div>

            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <label for="title" class="form-label">Auction Title</label>
                <input type="text" id="title" name="title" class="form-input" required>

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
                    <a href="admin.php" class="btn btn-secondary">Back to Panel</a>
                </div>
            </form>
        </div>
    </main>
</body>

</html>