<?php
session_start();
require 'sqlicon.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'support') {
    header("Location: admin-login.php");
    exit;
}

$admin_id = $_SESSION['user_id'];

$stmt = mysqli_prepare($con, "
    SELECT id, subject, priority, status, created_at
    FROM tickets
    WHERE assigned_to = ?
    ORDER BY created_at DESC
");
mysqli_stmt_bind_param($stmt, "i", $admin_id);
mysqli_stmt_execute($stmt);
$tickets = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./admin-dashboard.css">
    <title>My Tickets</title>
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
          <li><a href="./available-tickets.php">Available Tickets</a></li>
          <li><a href="./admin-tickets.php" class="active">My Tickets</a></li>
          <li><a href="./logout.php">Logout</a></li>
      </ul>
    </nav>
    <main class="dashboard">

    <header class="page-header">
        <h1>My Tickets</h1>
        <p>Tickets currently assigned to you.</p>
    </header>

    <section class="tickets-section">
        <div class="section-header">
            <h2>Assigned Tickets</h2>
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
                                    <a href="./admin-ticket-details.php?id=<?php echo $row['id']; ?>" class="view-btn">View</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6">You have no assigned tickets.</td></tr>
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
    navmenu.classList.toggle("active")
  })
</script>
</html>