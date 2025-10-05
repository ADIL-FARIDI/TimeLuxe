<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

include("db.php");

$error = null;
$auction = null;
$auction_id = null;

if (!isset($_GET['id'])) {
    header("Location: admin.php"); // Redirect if no ID is provided
    exit();
}

$auction_id = $_GET['id'];

// Handle auction update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $start_price = $_POST['start_price'];
    $end_time = $_POST['end_time'];

    // Fetch current image name before update
    $fetch_img_query = "SELECT image FROM auctions WHERE id = ?";
    $stmt_img = $conn->prepare($fetch_img_query);
    $stmt_img->bind_param("i", $auction_id);
    $stmt_img->execute();
    $current_image = $stmt_img->get_result()->fetch_assoc()['image'];
    $stmt_img->close();

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0 && !empty($_FILES['image']['name'])) {
        $image_name = time() . '_' . basename($_FILES['image']['name']); // Add timestamp to prevent name conflicts
        $image_tmp = $_FILES['image']['tmp_name'];
        $upload_dir = __DIR__ . "/../uploads/";
        $image_path = $upload_dir . $image_name;

        if (move_uploaded_file($image_tmp, $image_path)) {
            // Delete the old image file if it's different
            if ($current_image && $current_image !== $image_name && file_exists($upload_dir . $current_image)) {
                unlink($upload_dir . $current_image);
            }
            $current_image = $image_name;
        } else {
            $error = "Failed to upload new image. Please check directory permissions.";
        }
    }

    if (!$error) {
        $update_query = "UPDATE auctions SET title=?, description=?, start_price=?, end_time=?, image=? WHERE id=?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ssdssi", $title, $description, $start_price, $end_time, $current_image, $auction_id);

        if ($stmt->execute()) {
            $feedback_message = "Auction updated successfully!";
            header("Location: admin.php?feedback=" . urlencode($feedback_message));
            exit();
        } else {
            $error = "Error updating auction in the database.";
        }
    }
}

// Fetch auction details for the form
$query = "SELECT * FROM auctions WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $auction_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $auction = $result->fetch_assoc();
} else {
    // Redirect if auction not found
    header("Location: admin.php?feedback=" . urlencode("Error: Auction not found."));
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Timepiece | TimeLuxe Auctions</title>
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
            background-image: radial-gradient(circle, rgba(18, 18, 18, 0.8) 0%, rgba(18, 18, 18, 1) 75%), url('https://images.unsplash.com/photo-1610603114859-c7003b743758?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1770&q=80');
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
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-decoration: none;
            color: var(--primary-gold);
            padding: 8px 16px;
            border: 1px solid var(--primary-gold);
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .btn-view-auctions:hover {
            background-color: var(--primary-gold);
            color: var(--bg-color);
            box-shadow: 0 0 10px rgba(192, 160, 96, 0.5);
        }

        .logout-btn {
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
            animation: fadeIn 0.8s ease-out;
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

        .current-image-wrapper {
            margin-bottom: 20px;
        }

        .current-image {
            max-width: 150px;
            border-radius: 4px;
            border: 1px solid #333;
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
            transition: all 0.3s ease, box-shadow 0.3s ease;
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
            box-shadow: 0 0 15px rgba(192, 160, 96, 0.6);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: #222;
            color: var(--text-light);
        }

        .btn-secondary:hover {
            background-color: #333;
            color: white;
            transform: translateY(-2px);
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

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
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
                <h2 class="section-title">Edit Timepiece</h2>
            </div>

            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($auction): ?>
                <form method="POST" enctype="multipart/form-data">
                    <label for="title" class="form-label">Auction Title</label>
                    <input type="text" id="title" name="title" class="form-input" value="<?= htmlspecialchars($auction['title']); ?>" required>

                    <label for="description" class="form-label">Description</label>
                    <textarea id="description" name="description" class="form-textarea" required><?= htmlspecialchars($auction['description']); ?></textarea>

                    <label for="start_price" class="form-label">Starting Price (₹)</label>
                    <input type="number" id="start_price" name="start_price" step="100" class="form-input" value="<?= htmlspecialchars($auction['start_price']); ?>" required>

                    <label for="end_time" class="form-label">End Time</label>
                    <input type="datetime-local" id="end_time" name="end_time" class="form-input" value="<?= date('Y-m-d\TH:i', strtotime($auction['end_time'])); ?>" required>

                    <div class="current-image-wrapper">
                        <label class="form-label">Current Image</label>
                        <img src="../uploads/<?= htmlspecialchars($auction['image']); ?>" alt="Current auction image" class="current-image">
                    </div>

                    <label for="image" class="form-label">Upload New Image (Optional)</label>
                    <input type="file" id="image" name="image" class="form-input" accept="image/*">

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Update Auction</button>
                        <a href="admin.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            <?php else: ?>
                <p>Auction data could not be loaded.</p>
            <?php endif; ?>
        </div>
    </main>
</body>

</html>