<?php
require_once __DIR__ . '/db_connect.php';

$statBooks = (int)$pdo->query('SELECT COUNT(*) FROM books WHERE is_available = 1')->fetchColumn();
$statSwaps = (int)$pdo->query('SELECT COUNT(*) FROM swap_history')->fetchColumn();
$statUsers = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE role = "student"')->fetchColumn();
$statUnis  = (int)$pdo->query('SELECT COUNT(DISTINCT university) FROM users')->fetchColumn();

$featStmt = $pdo->prepare('SELECT b.*, u.university AS owner_uni FROM books b JOIN users u ON b.user_id = u.id WHERE b.is_available = 1 ORDER BY b.created_at DESC LIMIT 4');
$featStmt->execute();
$featured = $featStmt->fetchAll();

$placeholder = 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=400&q=80';

$contactSuccess = '';
$contactError   = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
    $cName = trim($_POST['contact_name'] ?? ''); $cEmail = trim($_POST['contact_email'] ?? '');
    $cSubject = trim($_POST['contact_subject'] ?? ''); $cMsg = trim($_POST['contact_message'] ?? '');
    if (empty($cName)||empty($cEmail)||empty($cSubject)||empty($cMsg)) { $contactError = 'Please fill in all fields.'; }
    elseif (!filter_var($cEmail, FILTER_VALIDATE_EMAIL)) { $contactError = 'Please enter a valid email address.'; }
    else { $contactSuccess = "Message sent! We'll get back to you within 24 hours."; }
}

$uniData = [
  'nust'=>['NUST Islamabad',    'https://images.unsplash.com/photo-1481627834876-b7833e8f5570?w=500&q=80','https://images.unsplash.com/photo-1456324504439-367cee3b3c32?w=500&q=80','https://images.unsplash.com/photo-1427504494785-3a9ca7044f45?w=500&q=80'],
  'fast'=>['FAST-NUCES Lahore', 'https://images.unsplash.com/photo-1507842217343-583bb7270b66?w=500&q=80','https://images.unsplash.com/photo-1531545514256-b1400bc00f31?w=500&q=80','https://images.unsplash.com/photo-1541339907198-e08756dedf3f?w=500&q=80'],
  'lums'=>['LUMS Lahore',       'https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?w=500&q=80','https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=500&q=80','https://images.unsplash.com/photo-1568667256549-094345857637?w=500&q=80'],
  'uet' =>['UET Lahore',        'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=500&q=80','https://images.unsplash.com/photo-1529390079861-591de354faf5?w=500&q=80','https://images.unsplash.com/photo-1488190211105-8b0e65b80b4e?w=500&q=80'],
  'qau' =>['QAU Islamabad',     'https://images.unsplash.com/photo-1497633762265-9d179a990aa6?w=500&q=80','https://images.unsplash.com/photo-1456324504439-367cee3b3c32?w=500&q=80','https://images.unsplash.com/photo-1481627834876-b7833e8f5570?w=500&q=80'],
];
$cntBook = $pdo->prepare('SELECT COUNT(*) FROM books b JOIN users u ON b.user_id=u.id WHERE u.university LIKE ? AND b.is_available=1');
$cntUser = $pdo->prepare('SELECT COUNT(*) FROM users WHERE university LIKE ? AND role="student"');
$catCnt  = $pdo->prepare('SELECT COUNT(*) FROM books WHERE category = ? AND is_available = 1');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>StudySwap Hub — Exchange Books &amp; Study Resources</title>
<link rel="stylesheet" href="style.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body>
<?php require __DIR__ . '/includes/navbar.php'; ?>

