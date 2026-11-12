<?php
require 'config.php';
require 'includes/auth.php';

requireLogin();

$user = currentUser();
$lang = currentLang();
$pageTitle = $lang === 'bn' ? 'নাগরিক ত্রাণ ড্যাশবোর্ড ও আবেদন — ReliefX' : 'Citizen Relief Dashboard & Requests — ReliefX';

$familyId = $user['family_id'] ?? null;
$nidHash  = $user['nid_hash'] ?? null;

// Fallback search by nid_hash if family_id was empty
if (!$familyId && $nidHash) {
    $stmt = $pdo->prepare("SELECT family_id FROM family WHERE nid_hash = ?");
    $stmt->execute([$nidHash]);
    $familyId = $stmt->fetchColumn() ?: null;
}

$family = null;
if ($familyId) {
    $stmt = $pdo->prepare("
        SELECT f.*, upz.upazila_name, dist.district_name, a.area_name
        FROM family f
        LEFT JOIN upazila upz ON f.upazila_id = upz.upazila_id
        LEFT JOIN district dist ON upz.district_id = dist.district_id
        LEFT JOIN area a ON f.area_id = a.area_id
        WHERE f.family_id = ?
    ");
    $stmt->execute([$familyId]);
    $family = $stmt->fetch();
}

// 1. Fetch Relief Distribution History for this Family
$reliefHistory = [];
if ($familyId) {
    $stmt = $pdo->prepare("
        SELECT d.*, r.item_name, r.category, r.unit, n.ngo_name, dp.point_name,
               COALESCE(u.upazila_name, a.area_name, 'কুড়িগ্রাম সদর') AS upazila,
               COALESCE(dist.district_name, a.district, 'কুড়িগ্রাম') AS district
        FROM distribution d
        JOIN relief_item r ON d.item_id = r.item_id
        JOIN ngo n ON d.ngo_id = n.ngo_id
        LEFT JOIN distribution_point dp ON d.point_id = dp.point_id
        LEFT JOIN area a ON dp.area_id = a.area_id
        LEFT JOIN upazila u ON COALESCE(dp.upazila_id, a.upazila_id) = u.upazila_id
        LEFT JOIN district dist ON u.district_id = dist.district_id
        WHERE d.family_id = ?
        ORDER BY d.dist_date DESC
    ");
    $stmt->execute([$familyId]);
    $reliefHistory = $stmt->fetchAll();
}

// 2. Calculate Category Cooldowns (7-day rule per category)
$categories = ['Food', 'Shelter', 'Medical', 'Cash'];
$categoryStatus = [];
$now = time();

foreach ($categories as $cat) {
    $lastDate = null;
    foreach ($reliefHistory as $row) {
        if ($row['category'] === $cat) {
            $lastDate = strtotime($row['dist_date']);
            break;
        }
    }
    if ($lastDate) {
        $daysPassed = floor(($now - $lastDate) / 86400);
        $isEligible = $daysPassed >= 7;
        $daysLeft = max(0, 7 - $daysPassed);
        $nextDate = date('d M Y', strtotime('+7 days', $lastDate));
    } else {
        $isEligible = true;
        $daysPassed = 999;
        $daysLeft = 0;
        $nextDate = null;
    }
    $categoryStatus[$cat] = [
        'eligible'    => $isEligible,
        'days_passed' => $daysPassed,
        'days_left'   => $daysLeft,
        'next_date'   => $nextDate,
        'last_date'   => $lastDate ? date('d M Y', $lastDate) : null
    ];
}

// 3. Emergency Relief Rule: Unlock after 5 days from last relief
$lastDistributionTime = !empty($reliefHistory) ? strtotime($reliefHistory[0]['dist_date']) : null;
$daysSinceLastDistribution = $lastDistributionTime ? floor(($now - $lastDistributionTime) / 86400) : 999;
$canApplyEmergency = ($daysSinceLastDistribution >= 5);
$emergencyDaysRemaining = max(0, 5 - $daysSinceLastDistribution);

$emergencyError = '';
$emergencySuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'apply_emergency') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $emergencyError = t('api_csrf_invalid');
    } elseif (!$canApplyEmergency) {
        $emergencyError = sprintf(t('emergency_locked_msg'), $emergencyDaysRemaining);
    } else {
        $reqCategory   = trim($_POST['requested_category'] ?? 'খাদ্য ও শিশু খাদ্য');
        $urgencyReason = trim($_POST['urgency_reason'] ?? '');
        $applicantName = $family['head_name'] ?? $user['head_name'] ?? $user['username'];
        $phone         = $family['phone'] ?? $user['phone'] ?? '';

        if ($urgencyReason === '') {
            $emergencyError = $lang === 'bn' ? 'জরুরি আবেদনের কারণ উল্লেখ করা আবশ্যক।' : 'Urgency reason is required.';
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO emergency_relief_request 
                (family_id, nid_hash, applicant_name, phone, requested_category, urgency_reason, days_since_last_relief, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'PENDING')
            ");
            $stmt->execute([
                $familyId ?: null,
                $nidHash ?: hashNID('0000000000'),
                $applicantName,
                $phone,
                $reqCategory,
                $urgencyReason,
                $daysSinceLastDistribution
            ]);
            $emergencySuccess = $lang === 'bn' 
                ? 'আপনার জরুরি ত্রাণের আবেদন সফলভাবে গৃহীত হয়েছে। ত্রাণ টিম দ্রুত এটি পর্যালোচনা করবে।'
                : 'Emergency relief request submitted successfully. Assessment team will review shortly.';
        }
    }
}

