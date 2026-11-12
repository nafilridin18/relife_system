<?php
require 'config.php';
require 'includes/auth.php';

if (isset($_SESSION['user'])) {
    if ($_SESSION['user']['role'] === 'citizen') {
        header('Location: citizen_dashboard.php');
    } elseif ($_SESSION['user']['role'] === 'admin') {
        header('Location: dashboard.php');
    } else {
        header('Location: distribute.php');
    }
    exit;
}

$lang = currentLang();
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $nid      = trim($_POST['nid'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $district = trim($_POST['district'] ?? 'কুড়িগ্রাম');
    $upazila  = trim($_POST['upazila'] ?? 'কুড়িগ্রাম সদর');
    $members  = max(1, (int)($_POST['members'] ?? 4));
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $errors[] = t('api_csrf_invalid');
    }
    if ($name === '' || $nid === '' || $phone === '' || $password === '') {
        $errors[] = $lang === 'bn' ? 'দয়া করে নাম, NID, মোবাইল নম্বর ও পাসওয়ার্ড পূরণ করুন।' : 'Please fill in Name, NID, Phone, and Password.';
    }
    if (strlen($nid) < 10) {
        $errors[] = $lang === 'bn' ? 'সঠিক জাতীয় পরিচয়পত্র বা জন্ম নিবন্ধন নম্বর প্রদান করুন (কমপক্ষে ১০ ডিজিট)।' : 'Please provide a valid NID or Birth Certificate number (min 10 digits).';
    }
    if (strlen($password) < 6) {
        $errors[] = t('err_password_length');
    }
    if ($password !== $confirm) {
        $errors[] = t('err_password_mismatch');
    }

    $nidHash = hashNID($nid);

    if (!$errors) {
        // Check if user already exists
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE nid_hash = ? OR phone = ?");
        $stmt->execute([$nidHash, $phone]);
        if ($stmt->fetch()) {
            $errors[] = $lang === 'bn' 
                ? 'এই NID বা মোবাইল নম্বরে ইতিমধ্যে একটি অ্যাকাউন্ট রয়েছে। অনুগ্রহ করে লগইন করুন।' 
                : 'An account already exists with this NID or phone number. Please log in.';
        }
    }

    if (!$errors) {
        $pdo->beginTransaction();
        try {
            // Check if family already exists in system (e.g. from prior relief distribution)
            $stmt = $pdo->prepare("SELECT family_id FROM family WHERE nid_hash = ?");
            $stmt->execute([$nidHash]);
            $existingFamily = $stmt->fetch();

            if ($existingFamily) {
                $familyId = $existingFamily['family_id'];
            } else {
                $stmtUpz = $pdo->prepare("SELECT upazila_id FROM upazila WHERE upazila_name LIKE ? LIMIT 1");
                $stmtUpz->execute(["%$upazila%"]);
                $upzId = $stmtUpz->fetchColumn() ?: 1;

                $stmt = $pdo->prepare("INSERT INTO family (head_name, nid_hash, phone, upazila_id, family_size) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $nidHash, $phone, $upzId, $members]);
                $familyId = $pdo->lastInsertId();
            }

            // Create username based on phone or nid
            $cleanNum = preg_replace('/\D/', '', $phone ?: $nid);
            $username = 'citizen_' . substr($cleanNum, -6);

            // Ensure unique username
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $username = 'citizen_' . substr($cleanNum, -6) . '_' . random_int(10, 99);
            }

            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("
                INSERT INTO users (username, password_hash, role, phone, nid_hash, family_id, is_verified) 
                VALUES (?, ?, 'citizen', ?, ?, ?, 1)
            ");
            $stmt->execute([$username, $passwordHash, $phone, $nidHash, $familyId]);
            $userId = $pdo->lastInsertId();

            $pdo->commit();

            // Set login session
            $_SESSION['user'] = [
                'user_id'   => $userId,
                'username'  => $username,
                'role'      => 'citizen',
                'ngo_id'    => null,
                'ngo_name'  => null,
                'family_id' => $familyId,
                'nid_hash'  => $nidHash,
                'phone'     => $phone,
                'head_name' => $name
            ];

            header('Location: citizen_dashboard.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = ($lang === 'bn' ? 'অ্যাকাউন্ট তৈরি ব্যর্থ হয়েছে: ' : 'Registration failed: ') . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script>(function(){try{if(localStorage.getItem('reliefx_theme')==='light'){document.documentElement.setAttribute('data-theme','light');}}catch(e){}})();</script>
<title><?= $lang==='bn' ? 'নাগরিক নিবন্ধন — ReliefX' : 'Citizen Registration — ReliefX' ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
<link rel="icon" type="image/png" href="img/logo-icon.png">
</head>
<body class="login-page">

<div class="auth-shell">
  <?php require 'includes/auth_brand.php'; ?>

  <div class="auth-form-side">
    <div style="position:absolute; top:20px; right:24px; display:flex; align-items:center; gap:10px; z-index:5;">
      <button type="button" class="theme-toggle-btn-light" aria-label="Toggle light/dark mode">
        <span class="icon-sun"><?= icon('sun',14) ?></span>
        <span class="icon-moon"><?= icon('moon',14) ?></span>
      </button>
      <div class="lang-toggle-light" style="margin:0;">
        <a href="switch_lang.php?lang=bn" class="<?= $lang==='bn'?'active':'' ?>">বাংলা</a>
        <a href="switch_lang.php?lang=en" class="<?= $lang==='en'?'active':'' ?>">EN</a>
      </div>
    </div>

    <div class="auth-form-inner" style="max-width:440px;">
      <a href="index.php" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:var(--ink-soft);text-decoration:none;margin-bottom:14px;">
        ← <?= $lang==='bn' ? 'লগইন পেজে ফিরুন' : 'Back to Login' ?>
      </a>

      <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
        <span style="font-size:26px;">👤</span>
        <h1 style="margin:0;font-size:22px;"><?= t('citizen_reg_title') ?></h1>
      </div>
      <p class="sub" style="margin-bottom:18px;"><?= t('citizen_reg_sub') ?></p>

      <?php if ($errors): ?>
        <div class="alert red show">
          <?php foreach ($errors as $err): ?>
            <div>• <?= htmlspecialchars($err) ?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

        <div class="field">
          <label><?= $lang==='bn' ? 'পূর্ণ নাম (পরিবার প্রধান)' : 'Full Name (Head of Family)' ?> *</label>
          <div class="input-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <input type="text" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" placeholder="<?= $lang==='bn'?'যেমন: রহিম উদ্দিন':'e.g. Rahim Uddin' ?>">
          </div>
        </div>

        <div class="field">
          <label><?= $lang==='bn' ? 'জাতীয় পরিচয়পত্র (NID) বা জন্ম নিবন্ধন নম্বর' : 'National ID (NID) or Birth Certificate' ?> *</label>
          <div class="input-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><line x1="15" y1="8" x2="17" y2="8"/><line x1="15" y1="12" x2="17" y2="12"/><line x1="7" y1="16" x2="17" y2="16"/></svg>
            <input type="text" name="nid" required value="<?= htmlspecialchars($_POST['nid'] ?? '') ?>" placeholder="<?= $lang==='bn'?'যেমন: 1985011234501':'e.g. 1985011234501' ?>">
          </div>
          <small style="color:var(--ink-soft);font-size:11.5px;"><?= $lang==='bn' ? '🔒 আপনার আসল NID কখনো ডাটাবেসে সেভ হয় না, শুধুমাত্র SHA-256 এনক্রিপ্টেড হ্যাশ সংরক্ষণ করা হয়।' : '🔒 Your plain NID is never stored; only an irreversible SHA-256 cryptographic hash is kept.' ?></small>
        </div>

        <div class="field">
          <label><?= $lang==='bn' ? 'মোবাইল নম্বর' : 'Mobile Phone Number' ?> *</label>
          <div class="input-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
            <input type="text" name="phone" required value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" placeholder="01711234567">
          </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
          <div class="field">
            <label><?= $lang==='bn' ? 'জেলা' : 'District' ?></label>
            <input type="text" name="district" value="<?= htmlspecialchars($_POST['district'] ?? 'কুড়িগ্রাম') ?>" required>
          </div>
          <div class="field">
            <label><?= $lang==='bn' ? 'উপজেলা' : 'Upazila' ?></label>
            <input type="text" name="upazila" value="<?= htmlspecialchars($_POST['upazila'] ?? 'কুড়িগ্রাম সদর') ?>" required>
          </div>
        </div>

        <div class="field">
          <label><?= $lang==='bn' ? 'পরিবারের সদস্য সংখ্যা' : 'Family Members Count' ?></label>
          <input type="number" name="members" min="1" max="25" value="<?= htmlspecialchars($_POST['members'] ?? '4') ?>" required>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
          <div class="field">
            <label><?= t('password') ?> *</label>
            <input type="password" name="password" required placeholder="••••••••">
          </div>
          <div class="field">
            <label><?= $lang==='bn' ? 'পাসওয়ার্ড নিশ্চিত করুন' : 'Confirm Password' ?> *</label>
            <input type="password" name="confirm" required placeholder="••••••••">
          </div>
        </div>

        <button class="btn" type="submit" style="margin-top:10px;">
          <?= $lang==='bn' ? 'নাগরিক অ্যাকাউন্ট তৈরি করুন' : 'Create Citizen Account' ?>
        </button>
      </form>

      <p style="text-align:center; margin-top:16px; font-size:13px; color:var(--ink-soft);">
        <?= $lang==='bn' ? 'ইতিমধ্যে অ্যাকাউন্ট আছে?' : 'Already have an account?' ?>
        <a href="index.php" style="color:var(--primary); font-weight:600;"><?= $lang==='bn' ? 'লগইন করুন' : 'Log in' ?></a>
      </p>
    </div>
  </div>
</div>

<script src="js/theme.js"></script>
</body>
</html>
