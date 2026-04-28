<?php
// ============================================================
//  StudySwap Hub — dashboard.php
// ============================================================
require_once __DIR__ . '/db_connect.php';
requireLogin('login.php');

$uid = currentUserId();

// Flash message
$flash = flash('flash_success');

// Stats
$totalBooks   = (int)$pdo->prepare('SELECT COUNT(*) FROM books WHERE user_id = ? AND is_available = 1')->execute([$uid]) && true
              ? $pdo->prepare('SELECT COUNT(*) FROM books WHERE user_id = ?')->execute([$uid]) ? 0 : 0 : 0;

// Cleaner stats fetch
$sBooks   = $pdo->prepare('SELECT COUNT(*) FROM books   WHERE user_id = ?');  $sBooks->execute([$uid]);  $totalBooks   = (int)$sBooks->fetchColumn();
$sSwaps   = $pdo->prepare('SELECT COUNT(*) FROM swap_history WHERE giver_id = ? OR receiver_id = ?'); $sSwaps->execute([$uid,$uid]); $totalSwaps = (int)$sSwaps->fetchColumn();
$sReqs    = $pdo->prepare('SELECT COUNT(*) FROM requests WHERE (requester_id = ? OR owner_id = ?) AND status = "pending"'); $sReqs->execute([$uid,$uid]); $pendingReqs = (int)$sReqs->fetchColumn();
$sWish    = $pdo->prepare('SELECT COUNT(*) FROM wishlist WHERE user_id = ?'); $sWish->execute([$uid]); $totalWish = (int)$sWish->fetchColumn();

// Incoming requests (pending) for the dashboard
$incomingStmt = $pdo->prepare(
    'SELECT r.*, b.title AS book_title,
            u.first_name, u.last_name, u.university AS req_uni
     FROM requests r
     JOIN books b ON r.book_id = b.id
     JOIN users u ON r.requester_id = u.id
     WHERE r.owner_id = ? AND r.status = "pending"
     ORDER BY r.created_at DESC LIMIT 5'
);
$incomingStmt->execute([$uid]);
$incoming = $incomingStmt->fetchAll();

// Accepted swap requests — books I can now download
$acceptedSwapsStmt = $pdo->prepare(
    'SELECT r.id AS req_id, r.book_id, b.title, b.author, b.edition, b.image, b.pdf_file,
            u.first_name AS owner_fn, u.last_name AS owner_ln, r.created_at
     FROM requests r
     JOIN books b ON r.book_id = b.id
     JOIN users u ON r.owner_id = u.id
     WHERE r.requester_id = ? AND r.status = "accepted"
     ORDER BY r.created_at DESC'
);
$acceptedSwapsStmt->execute([$uid]);
$acceptedSwaps = $acceptedSwapsStmt->fetchAll();

// My listed books
$myBooksStmt = $pdo->prepare(
    'SELECT b.*, (SELECT COUNT(*) FROM requests WHERE book_id = b.id AND status = "pending") AS req_count
     FROM books b WHERE b.user_id = ? ORDER BY b.created_at DESC LIMIT 8'
);
$myBooksStmt->execute([$uid]);
$myBooks = $myBooksStmt->fetchAll();

