<?php
require 'config.php';
require 'includes/auth.php';

if (isset($_SESSION['user'])) {
    if ($_SESSION['user']['role'] === 'admin') {
        header('Location: dashboard.php');
    } elseif ($_SESSION['user']['role'] === 'citizen') {
        header('Location: citizen_dashboard.php');
    } else {
        header('Location: distribute.php');
    }
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['username'] ?? '');
    $password   = $_POST['password'] ?? '';

    $nidHash = hashNID($identifier);

    $stmt = $pdo->prepare("
        SELECT u.*, n.ngo_name, f.head_name, upz.upazila_name, dist.district_name
        FROM users u 
        LEFT JOIN ngo n ON u.ngo_id = n.ngo_id 
        LEFT JOIN family f ON u.family_id = f.family_id
        LEFT JOIN upazila upz ON f.upazila_id = upz.upazila_id
        LEFT JOIN district dist ON upz.district_id = dist.district_id
        WHERE u.username = ? 
           OR u.email = ? 
           OR u.phone = ? 
           OR u.nid_hash = ?
    ");
    $stmt->execute([$identifier, $identifier, $identifier, $nidHash]);
    $row = $stmt->fetch();

    if ($row && password_verify($password, $row['password_hash'])) {
        if (!$row['is_verified']) {
            $error = t('err_not_verified');
        } else {
            $_SESSION['user'] = [
                'user_id'   => $row['user_id'],
                'username'  => $row['username'],
                'role'      => $row['role'],
                'ngo_id'    => $row['ngo_id'],
                'ngo_name'  => $row['ngo_name'],
                'family_id' => $row['family_id'],
                'nid_hash'  => $row['nid_hash'],
                'phone'     => $row['phone'],
                'head_name' => $row['head_name'] ?? $row['username']
            ];
            if ($row['role'] === 'admin') {
                header('Location: dashboard.php');
            } elseif ($row['role'] === 'citizen') {
                header('Location: citizen_dashboard.php');
            } else {
                header('Location: distribute.php');
            }
            exit;
        }
    } else {
        $error = t('err_bad_login');
    }
}
$lang = currentLang();
$brandNgo = $pdo->query("SELECT COUNT(*) c FROM ngo")->fetch()['c'];
$brandFamilies = $pdo->query("SELECT COUNT(*) c FROM family")->fetch()['c'];
$brandDist = $pdo->query("SELECT COUNT(*) c FROM distribution")->fetch()['c'];
$brandZones = 8;
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script>(function(){try{if(localStorage.getItem('reliefx_theme')==='light'){document.documentElement.setAttribute('data-theme','light');}}catch(e){}})();</script>
<title><?= t('login_page_title') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&family=Baloo+Da+2:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
<link rel="icon" type="image/png" href="img/logo-icon.png">
</head>
<body class="login-page">