<section class="hero" id="home">
  <div class="container">
    <div class="hero-inner">
      <div>
        <div class="hero-label"><i class="fas fa-graduation-cap"></i> Pakistan's Student Book Exchange</div>
        <h1>Share, Swap &amp;<br><em>Save</em> on Study<br>Resources</h1>
        <p class="hero-desc">Connect with students across universities. Swap books, share notes, and save money every semester — all in one clean platform.</p>
        <form class="hero-search search-wrap" action="browse.php" method="GET">
          <input type="text" name="q" placeholder="Search books, notes, past papers…" autocomplete="off"/>
          <button type="submit"><i class="fas fa-search"></i> Search</button>
        </form>
        <div class="hero-cta">
          <a href="browse.php"   class="btn btn-primary btn-lg"><i class="fas fa-compass"></i> Browse Resources</a>
          <a href="add-book.php" class="btn btn-outline  btn-lg"><i class="fas fa-plus"></i> Add Resource</a>
        </div>
        <div class="hero-stats">
          <div class="hero-stat"><strong><?= number_format($statBooks) ?>+</strong><p>Resources Listed</p></div>
          <div class="hero-stat"><strong><?= number_format($statSwaps) ?>+</strong><p>Swaps Done</p></div>
          <div class="hero-stat"><strong><?= number_format($statUsers) ?>+</strong><p>Students</p></div>
        </div>
      </div>
      <div class="hero-visual">
        <img src="https://images.unsplash.com/photo-1507842217343-583bb7270b66?w=700&q=85" alt="Library books"/>
        <div class="hero-pill hero-pill-1">
          <div class="pill-icon pi-brown"><i class="fas fa-book"></i></div>
          <div><strong><?= number_format($statBooks) ?>+ Books</strong><span>Available to swap</span></div>
        </div>
        <div class="hero-pill hero-pill-2">
          <div class="pill-icon pi-green"><i class="fas fa-users"></i></div>
          <div><strong><?= number_format($statUsers) ?> Students</strong><span>Joined so far</span></div>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="stats-bar">
  <div class="container">
    <div class="stats-grid">
      <div class="stat-item fade-up"><strong><?= number_format($statBooks) ?>+</strong><p>Books &amp; Notes Listed</p></div>
      <div class="stat-item fade-up"><strong><?= number_format($statSwaps) ?>+</strong><p>Successful Swaps</p></div>
      <div class="stat-item fade-up"><strong><?= $statUnis ?></strong><p>Universities</p></div>
      <div class="stat-item fade-up"><strong><?= number_format($statUsers) ?>+</strong><p>Students Joined</p></div>
    </div>
  </div>
</div>

