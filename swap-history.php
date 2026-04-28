<?php
// swap-history.php
require_once __DIR__ . '/db_connect.php';
requireLogin('login.php');
$uid = currentUserId();

$stmt = $pdo->prepare(
    'SELECT sh.*,
            bg.title AS book_given_title,
            br.title AS book_received_title,
            giver.first_name  AS giver_fn,  giver.last_name  AS giver_ln,
            recvr.first_name  AS recvr_fn,  recvr.last_name  AS recvr_ln
     FROM swap_history sh
     JOIN books bg       ON sh.book_given    = bg.id
     LEFT JOIN books br  ON sh.book_received = br.id
     JOIN users giver    ON sh.giver_id      = giver.id
     JOIN users recvr    ON sh.receiver_id   = recvr.id
     WHERE sh.giver_id = ? OR sh.receiver_id = ?
     ORDER BY sh.swap_date DESC'
);
$stmt->execute([$uid, $uid]);
$history = $stmt->fetchAll();
?>
<!DOCTYPE html><html lang="en">
<head><meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Swap History — StudySwap Hub</title>
<link rel="stylesheet" href="style.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/></head>
<body>
<?php require __DIR__ . '/includes/navbar.php'; ?>
<div class="page-header"><div class="container">
  <div class="breadcrumb"><a href="dashboard.php">Dashboard</a><i class="fas fa-chevron-right"></i><span>Swap History</span></div>
  <h1>Swap History</h1><p>Record of all your completed book exchanges</p>
</div></div>
<div class="container"><div class="dash-wrap">
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
  <div class="dash-main"><div class="dash-card">
    <div class="dash-card-header"><h3>Completed Swaps (<?= count($history) ?>)</h3></div>
    <?php if (empty($history)): ?>
      <div class="empty"><i class="fas fa-history"></i><h4>No swaps yet</h4>
        <p>Complete a swap request to see your exchange history here.</p></div>
    <?php else: ?>
    <div class="table-wrap"><table>
      <thead><tr><th>Book Given</th><th>Book Received</th><th>With</th><th>Date</th><th>Role</th></tr></thead>
      <tbody>
      <?php foreach ($history as $s):
        $iGiver = (int)$s['giver_id'] === $uid;
        $partner = $iGiver
          ? h($s['recvr_fn'].' '.$s['recvr_ln'])
          : h($s['giver_fn'].' '.$s['giver_ln']);
      ?>
      <tr>
        <td><strong><?= h($s['book_given_title']) ?></strong></td>
        <td><?= $s['book_received_title'] ? h($s['book_received_title']) : '<span style="color:var(--faint)">Free / None</span>' ?></td>
        <td><?= $partner ?></td>
        <td><?= date('M j, Y', strtotime($s['swap_date'])) ?></td>
        <td><span class="status <?= $iGiver ? 'status-accepted' : 'status-completed' ?>">
          <?= $iGiver ? 'Gave' : 'Received' ?>
        </span></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <?php endif; ?>
  </div></div>
</div></div>
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
</body></html>
