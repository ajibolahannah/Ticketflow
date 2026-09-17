<?php
session_start();
require 'sqlicon.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = mysqli_prepare($con, "SELECT id, subject, description, status, priority, created_at FROM tickets WHERE user_id = ? ORDER BY created_at DESC");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tickets</title>
    <link rel="stylesheet" href="./myticket.css">
</head>
<body>
    <section class="wrapper">
        <button class="navbar-toggle">
            <span class="bar"></span><span class="bar"></span><span class="bar"></span>
        </button>
        <nav class="sidenav">
            <div class="image"><img src="./logo.png" alt="logo" class="logo"></div>
            <ul>
                <li><a href="./user_dash.php">Dashboard</a></li>
                <li><a href="./myticket.php" class="active">My Tickets</a></li>
                <li><a href="./create-ticket.php">Create Ticket</a></li>
                <li><a href="./logout.php">Log Out</a></li>
            </ul>
        </nav>
        <main class="create">
            <header class="create-header">
                <a href="./user_dash.php">← Dashboard</a>
                <h2>My Tickets</h2>
                <p>Track and manage all your support tickets in one place</p>
            </header>
            <section class="ticket-list">
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <div class="ticket-card">
                            <div class="ticket-top">
                                <h3><?php echo htmlspecialchars($row['subject']); ?></h3>
                                <div class="badges">
                                    <span class="status <?php echo strtolower($row['status']); ?>"><?php echo htmlspecialchars($row['status']); ?></span>
                                    <span class="priority <?php echo strtolower($row['priority']); ?>"><?php echo ucfirst($row['priority']); ?></span>
                                </div>
                            </div>
                            <p class="tickect-details"><?php echo htmlspecialchars($row['description']); ?></p>
                            <div class="ticket-footer">
                                <small>Created: <?php echo date('F j, Y', strtotime($row['created_at'])); ?></small>
                                <a href="./ticket_details.php?id=<?php echo $row['id']; ?>" class="view-btn">View Details</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>You have no tickets yet. <a href="./create-ticket.php">Create one</a>.</p>
                <?php endif; ?>
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