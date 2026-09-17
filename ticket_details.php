<?php
session_start();
require 'sqlicon.php';
require 'upload-helper.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$ticket_id = $_GET['id'] ?? null;

if (!$ticket_id) {
    header("Location: myticket.php");
    exit;
}

// Fetch the ticket, joined with the ticket owner's info
$stmt = mysqli_prepare($con, "
    SELECT t.id, t.ticket_code, t.subject, t.description, t.image, t.status, t.priority, t.created_at, t.updated_at,
           u.id AS owner_id, u.name, u.email, u.phone
    FROM tickets t
    JOIN users u ON t.user_id = u.id
    WHERE t.id = ?
");
mysqli_stmt_bind_param($stmt, "i", $ticket_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$ticket = mysqli_fetch_assoc($result);

// Ticket doesn't exist, or doesn't belong to this user
if (!$ticket || $ticket['owner_id'] != $user_id) {
    header("Location: myticket.php");
    exit;
}

// Handle new reply
if (isset($_POST['submit_reply'])) {
    $message = trim($_POST['message']);
    list($replyImage, $uploadError) = handleImageUpload($_FILES['reply_image'] ?? null);

    if ($message !== "" && !$uploadError) {
        $stmt = mysqli_prepare($con, "INSERT INTO ticket_replies (ticket_id, sender_id, message, image) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iiss", $ticket_id, $user_id, $message, $replyImage);
        mysqli_stmt_execute($stmt);
    }

    header("Location: ticket_details.php?id=" . $ticket_id);
    exit;
}

// Fetch all replies for this ticket, joined with sender info
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
    <title>Ticket Details</title>
    <link rel="stylesheet" href="./ticket_details.css">
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
                <li><a href="./myticket.php">My Tickets</a></li>
                <li><a href="./create-ticket.php">Create Ticket</a></li>
                <li><a href="./logout.php">Log Out</a></li>
            </ul>
        </nav>
        <main class="view">
            <header class="view-header">
                <a href="./myticket.php">← Back to Tickets</a>
                <h2><?php echo htmlspecialchars($ticket['subject']); ?></h2>
                <p class="ticket-code"><?php echo htmlspecialchars($ticket['ticket_code'] ?? 'N/A'); ?></p>
                <p><?php echo htmlspecialchars($ticket['description']); ?></p>

                <?php if ($ticket['image']): ?>
                    <img src="uploads/<?php echo htmlspecialchars($ticket['image']); ?>" alt="Ticket attachment" style="max-width: 250px; border-radius: 8px; margin-top: 10px;">
                <?php endif; ?>
            </header>
            <section class="ticket-container">
                <section class="ticket-chat">
                    <div class="tab">
                        <h3 class="active">Conversation</h3>
                    </div>

                    <div class="messages">
                        <?php if (mysqli_num_rows($replies) > 0): ?>
                            <?php while ($msg = mysqli_fetch_assoc($replies)): ?>
                                <div class="message <?php echo $msg['role'] === 'support' ? 'admin' : 'customer'; ?>">
                                    <div class="avatar">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="currentColor" class="bi bi-person-circle" viewBox="0 0 16 16">
                                        <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0"/>
                                        <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8m8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1"/>
                                        </svg>
                                    </div>
                                    <div class="message-content">
                                        <div class="message-header">
                                            <h4><?php echo htmlspecialchars($msg['name']); ?></h4>
                                            <span><?php echo $msg['role'] === 'support' ? 'Admin' : 'Customer'; ?></span>
                                            <small><?php echo date('h:i A', strtotime($msg['created_at'])); ?></small>
                                        </div>
                                        <p><?php echo htmlspecialchars($msg['message']); ?></p>
                                        <?php if ($msg['image']): ?>
                                            <img src="uploads/<?php echo htmlspecialchars($msg['image']); ?>" alt="Attached image" style="max-width: 200px; border-radius: 8px; margin-top: 6px;">
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p>No replies yet.</p>
                        <?php endif; ?>

                        <div class="status-update">
                            Ticket status: <strong><?php echo htmlspecialchars($ticket['status']); ?></strong>
                        </div>
                    </div>

                    <form method="POST" action="ticket_details.php?id=<?php echo $ticket_id; ?>" enctype="multipart/form-data" class="reply-box">
                        <h3>Reply to this ticket</h3>
                        <textarea name="message" placeholder="Type your reply here..." required></textarea>
                        <input type="file" name="reply_image" accept="image/*">
                        <button type="submit" name="submit_reply" class="send-btn">Send Reply</button>
                    </form>

                </section>

                <aside class="ticket-info">
                    <h3>Ticket Information</h3>
                    <div class="info-row">
                        <span>Status</span>
                        <span class="badge <?php echo strtolower($ticket['status']); ?>"><?php echo htmlspecialchars($ticket['status']); ?></span>
                    </div>
                    <div class="info-row">
                        <span>Priority</span>
                        <span class="badge <?php echo strtolower($ticket['priority']); ?>"><?php echo ucfirst($ticket['priority']); ?></span>
                    </div>
                    <div class="info-row">
                        <span>Created</span>
                        <span><?php echo date('M j, Y', strtotime($ticket['created_at'])); ?></span>
                    </div>
                    <div class="info-row">
                        <span>Last Updated</span>
                        <span><?php echo date('M j, Y', strtotime($ticket['updated_at'])); ?></span>
                    </div>
                    <hr>
                    <h3>Customer Information</h3>
                    <div class="info-row">
                        <span>Name</span>
                        <span><?php echo htmlspecialchars($ticket['name']); ?></span>
                    </div>
                    <div class="info-row">
                        <span>Email</span>
                        <span><?php echo htmlspecialchars($ticket['email']); ?></span>
                    </div>
                    <div class="info-row">
                        <span>Phone</span>
                        <span><?php echo htmlspecialchars($ticket['phone']); ?></span>
                    </div>
                </aside>
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