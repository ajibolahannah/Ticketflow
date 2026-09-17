<?php
session_start();
require 'sqlicon.php';

// Guard: must be logged in AND be support staff
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'support') {
    header("Location: admin-login.php");
    exit;
}

$admin_id = $_SESSION['user_id'];

// Helper: run a count query with no parameters
function countAll($con, $sql) {
    $result = mysqli_query($con, $sql);
    $row = mysqli_fetch_row($result);
    return $row[0];
}

$total = countAll($con, "SELECT COUNT(*) FROM tickets");
$open = countAll($con, "SELECT COUNT(*) FROM tickets WHERE status = 'open'");
$resolved = countAll($con, "SELECT COUNT(*) FROM tickets WHERE status = 'resolved'");

// My tickets (assigned to this admin) — uses a parameter, so prepared
$stmt = mysqli_prepare($con, "SELECT COUNT(*) FROM tickets WHERE assigned_to = ?");
mysqli_stmt_bind_param($stmt, "i", $admin_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $mine);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

// Recent tickets across all users
$recent = mysqli_query($con, "
    SELECT t.id, t.subject, t.priority, t.status, a.name AS agent_name
    FROM tickets t
    LEFT JOIN users a ON t.assigned_to = a.id
    ORDER BY t.created_at DESC
    LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="./admin-dashboard.css">
</head>
<body>
  <section>
    <button class="nav-toggle">
        <span class="bar"></span><span class="bar"></span><span class="bar"></span>
    </button>
    <nav class="admin-nav">
      <img src="./logo.png" alt="" class="logo">
      <ul>
          <li><a href="./admin-dashboard.php" class="active">Dashboard</a></li>
          <li><a href="./available-tickets.php">Available Tickets</a></li>
          <li><a href="./admin-tickets.php">My Tickets</a></li>
          <li><a href="./logout.php">Logout</a></li>
      </ul>
  </nav>
  <main class="dashboard">
    <header>
      <h1>Admin Dashboard</h1>
      <p>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></p>
    </header>
    <section class="stats">
      <div class="box">
        <h2>Total tickets</h2>
        <p class="tickets-no"><?php echo $total; ?></p>
      </div>
      <div class="box">
        <h2>Open tickets</h2>
        <p class="tickets-no"><?php echo $open; ?></p>
      </div>
      <div class="box">
        <h2>My tickets</h2>
        <p class="tickets-no"><?php echo $mine; ?></p>
      </div>
      <div class="box">
        <h2>Resolved tickets</h2>
        <p class="tickets-no"><?php echo $resolved; ?></p>
      </div>
    </section>
    <section class="recent-tickets">
      <div class="section-header">
        <h2>Recent Tickets</h2>
      </div>
      <div class="tickets-table">
        <table>
          <thead>
            <tr>
              <th>Ticket ID</th>
              <th>Subject</th>
              <th>Priority</th>
              <th>Status</th>
              <th>Assigned To</th>
            </tr>
          </thead>
          <tbody>
            <?php if (mysqli_num_rows($recent) > 0): ?>
              <?php while ($row = mysqli_fetch_assoc($recent)): ?>
                <tr>
                  <td>#<?php echo str_pad($row['id'], 4, '0', STR_PAD_LEFT); ?></td>
                  <td><a href="./admin-ticket-details.php?id=<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['subject']); ?></a></td>
                  <td><?php echo ucfirst($row['priority']); ?></td>
                  <td><?php echo htmlspecialchars($row['status']); ?></td>
                  <td><?php echo $row['agent_name'] ? htmlspecialchars($row['agent_name']) : 'Unassigned'; ?></td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="5">No tickets yet.</td></tr>
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