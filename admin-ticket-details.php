<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require 'sqlicon.php';
require 'upload-helper.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'support') {
    header("Location: admin-login.php");
    exit;
}

$admin_id = $_SESSION['user_id'];
$ticket_id = $_GET['id'] ?? null;

if (!$ticket_id) {
    header("Location: admin-tickets.php");
    exit;
}

// Fetch the ticket + customer info
$stmt = mysqli_prepare($con, "
    SELECT t.id, t.ticket_code, t.subject, t.description, t.image, t.status, t.priority, t.assigned_to, t.created_at,
           u.name AS customer_name, u.email AS customer_email
    FROM tickets t
    JOIN users u ON t.user_id = u.id
    WHERE t.id = ?
");
mysqli_stmt_bind_param($stmt, "i", $ticket_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$ticket = mysqli_fetch_assoc($result);

// Ticket doesn't exist, or isn't assigned to this admin
if (!$ticket || $ticket['assigned_to'] != $admin_id) {
    header("Location: admin-tickets.php");
    exit;
}

// Handle reply
if (isset($_POST['submit_reply'])) {
    $message = trim($_POST['message']);
    list($replyImage, $uploadError) = handleImageUpload($_FILES['reply_image'] ?? null);

    if ($message !== "" && !$uploadError) {
        $stmt = mysqli_prepare($con, "INSERT INTO ticket_replies (ticket_id, sender_id, message, image) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iiss", $ticket_id, $admin_id, $message, $replyImage);
        mysqli_stmt_execute($stmt);
    }
    header("Location: admin-ticket-details.php?id=" . $ticket_id);
    exit;
}

// Handle status change
if (isset($_POST['mark_resolved'])) {
    $stmt = mysqli_prepare($con, "UPDATE tickets SET status = 'resolved' WHERE id = ? AND assigned_to = ?");
    mysqli_stmt_bind_param($stmt, "ii", $ticket_id, $admin_id);
    mysqli_stmt_execute($stmt);
    header("Location: admin-ticket-details.php?id=" . $ticket_id);
    exit;
}

// Fetch conversation
$stmt = mysqli_prepare($con, "
    SELECT r.message, r.image, r.created_at, u.name, u.role
    FROM ticket_replies r
    JOIN users u ON r.sender_id = u.id
    WHERE r.ticket_id = ?
    ORDER BY r.created_at ASC
");
mysqli_stmt_bind_param($stmt, "i", $ticket_id);
mysqli_stmt_execute($stmt);
$replies = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./admin-dashboard.css">
    <title>Ticket Details</title>
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
    <section>
        <main class="dashboard">

    <header class="page-header">
        <h1>Ticket Details</h1>
        <p>View and manage this support ticket.</p>
    </header>

    <section class="ticket-details">

        <div class="ticket-header">
            <div>
                <h2><?php echo htmlspecialchars($ticket['subject']); ?></h2>
                <p class="ticket-id"><?php echo htmlspecialchars($ticket['ticket_code'] ?? 'N/A'); ?></p>
            </div>
            <div class="ticket-status">
                <span class="priority <?php echo strtolower($ticket['priority']); ?>"><?php echo ucfirst($ticket['priority']); ?></span>
                <span class="status <?php echo strtolower($ticket['status']); ?>"><?php echo ucfirst($ticket['status']); ?></span>
            </div>
        </div>

        <div class="customer-info">
            <h3>Customer Information</h3>
            <div class="customer-details">
                <p><strong>Name:</strong> <?php echo htmlspecialchars($ticket['customer_name']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($ticket['customer_email']); ?></p>
            </div>
        </div>

        <div class="ticket-description">
            <h3>Issue Description</h3>
            <p><?php echo htmlspecialchars($ticket['description']); ?></p>
            <?php if ($ticket['image']): ?>
                <img src="uploads/<?php echo htmlspecialchars($ticket['image']); ?>" alt="Ticket attachment" style="max-width: 250px; border-radius: 8px; margin-top: 10px;">
            <?php endif; ?>
        </div>

        <div class="conversation">
            <h3>Conversation</h3>

            <?php if (mysqli_num_rows($replies) > 0): ?>
                <?php while ($msg = mysqli_fetch_assoc($replies)): ?>
                    <div class="message <?php echo $msg['role'] === 'support' ? 'admin-message' : 'customer-message'; ?>">
                        <div class="message-header">
                            <strong><?php echo $msg['role'] === 'support' ? 'You' : htmlspecialchars($msg['name']); ?></strong>
                            <span><?php echo date('M j, h:i A', strtotime($msg['created_at'])); ?></span>
                        </div>
                        <p><?php echo htmlspecialchars($msg['message']); ?></p>
                        <?php if ($msg['image']): ?>
                            <img src="uploads/<?php echo htmlspecialchars($msg['image']); ?>" alt="Attached image" style="max-width: 200px; border-radius: 8px; margin-top: 6px;">
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No messages yet.</p>
            <?php endif; ?>
        </div>

        <div class="reply-section">
            <h3>Reply</h3>
            <form method="POST" action="admin-ticket-details.php?id=<?php echo $ticket_id; ?>" enctype="multipart/form-data">
                <textarea name="message" placeholder="Write your reply..." required></textarea>
                <input type="file" name="reply_image" accept="image/*">
                <button type="submit" name="submit_reply" class="send-btn">Send Reply</button>
            </form>
        </div>

        <div class="ticket-actions">
            <h3>Ticket Actions</h3>
            <div class="action-buttons">
                <?php if ($ticket['status'] !== 'resolved'): ?>
                    <form method="POST" action="admin-ticket-details.php?id=<?php echo $ticket_id; ?>">
                        <button type="submit" name="mark_resolved" class="status-btn">Mark as Resolved</button>
                    </form>
                <?php else: ?>
                    <p>This ticket has been resolved.</p>
                <?php endif; ?>
            </div>
        </div>

    </section>

</main>
    </section>
</section>
</body>
<script>
  let navtoggle = document.querySelector(".nav-toggle")
  let navmenu = document.querySelector(".admin-nav")
  navtoggle.addEventListener("click", function() {
    navtoggle.classList.toggle("active")
    navmenu.classList.toggle("active")
  })
</script>
</html>