<section class="section" id="featured">
  <div class="container">
    <div class="section-header" style="display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:12px;">
      <div><p class="eyebrow">Popular Right Now</p><h2 class="section-title">Featured Resources</h2></div>
      <a href="browse.php" class="btn btn-outline btn-sm">View All <i class="fas fa-arrow-right"></i></a>
    </div>
    <?php if (empty($featured)): ?>
      <div class="empty"><i class="fas fa-book-open"></i><h4>No books listed yet</h4><p><a href="add-book.php" style="color:var(--brown-700)">Be the first to add one!</a></p></div>
    <?php else: ?>
    <div class="featured-grid">
      <?php foreach ($featured as $b): $img = $b['image'] ? 'uploads/books/'.h($b['image']) : $placeholder; ?>
      <a href="book-detail.php?id=<?= $b['id'] ?>" class="card fade-up">
        <div class="card-img"><img src="<?= $img ?>" alt="<?= h($b['title']) ?>" onerror="this.src='<?= $placeholder ?>'"/></div>
        <div class="card-body">
          <span class="badge <?= badgeClass($b['listing_type']) ?>"><?= badgeLabel($b['listing_type']) ?><?= $b['listing_type']==='sale'&&$b['price'] ? ' · Rs.'.number_format($b['price'],0) : '' ?></span>
          <h4><?= h($b['title']) ?></h4>
          <p><?= h($b['author']) ?><?= $b['edition'] ? ' · '.h($b['edition']).' Ed' : '' ?></p>
          <div class="card-meta"><span class="card-loc"><i class="fas fa-map-marker-alt"></i> <?= h($b['university']) ?></span></div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<section class="section" style="background:var(--brown-50);" id="categories">
  <div class="container">
    <div class="section-header center">
      <p class="eyebrow">What Are You Looking For?</p>
      <h2 class="section-title">Browse by Category</h2>
      <p class="section-sub">Find exactly what you need — from textbooks to notes and past papers.</p>
    </div>
    <div class="cat-grid">
      <?php foreach ([['Engineering','books','fa-book'],['Medical','notes','fa-heartbeat'],['Business','pdfs','fa-chart-line'],['Science','papers','fa-flask']] as [$cat,$cls,$icon]):
        $catCnt->execute([$cat]); $n = (int)$catCnt->fetchColumn(); ?>
      <a href="browse.php?category=<?= urlencode($cat) ?>" class="cat-card fade-up">
        <div class="cat-icon <?= $cls ?>"><i class="fas <?= $icon ?>"></i></div>
        <h4><?= $cat ?></h4>
        <span class="cat-count"><?= $n ?> items</span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section uni-section" id="universities">
  <div class="container">
    <div class="section-header center">
      <p class="eyebrow">Find Books Near You</p>
      <h2 class="section-title">Resources by University</h2>
      <p class="section-sub">Select your university to see resources from students near you.</p>
    </div>
    <div class="uni-tabs">
      <?php foreach ($uniData as $key => $d): ?>
      <button class="uni-tab" data-uni="<?= $key ?>"><?= $d[0] ?></button>
      <?php endforeach; ?>
    </div>
    <?php foreach ($uniData as $key => $d):
      $cntBook->execute(['%'.$d[0].'%']); $bc = (int)$cntBook->fetchColumn();
      $cntUser->execute(['%'.$d[0].'%']); $uc = (int)$cntUser->fetchColumn();
    ?>
    <div class="uni-panel" data-uni="<?= $key ?>">
      <div><img class="uni-img" src="<?= $d[1] ?>" alt="Library"/><p class="uni-img-cap"><i class="fas fa-book"></i> <?= $bc ?> books · <?= h($d[0]) ?></p></div>
      <div><img class="uni-img" src="<?= $d[2] ?>" alt="Students"/><p class="uni-img-cap"><i class="fas fa-users"></i> <?= $uc ?> active students</p></div>
      <div><img class="uni-img" src="<?= $d[3] ?>" alt="Campus"/><p class="uni-img-cap"><i class="fas fa-map-marker-alt"></i> <?= h($d[0]) ?> <a href="browse.php?uni=<?= urlencode($key) ?>" style="margin-left:8px;color:var(--brown-700);font-weight:600;font-size:.78rem;">Browse books →</a></p></div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="section steps-section" id="howitworks">
  <div class="container">
    <div class="section-header center">
      <p class="eyebrow">Simple Process</p>
      <h2 class="section-title">How It Works</h2>
      <p class="section-sub">Three easy steps to start exchanging study resources with students near you.</p>
    </div>
    <div class="steps-grid">
      <div class="step-card fade-up"><div class="step-num">1</div><h4>Upload Your Resource</h4><p>List books, notes, or PDFs. Set it as a swap, for sale, or free download for other students.</p></div>
      <div class="step-card fade-up"><div class="step-num">2</div><h4>Request a Swap</h4><p>Browse resources from students nearby. Send a swap request or make an offer in one click.</p></div>
      <div class="step-card fade-up"><div class="step-num">3</div><h4>Exchange &amp; Save</h4><p>Meet on campus or arrange delivery. Complete the exchange and save money every semester.</p></div>
    </div>
  </div>
</section>

