<?php
require_once __DIR__ . '/db_connect.php';
if (isLoggedIn()) { header('Location: dashboard.php'); exit; }
$error = ''; $old_email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? '')); $password = $_POST['password'] ?? ''; $old_email = $email;
    if (empty($email) || empty($password)) { $error = 'Please enter both email and password.'; }
    else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$email]); $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id']=$user['id']; $_SESSION['first_name']=$user['first_name'];
            $_SESSION['last_name']=$user['last_name']; $_SESSION['email']=$user['email'];
            $_SESSION['university']=$user['university']; $_SESSION['role']=$user['role'];
            $redirect = $_SESSION['intended'] ?? 'dashboard.php'; unset($_SESSION['intended']);
            header('Location: '.$redirect); exit;
        } else { $error = 'Incorrect email or password. Please try again.'; }
    }
}
?>
<!DOCTYPE html><html lang="en">
<head><meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Login — StudySwap Hub</title>
<link rel="stylesheet" href="style.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head><body>
<nav class="navbar" id="navbar"><div class="container"><div class="nav-wrap">
  <a href="index.php" class="nav-logo"><div class="logo-icon"><i class="fas fa-book-open"></i></div>StudySwap<span style="color:var(--brown-400)">Hub</span></a>
  <ul class="nav-links"><li><a href="index.php">Home</a></li><li><a href="browse.php">Browse</a></li></ul>
  <div class="nav-actions"><a href="login.php" class="btn btn-primary btn-sm">Login</a><a href="register.php" class="btn btn-outline btn-sm">Sign Up</a></div>
  <button class="hamburger" id="hamburger"><i class="fas fa-bars"></i></button>
</div></div></nav>
<div class="mobile-nav" id="mobileNav"><a href="index.php">Home</a><a href="browse.php">Browse</a><a href="register.php">Sign Up</a></div>
<div class="auth-page">
  <div class="auth-box fade-in">
    <div class="auth-brand"><div class="logo-icon"><i class="fas fa-book-open"></i></div><h2>StudySwap Hub</h2></div>
    <h2 class="auth-title">Welcome back</h2>
    <p class="auth-sub">Sign in to access your account and resources.</p>
    <?php if ($error): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= h($error) ?></div><?php endif; ?>
    <?php $flash=flash('flash_success'); if($flash): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= h($flash) ?></div><?php endif; ?>
    <form method="POST" action="login.php" novalidate>
      <div class="form-group"><label>Email Address</label><input type="email" name="email" class="form-control" placeholder="you@university.edu.pk" value="<?= h($old_email) ?>" required autofocus/></div>
      <div class="form-group"><label>Password</label><input type="password" name="password" class="form-control" placeholder="••••••••" required/></div>
      <div style="display:flex;justify-content:flex-end;margin-bottom:18px;"><a href="#" style="font-size:.82rem;color:var(--brown-500);">Forgot password?</a></div>
      <button type="submit" class="btn btn-primary btn-full btn-lg"><i class="fas fa-sign-in-alt"></i> Sign In</button>
    </form>
    <p class="auth-footer" style="margin-top:20px;">Don't have an account? <a href="register.php">Sign up free</a></p>
  </div>
</div>
<div id="toast"></div><script src="main.js"></script>
</body></html>
