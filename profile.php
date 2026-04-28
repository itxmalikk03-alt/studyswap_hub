<?php
// ============================================================
//  StudySwap Hub — profile.php
// ============================================================
require_once __DIR__ . '/db_connect.php';
requireLogin('login.php');

$uid = currentUserId();

// Handle profile update
$updateMsg = '';
$updateErr = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fn  = trim($_POST['first_name'] ?? '');
    $ln  = trim($_POST['last_name']  ?? '');
    $uni = trim($_POST['university'] ?? '');
    $bio = trim($_POST['bio']        ?? '');

    if (empty($fn) || empty($ln) || empty($uni)) {
        $updateErr = 'Name and university are required.';
    } else {
        $pdo->prepare(
            'UPDATE users SET first_name=?, last_name=?, university=?, bio=? WHERE id=?'
        )->execute([$fn, $ln, $uni, $bio ?: null, $uid]);

        // Refresh session
        $_SESSION['first_name'] = $fn;
        $_SESSION['last_name']  = $ln;
        $_SESSION['university'] = $uni;
        $updateMsg = 'Profile updated successfully!';
    }
}

// Fetch full user
$uStmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$uStmt->execute([$uid]);
$user = $uStmt->fetch();

// User's books
$booksStmt = $pdo->prepare('SELECT * FROM books WHERE user_id = ? ORDER BY created_at DESC');
$booksStmt->execute([$uid]);
$books = $booksStmt->fetchAll();

// Reviews received
$revStmt = $pdo->prepare(
    'SELECT rv.*, u.first_name, u.last_name
     FROM reviews rv JOIN users u ON rv.reviewer_id = u.id
     WHERE rv.reviewee_id = ? ORDER BY rv.created_at DESC LIMIT 10'
);
$revStmt->execute([$uid]);
$reviews = $revStmt->fetchAll();

$placeholder = 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=400&q=80';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>My Profile — StudySwap Hub</title>
<link rel="stylesheet" href="style.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body>
<?php require __DIR__ . '/includes/navbar.php'; ?>
<div class="container" style="padding-top:28px;padding-bottom:60px;">
  <div style="margin-bottom:12px;"><button onclick="history.back()" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</button></div>
  <div class="profile-tabs">
    <button class="profile-tab" data-tab="tab-books">My Books</button>
    <button class="profile-tab" data-tab="tab-reviews">Reviews</button>
    <button class="profile-tab" data-tab="tab-settings">Settings</button>
  </div>

  <!-- Books -->
  <div class="tab-panel" id="tab-books">
    <?php if (empty($books)): ?>
      <div class="empty"><i class="fas fa-book"></i><h4>No books yet</h4>
        <p><a href="add-book.php" style="color:var(--brown-700)">List your first book</a></p></div>
    <?php else: ?>
    <div class="book-grid">
      <?php foreach ($books as $b): ?>
      <a href="book-detail.php?id=<?= $b['id'] ?>" class="card">
        <div class="card-img">
          <img src="<?= $b['image'] ? 'uploads/books/' . h($b['image']) : $placeholder ?>"
               alt="<?= h($b['title']) ?>" onerror="this.src='<?= $placeholder ?>'"/>
        </div>
        <div class="card-body">
          <span class="badge <?= badgeClass($b['listing_type']) ?>"><?= badgeLabel($b['listing_type']) ?></span>
          <h4><?= h($b['title']) ?></h4>
          <p><?= h($b['condition_val']) ?></p>
        </div>
      </a>
      <?php endforeach; ?>
      <a href="add-book.php" class="card" style="display:flex;align-items:center;justify-content:center;min-height:240px;border-style:dashed;">
        <div style="text-align:center;color:var(--muted);">
          <i class="fas fa-plus" style="font-size:1.8rem;color:var(--brown-100);display:block;margin-bottom:8px;"></i>
          Add a book
        </div>
      </a>
    </div>
    <?php endif; ?>
  </div>

  <!-- Reviews -->
  <div class="tab-panel" id="tab-reviews">
    <?php if (empty($reviews)): ?>
      <div class="empty"><i class="fas fa-star"></i><h4>No reviews yet</h4>
        <p>Complete swaps to receive reviews from other students.</p></div>
    <?php else: ?>
    <div style="max-width:560px;display:flex;flex-direction:column;gap:14px;">
      <?php foreach ($reviews as $rv): ?>
      <div class="dash-card">
        <div style="display:flex;gap:12px;align-items:center;margin-bottom:10px;">
          <div class="sidebar-avatar" style="width:38px;height:38px;font-size:.9rem;">
            <?= strtoupper(substr($rv['first_name'], 0, 1)) ?>
          </div>
          <div>
            <strong style="font-size:.88rem;color:var(--brown-700);">
              <?= h($rv['first_name'] . ' ' . $rv['last_name']) ?>
            </strong>
            <div style="color:#F9A825;font-size:.8rem;"><?= str_repeat('★', $rv['rating']) ?></div>
          </div>
          <span style="font-size:.74rem;color:var(--faint);margin-left:auto;">
            <?= date('M j, Y', strtotime($rv['created_at'])) ?>
          </span>
        </div>
        <?php if ($rv['comment']): ?>
          <p style="font-size:.84rem;color:var(--muted);"><?= h($rv['comment']) ?></p>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Settings -->
  <div class="tab-panel" id="tab-settings">
    <div class="dash-card" style="max-width:560px;">
      <?php if ($updateMsg): ?>
        <div class="alert alert-success" style="margin-bottom:16px;">
          <i class="fas fa-check-circle"></i> <?= h($updateMsg) ?>
        </div>
      <?php endif; ?>
      <?php if ($updateErr): ?>
        <div class="alert alert-error" style="margin-bottom:16px;">
          <i class="fas fa-exclamation-circle"></i> <?= h($updateErr) ?>
        </div>
      <?php endif; ?>
      <form method="POST" action="profile.php">
        <div class="form-row">
          <div class="form-group">
            <label>First Name</label>
            <input type="text" name="first_name" class="form-control" value="<?= h($user['first_name']) ?>" required/>
          </div>
          <div class="form-group">
            <label>Last Name</label>
            <input type="text" name="last_name" class="form-control" value="<?= h($user['last_name']) ?>" required/>
          </div>
        </div>
        <div class="form-group">
          <label>Email <span style="color:var(--faint)">(cannot change)</span></label>
          <input type="email" class="form-control" value="<?= h($user['email']) ?>" disabled/>
        </div>
        <div class="form-group">
          <label>University</label>
          <select name="university" class="form-control" required>
            <?php foreach (['NUST Islamabad','FAST-NUCES Lahore','LUMS Lahore','UET Lahore','QAU Islamabad','IBA Karachi','Other'] as $u): ?>
              <option value="<?= h($u) ?>" <?= $user['university'] === $u ? 'selected' : '' ?>><?= h($u) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Bio</label>
          <textarea name="bio" class="form-control"><?= h($user['bio'] ?? '') ?></textarea>
        </div>
        <button type="submit" name="update_profile" class="btn btn-primary">
          <i class="fas fa-save"></i> Save Changes
        </button>
      </form>
    </div>
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
