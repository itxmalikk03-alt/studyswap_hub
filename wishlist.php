<?php
// wishlist.php
require_once __DIR__ . '/db_connect.php';
requireLogin('login.php');
$uid = currentUserId();

$stmt = $pdo->prepare(
    'SELECT b.*, w.added_at, u.first_name AS owner_fn, u.last_name AS owner_ln
     FROM wishlist w
     JOIN books b ON w.book_id = b.id
     JOIN users u ON b.user_id = u.id
     WHERE w.user_id = ?
     ORDER BY w.added_at DESC'
);
$stmt->execute([$uid]);
$items = $stmt->fetchAll();
$placeholder = 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=400&q=80';
?>
<!DOCTYPE html><html lang="en">
<head><meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Wishlist — StudySwap Hub</title>
<link rel="stylesheet" href="style.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/></head>
<body>
<?php require __DIR__ . '/includes/navbar.php'; ?>
<div class="page-header"><div class="container">
  <div class="breadcrumb"><a href="dashboard.php">Dashboard</a><i class="fas fa-chevron-right"></i><span>Wishlist</span></div>
  <h1>My Wishlist</h1><p>Books you have saved for later</p>
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
      <h3>Saved Books (<?= count($items) ?>)</h3>
      <a href="browse.php" class="btn btn-outline btn-sm">Browse More</a>
    </div>
    <?php if (empty($items)): ?>
      <div class="empty"><i class="fas fa-heart"></i><h4>Wishlist is empty</h4>
        <p>Click the ♡ on any book while browsing to save it here.</p></div>
    <?php else: ?>
    <div class="book-grid">
      <?php foreach ($items as $b): ?>
      <div class="card">
        <a href="book-detail.php?id=<?= $b['id'] ?>">
          <div class="card-img">
            <img src="<?= $b['image'] ? 'uploads/books/'.h($b['image']) : $placeholder ?>"
                 alt="<?= h($b['title']) ?>" onerror="this.src='<?= $placeholder ?>'"/>
          </div>
        </a>
        <div class="card-body">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:6px;">
            <span class="badge <?= badgeClass($b['listing_type']) ?>"><?= badgeLabel($b['listing_type']) ?></span>
            <form method="POST" action="wishlist-toggle.php" style="margin:0;">
              <input type="hidden" name="book_id" value="<?= $b['id'] ?>"/>
              <input type="hidden" name="redirect" value="wishlist.php"/>
              <button type="submit" class="wish-btn active" title="Remove from wishlist">
                <i class="fas fa-heart"></i>
              </button>
            </form>
          </div>
          <a href="book-detail.php?id=<?= $b['id'] ?>">
            <h4><?= h($b['title']) ?></h4>
            <p><?= h($b['university']) ?><?= $b['price'] ? ' · Rs.'.number_format($b['price'],0) : '' ?></p>
          </a>
        </div>
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
