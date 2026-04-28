<?php
// my-books.php
require_once __DIR__ . '/db_connect.php';
requireLogin('login.php');
$uid = currentUserId();
$flash = flash('flash_success');

$stmt = $pdo->prepare(
    'SELECT b.*, (SELECT COUNT(*) FROM requests r WHERE r.book_id = b.id AND r.status="pending") AS req_count
     FROM books b WHERE b.user_id = ? ORDER BY b.created_at DESC'
);
$stmt->execute([$uid]);
$books = $stmt->fetchAll();
?>
<!DOCTYPE html><html lang="en">
<head><meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>My Books — StudySwap Hub</title>
<link rel="stylesheet" href="style.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/></head>
<body>
<?php require __DIR__ . '/includes/navbar.php'; ?>
<div class="page-header"><div class="container">
  <div class="breadcrumb"><a href="dashboard.php">Dashboard</a><i class="fas fa-chevron-right"></i><span>My Books</span></div>
  <h1>My Books</h1><p>Manage all resources you have listed</p>
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
  <div class="dash-main">
    <?php if ($flash): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= h($flash) ?></div><?php endif; ?>
    <div class="dash-card">
      <div class="dash-card-header">
        <h3>All Listings (<?= count($books) ?>)</h3>
        <a href="add-book.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Book</a>
      </div>
      <?php if (empty($books)): ?>
        <div class="empty"><i class="fas fa-book"></i><h4>No books yet</h4>
          <p><a href="add-book.php" style="color:var(--brown-700)">List your first book</a></p></div>
      <?php else: ?>
      <div class="table-wrap"><table>
        <thead><tr><th>Title</th><th>Type</th><th>Condition</th><th>Requests</th><th>Listed</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($books as $b): ?>
        <tr>
          <td><strong><?= h($b['title']) ?></strong><br><small style="color:var(--faint)"><?= h($b['author']) ?><?= $b['edition'] ? ' · '.$b['edition'] : '' ?></small></td>
          <td><span class="badge <?= badgeClass($b['listing_type']) ?>"><?= badgeLabel($b['listing_type']) ?><?= $b['price'] ? ' Rs.'.number_format($b['price'],0) : '' ?></span></td>
          <td><?= h($b['condition_val']) ?></td>
          <td><?= $b['req_count'] ?></td>
          <td><?= date('M j, Y', strtotime($b['created_at'])) ?></td>
          <td><div style="display:flex;gap:6px;">
            <a href="book-detail.php?id=<?= $b['id'] ?>" class="btn btn-ghost btn-sm">View</a>
            <a href="delete-book.php?id=<?= $b['id'] ?>" class="btn btn-danger btn-sm"
               onclick="return confirm('Remove this listing permanently?')">Delete</a>
          </div></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
      <?php endif; ?>
    </div>
  </div>
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