// 4. Fetch Submitted Emergency Requests
$myEmergencyRequests = [];
if ($familyId || $nidHash) {
    $stmt = $pdo->prepare("
        SELECT * FROM emergency_relief_request 
        WHERE (family_id = ? AND family_id IS NOT NULL) OR nid_hash = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$familyId ?: 0, $nidHash ?: '']);
    $myEmergencyRequests = $stmt->fetchAll();
}

require 'includes/header.php';
?>

<div class="wrap" style="max-width:1100px; margin:24px auto; padding:0 16px;">

  <!-- Citizen Profile Hero Bar -->
  <div class="citizen-profile-bar">
    <div>
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
        <h2 style="margin:0;font-size:22px;color:var(--ink);">
          <?= $lang==='bn' ? 'স্বাগতম, ' : 'Welcome, ' ?>
          <span style="color:var(--primary);"><?= htmlspecialchars($family['head_name'] ?? $user['head_name'] ?? $user['username']) ?></span>
        </h2>
        <span class="citizen-badge">
          <?= icon('shield-check',13) ?>
          <?= $lang==='bn' ? 'যাচাইকৃত পরিবার' : 'Verified Family' ?>
        </span>
      </div>
      <div style="font-size:13px;color:var(--ink-soft);display:flex;flex-wrap:wrap;gap:16px;">
        <span>📞 <?= htmlspecialchars($user['phone'] ?? $family['phone'] ?? 'N/A') ?></span>
        <span>📍 <?= htmlspecialchars(($family['upazila_name'] ?? $family['area_name'] ?? 'কুড়িগ্রাম সদর') . ', ' . ($family['district_name'] ?? 'কুড়িগ্রাম')) ?></span>
        <span>👥 <?= $lang==='bn' ? 'সদস্য: ' : 'Members: ' ?> <?= (int)($family['family_size'] ?? 4) ?></span>
        <span>🔒 NID Hash: <code><?= substr($nidHash ?? 'hash', 0, 12) ?>...</code></span>
      </div>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      <a href="sos_portal.php" class="btn" style="background:rgba(255,107,107,0.18);color:var(--alert);border:1px solid rgba(255,107,107,0.35);text-decoration:none;font-size:12px;padding:8px 14px;">
        🚨 SOS Portal
      </a>
      <a href="weather_alerts.php" class="btn btn-ghost" style="text-decoration:none;font-size:12px;padding:8px 14px;">
        🌊 <?= $lang==='bn'?'বন্যা সতর্কতা':'Weather & Alerts' ?>
      </a>
    </div>
  </div>

  <!-- SECTION 1: Next Eligibility Status (7-Day Cooldown Per Category) -->
  <div style="margin-bottom:28px;">
    <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:12px;flex-wrap:wrap;gap:10px;">
      <div>
        <h3 style="margin:0;font-size:18px;color:var(--ink);display:flex;align-items:center;gap:8px;">
          ⏱️ <?= t('cooldown_status_title') ?>
        </h3>
        <p style="margin:4px 0 0;font-size:12.5px;color:var(--ink-soft);">
          <?= t('cooldown_status_sub') ?>
        </p>
      </div>
      <span style="font-size:11.5px;color:var(--ink-soft);background:rgba(255,255,255,0.05);padding:4px 10px;border-radius:12px;border:1px solid var(--border);">
        <?= $lang==='bn' ? 'ডুপ্লিকেট প্রতিরোধ নিয়ম: ৭ দিন' : 'Rule: 7-day category cycle' ?>
      </span>
    </div>

    <div class="eligibility-grid">
      <?php
      $catIcons = [
        'Food'    => '🍚',
        'Shelter' => '⛺',
        'Medical' => '💊',
        'Cash'    => '💵'
      ];
      $catNamesBn = [
        'Food'    => 'চাল ও খাদ্য সামগ্রী',
        'Shelter' => 'বাসস্থান ও ত্রিপল',
        'Medical' => 'ওষুধ ও স্যালাইন',
        'Cash'    => 'নগদ অর্থ সহায়তা'
      ];

      foreach ($categoryStatus as $cat => $st):
        $icon = $catIcons[$cat] ?? '📦';
        $title = $lang==='bn' ? ($catNamesBn[$cat] ?? $cat) : $cat;
        $isEligible = $st['eligible'];
      ?>
      <div class="eligibility-card <?= $isEligible ? 'status-eligible' : 'status-cooldown' ?>">
        <div>
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
            <span style="font-size:20px;"><?= $icon ?></span>
            <?php if ($isEligible): ?>
              <span class="status-pill approved"><?= t('eligible_now') ?></span>
            <?php else: ?>
              <span class="status-pill pending"><?= $st['days_left'] ?> <?= $lang==='bn'?'দিন বাকি':'days left' ?></span>
            <?php endif; ?>
          </div>
          <h4 style="margin:0 0 4px;font-size:14.5px;color:var(--ink);"><?= $title ?></h4>
          <div style="font-size:12px;color:var(--ink-soft);">
            <?php if ($st['last_date']): ?>
              <?= $lang==='bn' ? 'শেষ গ্রহণ: ' : 'Last received: ' ?><b><?= $st['last_date'] ?></b>
            <?php else: ?>
              <?= $lang==='bn' ? 'পূর্বে কোনো বরাদ্দ নেওয়া হয়নি' : 'No prior distribution recorded' ?>
            <?php endif; ?>
          </div>
        </div>

        <div style="margin-top:14px;padding-top:10px;border-top:1px solid var(--border);">
          <?php if ($isEligible): ?>
            <div style="font-size:12px;color:var(--safe);font-weight:600;display:flex;align-items:center;gap:4px;">
              <?= icon('shield-check',13) ?>
              <?= $lang==='bn' ? 'বিতরণ পয়েন্টে ত্রাণ পাওয়ার উপযুক্ত' : 'Eligible for next allocation' ?>
            </div>
          <?php else: ?>
            <div class="cooldown-bar">
              <div class="cooldown-fill" style="width:<?= min(100, round(($st['days_passed'] / 7) * 100)) ?>%;"></div>
            </div>
            <div style="font-size:11.5px;color:var(--warn);display:flex;justify-content:space-between;">
              <span><?= $lang==='bn' ? 'পরবর্তী তারিখ:' : 'Eligible on:' ?></span>
              <b><?= $st['next_date'] ?></b>
            </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- SECTION 2: Emergency Relief Request (Unlocked after 5 days) -->
  <div class="emergency-form-box">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;margin-bottom:16px;">
      <div>
        <h3 style="margin:0;font-size:18px;color:var(--ink);display:flex;align-items:center;gap:8px;">
          🚨 <?= t('emergency_relief_title') ?>
        </h3>
        <p style="margin:4px 0 0;font-size:12.5px;color:var(--ink-soft);">
          <?= t('emergency_relief_sub') ?>
        </p>
      </div>

      <?php if ($canApplyEmergency): ?>
        <span class="status-pill approved" style="font-size:12px;padding:5px 12px;">
          🔓 <?= $lang==='bn' ? 'জরুরি আবেদন উন্মুক্ত' : 'Emergency Request Unlocked' ?>
        </span>
      <?php else: ?>
        <span class="status-pill pending" style="font-size:12px;padding:5px 12px;">
          🔒 <?= sprintf($lang==='bn' ? 'আনলক হতে %d দিন বাকি' : '%d days remaining to unlock', $emergencyDaysRemaining) ?>
        </span>
      <?php endif; ?>
    </div>

    <?php if ($emergencySuccess): ?>
      <div class="alert green show" style="margin-bottom:16px;"><?= htmlspecialchars($emergencySuccess) ?></div>
    <?php endif; ?>
    <?php if ($emergencyError): ?>
      <div class="alert red show" style="margin-bottom:16px;"><?= htmlspecialchars($emergencyError) ?></div>
    <?php endif; ?>

    <?php if ($canApplyEmergency): ?>
      <div style="background:rgba(52,211,153,0.08);border:1px solid rgba(52,211,153,0.25);border-radius:8px;padding:12px 14px;margin-bottom:18px;font-size:13px;color:var(--safe);">
        <?= t('emergency_unlocked_msg') ?>
      </div>

      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="action" value="apply_emergency">

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:14px;">
          <div class="field">
            <label><?= t('emergency_category_label') ?> *</label>
            <select name="requested_category" required style="width:100%;padding:10px;background:var(--input-bg);color:var(--ink);border:1px solid var(--border);border-radius:8px;">
              <option value="চাল ও শুকনো খাবার"><?= $lang==='bn'?'চাল ও শুকনো খাবার (Rice & Dry Food)':'Rice & Dry Food' ?></option>
              <option value="শিশুখাদ্য ও গুঁড়ো দুধ"><?= $lang==='bn'?'শিশুখাদ্য ও গুঁড়ো দুধ (Infant Nutrition)':'Baby Food & Milk' ?></option>
              <option value="জরুরি ওষুধ ও খাবার স্যালাইন"><?= $lang==='bn'?'জরুরি ওষুধ ও খাবার স্যালাইন (Medicine & Saline)':'Emergency Medicine & Saline' ?></option>
              <option value="ত্রিপল ও জরুরি ছাউনি"><?= $lang==='bn'?'ত্রিপল ও জরুরি ছাউনি (Shelter & Tarpaulin)':'Tarpaulin & Shelter' ?></option>
              <option value="বিশুদ্ধ খাবার পানি ও ওয়াটার কিট"><?= $lang==='bn'?'বিশুদ্ধ খাবার পানি ও ওয়াটার কিট (Purified Water Kit)':'Drinking Water Kit' ?></option>
            </select>
          </div>

          <div class="field">
            <label><?= $lang==='bn'?'জরুরি যোগাযোগের বিকল্প ফোন নম্বর':'Alternative Contact Phone' ?></label>
            <input type="text" name="alt_phone" placeholder="017xxxxxxxx" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
          </div>
        </div>

        <div class="field" style="margin-top:10px;">
          <label><?= t('emergency_reason_label') ?> *</label>
          <textarea name="urgency_reason" rows="3" required placeholder="<?= t('emergency_reason_ph') ?>" style="width:100%;padding:10px;background:var(--input-bg);color:var(--ink);border:1px solid var(--border);border-radius:8px;resize:vertical;"></textarea>
        </div>

        <button class="btn" type="submit" style="margin-top:10px;background:var(--primary);color:#fff;">
          <?= t('emergency_submit_btn') ?>
        </button>
      </form>
    <?php else: ?>
      <div style="background:rgba(251,191,36,0.08);border:1px solid rgba(251,191,36,0.25);border-radius:8px;padding:16px;font-size:13px;color:var(--warn);line-height:1.6;">
        <b>⏳ <?= sprintf(t('emergency_locked_msg'), $emergencyDaysRemaining) ?></b><br>
        <?= $lang==='bn'
          ? 'নিয়ম অনুযায়ী একই পরিবার যাতে ন্যায্যভাবে জরুরি কোটায় সহায়তা পায়, সেজন্য শেষ ত্রাণ গ্রহণের ৫ দিন পর এই ফরমটি স্বয়ংক্রিয়ভাবে সক্রিয় হবে।' 
          : 'According to fair allocation standards, emergency applications unlock 5 days after your last relief distribution.' ?>
      </div>
    <?php endif; ?>

    <!-- Previously Submitted Requests -->
    <?php if ($myEmergencyRequests): ?>
      <div style="margin-top:24px;padding-top:18px;border-top:1px solid var(--border);">
        <h4 style="margin:0 0 12px;font-size:15px;color:var(--ink);">
          📋 <?= $lang==='bn' ? 'আপনার পূর্ববর্তী জরুরি আবেদনের স্ট্যাটাস' : 'Your Submitted Emergency Requests' ?>
        </h4>
        <div style="overflow-x:auto;">
          <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead>
              <tr style="border-bottom:1px solid var(--border);color:var(--ink-soft);text-align:left;">
                <th style="padding:8px;"><?= $lang==='bn'?'তারিখ':'Date' ?></th>
                <th style="padding:8px;"><?= $lang==='bn'?'সহায়তার ধরন':'Category' ?></th>
                <th style="padding:8px;"><?= $lang==='bn'?'কারণ':'Reason' ?></th>
                <th style="padding:8px;"><?= $lang==='bn'?'স্ট্যাটাস':'Status' ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($myEmergencyRequests as $req): 
                $stClass = strtolower($req['status']);
                $stName = t('emergency_status_' . $stClass);
              ?>
              <tr style="border-bottom:1px solid rgba(255,255,255,0.04);">
                <td style="padding:10px 8px;"><?= date('d M Y, h:i A', strtotime($req['created_at'])) ?></td>
                <td style="padding:10px 8px;"><b><?= htmlspecialchars($req['requested_category']) ?></b></td>
                <td style="padding:10px 8px;max-width:320px;"><?= htmlspecialchars($req['urgency_reason']) ?></td>
                <td style="padding:10px 8px;">
                  <span class="status-pill <?= $stClass ?>"><?= $stName ?></span>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- SECTION 3: My Relief History Table -->
  <div class="card" style="margin-bottom:28px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;flex-wrap:wrap;gap:8px;">
      <div>
        <h3 style="margin:0;font-size:18px;color:var(--ink);display:flex;align-items:center;gap:8px;">
          📦 <?= t('relief_history_title') ?>
        </h3>
        <p style="margin:4px 0 0;font-size:12.5px;color:var(--ink-soft);">
          <?= t('relief_history_sub') ?>
        </p>
      </div>
      <span style="font-size:12px;color:var(--ink-soft);background:rgba(255,255,255,0.05);padding:4px 10px;border-radius:12px;">
        <?= count($reliefHistory) ?> <?= $lang==='bn'?'টি রেকর্ড':'records' ?>
      </span>
    </div>

    <?php if (empty($reliefHistory)): ?>
      <div style="text-align:center;padding:32px 16px;color:var(--ink-soft);font-size:13.5px;">
        <?= $lang==='bn' ? 'আপনার নামে এখন পর্যন্ত কোনো ত্রাণ বিতরণের রেকর্ড নথিভুক্ত হয়নি।' : 'No relief distribution records found for your NID.' ?>
      </div>
    <?php else: ?>
      <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:13.5px;">
          <thead>
            <tr style="border-bottom:1px solid var(--border);color:var(--ink-soft);text-align:left;">
              <th style="padding:10px 8px;"><?= t('th_date') ?></th>
              <th style="padding:10px 8px;"><?= t('th_item') ?></th>
              <th style="padding:10px 8px;"><?= t('th_quantity') ?></th>
              <th style="padding:10px 8px;"><?= $lang==='bn'?'প্রদানকারী এনজিও':'Providing NGO' ?></th>
              <th style="padding:10px 8px;"><?= $lang==='bn'?'বিতরণ পয়েন্ট / কেন্দ্র':'Distribution Point' ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($reliefHistory as $row): 
              $tagClass = 'tag-' . strtolower($row['category'] ?? 'food');
            ?>
            <tr style="border-bottom:1px solid rgba(255,255,255,0.04);">
              <td style="padding:12px 8px;font-family:'Space Grotesk',sans-serif;color:var(--ink-soft);">
                <?= date('d M Y, h:i A', strtotime($row['dist_date'])) ?>
              </td>
              <td style="padding:12px 8px;">
                <b><?= htmlspecialchars(tEntity($row['item_name'])) ?></b>
                <span class="tag <?= $tagClass ?>" style="margin-left:6px;font-size:10.5px;padding:2px 6px;">
                  <?= htmlspecialchars(tCategory($row['category'])) ?>
                </span>
              </td>
              <td style="padding:12px 8px;font-weight:700;font-family:'Space Grotesk',sans-serif;">
                <?= (float)$row['quantity'] ?> <?= htmlspecialchars(tEntity($row['unit'])) ?>
              </td>
              <td style="padding:12px 8px;color:var(--primary);">
                <?= htmlspecialchars(tEntity($row['ngo_name'])) ?>
              </td>
              <td style="padding:12px 8px;color:var(--ink-soft);">
                <?= htmlspecialchars(tEntity($row['point_name'] ?? 'নিকটস্থ ইউনিয়ন মাঠ')) ?>
                <?php if (!empty($row['upazila'])): ?>
                  <small style="display:block;font-size:11px;"><?= htmlspecialchars(tEntity($row['upazila'])) ?></small>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

</div>

<?php require 'includes/footer.php'; ?>