// Handle request action (accept/decline) from dashboard
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['req_action'])) {
    $reqId  = (int)($_POST['req_id'] ?? 0);
    $action = $_POST['req_action'];

    if (in_array($action, ['accepted','declined']) && $reqId > 0) {
        // Verify this request belongs to current user
        $verif = $pdo->prepare('SELECT * FROM requests WHERE id = ? AND owner_id = ?');
        $verif->execute([$reqId, $uid]);
        $req = $verif->fetch();

        if ($req) {
            $pdo->prepare('UPDATE requests SET status = ? WHERE id = ?')
                ->execute([$action, $reqId]);

            // If accepted, mark book as unavailable & record swap history
            if ($action === 'accepted') {
                $pdo->prepare('UPDATE books SET is_available = 0 WHERE id = ?')
                    ->execute([$req['book_id']]);

                $pdo->prepare(
                    'INSERT INTO swap_history (request_id, giver_id, receiver_id, book_given, book_received, swap_date)
                     VALUES (?, ?, ?, ?, ?, CURDATE())'
                )->execute([
                    $reqId, $uid, $req['requester_id'],
                    $req['book_id'], $req['offer_book_id'],
                ]);

                // Update swap counts
                $pdo->prepare('UPDATE users SET total_swaps = total_swaps + 1 WHERE id IN (?,?)')
                    ->execute([$uid, $req['requester_id']]);

                sendNotification($pdo, $req['requester_id'], 'request_accepted',
                    'Your swap request was accepted! Contact the owner to arrange the exchange.',
                    'swap-history.php');
            } else {
                sendNotification($pdo, $req['requester_id'], 'request_declined',
                    'Your swap request was declined. Browse other books to find a match.',
                    'browse.php');
            }

            header('Location: dashboard.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Dashboard — StudySwap Hub</title>
<link rel="stylesheet" href="style.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body>
<?php require __DIR__ . '/includes/navbar.php'; ?>
<div class="page-header">
  <div class="container">
    <h1>Dashboard</h1>
    <p>Welcome back, <?= h($_SESSION['first_name']) ?> 👋 — here's your activity overview</p>
    <button onclick="history.back()" class="btn btn-outline" style="margin-top:10px;"><i class="fas fa-arrow-left"></i> Back</button>
  </div>
</div>

<div class="container">
  <div class="dash-wrap">
    <?php
// ============================================================
//  StudySwap Hub — includes/dashboard_sidebar.php
// ============================================================
if (!function_exists('isLoggedIn')) {
    require_once dirname(__DIR__) . '/db_connect.php';
}
$_cp = basename($_SERVER['PHP_SELF']);
$_uc = unreadCount($pdo, currentUserId());
?>
<aside class="dash-sidebar">
  <div class="sidebar-user">
    <div class="sidebar-avatar">
      <?= strtoupper(substr($_SESSION['first_name']??'U',0,1)) ?>
    </div>
    <div>
      <strong><?= h(($_SESSION['first_name']??'').' '.($_SESSION['last_name']??'')) ?></strong>
      <span><?= h($_SESSION['university']??'') ?></span>
    </div>
  </div>

  <a href="dashboard.php"     <?= $_cp==='dashboard.php'    ?'class="active"':'' ?>><i class="fas fa-th-large"></i> Overview</a>
  <a href="my-books.php"      <?= $_cp==='my-books.php'     ?'class="active"':'' ?>><i class="fas fa-book"></i> My Books</a>
  <a href="requests.php"      <?= $_cp==='requests.php'     ?'class="active"':'' ?>><i class="fas fa-exchange-alt"></i> Requests</a>
  <a href="wishlist.php"      <?= $_cp==='wishlist.php'     ?'class="active"':'' ?>><i class="fas fa-heart"></i> Wishlist</a>
  <a href="swap-history.php"  <?= $_cp==='swap-history.php' ?'class="active"':'' ?>><i class="fas fa-history"></i> Swap History</a>
  <a href="notifications.php" <?= $_cp==='notifications.php'?'class="active"':'' ?>>
    <i class="fas fa-bell"></i> Notifications
    <?php if ($_uc > 0): ?>
      <span class="badge badge-new" style="font-size:.62rem;padding:2px 7px;margin-left:auto;"><?= $_uc ?></span>
    <?php endif; ?>
  </a>
  <a href="profile.php"       <?= $_cp==='profile.php'      ?'class="active"':'' ?>><i class="fas fa-user"></i> Profile</a>

  <?php if (($_SESSION['role']??'')==='admin'): ?>
  <a href="admin.php"         <?= $_cp==='admin.php'        ?'class="active"':'' ?>><i class="fas fa-shield-alt"></i> Admin Panel</a>
  <?php endif; ?>

  <div class="sidebar-logout">
    <a href="logout.php" style="color:#C62828;"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>
</aside>

    <div class="dash-main">

      <?php if ($flash): ?>
        <div class="alert alert-success">
          <i class="fas fa-check-circle"></i> <?= h($flash) ?>
        </div>
      <?php endif; ?>

      <!-- Stats -->
      <div class="dash-card">
        <div class="dash-card-header">
          <h3>My Activity</h3>
          <a href="my-books.php" class="btn btn-outline btn-sm">View All Books</a>
        </div>
        <div class="mini-stats">
          <div class="mini-stat"><strong><?= $totalBooks ?></strong><p>Books Listed</p></div>
          <div class="mini-stat"><strong><?= $totalSwaps ?></strong><p>Swaps Done</p></div>
          <div class="mini-stat"><strong><?= $pendingReqs ?></strong><p>Pending Requests</p></div>
          <div class="mini-stat"><strong><?= $totalWish ?></strong><p>Wishlist Items</p></div>
        </div>
      </div>

      <!-- Incoming requests -->
      <div class="dash-card">
        <div class="dash-card-header">
          <h3>Incoming Requests</h3>
          <a href="requests.php" class="btn btn-outline btn-sm">View All</a>
        </div>

        <?php if (empty($incoming)): ?>
          <div class="empty">
            <i class="fas fa-exchange-alt"></i>
            <h4>No pending requests</h4>
            <p>When students request your books, they'll appear here.</p>
          </div>
        <?php else: ?>
          <?php foreach ($incoming as $req): ?>
          <form method="POST" action="dashboard.php" style="margin:0;">
            <input type="hidden" name="req_id" value="<?= $req['id'] ?>"/>
            <div class="req-card">
              <div class="req-avatar"><i class="fas fa-user"></i></div>
              <div class="req-info">
                <h4><?= h($req['first_name'] . ' ' . $req['last_name']) ?> → your "<?= h($req['book_title']) ?>"</h4>
                <p><?= h($req['req_uni']) ?> · <?= date('M j', strtotime($req['created_at'])) ?></p>
              </div>
              <span class="status status-pending">Pending</span>
              <div class="req-actions">
                <button type="submit" name="req_action" value="accepted"
                        class="btn btn-primary btn-sm">
                  <i class="fas fa-check"></i> Accept
                </button>
                <button type="submit" name="req_action" value="declined"
                        class="btn btn-ghost btn-sm">
                  <i class="fas fa-times"></i> Decline
                </button>
              </div>
            </div>
          </form>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- Accepted Swaps — Books available to download -->
      <div class="dash-card">
        <div class="dash-card-header">
          <h3><i class="fas fa-download" style="color:var(--brown-600);margin-right:6px;"></i>My Accepted Swaps — Ready to Download</h3>
          <a href="requests.php" class="btn btn-outline btn-sm">View All Requests</a>
        </div>

        <?php if (empty($acceptedSwaps)): ?>
          <div class="empty">
            <i class="fas fa-box-open"></i>
            <h4>No accepted swaps yet</h4>
            <p>Jab koi owner aapki request accept karay ga, book yahan download ke liye available hogi.</p>
          </div>
        <?php else: ?>
          <?php foreach ($acceptedSwaps as $swap): ?>
          <div class="req-card" style="align-items:center;">
            <div class="req-avatar" style="background:var(--brown-100);color:var(--brown-700);overflow:hidden;padding:0;">
              <?php if ($swap['image']): ?>
                <img src="uploads/books/<?= h($swap['image']) ?>" alt="cover"
                     style="width:48px;height:48px;object-fit:cover;border-radius:8px;"/>
              <?php else: ?>
                <i class="fas fa-book"></i>
              <?php endif; ?>
            </div>
            <div class="req-info">
              <h4><?= h($swap['title']) ?></h4>
              <p style="margin:2px 0 0;">
                <?= $swap['author'] ? h($swap['author']) : '' ?>
                <?= $swap['edition'] ? ' · ' . h($swap['edition']) : '' ?>
              </p>
              <p style="margin:2px 0 0;font-size:.82rem;color:var(--faint);">
                Owner: <?= h($swap['owner_fn'] . ' ' . $swap['owner_ln']) ?> &nbsp;·&nbsp;
                Accepted on: <?= date('M j, Y', strtotime($swap['created_at'])) ?>
              </p>
            </div>
            <div style="flex-shrink:0;">
              <?php if ($swap['pdf_file']): ?>
                <a href="download-book.php?id=<?= (int)$swap['book_id'] ?>"
                   class="btn btn-primary btn-sm"
                   style="display:inline-flex;align-items:center;gap:6px;background:var(--brown-600);">
                  <i class="fas fa-file-pdf"></i> Download PDF
                </a>
              <?php else: ?>
                <span class="badge" style="background:#f0ad4e;color:#fff;padding:6px 12px;border-radius:20px;font-size:.78rem;">
                  <i class="fas fa-clock"></i> PDF not uploaded yet
                </span>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- My books table -->
      <div class="dash-card">
        <div class="dash-card-header">
          <h3>My Listed Books</h3>
          <a href="add-book.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Book</a>
        </div>

        <?php if (empty($myBooks)): ?>
          <div class="empty">
            <i class="fas fa-book"></i>
            <h4>No books listed yet</h4>
            <p><a href="add-book.php" style="color:var(--brown-700)">List your first book</a> and start swapping!</p>
          </div>
        <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>Title</th><th>Type</th><th>Condition</th><th>Requests</th><th>Listed</th><th>Actions</th></tr>
            </thead>
            <tbody>
              <?php foreach ($myBooks as $b): ?>
              <tr>
                <td>
                  <strong><?= h($b['title']) ?></strong>
                  <?php if ($b['author']): ?>
                    <br><small style="color:var(--faint)"><?= h($b['author']) ?><?= $b['edition'] ? ' · ' . h($b['edition']) : '' ?></small>
                  <?php endif; ?>
                </td>
                <td><span class="badge <?= badgeClass($b['listing_type']) ?>"><?= badgeLabel($b['listing_type']) ?></span></td>
                <td><?= h($b['condition_val']) ?></td>
                <td><?= $b['req_count'] ?></td>
                <td><?= date('M j', strtotime($b['created_at'])) ?></td>
                <td>
                  <div style="display:flex;gap:6px;">
                    <a href="book-detail.php?id=<?= $b['id'] ?>" class="btn btn-ghost btn-sm">View</a>
                    <a href="delete-book.php?id=<?= $b['id'] ?>" class="btn btn-danger btn-sm"
                       onclick="return confirm('Remove this listing?')">Delete</a>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>

    </div><!-- end dash-main -->
  </div>
</div>

<?php
// ============================================================
//  StudySwap Hub — includes/footer.php
// ============================================================
?>
<footer>
  <div class="container">
    <div class="footer-inner">
      <div class="footer-brand">
        <a href="index.php" class="nav-logo" style="color:var(--cream)">
          <div class="logo-icon"><i class="fas fa-book-open"></i></div>
          StudySwapHub
        </a>
        <p>Pakistan's student-first platform for sharing, swapping, and discovering study resources near you.</p>
        <div class="footer-socials">
          <a href="#" class="soc-btn"><i class="fab fa-facebook-f"></i></a>
          <a href="#" class="soc-btn"><i class="fab fa-instagram"></i></a>
          <a href="#" class="soc-btn"><i class="fab fa-twitter"></i></a>
          <a href="#" class="soc-btn"><i class="fab fa-whatsapp"></i></a>
        </div>
      </div>
      <div class="footer-col">
        <h5>Platform</h5>
        <ul>
          <li><a href="browse.php">Browse Resources</a></li>
          <li><a href="add-book.php">Add a Book</a></li>
          <li><a href="requests.php">My Requests</a></li>
          <li><a href="my-books.php">My Books</a></li>
          <li><a href="wishlist.php">Wishlist</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h5>Account</h5>
        <ul>
          <li><a href="profile.php">Profile</a></li>
          <li><a href="dashboard.php">Dashboard</a></li>
          <li><a href="swap-history.php">Swap History</a></li>
          <li><a href="notifications.php">Notifications</a></li>
          <li><a href="login.php">Login</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h5>Info</h5>
        <ul>
          <li><a href="index.php#about">About Us</a></li>
          <li><a href="index.php#howitworks">How It Works</a></li>
          <li><a href="index.php#contact">Contact</a></li>
          <li><a href="#">Privacy Policy</a></li>
          <li><a href="#">Terms of Use</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <p>&copy; <?= date('Y') ?> StudySwap Hub &mdash; Made with &hearts; for Pakistani students.</p>
      <p>Pakistan &#127477;&#127472;</p>
    </div>
  </div>
</footer>
<div id="toast"></div>
<script src="main.js"></script>
</body>
</html>