<div class="scroll-progress"><div class="scroll-progress-fill" id="scrollProgress"></div></div>

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

    <div class="auth-form-inner">
      <img src="img/logo-icon.png" alt="ReliefX" class="login-logo mobile-only-logo">
      <h1 id="portalHeading"><?= t('login_title') ?></h1>
      <p class="sub" id="portalSub"><?= t('login_sub') ?></p>

      <!-- Multi-Role Persona Switcher -->
      <div class="login-role-tabs" id="roleTabs">
        <button type="button" class="login-role-tab active" data-role="admin" id="tabBtnAdmin">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          <span><?= t('role_admin') ?></span>
        </button>
        <button type="button" class="login-role-tab" data-role="ngo" id="tabBtnNgo">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18M15 9h6M15 15h6M3 9h6M3 15h6"/></svg>
          <span><?= t('role_ngo') ?></span>
        </button>
        <button type="button" class="login-role-tab" data-role="citizen" id="tabBtnCitizen">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          <span><?= t('role_citizen') ?></span>
        </button>
        <button type="button" class="login-role-tab" data-role="guest" id="tabBtnGuest">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
          <span><?= t('role_guest') ?></span>
        </button>
      </div>

      <div class="login-role-desc" id="roleDesc">
        <?= t('login_tab_admin_desc') ?>
      </div>

      <?php if ($error): ?>
        <div class="alert red show"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <!-- Standard Form for Admin, NGO, Citizen -->
      <form method="POST" id="authLoginForm">
        <div class="field">
          <label id="usernameLabel"><?= t('username') ?></label>
          <div class="input-icon">
            <svg id="userInputIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <input type="text" name="username" id="usernameInput" required autofocus placeholder="admin">
          </div>
        </div>
        <div class="field">
          <label><?= t('password') ?></label>
          <div class="input-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            <input type="password" name="password" id="passwordInput" required placeholder="••••••••">
          </div>
          <p style="text-align:right; margin-top:6px;">
            <a href="forgot_password.php" style="font-size:12.5px;"><?= t('forgot_pw_link') ?></a>
          </p>
        </div>
        <button class="btn" type="submit" id="loginSubmitBtn"><?= t('btn_login_admin') ?></button>
      </form>

      <!-- Guest Portal Panel (Visible when Guest tab selected) -->
      <div class="guest-panel" id="guestPanel" style="display:none;">
        <div style="font-size:38px; margin-bottom:10px;">🌐</div>
        <h3><?= $lang==='bn' ? 'উন্মুক্ত পাবলিক ত্রাণ ট্র্যাকিং' : 'Open Public Tracking' ?></h3>
        <p><?= $lang==='bn' ? 'কোনো পাসওয়ার্ড বা রেজিস্ট্রেশন ছাড়াই বন্যাদুর্গত এলাকার ত্রাণ বিতরণ পরিস্থিতি, লাইভ আবহাওয়া সতর্কতা এবং জরুরি এসওএস পোর্টাল দেখুন।' : 'Access live relief distribution status across flood zones, real-time hydrological weather alerts, and emergency SOS without an account.' ?></p>
        <a href="guest_dashboard.php" class="btn" style="display:block; text-decoration:none; margin-bottom:12px;">
          <?= t('btn_continue_guest') ?>
        </a>
        <div style="display:flex; justify-content:center; gap:10px; flex-wrap:wrap; margin-top:14px;">
          <a href="sos_portal.php" class="btn" style="font-size:11.5px;padding:6px 12px;background:rgba(255,107,107,0.18);color:var(--alert);border:1px solid rgba(255,107,107,0.35);text-decoration:none;">🚨 SOS Portal</a>
          <a href="weather_alerts.php" class="btn btn-ghost" style="font-size:11.5px;padding:6px 12px;text-decoration:none;">🌊 Live Weather</a>
        </div>
      </div>

      <!-- Quick Credentials / Helpers -->
      <div id="demoCredsBox" class="demo-creds" style="margin-top:18px;">
        <div id="demoAdminCreds">
          <b><?= t('admin_account') ?></b>:<br>
          <code>admin</code> / <code>Admin@123</code> (District Relief Officer)
          <button type="button" onclick="fillCreds('admin','Admin@123')" style="margin-left:8px;font-size:11px;padding:2px 8px;border-radius:4px;background:var(--primary);color:#fff;border:none;cursor:pointer;">Auto-fill</button>
        </div>
        <div id="demoNgoCreds" style="display:none;">
          <b>NGO Operator Demo Accounts (Password: <code>operator123</code>)</b>:<br>
          <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:6px;">
            <button type="button" onclick="fillCreds('asha_operator','operator123')" style="font-size:11px;padding:3px 8px;border-radius:4px;background:rgba(255,255,255,0.08);color:var(--ink);border:1px solid var(--border);cursor:pointer;">আশা ফাউন্ডেশন</button>
            <button type="button" onclick="fillCreds('relief_operator','operator123')" style="font-size:11px;padding:3px 8px;border-radius:4px;background:rgba(255,255,255,0.08);color:var(--ink);border:1px solid var(--border);cursor:pointer;">রিলিফ বাংলাদেশ</button>
            <button type="button" onclick="fillCreds('sfv_operator','operator123')" style="font-size:11px;padding:3px 8px;border-radius:4px;background:rgba(255,255,255,0.08);color:var(--ink);border:1px solid var(--border);cursor:pointer;">সেভ দ্য ফ্লাড (SFV)</button>
          </div>
        </div>
        <div id="demoCitizenCreds" style="display:none;">
          <b><?= $lang==='bn' ? 'নাগরিক ডেমো একাউন্ট' : 'Citizen Demo Account' ?></b>:<br>
          <span><?= $lang==='bn' ? 'রহিম উদ্দিন (NID: 1985011234501)' : 'Rahim Uddin (NID: 1985011234501)' ?></span>
          <button type="button" onclick="fillCreds('1985011234501','Citizen@123')" style="margin-left:8px;font-size:11px;padding:2px 8px;border-radius:4px;background:var(--safe);color:#fff;border:none;cursor:pointer;">Auto-fill Citizen</button>
        </div>
      </div>

      <!-- Registration Links for NGO & Citizen -->
      <div style="display:flex; flex-direction:column; gap:8px; margin-top:20px; padding-top:14px; border-top:1px solid var(--border); font-size:13px; text-align:center;">
        <div>
          <?= $lang==='bn' ? 'সাধারণ নাগরিক / ত্রাণপ্রার্থী?' : 'Citizen or Beneficiary?' ?>
          <a href="register_user.php" style="font-weight:600; color:var(--primary);">
            <b><?= t('register_as_citizen_link') ?> →</b>
          </a>
        </div>
        <div>
          <?= $lang==='bn' ? 'নতুন এনজিও সংস্থা?' : 'New NGO Foundation?' ?>
          <a href="register_ngo.php" style="font-weight:500;">
            <?= t('register_as_ngo_link') ?>
          </a>
        </div>
        <div>
          <a href="guest_dashboard.php" style="color:var(--water); font-size:12.5px;">
            🌐 <?= t('guest_view_link') ?> (<?= $lang==='bn'?'কোনো পাসওয়ার্ড ছাড়াই দেখুন':'Explore without login' ?>) →
          </a>
        </div>
      </div>

    </div>
  </div>

  <div class="scroll-hint" onclick="document.getElementById('howItWorks').scrollIntoView({behavior:'smooth'})">
    <span><?= t('scroll_hint') ?></span>
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
  </div>
