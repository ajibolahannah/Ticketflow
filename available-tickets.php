<?php
session_start();
require 'sqlicon.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'support') {
    header("Location: admin-login.php");
    exit;
}

$admin_id = $_SESSION['user_id'];

// Handle claiming a ticket
if (isset($_POST['claim_id'])) {
    $ticket_id = $_POST['claim_id'];

    $stmt = mysqli_prepare($con, "UPDATE tickets SET assigned_to = ?, status = 'in_progress' WHERE id = ? AND assigned_to IS NULL");
    mysqli_stmt_bind_param($stmt, "ii", $admin_id, $ticket_id);
    mysqli_stmt_execute($stmt);

    header("Location: available-tickets.php");
    exit;
}

// Fetch all unassigned tickets
$tickets = mysqli_query($con, "
    SELECT id, subject, priority, status, created_at
    FROM tickets
    WHERE assigned_to IS NULL
    ORDER BY created_at ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./admin-dashboard.css">
    <title>Available Tickets</title>
</head>
<body>
  <section>
    <button class="nav-toggle">
        <span class="bar"></span><span class="bar"></span><span class="bar"></span>
    </button>
    <nav class="admin-nav">
      <img src="./logo.png" alt="" class="logo">
      <ul>
          <li><a href="./admin-dashboard.php">Dashboard</a></li>
          <li><a href="./available-tickets.php" class="active">Available Tickets</a></li>
          <li><a href="./admin-tickets.php">My Tickets</a></li>
          <li><a href="./logout.php">Logout</a></li>
      </ul>
    </nav>
    <main class="dashboard">

    <header class="page-header">
        <h1>Available Tickets</h1>
        <p>Tickets waiting to be assigned to a support agent.</p>
    </header>

    <section class="tickets-section">
        <div class="section-header">
            <h2>Unassigned Tickets</h2>
        </div>

        <div class="tickets-table">
            <table>
                <thead>
                    <tr>
                        <th>Ticket ID</th>
                        <th>Subject</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($tickets) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($tickets)): ?>
                            <tr>
                                <td>#<?php echo str_pad($row['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars($row['subject']); ?></td>
                                <td><span class="priority <?php echo strtolower($row['priority']); ?>"><?php echo ucfirst($row['priority']); ?></span></td>
                                <td><span class="status <?php echo strtolower($row['status']); ?>"><?php echo ucfirst($row['status']); ?></span></td>
                                <td><?php echo date('M j', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <form method="POST" action="available-tickets.php">
                                        <input type="hidden" name="claim_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" class="claim-btn">Claim</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6">No unassigned tickets right now.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
    </main>
  </section>
<script>
  let navtoggle = document.querySelector(".nav-toggle")
  let navmenu = document.querySelector(".admin-nav")
  navtoggle.addEventListener("click", function() {
    navtoggle.classList.toggle("active")