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
