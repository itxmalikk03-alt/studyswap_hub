<?php
// ============================================================
//  StudySwap Hub — book-detail.php
// ============================================================
require_once __DIR__ . '/db_connect.php';

$bookId = (int)($_GET['id'] ?? 0);
if ($bookId <= 0) { header('Location: browse.php'); exit; }

// Fetch book with owner info
$stmt = $pdo->prepare(
    'SELECT b.*, u.first_name, u.last_name, u.university AS owner_uni,
            u.total_swaps, u.rating, u.id AS owner_id
     FROM books b
     JOIN users u ON b.user_id = u.id
     WHERE b.id = ? AND b.is_available = 1'
);
$stmt->execute([$bookId]);
$book = $stmt->fetch();
if (!$book) { header('Location: browse.php'); exit; }

// Increment view count
$pdo->prepare('UPDATE books SET view_count = view_count + 1 WHERE id = ?')->execute([$bookId]);

// Check if current user already sent a request for this book
$existingReq = null;
if (isLoggedIn()) {
    $eq = $pdo->prepare(
        'SELECT id, status FROM requests
         WHERE book_id = ? AND requester_id = ? AND status IN ("pending","accepted")
         LIMIT 1'
    );
    $eq->execute([$bookId, currentUserId()]);
    $existingReq = $eq->fetch();
}

// Wishlist check
$inWish = false;
if (isLoggedIn()) {
    $wq = $pdo->prepare('SELECT id FROM wishlist WHERE user_id = ? AND book_id = ? LIMIT 1');
    $wq->execute([currentUserId(), $bookId]);
    $inWish = (bool) $wq->fetch();
}

// Handle swap request submission
$reqMsg = '';
$reqError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_request'])) {
    if (!isLoggedIn()) {
        $_SESSION['intended'] = 'book-detail.php?id=' . $bookId;
        header('Location: login.php');
        exit;
    }
    if (currentUserId() === (int)$book['owner_id']) {
        $reqError = 'You cannot request your own book.';
    } elseif ($existingReq) {
        $reqError = 'You already have a request for this book.';
    } else {
        $message  = trim($_POST['message'] ?? '');
        $offerBid = (int)($_POST['offer_book_id'] ?? 0) ?: null;

        $ins = $pdo->prepare(
            'INSERT INTO requests (book_id, requester_id, owner_id, offer_book_id, message)
             VALUES (?, ?, ?, ?, ?)'
        );
        $ins->execute([$bookId, currentUserId(), $book['owner_id'], $offerBid, $message ?: null]);

        // Notify the book owner
        sendNotification($pdo, (int)$book['owner_id'], 'swap_request',
            $_SESSION['first_name'] . ' ' . $_SESSION['last_name'] . ' wants to swap for your book "' . $book['title'] . '".',
            'requests.php');

        $reqMsg = 'Swap request sent successfully!';
    }
}

// User's own books for "offer in swap" dropdown
$myBooks = [];
if (isLoggedIn() && $book['listing_type'] === 'swap') {
    $mb = $pdo->prepare(
        'SELECT id, title FROM books WHERE user_id = ? AND is_available = 1 AND id != ? ORDER BY title'
    );
    $mb->execute([currentUserId(), $bookId]);
    $myBooks = $mb->fetchAll();
}

// Related books
$rel = $pdo->prepare(
    'SELECT b.id, b.title, b.listing_type, b.image, b.university
     FROM books b
     WHERE b.category = ? AND b.id != ? AND b.is_available = 1
     ORDER BY RAND() LIMIT 3'
);
$rel->execute([$book['category'], $bookId]);
$related = $rel->fetchAll();

$placeholder = 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=400&q=80';
$bookImg = $book['image'] ? 'uploads/books/' . h($book['image']) : $placeholder;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title><?= h($book['title']) ?> — StudySwap Hub</title>
<link rel="stylesheet" href="style.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body>
<?php require __DIR__ . '/includes/navbar.php'; ?>
<div class="page-header">
  <div class="container">
    <div class="breadcrumb">
      <a href="index.php">Home</a><i class="fas fa-chevron-right"></i>
      <a href="browse.php">Browse</a><i class="fas fa-chevron-right"></i>
      <span><?= h($book['title']) ?></span>
    </div>
    <h1>Book Detail</h1>
    <button onclick="history.back()" class="btn btn-outline" style="margin-top:10px;"><i class="fas fa-arrow-left"></i> Back</button>
  </div>
</div>

