<?php
// ============================================================
//  StudySwap Hub — add-book.php (Updated with PDF Upload)
// ============================================================
require_once __DIR__ . '/db_connect.php';
requireLogin('login.php');   // must be logged in

$errors  = [];
$old     = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── Collect inputs ────────────────────────────────────
    $old['title']         = trim($_POST['title']         ?? '');
    $old['author']        = trim($_POST['author']        ?? '');
    $old['edition']       = trim($_POST['edition']       ?? '');
    $old['category']      = trim($_POST['category']      ?? '');
    $old['condition_val'] = trim($_POST['condition_val'] ?? '');
    $old['listing_type']  = trim($_POST['listing_type']  ?? '');
    $old['price']         = trim($_POST['price']         ?? '');
    $old['swap_for']      = trim($_POST['swap_for']      ?? '');
    $old['university']    = trim($_POST['university']    ?? '');
    $old['description']   = trim($_POST['description']   ?? '');

    // ── Validate ──────────────────────────────────────────
    $validCats  = ['Engineering','Medical','Business','Science','Arts & Humanities','Law','Other'];
    $validConds = ['Like New','Good','Fair','Acceptable'];
    $validTypes = ['swap','sale','free'];

    if (empty($old['title']))                     $errors['title']        = 'Title is required.';
    if (empty($old['author']))                    $errors['author']       = 'Author is required.';
    if (!in_array($old['category'], $validCats))  $errors['category']     = 'Select a valid category.';
    if (!in_array($old['condition_val'], $validConds)) $errors['condition_val'] = 'Select a condition.';
    if (!in_array($old['listing_type'], $validTypes))  $errors['listing_type']  = 'Select a listing type.';
    if (empty($old['university']))                $errors['university']   = 'Select your university.';

    $price = null;
    if ($old['listing_type'] === 'sale') {
        if (!is_numeric($old['price']) || (float)$old['price'] < 0) {
            $errors['price'] = 'Enter a valid price (Rs. 0 or more).';
        } else {
            $price = (float) $old['price'];
        }
    }

    // ── Handle Image Upload ───────────────────────────────
    $imageName = null;
    if (!empty($_FILES['book_image']['name'])) {
        $file    = $_FILES['book_image'];
        $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
        if (!in_array($file['type'], $allowed)) {
            $errors['image'] = 'Only JPG, PNG, WebP or GIF allowed.';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $errors['image'] = 'Image must be smaller than 5 MB.';
        } else {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $imageName = uniqid('book_', true) . '.' . strtolower($ext);
            $uploadDir = __DIR__ . '/uploads/books/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            move_uploaded_file($file['tmp_name'], $uploadDir . $imageName);
        }
    }

    // ── Handle PDF Upload ─────────────────────────────────
    $pdfName = null;
    if (!empty($_FILES['book_pdf']['name'])) {
        $file = $_FILES['book_pdf'];
        if ($file['type'] !== 'application/pdf') {
            $errors['pdf'] = 'Only PDF files are allowed.';
        } elseif ($file['size'] > 15 * 1024 * 1024) {
            $errors['pdf'] = 'PDF must be smaller than 15 MB.';
        } else {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $pdfName = uniqid('doc_', true) . '.' . strtolower($ext);
            $pdfDir = __DIR__ . '/uploads/pdfs/';
            if (!is_dir($pdfDir)) mkdir($pdfDir, 0755, true);
            move_uploaded_file($file['tmp_name'], $pdfDir . $pdfName);
        }
    }

    // ── Insert into DB ────────────────────────────────────
    if (empty($errors)) {
        $stmt = $pdo->prepare(
            'INSERT INTO books 
                (user_id, title, author, edition, category, condition_val, 
                 listing_type, price, swap_for, university, description, image, pdf_file)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            currentUserId(),
            $old['title'],
            $old['author'],
            $old['edition'] ?: null,
            $old['category'],
            $old['condition_val'],
            $old['listing_type'],
            $price,
            ($old['listing_type'] === 'swap' && !empty($old['swap_for'])) ? $old['swap_for'] : null,
            $old['university'],
            $old['description'] ?: null,
            $imageName,
            $pdfName
        ]);

        $bookId = (int) $pdo->lastInsertId();
        sendNotification($pdo, currentUserId(), 'system', 'Your resource "' . $old['title'] . '" is live.', 'book-detail.php?id=' . $bookId);
        redirectWith('my-books.php', 'flash_success', 'Listed successfully!');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Add Resource — StudySwap Hub</title>
<link rel="stylesheet" href="style.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<style>
.add-layout{display:grid;grid-template-columns:1fr 320px;gap:24px;padding:32px 0}
@media(max-width:768px){.add-layout{grid-template-columns:1fr}}
.file-name-preview { font-size: 0.75rem; color: var(--brown-500); margin-top: 5px; display: block; word-break: break-all; }
</style>
</head>
<body>
<?php require __DIR__ . '/includes/navbar.php'; ?>

