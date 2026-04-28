<?php
// ============================================================
//  StudySwap Hub — browse.php
//  Dynamically fetches books with search + filter support
// ============================================================
require_once __DIR__ . '/db_connect.php';

// ── Read filter params ────────────────────────────────────────
$search   = trim($_GET['q']        ?? '');
$category = trim($_GET['category'] ?? '');
$type     = trim($_GET['type']     ?? '');
$uni      = trim($_GET['uni']      ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 12;
$offset   = ($page - 1) * $perPage;

// ── Build query ───────────────────────────────────────────────
$where  = ['b.is_available = 1'];
$params = [];

if ($search !== '') {
    $where[]  = '(b.title LIKE ? OR b.author LIKE ? OR b.description LIKE ?)';
    $like     = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
if ($category !== '') {
    $where[]  = 'b.category = ?';
    $params[] = $category;
}
if ($type !== '') {
    $where[]  = 'b.listing_type = ?';
    $params[] = $type;
}
if ($uni !== '') {
    $where[]  = 'b.university LIKE ?';
    $params[] = '%' . $uni . '%';
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Count total for pagination
$countStmt = $pdo->prepare(
    "SELECT COUNT(*) FROM books b $whereSQL"
);
$countStmt->execute($params);
$total     = (int) $countStmt->fetchColumn();
$totalPages = (int) ceil($total / $perPage);

// Fetch books
$stmt = $pdo->prepare(
    "SELECT b.*, u.first_name, u.last_name, u.university AS user_uni
     FROM books b
     JOIN users u ON b.user_id = u.id
     $whereSQL
     ORDER BY b.created_at DESC
     LIMIT ? OFFSET ?"
);
$params[] = $perPage;
$params[] = $offset;
$stmt->execute($params);
$books = $stmt->fetchAll();

// Wishlist set (for logged-in users — show filled heart)
$wishlistSet = [];
if (isLoggedIn()) {
    $ws = $pdo->prepare('SELECT book_id FROM wishlist WHERE user_id = ?');
    $ws->execute([currentUserId()]);
    $wishlistSet = array_column($ws->fetchAll(), 'book_id');
}

// Book placeholder image
$placeholder = 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=400&q=80';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Browse Resources — StudySwap Hub</title>
<link rel="stylesheet" href="style.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<style>
    .browse-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:20px}
    /* PDF Indicator Styling */
    .card-img { position: relative; }
    .pdf-indicator {
        position: absolute;
        top: 10px;
        left: 10px;
        background: rgba(231, 76, 60, 0.9);
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.7rem;
        font-weight: bold;
        z-index: 2;
        display: flex;
        align-items: center;
        gap: 4px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
</style>
</head>
<body>
<?php require __DIR__ . '/includes/navbar.php'; ?>
<div class="page-header">
  <div class="container">
    <div class="breadcrumb">
      <a href="index.php">Home</a><i class="fas fa-chevron-right"></i><span>Browse</span>
    </div>
    <h1>Browse Resources</h1>
    <p>Find books, notes, and past papers from students near you</p>
  </div>
</div>

<section class="section-sm">
  <div class="container">

    <form method="GET" action="browse.php" class="filter-bar">
      <div class="form-group" style="flex:2;min-width:200px;">
        <label>Search</label>
        <div class="search-wrap" style="border-radius:var(--radius-md);padding:5px 10px 5px 14px;">
          <i class="fas fa-search" style="color:var(--faint);margin-right:8px;"></i>
          <input type="text" name="q" id="browseSearch"
                 placeholder="Title, author, subject…"
                 value="<?= h($search) ?>"
                 style="border:none;outline:none;background:transparent;width:100%;font-size:.88rem;color:var(--ink);"/>
        </div>
      </div>
      <div class="form-group">
        <label>Category</label>
        <select name="category" id="catFilter" class="form-control">
          <option value="">All Categories</option>
          <?php foreach (['Engineering','Medical','Business','Science','Arts & Humanities','Law','Other'] as $cat): ?>
            <option value="<?= h($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= h($cat) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Type</label>
        <select name="type" id="typeFilter" class="form-control">
          <option value="">All Types</option>
          <option value="swap" <?= $type === 'swap' ? 'selected' : '' ?>>Swap</option>
          <option value="free" <?= $type === 'free' ? 'selected' : '' ?>>Free</option>
          <option value="sale" <?= $type === 'sale' ? 'selected' : '' ?>>For Sale</option>
        </select>
      </div>
      <div class="form-group">
        <label>University</label>
        <select name="uni" id="uniFilter" class="form-control">
          <option value="">All Universities</option>
          <?php foreach (['NUST','FAST','LUMS','UET','QAU','IBA'] as $u): ?>
            <option value="<?= $u ?>" <?= stripos($uni, $u) !== false ? 'selected' : '' ?>><?= $u ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn btn-primary btn-sm" style="margin-top:22px;">
        <i class="fas fa-filter"></i> Filter
      </button>
      <?php if ($search || $category || $type || $uni): ?>
        <a href="browse.php" class="btn btn-ghost btn-sm" style="margin-top:22px;">
          <i class="fas fa-times"></i> Clear
        </a>
      <?php endif; ?>
    </form>

    <p style="font-size:.84rem;color:var(--muted);margin-bottom:20px;">
      Showing <strong><?= $total ?></strong> resource<?= $total !== 1 ? 's' : '' ?>
    </p>

    <?php if (empty($books)): ?>
      <div class="empty">
        <i class="fas fa-book-open"></i>
        <h4>No resources found</h4>
        <p>Try adjusting your search or filters, or <a href="add-book.php" style="color:var(--brown-700)">add the first one</a>.</p>
      </div>
    <?php else: ?>
    <div class="browse-grid">
      <?php foreach ($books as $book):
        $img = $book['image']
            ? 'uploads/books/' . h($book['image'])
            : $placeholder;
        $inWish = in_array($book['id'], $wishlistSet);
      ?>
      <div class="book-card-wrap fade-up">
        <div class="card">
          <a href="book-detail.php?id=<?= $book['id'] ?>">
            <div class="card-img">
              <?php if (!empty($book['pdf_file'])): ?>
                <span class="pdf-indicator" title="Digital PDF Available">
                    <i class="fas fa-file-pdf"></i> PDF
                </span>
              <?php endif; ?>
              
              <img src="<?= $img ?>" alt="<?= h($book['title']) ?>"
                   onerror="this.src='<?= $placeholder ?>'"/>
            </div>
          </a>
          <div class="card-body">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:6px;">
              <span class="badge <?= badgeClass($book['listing_type']) ?>">
                <?= badgeLabel($book['listing_type']) ?>
                <?php if ($book['listing_type'] === 'sale' && $book['price']): ?>
                  · Rs.<?= number_format($book['price'], 0) ?>
                <?php endif; ?>
              </span>
              <?php if (isLoggedIn()): ?>
              <form method="POST" action="wishlist-toggle.php" style="margin:0;">
                <input type="hidden" name="book_id" value="<?= $book['id'] ?>"/>
                <input type="hidden" name="redirect" value="<?= h($_SERVER['REQUEST_URI']) ?>"/>
                <button type="submit" class="wish-btn <?= $inWish ? 'active' : '' ?>"
                        title="<?= $inWish ? 'Remove from' : 'Add to' ?> wishlist">
                  <i class="<?= $inWish ? 'fas' : 'far' ?> fa-heart"></i>
                </button>
              </form>
              <?php endif; ?>
            </div>
            <a href="book-detail.php?id=<?= $book['id'] ?>">
              <h4><?= h($book['title']) ?></h4>
              <p><?= h($book['author']) ?><?= $book['edition'] ? ' · ' . h($book['edition']) . ' Ed' : '' ?></p>
              <div class="card-meta">
                <span class="card-loc"><i class="fas fa-map-marker-alt"></i> <?= h($book['university']) ?></span>
              </div>
            </a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <div style="display:flex;justify-content:center;gap:8px;margin-top:36px;flex-wrap:wrap;">
      <?php for ($p = 1; $p <= $totalPages; $p++):
        $params_p = array_merge($_GET, ['page' => $p]);
        $url = 'browse.php?' . http_build_query($params_p);
      ?>
        <a href="<?= $url ?>"
           class="btn <?= $p === $page ? 'btn-primary' : 'btn-ghost' ?> btn-sm">
          <?= $p ?>
        </a>
      <?php endfor; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>

  </div>
</section>

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