</div>

<script>
const roleConfig = {
  admin: {
    heading: <?= json_encode($lang==='bn' ? 'এডমিন পোর্টাল' : 'Admin Portal') ?>,
    sub: <?= json_encode($lang==='bn' ? 'জেলা ত্রাণ কর্মকর্তা ও সিস্টেম অ্যাডমিনিস্ট্রেটর' : 'District Relief Officer & System Administrator') ?>,
    desc: <?= json_encode(t('login_tab_admin_desc')) ?>,
    label: <?= json_encode($lang==='bn' ? 'এডমিন ইউজারনেম / ইমেইল' : 'Admin Username / Email') ?>,
    placeholder: 'admin',
    btnText: <?= json_encode(t('btn_login_admin')) ?>
  },
  ngo: {
    heading: <?= json_encode($lang==='bn' ? 'এনজিও অপারেটর লগইন' : 'NGO Operator Login') ?>,
    sub: <?= json_encode($lang==='bn' ? 'মাঠপর্যায়ে ত্রাণ বিতরণ ও ডুপ্লিকেট রোধ' : 'Field Relief Distribution & Duplicate Prevention') ?>,
    desc: <?= json_encode(t('login_tab_ngo_desc')) ?>,
    label: <?= json_encode($lang==='bn' ? 'অপারেটর ইউজারনেম / ইমেইল' : 'Operator Username / Email') ?>,
    placeholder: 'asha_operator',
    btnText: <?= json_encode(t('btn_login_ngo')) ?>
  },
  citizen: {
    heading: <?= json_encode($lang==='bn' ? 'নাগরিক ও ত্রাণপ্রার্থী লগইন' : 'Citizen & Beneficiary Login') ?>,
    sub: <?= json_encode($lang==='bn' ? 'ত্রাণ হিসেব, পরবর্তী তারিখ ও জরুরি আবেদনের জন্য' : 'View relief history, next eligible date & emergency relief') ?>,
    desc: <?= json_encode(t('login_tab_citizen_desc')) ?>,
    label: <?= json_encode($lang==='bn' ? 'জাতীয় পরিচয়পত্র (NID) বা মোবাইল নম্বর' : 'National ID (NID) or Mobile Phone') ?>,
    placeholder: '1985011234501 or 01711234567',
    btnText: <?= json_encode(t('btn_login_citizen')) ?>
  },
  guest: {
    heading: <?= json_encode($lang==='bn' ? 'উন্মুক্ত পাবলিক ট্র্যাকিং' : 'Public Tracking') ?>,
    sub: <?= json_encode($lang==='bn' ? 'লগইন ছাড়াই উন্মুক্ত ত্রাণ হিসেব ও আবহাওয়া তথ্য' : 'Open Relief Tracking & Alerts Without Login') ?>,
    desc: <?= json_encode(t('login_tab_guest_desc')) ?>,
    label: '',
    placeholder: '',
    btnText: ''
  }
};

