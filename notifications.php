<?php
// notifications.php
require_once __DIR__ . '/db_connect.php';
requireLogin('login.php');
$uid = currentUserId();

// Mark all as read if requested
if (isset($_POST['mark_all'])) {
    $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?')->execute([$uid]);
    header('Location: notifications.php'); exit;
}

// Mark single notification as read
if (isset($_GET['read'])) {
    $nid = (int)$_GET['read'];
    $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?')
        ->execute([$nid, $uid]);
    // Redirect to the notification link if set
    $nl = $pdo->prepare('SELECT link FROM notifications WHERE id = ? AND user_id = ?');
    $nl->execute([$nid, $uid]);
    $notif = $nl->fetch();
    header('Location: ' . ($notif['link'] ?: 'notifications.php'));
    exit;
}

// Fetch all notifications for this user
$stmt = $pdo->prepare(
    'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC'
);
$stmt->execute([$uid]);
$notifs = $stmt->fetchAll();

$unread = array_sum(array_column($notifs, 'is_read') === array_map(fn() => 0, $notifs) ? [0] : []);
$unread = count(array_filter($notifs, fn($n) => !$n['is_read']));

// Icon map by type
$iconMap = [
    'swap_request'      => 'fa-exchange-alt',
    'request_accepted'  => 'fa-check-circle',
    'request_declined'  => 'fa-times-circle',
    'swap_completed'    => 'fa-handshake',
    'new_book'          => 'fa-book',
    'system'            => 'fa-bell',
];
?>
<!DOCTYPE html><html lang="en">
<head><meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Notifications — StudySwap Hub</title>
<link rel="stylesheet" href="style.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/></head>
<body>
<?php require __DIR__ . '/includes/navbar.php'; ?>
<div class="page-header"><div class="container">
  <div class="breadcrumb"><a href="dashboard.php">Dashboard</a><i class="fas fa-chevron-right"></i><span>Notifications</span></div>
  <h1>Notifications</h1>
  <p><?= $unread ?> unread notification<?= $unread !== 1 ? 's' : '' ?></p>
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
    <div class="dash-card-header">
      <h3>All Notifications (<?= count($notifs) ?>)</h3>
      <?php if ($unread > 0): ?>
      <form method="POST" action="notifications.php" style="margin:0;">
        <button type="submit" name="mark_all" class="btn btn-ghost btn-sm">
          <i class="fas fa-check-double"></i> Mark all as read
        </button>
      </form>
      <?php endif; ?>
    </div>

    <?php if (empty($notifs)): ?>
      <div class="empty">
        <i class="fas fa-bell"></i>
        <h4>No notifications yet</h4>
        <p>Notifications about swap requests and activity will appear here.</p>
      </div>
    <?php else: ?>
    <div class="notif-list">
      <?php foreach ($notifs as $n):
        $icon = $iconMap[$n['type']] ?? 'fa-bell';
        $link = $n['link'] ? 'notifications.php?read=' . $n['id'] : '#';
      ?>
      <div class="notif-item" style="<?= !$n['is_read'] ? 'background:var(--brown-50);border-radius:var(--radius-sm);padding:8px;margin:-8px;' : '' ?>">
        <div class="notif-dot <?= !$n['is_read'] ? 'unread' : '' ?>"></div>
        <div style="flex:1;">
          <a href="<?= $link ?>" style="display:flex;align-items:flex-start;gap:10px;text-decoration:none;">
            <i class="fas <?= $icon ?>" style="color:var(--brown-<?= !$n['is_read'] ? '700' : '300' ?>);margin-top:3px;flex-shrink:0;"></i>
            <div>
              <div class="notif-text" style="color:<?= !$n['is_read'] ? 'var(--ink)' : 'var(--muted)' ?>;">
                <?= h($n['message']) ?>
              </div>
              <div class="notif-time"><?= date('M j, Y · g:i A', strtotime($n['created_at'])) ?></div>
            </div>
          </a>
        </div>
        <?php if (!$n['is_read']): ?>
          <span style="width:8px;height:8px;border-radius:50%;background:var(--brown-700);flex-shrink:0;margin-top:6px;"></span>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
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