<section class="section" id="about">
  <div class="container">
    <div class="about-inner">
      <div class="about-img"><img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=600&q=85" alt="Students collaborating"/></div>
      <div>
        <p class="eyebrow">About Us</p>
        <h2 class="section-title">Built for Students,<br>by Students</h2>
        <p class="section-sub">StudySwap Hub was created with one goal — make education more affordable and accessible for every student in Pakistan by enabling peer-to-peer resource sharing.</p>
        <div class="about-points">
          <div class="about-point"><i class="fas fa-wallet"></i><div><h5>Save Money</h5><p>Swap instead of buying. Download free notes. Cut your study costs by up to 80%.</p></div></div>
          <div class="about-point"><i class="fas fa-leaf"></i><div><h5>Eco-Friendly</h5><p>Reuse books instead of printing. Reduce paper waste one swap at a time.</p></div></div>
          <div class="about-point"><i class="fas fa-shield-alt"></i><div><h5>Safe &amp; Trusted</h5><p>Verified student profiles, ratings, and secure exchanges you can rely on.</p></div></div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section contact-section" id="contact">
  <div class="container">
    <div class="contact-inner">
      <div class="contact-info">
        <p class="eyebrow">Get In Touch</p>
        <h2 class="section-title">Contact Us</h2>
        <p>Have a question or suggestion? We'd love to hear from you.</p>
        <div class="contact-items">
          <div class="contact-item"><i class="fas fa-envelope"></i><span>hello@studyswaphub.pk</span></div>
          <div class="contact-item"><i class="fas fa-phone"></i><span>+92-300-1234567</span></div>
          <div class="contact-item"><i class="fas fa-map-marker-alt"></i><span>Islamabad, Pakistan</span></div>
        </div>
      </div>
      <div class="contact-form-card">
        <h3 style="font-size:1.1rem;color:var(--brown-700);margin-bottom:20px;">Send a Message</h3>
        <?php if ($contactSuccess): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= h($contactSuccess) ?></div><?php endif; ?>
        <?php if ($contactError):   ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= h($contactError) ?></div><?php endif; ?>
        <form method="POST" action="index.php#contact" novalidate>
          <div class="form-row">
            <div class="form-group"><label>Your Name</label><input type="text" name="contact_name" class="form-control" placeholder="Ahmed Khan" required/></div>
            <div class="form-group"><label>Email</label><input type="email" name="contact_email" class="form-control" placeholder="you@example.com" required/></div>
          </div>
          <div class="form-group"><label>Subject</label><input type="text" name="contact_subject" class="form-control" placeholder="How can we help?" required/></div>
          <div class="form-group"><label>Message</label><textarea name="contact_message" class="form-control" placeholder="Write your message here…" required></textarea></div>
          <button type="submit" name="contact_submit" class="btn btn-primary btn-full"><i class="fas fa-paper-plane"></i> Send Message</button>
        </form>
      </div>
    </div>
  </div>
</section>

<section class="cta-section" id="cta">
  <div class="container">
    <p class="eyebrow" style="color:rgba(253,249,246,.5);">Join Today — It's Free</p>
    <h2>Start Swapping Study Resources</h2>
    <p>Thousands of students are already saving money every semester. Join them today.</p>
    <div class="cta-btns">
      <?php if (isLoggedIn()): ?>
        <a href="add-book.php" class="btn btn-cream btn-lg"><i class="fas fa-upload"></i> Upload a Resource</a>
        <a href="browse.php"   class="btn btn-outline btn-lg" style="border-color:rgba(253,249,246,.4);color:var(--cream)"><i class="fas fa-compass"></i> Browse Books</a>
      <?php else: ?>
        <a href="register.php" class="btn btn-cream btn-lg"><i class="fas fa-user-plus"></i> Create Free Account</a>
        <a href="browse.php"   class="btn btn-outline btn-lg" style="border-color:rgba(253,249,246,.4);color:var(--cream)"><i class="fas fa-compass"></i> Browse Books</a>
      <?php endif; ?>
    </div>
    <!-- Admin Panel button — always visible for easy admin access -->
    <p style="margin-top:22px;">
      <a href="admin.php"
         style="display:inline-flex;align-items:center;gap:7px;background:rgba(255,255,255,0.15);color:var(--cream);border:1.5px solid rgba(255,255,255,0.35);padding:9px 22px;border-radius:50px;font-size:.85rem;font-weight:600;text-decoration:none;transition:background .2s;"
         onmouseover="this.style.background='rgba(255,255,255,0.25)'"
         onmouseout="this.style.background='rgba(255,255,255,0.15)'">
        <i class="fas fa-shield-alt"></i> Admin Panel
      </a>
    </p>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
</body>
</html>