function selectRole(role) {
  document.querySelectorAll('.login-role-tab').forEach(b => b.classList.remove('active'));
  const btn = document.querySelector(`.login-role-tab[data-role="${role}"]`);
  if (btn) btn.classList.add('active');

  const conf = roleConfig[role];
  document.getElementById('portalHeading').textContent = conf.heading;
  document.getElementById('portalSub').textContent = conf.sub;
  document.getElementById('roleDesc').textContent = conf.desc;

  const form = document.getElementById('authLoginForm');
  const guestPanel = document.getElementById('guestPanel');
  const demoBox = document.getElementById('demoCredsBox');
  const adminDemo = document.getElementById('demoAdminCreds');
  const ngoDemo = document.getElementById('demoNgoCreds');
  const citizenDemo = document.getElementById('demoCitizenCreds');

  if (role === 'guest') {
    form.style.display = 'none';
    guestPanel.style.display = 'block';
    demoBox.style.display = 'none';
  } else {
    form.style.display = 'block';
    guestPanel.style.display = 'none';
    demoBox.style.display = 'block';

    document.getElementById('usernameLabel').textContent = conf.label;
    document.getElementById('usernameInput').placeholder = conf.placeholder;
    document.getElementById('loginSubmitBtn').textContent = conf.btnText;

    adminDemo.style.display = role === 'admin' ? 'block' : 'none';
    ngoDemo.style.display = role === 'ngo' ? 'block' : 'none';
    citizenDemo.style.display = role === 'citizen' ? 'block' : 'none';
  }
}

document.querySelectorAll('.login-role-tab').forEach(btn => {
  btn.addEventListener('click', () => {
    selectRole(btn.dataset.role);
  });
});

function fillCreds(u, p) {
  document.getElementById('usernameInput').value = u;
  document.getElementById('passwordInput').value = p;
}
</script>

  <div class="scroll-hint" onclick="document.getElementById('howItWorks').scrollIntoView({behavior:'smooth'})">
    <span><?= t('scroll_hint') ?></span>
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
  </div>
</div>

<section class="landing-section" id="howItWorks">
  <div class="reveal">
    <div class="section-eyebrow"><?= t('steps_eyebrow') ?></div>
    <h2 class="section-title"><?= t('steps_title') ?></h2>
    <p class="section-sub"><?= t('steps_sub') ?></p>
  </div>
  <div class="steps-row reveal-stagger reveal">
    <div class="step-card tilt"><div class="step-icon"><?= icon('file-plus',22) ?><span class="step-num">1</span></div><h3><?= t('step1_title') ?></h3><p><?= t('step1_desc') ?></p></div>
    <div class="step-card tilt"><div class="step-icon"><?= icon('shield-check',22) ?><span class="step-num">2</span></div><h3><?= t('step2_title') ?></h3><p><?= t('step2_desc') ?></p></div>
    <div class="step-card tilt"><div class="step-icon"><?= icon('package',22) ?><span class="step-num">3</span></div><h3><?= t('step3_title') ?></h3><p><?= t('step3_desc') ?></p></div>
    <div class="step-card tilt"><div class="step-icon"><?= icon('scale',22) ?><span class="step-num">4</span></div><h3><?= t('step4_title') ?></h3><p><?= t('step4_desc') ?></p></div>
  </div>
</section>