<div class="container">
  <div class="book-detail-wrap">

    <div class="book-detail-img">
      <img src="<?= $bookImg ?>" alt="<?= h($book['title']) ?>"
           onerror="this.src='<?= $placeholder ?>'"/>

      <?php if ($reqMsg): ?>
        <div class="alert alert-success" style="margin-top:14px;">
          <i class="fas fa-check-circle"></i> <?= h($reqMsg) ?>
        </div>
      <?php elseif ($reqError): ?>
        <div class="alert alert-error" style="margin-top:14px;">
          <i class="fas fa-exclamation-circle"></i> <?= h($reqError) ?>
        </div>
      <?php endif; ?>

      <?php if (!isLoggedIn()): ?>
        <div class="detail-actions" style="margin-top:14px;">
          <a href="login.php" class="btn btn-primary btn-lg" style="flex:1;justify-content:center;">
            <i class="fas fa-sign-in-alt"></i> Login to Request
          </a>
        </div>

      <?php elseif (currentUserId() === (int)$book['owner_id']): ?>
        <div class="alert alert-info" style="margin-top:14px;">
          <i class="fas fa-info-circle"></i> This is your listing.
          <a href="my-books.php" style="color:var(--brown-700);font-weight:600;">Manage it</a>
        </div>

      <?php elseif ($existingReq): ?>
        <div class="alert alert-info" style="margin-top:14px;">
          <i class="fas fa-check"></i> Request sent — Status: <strong><?= h($existingReq['status']) ?></strong>
        </div>

      <?php else: ?>
        
        <?php if ($book['listing_type'] === 'sale'): ?>
            <div style="margin-top:14px;">
                <a href="buy-book.php?id=<?= $bookId ?>" class="btn btn-primary btn-lg" style="width:100%; justify-content:center; background:#27ae60; border:none;">
                    <i class="fas fa-shopping-cart"></i> Buy Now (Rs. <?= number_format($book['price']) ?>)
                </a>
            </div>
        <?php endif; ?>

        <?php if ($book['listing_type'] !== 'sale'): ?>
        <form method="POST" action="book-detail.php?id=<?= $bookId ?>" style="margin-top:14px;">
          <?php if ($book['listing_type'] === 'swap' && !empty($myBooks)): ?>
          <div class="form-group">
            <label style="font-size:.82rem;color:var(--brown-600);font-weight:600;">Offer one of your books (optional)</label>
            <select name="offer_book_id" class="form-control">
              <option value="">— select a book to offer —</option>
              <?php foreach ($myBooks as $mb): ?>
                <option value="<?= $mb['id'] ?>"><?= h($mb['title']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php endif; ?>
          <div class="form-group">
            <label style="font-size:.82rem;color:var(--brown-600);font-weight:600;">Message (optional)</label>
            <textarea name="message" class="form-control" rows="3"
                      placeholder="Introduce yourself, your location on campus…"></textarea>
          </div>
          <div class="detail-actions">
            <button type="submit" name="send_request" class="btn btn-primary btn-lg"
                    style="flex:1;justify-content:center;">
              <i class="fas fa-exchange-alt"></i>
              <?= $book['listing_type'] === 'free' ? 'Request Free Copy' : 'Request Swap' ?>
            </button>
          </div>
        </form>
        <?php endif; ?>

        <form method="POST" action="wishlist-toggle.php" style="margin-top:10px;">
          <input type="hidden" name="book_id" value="<?= $bookId ?>"/>
          <input type="hidden" name="redirect" value="book-detail.php?id=<?= $bookId ?>"/>
          <button type="submit" class="btn btn-ghost" style="width:100%; justify-content:center; padding:10px;">
            <i class="<?= $inWish ? 'fas' : 'far' ?> fa-heart" style="color: #e74c3c;"></i> 
            <?= $inWish ? 'Remove from Wishlist' : 'Add to Wishlist' ?>
          </button>
        </form>
      <?php endif; ?>

      <div style="margin-top:10px;">
        <a href="browse.php" class="btn btn-ghost" style="width:100%;justify-content:center;">
          <i class="fas fa-arrow-left"></i> Back to Browse
        </a>
      </div>
    </div>

    <div>
      <span class="badge <?= badgeClass($book['listing_type']) ?>" style="margin-bottom:14px;">
        <?= badgeLabel($book['listing_type']) ?>
        <?php if ($book['listing_type'] === 'sale' && $book['price']): ?>
          — Rs. <?= number_format($book['price'], 0) ?>
        <?php endif; ?>
      </span>

      <h1 style="font-size:clamp(1.4rem,3vw,1.9rem);color:var(--brown-700);margin-bottom:6px;">
        <?= h($book['title']) ?>
      </h1>
      <p style="color:var(--muted);margin-bottom:20px;">by <?= h($book['author']) ?></p>

      <div class="book-meta">
        <?php if ($book['edition']): ?>
        <div class="book-meta-row"><strong>Edition</strong><span><?= h($book['edition']) ?></span></div>
        <?php endif; ?>
        <div class="book-meta-row">
          <strong>Condition</strong>
          <span><span class="badge badge-free" style="font-size:.72rem;"><?= h($book['condition_val']) ?></span></span>
        </div>
        <div class="book-meta-row"><strong>Category</strong><span><?= h($book['category']) ?></span></div>
        <div class="book-meta-row">
          <strong>University</strong>
          <span><i class="fas fa-map-marker-alt" style="color:var(--brown-400);margin-right:4px;"></i><?= h($book['university']) ?></span>
        </div>
        <div class="book-meta-row"><strong>Listed</strong><span><?= date('M j, Y', strtotime($book['created_at'])) ?></span></div>
        <?php if ($book['swap_for']): ?>
        <div class="book-meta-row"><strong>Looking for</strong><span><?= h($book['swap_for']) ?></span></div>
        <?php endif; ?>
        <div class="book-meta-row"><strong>Views</strong><span><?= $book['view_count'] ?> times</span></div>
      </div>

      <?php if ($book['description']): ?>
      <div style="background:var(--brown-50);border-radius:var(--radius-md);padding:16px;margin:20px 0;">
        <h4 style="font-size:.9rem;color:var(--brown-700);margin-bottom:8px;">Description</h4>
        <p style="font-size:.86rem;color:var(--muted);line-height:1.8;"><?= nl2br(h($book['description'])) ?></p>
      </div>
      <?php endif; ?>

      <?php if (!empty($book['pdf_file'])): ?>
      <div style="margin: 20px 0; padding: 15px; border: 2px dashed var(--brown-200); border-radius: var(--radius-md); background: #fff; text-align: center;">
          <h4 style="font-size: .9rem; color: var(--brown-700); margin-bottom: 10px;">
              <i class="fas fa-file-pdf" style="color: #e74c3c;"></i> Digital Copy Available
          </h4>
          
          <?php 
              // Agar free hai toh process-free-download par jaye, warna direct download-book par (jispe security check laga hai)
              $downloadUrl = ($book['listing_type'] === 'free') 
                             ? "process-free-download.php?id=" . $book['id'] 
                             : "download-book.php?id=" . $book['id'];
          ?>

          <a href="<?= $downloadUrl ?>" 
             class="btn btn-primary" 
             style="width: 100%; justify-content: center;">
             <i class="fas fa-download"></i> Get PDF Access
          </a>
          
          <p style="font-size: .7rem; color: var(--muted); margin-top: 8px;">
              <?php if($book['listing_type'] === 'free'): ?>
                  Free to download for everyone.
              <?php else: ?>
                  Access will be unlocked after approval/payment.
              <?php endif; ?>
          </p>
      </div>
      <?php endif; ?>

      <div class="owner-card">
        <div class="sidebar-avatar">
          <?= strtoupper(substr($book['first_name'], 0, 1)) ?>
        </div>
        <div style="flex:1;">
          <strong style="display:block;font-size:.9rem;color:var(--brown-700);">
            <?= h($book['first_name'] . ' ' . $book['last_name']) ?>
          </strong>
          <span style="font-size:.78rem;color:var(--muted);">
            <?= h($book['owner_uni']) ?>
            · <?= $book['total_swaps'] ?> swaps
            · ★ <?= number_format($book['rating'], 1) ?>
          </span>
        </div>
      </div>
    </div>
  </div>

  <?php if (!empty($related)): ?>
  <div style="padding:40px 0 60px;">
    <h3 style="font-size:1.1rem;color:var(--brown-700);margin-bottom:20px;">You Might Also Like</h3>
    <div class="book-grid">
      <?php foreach ($related as $r): ?>
      <a href="book-detail.php?id=<?= $r['id'] ?>" class="card">
        <div class="card-img">
          <img src="<?= $r['image'] ? 'uploads/books/' . h($r['image']) : $placeholder ?>"
               alt="<?= h($r['title']) ?>" onerror="this.src='<?= $placeholder ?>'"/>
        </div>
        <div class="card-body">
          <span class="badge <?= badgeClass($r['listing_type']) ?>"><?= badgeLabel($r['listing_type']) ?></span>
          <h4><?= h($r['title']) ?></h4>
          <p><?= h($r['university']) ?></p>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<footer>
  <div class="container">
    <div class="footer-bottom">
      <p>&copy; <?= date('Y') ?> StudySwap Hub &mdash; Made with &hearts; for Pakistani students.</p>
    </div>
  </div>
</footer>
<script src="main.js"></script>
</body>
</html>