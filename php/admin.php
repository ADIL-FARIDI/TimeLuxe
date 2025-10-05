<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

include("db.php");

$feedback_message = null;

// Handle auction deletion
if (isset($_GET['delete_auction'])) {
    $auction_id = $_GET['delete_auction'];
    // First, fetch the image path to delete the file
    $image_query = "SELECT image FROM auctions WHERE id = ?";
    $stmt = $conn->prepare($image_query);
    $stmt->bind_param("i", $auction_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $auction_data = $result->fetch_assoc();

    if ($auction_data && !empty($auction_data['image'])) {
        $image_path = '../uploads/' . $auction_data['image']; // Adjusted path
        if (file_exists($image_path)) {
            unlink($image_path); // Delete the image file
        }
    }

    // Then delete bids associated with the auction
    $delete_bids_query = "DELETE FROM bids WHERE auction_id = ?";
    $stmt = $conn->prepare($delete_bids_query);
    $stmt->bind_param("i", $auction_id);
    $stmt->execute();

    // Finally, delete the auction itself
    $delete_auction_query = "DELETE FROM auctions WHERE id = ?";
    $stmt = $conn->prepare($delete_auction_query);
    $stmt->bind_param("i", $auction_id);
    if ($stmt->execute()) {
        $feedback_message = "Auction and associated data deleted successfully.";
    } else {
        $feedback_message = "Error deleting auction: " . $stmt->error;
    }
    header("Location: admin.php?feedback=" . urlencode($feedback_message));
    exit();
}

// Handle user deletion
if (isset($_GET['delete_user'])) {
    $user_id = $_GET['delete_user'];

    if ($user_id == $_SESSION['user_id']) {
        $feedback_message = "Error: Cannot delete the currently logged-in admin account.";
        header("Location: admin.php?feedback=" . urlencode($feedback_message));
        exit();
    }

    // Delete user
    $delete_user_query = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($delete_user_query);
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        $feedback_message = "User deleted successfully.";
    } else {
        $feedback_message = "Error deleting user: " . $stmt->error;
    }
    header("Location: admin.php?feedback=" . urlencode($feedback_message));
    exit();
}

