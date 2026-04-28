<?php
// ============================================================
//  StudySwap Hub — includes/navbar.php
//  Shared navbar — session-aware, works on Windows & Linux
// ============================================================

// Guard: db_connect must already be loaded before this file
if (!function_exists('isLoggedIn')) {
    require_once dirname(__DIR__) . '/db_connect.php';
}

$_unread      = isLoggedIn() ? unreadCount($pdo, currentUserId()) : 0;
$_currentPage = basename($_SERVER['PHP_SELF']);
?>
<!-- ══ NAVBAR ══════════════════════════════════════════════ -->
<nav class="navbar" id="navbar">
  <div class="container">
    <div class="nav-wrap">

      <a href="index.php" class="nav-logo">
        <div class="logo-icon"><i class="fas fa-book-open"></i></div>
        StudySwap<span style="color:var(--brown-400)">Hub</span>
      </a>

      <ul class="nav-links">
        <li><a href="index.php"     <?= $_currentPage==='index.php'     ?'class="active"':'' ?>>Home</a></li>
        <li><a href="browse.php"    <?= $_currentPage==='browse.php'    ?'class="active"':'' ?>>Browse</a></li>
        <li><a href="add-book.php"  <?= $_currentPage==='add-book.php'  ?'class="active"':'' ?>>Add Book</a></li>
        <li><a href="requests.php"  <?= $_currentPage==='requests.php'  ?'class="active"':'' ?>>Requests</a></li>
        <li><a href="dashboard.php" <?= $_currentPage==='dashboard.php' ?'class="active"':'' ?>>Dashboard</a></li>
      </ul>

      <div class="nav-actions">
        <?php if (isLoggedIn()): ?>
          <a href="notifications.php" class="btn btn-ghost btn-sm" style="position:relative;">
            <i class="fas fa-bell"></i>
            <?php if ($_unread > 0): ?>
              <span style="position:absolute;top:-4px;right:-4px;background:var(--brown-700);color:var(--cream);font-size:.58rem;font-weight:700;width:16px;height:16px;border-radius:50%;display:flex;align-items:center;justify-content:center;"><?= $_unread ?></span>
            <?php endif; ?>
          </a>
          <a href="profile.php" class="nav-user">
            <div class="avatar"><?= strtoupper(substr($_SESSION['first_name']??'U',0,1)) ?></div>
            <?= h($_SESSION['first_name']??'User') ?>
          </a>
          <a href="logout.php" class="btn btn-ghost btn-sm">Logout</a>
        <?php else: ?>
          <a href="login.php"    class="btn btn-ghost   btn-sm">Login</a>
          <a href="register.php" class="btn btn-primary btn-sm">Sign Up</a>
        <?php endif; ?>
      </div>

      <button class="hamburger" id="hamburger"><i class="fas fa-bars"></i></button>
    </div>
  </div>
</nav>

<div class="mobile-nav" id="mobileNav">
  <a href="index.php">Home</a>
  <a href="browse.php">Browse Books</a>
  <a href="add-book.php">Add Book</a>
  <a href="requests.php">Requests</a>
  <a href="dashboard.php">Dashboard</a>
  <?php if (isLoggedIn()): ?>
    <a href="profile.php">My Profile</a>
    <a href="notifications.php">Notifications<?= $_unread>0?" ({$_unread})":'' ?></a>
    <div class="mob-actions"><a href="logout.php" class="btn btn-ghost btn-sm">Logout</a></div>
  <?php else: ?>
    <div class="mob-actions">
      <a href="login.php"    class="btn btn-ghost   btn-sm">Login</a>
      <a href="register.php" class="btn btn-primary btn-sm">Sign Up</a>
    </div>
  <?php endif; ?>
</div>
