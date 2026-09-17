<?php
session_start();
require 'sqlicon.php';

// Guard: kick out anyone not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$name = $_SESSION['name'];

// Total tickets for this user
$stmt = mysqli_prepare($con, "SELECT COUNT(*) FROM tickets WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $total);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

// Count by status
function countByStatus($con, $user_id, $status) {
    $stmt = mysqli_prepare($con, "SELECT COUNT(*) FROM tickets WHERE user_id = ? AND status = ?");
    mysqli_stmt_bind_param($stmt, "is", $user_id, $status);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $count);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    return $count;
}

$open = countByStatus($con, $user_id, 'open');
$in_progress = countByStatus($con, $user_id, 'in_progress');
$resolved = countByStatus($con, $user_id, 'resolved');

// Recent tickets (latest 5)
$stmt = mysqli_prepare($con, "SELECT id, subject, status, created_at FROM tickets WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User dashboard</title>
    <link rel="stylesheet" href="./user_dash.css">
</head>
<body>
    <section class="wrapper">
        <button class="navbar-toggle">
            <span class="bar"></span>
            <span class="bar"></span>
            <span class="bar"></span>
        </button>
        <nav class="sidenav">
            <div class="image">
                <img src="./logo.png" alt="logo" class="logo">
            </div>
            <ul>
                <li><a href="./myticket.php" class="active">Dashboard</a></li>
                <li><a href="myticket.php">My Tickets</a></li>
                <li><a href="./create-tickect.html">Create Ticket</a></li>
                <li><a href="./logout.php">Log Out</a></li>
            </ul>
        </nav>
        <main class="dashboard">

    <header class="dashboard-header">
        <h1>Good Day, <?php echo htmlspecialchars($name); ?></h1>
        <p>Welcome back to TicketFlow.</p>
    </header>

    <section class="stats">
        <div class="card">
            <h2><?php echo $total; ?></h2>
            <p>Total Tickets</p>
        </div>
        <div class="card">
            <h2><?php echo $open; ?></h2>
            <p>Open</p>
        </div>
        <div class="card">
            <h2><?php echo $in_progress; ?></h2>
            <p>In Progress</p>
        </div>
        <div class="card">
            <h2><?php echo $resolved; ?></h2>
            <p>Resolved</p>
        </div>
    </section>

    <section class="recent">
        <h2>Recent Tickets</h2>
        <div class="table-container">
        <table>
            <tr>
                <th>ID</th>
                <th>Subject</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td>#<?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['subject']); ?></td>
                        <td><?php echo htmlspecialchars($row['status']); ?></td>
                        <td><?php echo date('d M', strtotime($row['created_at'])); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4">You have no tickets yet.</td></tr>
            <?php endif; ?>
        </table>
        </div>
    </section>

</main>
    </section>

<script>
    let togglebutton = document.querySelector(".navbar-toggle");
    let navmenu = document.querySelector(".sidenav");
    togglebutton.addEventListener("click", () =>{
        togglebutton.classList.toggle("active");
        navmenu.classList.toggle("active");
    })
    let navlinks = document.querySelectorAll(".sidenav ul li a");
        navlinks.forEach(link => {
            link.addEventListener("click", () => {
                togglebutton.classList.remove("active");
                navmenu.classList.remove("active");
            });
    });
</script>
</html>