<div class="page-header">
  <div class="container">
    <div class="breadcrumb">
      <a href="dashboard.php">Dashboard</a><i class="fas fa-chevron-right"></i><span>Add Resource</span>
    </div>
    <h1>List a Resource</h1>
    <p>Upload books, notes, or digital PDFs</p>
  </div>
</div>

<div class="container">
  <div class="add-layout">
    <form method="POST" action="add-book.php" enctype="multipart/form-data" novalidate>
      
      <div class="dash-card">
        <?php if (!empty($errors)): ?>
          <div class="alert alert-error" style="margin-bottom:18px;">Fix the errors below.</div>
        <?php endif; ?>

        <div class="form-group">
          <label>Title *</label>
          <input type="text" name="title" class="form-control <?= isset($errors['title'])?'error':'' ?>" value="<?= h($old['title']??'') ?>" required/>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Author *</label>
            <input type="text" name="author" class="form-control" value="<?= h($old['author']??'') ?>"/>
          </div>
          <div class="form-group">
            <label>Edition</label>
            <input type="text" name="edition" class="form-control" value="<?= h($old['edition']??'') ?>"/>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Category *</label>
            <select name="category" class="form-control" required>
              <option value="">Select</option>
              <?php foreach (['Engineering','Medical','Business','Science','Arts & Humanities','Law','Other'] as $cat): ?>
                <option value="<?= $cat ?>" <?= ($old['category']??'')===$cat?'selected':'' ?>><?= $cat ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Condition *</label>
            <select name="condition_val" class="form-control" required>
              <option value="Good">Good</option>
              <option value="Like New">Like New</option>
              <option value="Fair">Fair</option>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Listing Type *</label>
            <select name="listing_type" id="listingType" class="form-control">
              <option value="swap">Swap</option>
              <option value="sale">Sale</option>
              <option value="free">Free</option>
            </select>
          </div>
          <div class="form-group" id="priceGroup" style="display:none;">
            <label>Price (Rs.)</label>
            <input type="number" name="price" class="form-control" value="<?= h($old['price']??'') ?>"/>
          </div>
        </div>

        <div class="form-group">
          <label>University *</label>
          <input type="text" name="university" class="form-control" value="<?= h($old['university']??$_SESSION['university']??'') ?>"/>
        </div>

        <div class="form-group">
          <label>Description</label>
          <textarea name="description" class="form-control" rows="3"><?= h($old['description']??'') ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary btn-full btn-lg">
          <i class="fas fa-plus-circle"></i> List This Resource
        </button>
      </div>

      <div style="display:flex;flex-direction:column;gap:20px;">
        <div class="dash-card">
          <h3 style="font-size:.9rem; margin-bottom:10px;"><i class="fas fa-image"></i> Cover Photo</h3>
          <label for="bookImageInput" class="upload-box">
            <i class="fas fa-camera"></i>
            <p>Upload Image</p>
          </label>
          <input type="file" name="book_image" id="bookImageInput" accept="image/*" style="display:none;"/>
          <img id="img-preview" src="" style="max-width:100%; margin-top:10px; border-radius:8px; display:none;"/>
        </div>

        <div class="dash-card">
          <h3 style="font-size:.9rem; margin-bottom:10px;"><i class="fas fa-file-pdf"></i> Digital PDF (Optional)</h3>
          <?php if (isset($errors['pdf'])): ?>
            <span class="field-error show"><?= h($errors['pdf']) ?></span>
          <?php endif; ?>
          <label for="bookPdfInput" class="upload-box" style="border-style: dashed; background: var(--brown-50);">
            <i class="fas fa-file-upload" style="color:var(--brown-400)"></i>
            <p>Attach PDF Notes</p>
            <span id="pdf-name" class="file-name-preview">Max 15MB</span>
          </label>
          <input type="file" name="book_pdf" id="bookPdfInput" accept="application/pdf" style="display:none;"/>
        </div>

        <div class="dash-card">
          <h4 style="font-size:.85rem; margin-bottom:8px;">Quick Tips</h4>
          <ul style="font-size:.78rem; color:var(--muted); padding-left:15px;">
            <li>Clear photos get more requests.</li>
            <li>Digital notes (PDF) are highly valued.</li>
          </ul>
        </div>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
// Toggle Price field
const lt = document.getElementById('listingType');
if (lt) lt.addEventListener('change', function () {
  document.getElementById('priceGroup').style.display = this.value === 'sale' ? '' : 'none';
});

// Image Preview
document.getElementById('bookImageInput').onchange = function(evt) {
  const [file] = this.files;
  if (file) {
    const preview = document.getElementById('img-preview');
    preview.src = URL.createObjectURL(file);
    preview.style.display = 'block';
  }
};

// PDF Name Preview
document.getElementById('bookPdfInput').onchange = function(evt) {
  const file = this.files[0];
  if (file) {
    document.getElementById('pdf-name').textContent = "Selected: " + file.name;
    document.getElementById('pdf-name').style.color = "green";
  }
};
</script>
</body>
</html>