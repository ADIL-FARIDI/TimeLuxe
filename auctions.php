<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include("php/db.php");

// Get the logged-in user's name
$user_id = $_SESSION['user_id'];
$user_query = "SELECT username FROM users WHERE id = ?";
$stmt_user = $conn->prepare($user_query);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_result = $stmt_user->get_result();
$user = $user_result->fetch_assoc();
$username = $user['username'];

// --- Filter Logic ---
$selected_brand = $_GET['brand'] ?? 'all';
// Expanded the list of brands for the filter
$brands = [
    'RM' => 'Richard Mille',
    'PP' => 'Patek Philippe',
    'AP' => 'Audemars Piguet',
    'Hublot' => 'Hublot',
    'VC' => 'Vacheron Constantin'
];

$query = "SELECT *, TIMESTAMPDIFF(SECOND, NOW(), end_time) AS time_left 
          FROM auctions 
          WHERE end_time > NOW() AND status != 'expired'";

if ($selected_brand !== 'all' && array_key_exists($selected_brand, $brands)) {
    $query .= " AND brand = ?";
}

$query .= " ORDER BY start_time ASC";

$stmt = $conn->prepare($query);

if ($selected_brand !== 'all' && array_key_exists($selected_brand, $brands)) {
    $stmt->bind_param("s", $selected_brand);
}

$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Auctions | TimeLuxe Auctions</title>
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

        .gallery-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 40px;
            background-color: rgba(10, 10, 10, 0.7);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-dark);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-logo {
            height: 40px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .admin-btn {
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

        .admin-btn:hover {
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
            background-color: var(--primary-gold);
            border-color: var(--primary-gold);
            color: var(--bg-color);
        }

        .gallery-container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 40px;
        }

        .gallery-title {
            font-family: var(--font-serif);
            font-size: 3rem;
            text-align: center;
            margin-bottom: 20px;
        }

        .filter-bar {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            /* Allow buttons to wrap on smaller screens */
            gap: 15px;
            margin-bottom: 40px;
        }

        .filter-btn {
            font-family: var(--font-sans);
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-decoration: none;
            color: var(--text-light);
            padding: 10px 20px;
            border: 1px solid #333;
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .filter-btn.active,
        .filter-btn:hover {
            background-color: var(--primary-gold);
            border-color: var(--primary-gold);
            color: var(--bg-color);
            box-shadow: 0 0 10px rgba(192, 160, 96, 0.5);
        }

        .auction-grid {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 30px;
        }

        .auction-card-link {
            text-decoration: none;
            color: inherit;
        }

        .auction-card {
            width: 320px;
            background: var(--card-bg);
            border-radius: 8px;
            border: 1px solid var(--border-dark);
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            transition: transform 0.4s ease, box-shadow 0.4s ease;
        }

        .auction-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.8), 0 0 20px -5px var(--primary-gold);
        }

        .card-image-wrapper {
            background-color: #000;
            padding: 20px 0;
        }

        .watch-image {
            width: 80%;
            height: 200px;
            display: block;
            margin: 0 auto;
            object-fit: contain;
            filter: drop-shadow(0 10px 10px rgba(0, 0, 0, 0.7));
            transition: transform 0.4s ease;
        }

        .auction-card:hover .watch-image {
            transform: scale(1.1);
        }

        .card-content {
            padding: 20px;
        }

        .watch-brand {
            font-size: 0.8rem;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }

        .watch-model {
            font-family: var(--font-serif);
            font-size: 1.6rem;
            color: var(--text-color);
            margin: 0 0 15px 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .auction-details {
            display: flex;
            justify-content: space-between;
            border-top: 1px solid var(--border-dark);
            padding-top: 15px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-item span:first-child {
            font-size: 0.8rem;
            color: var(--text-light);
            text-transform: uppercase;
        }

        .detail-item span:last-child {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-gold);
        }
    </style>
</head>

<body>

    <header class="gallery-header">
        <a href="auctions.php"><img src="./assets/logo-no-bg-W.png" alt="TimeLuxe Monogram" class="header-logo"></a>
        <div class="user-info">
            <span>Welcome, <?php echo htmlspecialchars($username); ?></span>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <a href="php/admin.php" class="admin-btn">Admin Panel</a>
            <?php endif; ?>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </header>

    <main class="gallery-container">
        <h1 class="gallery-title">Live Auctions</h1>

        <div class="filter-bar">
            <a href="auctions.php" class="filter-btn <?= $selected_brand === 'all' ? 'active' : '' ?>">All Brands</a>
            <?php foreach ($brands as $code => $name): ?>
                <a href="auctions.php?brand=<?= $code ?>" class="filter-btn <?= $selected_brand === $code ? 'active' : '' ?>"><?= $name ?></a>
            <?php endforeach; ?>
        </div>

        <div class="auction-grid">
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $formatted_price = number_format($row['start_price']);
                    echo "
                    <a href='bid.php?auction_id={$row['id']}' class='auction-card-link'>
                        <div class='auction-card'>
                            <div class='card-image-wrapper'>
                                <img src='uploads/{$row['image']}' alt='{$row['title']}' class='watch-image'>
                            </div>
                            <div class='card-content'>
                                <div class='watch-brand'>{$row['brand']}</div>
                                <h3 class='watch-model'>{$row['title']}</h3>
                                <div class='auction-details'>
                                    <div class='detail-item'>
                                        <span>Starting Price</span>
                                        <span>₹{$formatted_price}</span>
                                    </div>
                                    <div class='detail-item'>
                                        <span>Time Left</span>
                                        <span class='timer' data-time='{$row['time_left']}'></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                    ";
                }
            } else {
                echo "<p style='text-align: center; width: 100%; font-size: 1.2rem; color: var(--text-light);'>No active auctions found for this brand.</p>";
            }
            ?>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const timers = document.querySelectorAll('.timer');
            timers.forEach(timer => {
                let secondsLeft = parseInt(timer.dataset.time, 10);
                const intervalId = setInterval(() => {
                    if (secondsLeft <= 0) {
                        clearInterval(intervalId);
                        timer.textContent = "Auction Ended";
                        timer.closest('.auction-card-link').style.opacity = '0.6';
                        timer.closest('.auction-card-link').style.pointerEvents = 'none';
                        return;
                    }
                    secondsLeft--;
                    const days = Math.floor(secondsLeft / 86400);
                    const hours = Math.floor((secondsLeft % 86400) / 3600);
                    const minutes = Math.floor((secondsLeft % 3600) / 60);
                    const seconds = secondsLeft % 60;
                    if (days > 0) {
                        timer.textContent = `${days}d ${hours}h ${minutes}m`;
                    } else {
                        timer.textContent = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                    }
                }, 1000);
            });
        });
    </script>
</body>

</html>