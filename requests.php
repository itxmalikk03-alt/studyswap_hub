<?php
// requests.php
require_once __DIR__ . '/db_connect.php';
requireLogin('login.php');
$uid = currentUserId();

// Handle accept/decline
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['req_action'])) {
    $reqId  = (int)$_POST['req_id'];
    $action = $_POST['req_action'];
    if (in_array($action, ['accepted','declined']) && $reqId > 0) {
        $verif = $pdo->prepare('SELECT * FROM requests WHERE id = ? AND owner_id = ?');
        $verif->execute([$reqId, $uid]);
        $req = $verif->fetch();
        if ($req) {
            $pdo->prepare('UPDATE requests SET status = ? WHERE id = ?')->execute([$action, $reqId]);
            if ($action === 'accepted') {
                // Book ko unavailable mark karna
                $pdo->prepare('UPDATE books SET is_available = 0 WHERE id = ?')->execute([$req['book_id']]);
                
                // Swap history mein entry
                $pdo->prepare('INSERT INTO swap_history (request_id,giver_id,receiver_id,book_given,book_received,swap_date) VALUES (?,?,?,?,?,CURDATE())')
                    ->execute([$reqId,$uid,$req['requester_id'],$req['book_id'],$req['offer_book_id']]);
                
                $pdo->prepare('UPDATE users SET total_swaps=total_swaps+1 WHERE id IN (?,?)')->execute([$uid,$req['requester_id']]);
                
                sendNotification($pdo,$req['requester_id'],'request_accepted','Your request was accepted! You can now access the resources.','swap-history.php');
            } else {
                sendNotification($pdo,$req['requester_id'],'request_declined','Your swap request was declined.','browse.php');
            }
        }
    }
    header('Location: requests.php'); exit;
}

// Incoming
$inStmt = $pdo->prepare(
    'SELECT r.*, b.title AS book_title, u.first_name, u.last_name, u.university AS req_uni
     FROM requests r JOIN books b ON r.book_id=b.id JOIN users u ON r.requester_id=u.id
     WHERE r.owner_id=? ORDER BY r.created_at DESC'
);
$inStmt->execute([$uid]); $incoming = $inStmt->fetchAll();

// Outgoing
$outStmt = $pdo->prepare(
    'SELECT r.*, b.title AS book_title, u.first_name AS own_fn, u.last_name AS own_ln
     FROM requests r JOIN books b ON r.book_id=b.id JOIN users u ON r.owner_id=u.id
     WHERE r.requester_id=? ORDER BY r.created_at DESC'
);
$outStmt->execute([$uid]); $outgoing = $outStmt->fetchAll();

$statusClass = ['pending'=>'status-pending','accepted'=>'status-accepted','declined'=>'status-declined','completed'=>'status-completed','cancelled'=>'status-declined'];
?>
<!DOCTYPE html><html lang="en">
<head><meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Requests — StudySwap Hub</title>
<link rel="stylesheet" href="style.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/></head>
<body>
<?php require __DIR__ . '/includes/navbar.php'; ?>
<div class="page-header"><div class="container">
  <div class="breadcrumb"><a href="dashboard.php">Dashboard</a><i class="fas fa-chevron-right"></i><span>Requests</span></div>
  <h1>Swap Requests</h1><p>Manage incoming and outgoing book exchange requests</p>
  <button onclick="history.back()" class="btn btn-outline" style="margin-top:10px;"><i class="fas fa-arrow-left"></i> Back</button>
</div></div>
<div class="container"><div class="dash-wrap">
  
<aside class="dash-sidebar">
  <?php 
    // Quick sidebar check
    $_cp = basename($_SERVER['PHP_SELF']);
    $_uc = unreadCount($pdo, currentUserId());
  ?>
  <div class="sidebar-user">
    <div class="sidebar-avatar">
      <?= strtoupper(substr($_SESSION['first_name']??'U',0,1)) ?>
    </div>
    <div>
      <strong><?= h(($_SESSION['first_name']??'').' '.($_SESSION['last_name']??'')) ?></strong>
      <span><?= h($_SESSION['university']??'') ?></span>
    </div>
  </div>

  <a href="dashboard.php"     <?= $_cp==='dashboard.php'     ?'class="active"':'' ?>><i class="fas fa-th-large"></i> Overview</a>
  <a href="my-books.php"      <?= $_cp==='my-books.php'      ?'class="active"':'' ?>><i class="fas fa-book"></i> My Books</a>
  <a href="requests.php"      <?= $_cp==='requests.php'      ?'class="active"':'' ?>><i class="fas fa-exchange-alt"></i> Requests</a>
  <a href="wishlist.php"      <?= $_cp==='wishlist.php'      ?'class="active"':'' ?>><i class="fas fa-heart"></i> Wishlist</a>
  <a href="swap-history.php"  <?= $_cp==='swap-history.php' ?'class="active"':'' ?>><i class="fas fa-history"></i> Swap History</a>
  <a href="notifications.php" <?= $_cp==='notifications.php'?'class="active"':'' ?>>
    <i class="fas fa-bell"></i> Notifications
    <?php if ($_uc > 0): ?>
      <span class="badge badge-new" style="font-size:.62rem;padding:2px 7px;margin-left:auto;"><?= $_uc ?></span>
    <?php endif; ?>
  </a>
  <a href="profile.php"       <?= $_cp==='profile.php'       ?'class="active"':'' ?>><i class="fas fa-user"></i> Profile</a>

  <?php if (($_SESSION['role']??'')==='admin'): ?>
  <a href="admin.php"          <?= $_cp==='admin.php'         ?'class="active"':'' ?>><i class="fas fa-shield-alt"></i> Admin Panel</a>
  <?php endif; ?>

  <div class="sidebar-logout">
    <a href="logout.php" style="color:#C62828;"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>