<section class="landing-section tight impact-section">
  <div class="reveal">
    <div class="section-eyebrow"><?= t('impact_eyebrow') ?></div>
    <h2 class="section-title"><?= t('impact_title') ?></h2>
    <p class="section-sub"><?= t('impact_sub') ?></p>
  </div>
  <div class="impact-grid reveal-stagger reveal">
    <div class="impact-card tilt">
      <div class="impact-icon"><?= icon('users',22) ?></div>
      <div class="impact-num"><b data-count="<?= $brandFamilies ?>" data-suffix="+">0</b></div>
      <div class="impact-label"><?= t('stat_families') ?></div>
    </div>
    <div class="impact-card tilt">
      <div class="impact-icon"><?= icon('package',22) ?></div>
      <div class="impact-num"><b data-count="<?= $brandDist ?>" data-suffix="+">0</b></div>
      <div class="impact-label"><?= t('stat_distributions') ?></div>
    </div>
    <div class="impact-card tilt">
      <div class="impact-icon"><?= icon('map-pin',22) ?></div>
      <div class="impact-num"><b data-count="<?= $brandZones ?>">0</b></div>
      <div class="impact-label"><?= t('stat_zones') ?></div>
    </div>
    <div class="impact-card tilt">
      <div class="impact-icon"><?= icon('shield-check',22) ?></div>
      <div class="impact-num"><b data-count="<?= $brandNgo ?>" data-suffix="+">0</b></div>
      <div class="impact-label"><?= t('impact_stat_ngo') ?></div>
    </div>
  </div>
</section>

<div class="landing-section tinted">
  <div class="landing-section">
    <div class="reveal">
      <div class="section-eyebrow"><?= t('features_eyebrow') ?></div>
      <h2 class="section-title"><?= t('features_title') ?></h2>
      <p class="section-sub"><?= t('features_sub') ?></p>
    </div>
    <div class="feature-grid reveal-stagger reveal">
      <div class="feature-card tilt"><div class="feature-icon"><?= icon('lock',21) ?></div><h3><?= t('feature1_title') ?></h3><p><?= t('feature1_desc') ?></p></div>
      <div class="feature-card tilt"><div class="feature-icon"><?= icon('map-pin',21) ?></div><h3><?= t('feature2_title') ?></h3><p><?= t('feature2_desc') ?></p></div>
      <div class="feature-card tilt"><div class="feature-icon"><?= icon('package',21) ?></div><h3><?= t('feature3_title') ?></h3><p><?= t('feature3_desc') ?></p></div>
      <div class="feature-card tilt"><div class="feature-icon"><?= icon('search',21) ?></div><h3><?= t('feature4_title') ?></h3><p><?= t('feature4_desc') ?></p></div>
    </div>
  </div>
</div>

<section class="trust-band reveal">
  <div class="section-eyebrow"><?= t('trust_eyebrow') ?></div>
  <h2 class="section-title" style="margin-bottom:28px;"><?= t('trust_title') ?></h2>
  <div class="marquee">
    <div class="marquee-track">
      <?php
        $trustItems = [
          ['lock','trust_item1'], ['zap','trust_item2'], ['search','trust_item3'],
          ['shield-check','trust_item4'], ['users','trust_item5'], ['check','trust_item6'],
        ];
        for ($r = 0; $r < 2; $r++):
          foreach ($trustItems as $ti):
      ?>
        <div class="trust-chip"><?= icon($ti[0],16) ?><span><?= t($ti[1]) ?></span></div>
      <?php endforeach; endfor; ?>
    </div>
  </div>
</section>

<section class="landing-section">
  <div class="showcase-row">
    <div class="showcase-mockup reveal">
      <div class="device-mockup">
        <div class="device-mockup-bar"><span></span><span></span><span></span></div>
        <img src="img/distribute-preview.png" alt="ReliefX distribution entry preview">
      </div>
      <div class="showcase-live-badge"><span class="live-dot"></span> <?= $lang==='bn' ? 'লাইভ ডেটা' : 'Live Data' ?></div>
    </div>
    <div class="showcase-text reveal">
      <h2><?= t('showcase_title') ?></h2>
      <p style="color:var(--ink-soft); font-size:13.5px; line-height:1.7; margin:0 0 20px;"><?= t('showcase_desc') ?></p>
      <ul class="showcase-list">
        <li><span class="tick">✓</span><?= t('showcase_point1') ?></li>
        <li><span class="tick">✓</span><?= t('showcase_point2') ?></li>
        <li><span class="tick">✓</span><?= t('showcase_point3') ?></li>
        <li><span class="tick">✓</span><?= t('showcase_point4') ?></li>
      </ul>
    </div>
  </div>
