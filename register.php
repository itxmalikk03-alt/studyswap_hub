<?php
require_once __DIR__ . '/db_connect.php';
if (isLoggedIn()) { header('Location: dashboard.php'); exit; }
$errors = []; $old = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = ['first_name'=>trim($_POST['first_name']??''),'last_name'=>trim($_POST['last_name']??''),'email'=>strtolower(trim($_POST['email']??'')),'university'=>trim($_POST['university']??''),'student_id'=>trim($_POST['student_id']??'')];
    $password = $_POST['password']??''; $password2 = $_POST['password2']??'';
    if (empty($old['first_name'])) $errors['first_name']='First name is required.';
    if (empty($old['last_name']))  $errors['last_name'] ='Last name is required.';
    if (!filter_var($old['email'],FILTER_VALIDATE_EMAIL)) $errors['email']='Enter a valid email.';
    if (empty($old['university'])) $errors['university']='Please select your university.';
    if (strlen($password)<8)       $errors['password']  ='Password must be at least 8 characters.';
    if ($password!==$password2)    $errors['password2'] ='Passwords do not match.';
    if (empty($_POST['terms']??''))$errors['terms']      ='You must accept the terms.';
    if (empty($errors['email'])) {
        $chk=$pdo->prepare('SELECT id FROM users WHERE email = ?'); $chk->execute([$old['email']]);
        if ($chk->fetch()) $errors['email']='This email is already registered.';
    }
    if (empty($errors)) {
        $hash=$password_hash=$hash=password_hash($password,PASSWORD_BCRYPT,['cost'=>12]);
        $pdo->prepare('INSERT INTO users (first_name,last_name,email,password_hash,university,student_id) VALUES (?,?,?,?,?,?)')->execute([$old['first_name'],$old['last_name'],$old['email'],$hash,$old['university'],$old['student_id']?:null]);
        $uid=(int)$pdo->lastInsertId();
        sendNotification($pdo,$uid,'system','Welcome to StudySwap Hub! Start browsing or list your first resource.','browse.php');
        $_SESSION['user_id']=$uid; $_SESSION['first_name']=$old['first_name']; $_SESSION['last_name']=$old['last_name']; $_SESSION['email']=$old['email']; $_SESSION['university']=$old['university']; $_SESSION['role']='student';
        redirectWith('dashboard.php','flash_success','Account created! Welcome to StudySwap Hub 🎉');
    }
}
$unis=['NUST Islamabad','FAST-NUCES Lahore','LUMS Lahore','UET Lahore','QAU Islamabad','IBA Karachi','Other'];
?>
<!DOCTYPE html><html lang="en">
<head><meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Register — StudySwap Hub</title>
<link rel="stylesheet" href="style.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head><body>
<nav class="navbar" id="navbar"><div class="container"><div class="nav-wrap">
  <a href="index.php" class="nav-logo"><div class="logo-icon"><i class="fas fa-book-open"></i></div>StudySwap<span style="color:var(--brown-400)">Hub</span></a>
  <ul class="nav-links"><li><a href="index.php">Home</a></li><li><a href="browse.php">Browse</a></li></ul>
  <div class="nav-actions"><a href="login.php" class="btn btn-ghost btn-sm">Login</a><a href="register.php" class="btn btn-primary btn-sm">Sign Up</a></div>
  <button class="hamburger" id="hamburger"><i class="fas fa-bars"></i></button>
</div></div></nav>
<div class="mobile-nav" id="mobileNav"><a href="index.php">Home</a><a href="browse.php">Browse</a><a href="login.php">Login</a></div>
<div class="auth-page">
  <div class="auth-box fade-in" style="max-width:500px;">
    <div class="auth-brand"><div class="logo-icon"><i class="fas fa-book-open"></i></div><h2>StudySwap Hub</h2></div>
    <h2 class="auth-title">Create your account</h2>
    <p class="auth-sub">Join thousands of students exchanging resources.</p>
    <?php if (!empty($errors)): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Please fix the errors below.</div><?php endif; ?>
    <form method="POST" action="register.php" novalidate>
      <div class="form-row">
        <div class="form-group"><label>First Name *</label><input type="text" name="first_name" class="form-control <?= isset($errors['first_name'])?'error':'' ?>" placeholder="Ahmed" value="<?= h($old['first_name']??'') ?>" required/><?php if(isset($errors['first_name'])): ?><span class="field-error show"><?= h($errors['first_name']) ?></span><?php endif; ?></div>
        <div class="form-group"><label>Last Name *</label><input type="text" name="last_name" class="form-control <?= isset($errors['last_name'])?'error':'' ?>" placeholder="Khan" value="<?= h($old['last_name']??'') ?>" required/><?php if(isset($errors['last_name'])): ?><span class="field-error show"><?= h($errors['last_name']) ?></span><?php endif; ?></div>
      </div>
      <div class="form-group"><label>Email *</label><input type="email" name="email" class="form-control <?= isset($errors['email'])?'error':'' ?>" placeholder="ahmed@student.nust.edu.pk" value="<?= h($old['email']??'') ?>" required/><?php if(isset($errors['email'])): ?><span class="field-error show"><?= h($errors['email']) ?></span><?php endif; ?></div>
      <div class="form-group"><label>University *</label>
        <select name="university" class="form-control <?= isset($errors['university'])?'error':'' ?>" required>
          <option value="">Select your university</option>
          <?php foreach($unis as $u): ?><option value="<?= h($u) ?>" <?= ($old['university']??'')===$u?'selected':'' ?>><?= h($u) ?></option><?php endforeach; ?>
        </select><?php if(isset($errors['university'])): ?><span class="field-error show"><?= h($errors['university']) ?></span><?php endif; ?>
      </div>
      <div class="form-group"><label>Student ID <span style="color:var(--faint)">(optional)</span></label><input type="text" name="student_id" class="form-control" placeholder="e.g. 2021-CS-101" value="<?= h($old['student_id']??'') ?>"/></div>
      <div class="form-row">
        <div class="form-group"><label>Password *</label><input type="password" name="password" id="regPass" class="form-control <?= isset($errors['password'])?'error':'' ?>" placeholder="Min 8 characters" required/><?php if(isset($errors['password'])): ?><span class="field-error show"><?= h($errors['password']) ?></span><?php endif; ?></div>
        <div class="form-group"><label>Confirm Password *</label><input type="password" name="password2" id="regPass2" class="form-control <?= isset($errors['password2'])?'error':'' ?>" placeholder="Re-enter password" required/><?php if(isset($errors['password2'])): ?><span class="field-error show"><?= h($errors['password2']) ?></span><?php endif; ?></div>
      </div>
      <div class="form-group"><label class="checkbox-label"><input type="checkbox" name="terms" <?= !empty($_POST['terms'])?'checked':'' ?> required/> I agree to the <a href="#" style="color:var(--brown-700);font-weight:600">Terms of Service</a> and <a href="#" style="color:var(--brown-700);font-weight:600">Privacy Policy</a></label><?php if(isset($errors['terms'])): ?><span class="field-error show"><?= h($errors['terms']) ?></span><?php endif; ?></div>
      <button type="submit" class="btn btn-primary btn-full btn-lg"><i class="fas fa-user-plus"></i> Create Account</button>
    </form>
    <p class="auth-footer">Already have an account? <a href="login.php">Sign in</a></p>
  </div>
</div>
<div id="toast"></div><script src="main.js"></script>
</body></html>