</aside>

  <div class="dash-main">

    <div class="dash-card">
      <div class="dash-card-header"><h3>Incoming Requests (<?= count($incoming) ?>)</h3></div>
      <?php if (empty($incoming)): ?>
        <div class="empty"><i class="fas fa-inbox"></i><h4>No incoming requests</h4><p>When students request your books, they'll show here.</p></div>
      <?php else: ?>
        <?php foreach ($incoming as $req): ?>
          <div class="req-card">
            <div class="req-avatar"><i class="fas fa-user"></i></div>
            <div class="req-info">
              <h4><?= h($req['first_name'].' '.$req['last_name']) ?> → "<?= h($req['book_title']) ?>"</h4>
              <p><?= h($req['req_uni']) ?> · <?= date('M j, Y', strtotime($req['created_at'])) ?></p>
              <?php if ($req['message']): ?><p style="font-style:italic;color:var(--brown-500);">"<?= h($req['message']) ?>"</p><?php endif; ?>
            </div>
            
            <div style="text-align:right;">
                <span class="status <?= $statusClass[$req['status']] ?? 'status-pending' ?>" style="display:block; margin-bottom:10px; text-align:center;">
                    <?= ucfirst($req['status']) ?>
                </span>

                <?php if ($req['status'] === 'pending'): ?>
                <div class="req-actions" style="display:flex; gap:8px;">
                  <form method="POST" action="requests.php" style="margin:0;">
                    <input type="hidden" name="req_id" value="<?= $req['id'] ?>"/>
                    <button type="submit" name="req_action" value="accepted" class="btn btn-primary btn-sm" style="background:#27ae60; border:none;">
                        <i class="fas fa-check"></i> Accept
                    </button>
                  </form>
                  <form method="POST" action="requests.php" style="margin:0;">
                    <input type="hidden" name="req_id" value="<?= $req['id'] ?>"/>
                    <button type="submit" name="req_action" value="declined" class="btn btn-ghost btn-sm" style="color:#e74c3c;">
                        <i class="fas fa-times"></i> Decline
                    </button>
                  </form>
                </div>
                <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="dash-card">
      <div class="dash-card-header"><h3>My Outgoing Requests (<?= count($outgoing) ?>)</h3></div>
      <?php if (empty($outgoing)): ?>
        <div class="empty"><i class="fas fa-paper-plane"></i><h4>No outgoing requests</h4>
          <p><a href="browse.php" style="color:var(--brown-700)">Browse books</a> to send your first request.</p></div>
      <?php else: ?>
        <?php foreach ($outgoing as $req): ?>
        <div class="req-card">
          <div class="req-avatar"><i class="fas fa-book"></i></div>
          <div class="req-info">
            <h4>You requested "<?= h($req['book_title']) ?>" from <?= h($req['own_fn'].' '.$req['own_ln']) ?></h4>
            <p><?= date('M j, Y', strtotime($req['created_at'])) ?></p>
            
            <?php if($req['status'] === 'accepted'): ?>
                <a href="download-book.php?id=<?= $req['book_id'] ?>" class="btn btn-primary btn-sm" style="margin-top:8px; display:inline-flex;">
                    <i class="fas fa-download"></i> Download PDF
                </a>
            <?php endif; ?>
          </div>
          <span class="status <?= $statusClass[$req['status']] ?? 'status-pending' ?>"><?= ucfirst($req['status']) ?></span>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </div>
</div></div>

<footer>
  <div class="container">
    <div class="footer-bottom">
      <p>&copy; <?= date('Y') ?> StudySwap Hub &mdash; Made with &hearts; for Pakistani students.</p>
    </div>
  </div>
</footer>
<script src="main.js"></script>
</body></html>