// Handle feedback message display
if (isset($_GET['feedback'])) {
    $feedback_message = htmlspecialchars($_GET['feedback']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Control Panel | TimeLuxe Auctions</title>
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
            --error-red: #E53935;
            --success-green: #43A047;
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
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 40px;
            animation: fadeIn 0.8s ease-out;
        }

        .ledger-panel {
            background: rgba(10, 10, 10, 0.6);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-dark);
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            margin-bottom: 40px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .section-title {
            font-family: var(--font-serif);
            font-size: 2.5rem;
            margin: 0;
            color: var(--primary-gold);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
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

        .btn svg {
            width: 16px;
            height: 16px;
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

        .btn-edit {
            background-color: var(--success-green);
            color: white;
        }

        .btn-edit:hover {
            background-color: #4CAF50;
            box-shadow: 0 0 15px rgba(67, 160, 71, 0.6);
            transform: translateY(-2px);
        }

        .btn-delete {
            background-color: var(--error-red);
            color: white;
        }

        .btn-delete:hover {
            background-color: #F44336;
            box-shadow: 0 0 15px rgba(229, 57, 53, 0.6);
            transform: translateY(-2px);
        }

        .btn-delete:disabled {
            background-color: #555;
            cursor: not-allowed;
        }

        .btn-delete:disabled:hover {
            background-color: #555;
            box-shadow: none;
            transform: none;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        .data-ledger {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .data-ledger th,
        .data-ledger td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #333;
            white-space: nowrap;
        }

        .data-ledger th {
            font-family: var(--font-sans);
            font-weight: 600;
            font-size: 0.8rem;
            color: var(--primary-gold);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .data-ledger td {
            color: var(--text-color);
        }

        .data-ledger tr {
            transition: background-color 0.2s ease;
        }

        .data-ledger tr:hover {
            background-color: rgba(30, 30, 30, 0.5);
        }

        .data-ledger tr:last-child td {
            border-bottom: none;
        }

        .data-ledger td .btn {
            padding: 6px 12px;
            font-size: 0.8rem;
            margin-right: 8px;
        }

        .data-ledger td .btn svg {
            width: 14px;
            height: 14px;
        }

        .feedback-message {
            padding: 15px 20px;
            margin: 20px auto;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 600;
            text-align: center;
            max-width: 800px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(10, 10, 10, 0.8);
            border: 1px solid var(--border-dark);
            animation: fadeIn 0.5s ease-out;
        }

        .feedback-success {
            color: var(--success-green);
            border-color: var(--success-green);
        }

        .feedback-error {
            color: var(--error-red);
            border-color: var(--error-red);
        }

        .feedback-close {
            background: none;
            border: none;
            color: inherit;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0 5px;
            transition: transform 0.2s ease;
        }

        .feedback-close:hover {
            transform: scale(1.2);
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(8px);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }

        .modal-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }

        .modal-content {
            background: var(--card-bg);
            padding: 40px;
            border-radius: 8px;
            border: 1px solid var(--border-dark);
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.7);
            max-width: 450px;
            transform: translateY(-50px);
            opacity: 0;
            transition: all 0.3s ease-out;
        }

        .modal-overlay.active .modal-content {
            transform: translateY(0);
            opacity: 1;
        }

        .modal-title {
            font-family: var(--font-serif);
            font-size: 2rem;
            margin-top: 0;
            color: var(--primary-gold);
        }

        .modal-body {
            color: var(--text-light);
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .modal-actions {
            display: flex;
            justify-content: center;
            gap: 20px;
        }

        .modal-btn {
            width: 140px;
        }

        .btn-cancel {
            background-color: #333;
            color: white;
        }

        .btn-cancel:hover {
            background-color: #444;
            box-shadow: 0 0 15px rgba(51, 51, 51, 0.6);
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

    <?php if ($feedback_message): ?>
        <div class="feedback-message <?= strpos($feedback_message, 'Error') !== false ? 'feedback-error' : 'feedback-success' ?>">
            <span><?= $feedback_message ?></span>
            <button class="feedback-close" onclick="this.parentElement.style.display='none';">&times;</button>
        </div>
    <?php endif; ?>

    <main class="admin-container">
        <div class="ledger-panel">
            <div class="section-header">
                <h2 class="section-title">Manage Auctions</h2>
                <a href="add_auction.php" class="btn btn-primary">
                    <svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='currentColor'>
                        <path fill-rule='evenodd' d='M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z' clip-rule='evenodd' />
                    </svg>
                    Add New Auction
                </a>
            </div>
            <div class="table-wrapper">
                <table class="data-ledger">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Start Price</th>
                            <th>End Time</th>
                            <th>Highest Bid</th>
                            <th>Highest Bidder</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT a.*, 
                        (SELECT MAX(b.bid_amount) FROM bids b WHERE b.auction_id = a.id) AS highest_bid,
                        (SELECT u.username FROM bids b JOIN users u ON b.user_id = u.id WHERE b.auction_id = a.id ORDER BY b.bid_amount DESC LIMIT 1) AS highest_bidder
                        FROM auctions a ORDER BY a.end_time DESC";
                        $result = $conn->query($query);
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>
                                <td>" . htmlspecialchars($row['title']) . "</td>
                                <td>₹" . number_format($row['start_price']) . "</td>
                                <td>" . date("M j, Y, g:i a", strtotime($row['end_time'])) . "</td>
                                <td>" . ($row['highest_bid'] ? "₹" . number_format($row['highest_bid'], 2) : "No bids") . "</td>
                                <td>" . ($row['highest_bidder'] ? htmlspecialchars($row['highest_bidder']) : "N/A") . "</td>
                                <td>
                                    <a href='edit_auction.php?id={$row['id']}' class='btn btn-edit'><svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='currentColor'><path d='M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z'/><path fill-rule='evenodd' d='M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z' clip-rule='evenodd'/></svg><span>Edit</span></a>
                                    <button onclick='showDeleteModal(\"auction\", {$row['id']}, \"" . htmlspecialchars($row['title'], ENT_QUOTES) . "\")' class='btn btn-delete'><svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='currentColor'><path fill-rule='evenodd' d='M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z' clip-rule='evenodd'/></svg><span>Delete</span></button>
                                </td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' style='text-align:center;'>No auctions found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="ledger-panel">
            <div class="section-header">
                <h2 class="section-title">Manage Users</h2>
            </div>
            <div class="table-wrapper">
                <table class="data-ledger">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT id, username, email, role FROM users ORDER BY username ASC";
                        $result = $conn->query($query);
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $delete_button_disabled = ($row['id'] == $_SESSION['user_id']) ? 'disabled' : '';
                                echo "<tr>
                                <td>" . htmlspecialchars($row['username']) . "</td>
                                <td>" . htmlspecialchars($row['email']) . "</td>
                                <td>" . htmlspecialchars($row['role']) . "</td>
                                <td>
                                    <button onclick='showDeleteModal(\"user\", {$row['id']}, \"" . htmlspecialchars($row['username'], ENT_QUOTES) . "\")' class='btn btn-delete' {$delete_button_disabled}><svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='currentColor'><path fill-rule='evenodd' d='M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z' clip-rule='evenodd'/></svg><span>Delete</span></button>
                                </td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4' style='text-align:center;'>No users found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div id="deleteModal" class="modal-overlay">
        <div class="modal-content">
            <h3 id="modalTitle" class="modal-title">Confirm Deletion</h3>
            <p id="modalBody" class="modal-body">This action cannot be undone.</p>
            <div class="modal-actions">
                <button id="modalCancelBtn" class="btn btn-cancel modal-btn">Cancel</button>
                <a id="modalConfirmLink" href="#" class="btn btn-delete modal-btn">Delete</a>
            </div>
        </div>
    </div>

    <script>
        const modal = document.getElementById('deleteModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalBody = document.getElementById('modalBody');
        const modalConfirmLink = document.getElementById('modalConfirmLink');
        const modalCancelBtn = document.getElementById('modalCancelBtn');

        function showDeleteModal(type, id, name) {
            modalTitle.textContent = `Delete ${type === 'auction' ? 'Auction' : 'User'}: ${name}`;
            modalBody.textContent = `Are you sure you want to permanently delete "${name}"? This action cannot be undone and will remove all associated data.`;
            modalConfirmLink.href = `admin.php?delete_${type}=` + id;

            modal.classList.add('active');
        }

        function hideModal() {
            modal.classList.remove('active');
        }

        modalCancelBtn.onclick = hideModal;
        modal.addEventListener('click', function(event) {
            if (event.target === modal) {
                hideModal();
            }
        });

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                hideModal();
            }
        });
    </script>

</body>

</html>