</section>

<div class="landing-section tinted">
  <div class="landing-section">
    <div class="reveal">
      <div class="section-eyebrow"><?= t('testimonials_eyebrow') ?></div>
      <h2 class="section-title"><?= t('testimonials_title') ?></h2>
      <p class="section-sub"><?= t('testimonials_sub') ?></p>
    </div>
    <div class="testimonial-grid reveal-stagger reveal">
      <div class="testimonial-card tilt">
        <div class="testimonial-quote-mark">“</div>
        <p class="testimonial-text"><?= t('testimonial1_quote') ?></p>
        <div class="testimonial-person">
          <div class="testimonial-avatar"><?= mb_substr(t('testimonial1_name'), 0, 1) ?></div>
          <div><b><?= t('testimonial1_name') ?></b><span><?= t('testimonial1_role') ?></span></div>
        </div>
      </div>
      <div class="testimonial-card tilt">
        <div class="testimonial-quote-mark">“</div>
        <p class="testimonial-text"><?= t('testimonial2_quote') ?></p>
        <div class="testimonial-person">
          <div class="testimonial-avatar"><?= mb_substr(t('testimonial2_name'), 0, 1) ?></div>
          <div><b><?= t('testimonial2_name') ?></b><span><?= t('testimonial2_role') ?></span></div>
        </div>
      </div>
      <div class="testimonial-card tilt">
        <div class="testimonial-quote-mark">“</div>
        <p class="testimonial-text"><?= t('testimonial3_quote') ?></p>
        <div class="testimonial-person">
          <div class="testimonial-avatar"><?= mb_substr(t('testimonial3_name'), 0, 1) ?></div>
          <div><b><?= t('testimonial3_name') ?></b><span><?= t('testimonial3_role') ?></span></div>
        </div>
      </div>
    </div>
  </div>
</div>

<section class="landing-section" id="faq">
  <div class="reveal">
    <div class="section-eyebrow"><?= t('faq_eyebrow') ?></div>
    <h2 class="section-title"><?= t('faq_title') ?></h2>
    <p class="section-sub"><?= t('faq_sub') ?></p>
  </div>
  <div class="faq-list reveal">
    <?php for ($i = 1; $i <= 5; $i++): ?>
    <div class="faq-item">
      <button type="button" class="faq-question">
        <span><?= t("faq{$i}_q") ?></span>
        <?= icon('chevron-down',18) ?>
      </button>
      <div class="faq-answer-wrap"><div class="faq-answer"><?= t("faq{$i}_a") ?></div></div>
    </div>
    <?php endfor; ?>
  </div>
</section>

<section class="landing-section cta-banner-wrap">
  <div class="cta-banner reveal">
    <div class="cta-banner-glow"></div>
    <h2><?= t('cta_title') ?></h2>
    <p><?= t('cta_sub') ?></p>
    <div class="cta-banner-actions">
      <a href="register_ngo.php" class="btn btn-accent"><?= t('cta_btn') ?></a>
      <a href="#howItWorks" class="btn btn-ghost-light"><?= t('cta_btn_secondary') ?></a>
    </div>
  </div>
</section>

<footer class="site-footer">
  <img src="img/logo-icon.png" alt="ReliefX" class="footer-logo">
  <div class="footer-brand">ReliefX</div>
  <p style="font-size:12.5px; opacity:.8; max-width:380px; margin:0 auto;"><?= t('footer_tagline') ?></p>
  <div class="footer-credits">
    <b>ReliefX</b> — Flood Relief Management System<br>
    Developed by Team <b>StratifyX</b> · Section 67_F1<br>
    Gulam Sakaria (242-15-218) · MD. Ashraful Islam (242-15-487) ·
    Mir Samiul Haque (242-15-350) · Nafil Ardul Ridin (242-15-705) ·
    Zidne Hasan (242-15-356)
  </div>
</footer>

<button type="button" id="backToTop" class="back-to-top" aria-label="Back to top">
  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
</button>

<script src="js/theme.js"></script>
<script src="js/auth.js"></script>
</body>
</html>
