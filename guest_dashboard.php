<?php
require 'config.php';
require 'includes/auth.php';

$lang = currentLang();
$pageTitle = $lang === 'bn' 
    ? 'উন্মুক্ত ত্রাণ বিতরণ ট্র্যাকিং ও জনস্বচ্ছতা ড্যাশবোর্ড — ReliefX' 
    : 'Public Relief Distribution Transparency Portal — ReliefX';

// Aggregated Relief Distribution by Upazila / Area
$stmt = $pdo->query("
    SELECT 
        COALESCE(u.upazila_name, a.area_name, 'কুড়িগ্রাম সদর') AS upazila,
        COALESCE(dist.district_name, a.district, 'কুড়িগ্রাম') AS district,
        COUNT(d.dist_id) AS total_count,
        MAX(d.dist_date) AS last_date,
        GROUP_CONCAT(DISTINCT n.ngo_name ORDER BY n.ngo_name SEPARATOR ' • ') AS ngos,
        GROUP_CONCAT(DISTINCT r.category ORDER BY r.category SEPARATOR ', ') AS categories,
        SUM(d.quantity) AS total_quantity
    FROM distribution d
    JOIN relief_item r ON d.item_id = r.item_id
    JOIN ngo n ON d.ngo_id = n.ngo_id
    LEFT JOIN distribution_point dp ON d.point_id = dp.point_id
    LEFT JOIN area a ON dp.area_id = a.area_id
    LEFT JOIN upazila u ON COALESCE(dp.upazila_id, a.upazila_id) = u.upazila_id
    LEFT JOIN district dist ON u.district_id = dist.district_id
    GROUP BY upazila, district
    ORDER BY last_date DESC
");
$areaFeed = $stmt->fetchAll();

// Overall Stats for Transparency
$totalDistributions = $pdo->query("SELECT COUNT(*) FROM distribution")->fetchColumn();
$totalFamilies      = $pdo->query("SELECT COUNT(*) FROM family")->fetchColumn();
$totalNgos          = $pdo->query("SELECT COUNT(*) FROM ngo")->fetchColumn();
$totalAreas         = count($areaFeed);

// Most recent 10 individual distributions for live ticker/feed
$stmtRecent = $pdo->query("
    SELECT d.dist_date, d.quantity, r.item_name, r.category, r.unit, n.ngo_name,
           COALESCE(u.upazila_name, a.area_name, 'কুড়িগ্রাম সদর') AS upazila,
           COALESCE(dist.district_name, a.district, 'কুড়িগ্রাম') AS district
    FROM distribution d
    JOIN relief_item r ON d.item_id = r.item_id
    JOIN ngo n ON d.ngo_id = n.ngo_id
    LEFT JOIN distribution_point dp ON d.point_id = dp.point_id
    LEFT JOIN area a ON dp.area_id = a.area_id
    LEFT JOIN upazila u ON COALESCE(dp.upazila_id, a.upazila_id) = u.upazila_id
    LEFT JOIN district dist ON u.district_id = dist.district_id
    ORDER BY d.dist_date DESC
    LIMIT 10
");
$recentStream = $stmtRecent->fetchAll();

$user = currentUser();
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script>(function(){try{if(localStorage.getItem('reliefx_theme')==='light'){document.documentElement.setAttribute('data-theme','light');}}catch(e){}})();</script>
<title><?= htmlspecialchars($pageTitle) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
<link rel="icon" type="image/png" href="img/logo-icon.png">
</head>
<body>

<!-- Public Topbar -->
<div class="topbar">
  <div class="brand">
    <div class="brand-mark"><img src="img/logo-icon.png" alt="ReliefX logo"></div>
    <div class="brand-text">
      <h1><?= t('brand_title') ?></h1>
      <span><?= $lang==='bn' ? 'উন্মুক্ত নাগরিক ও পাবলিক ট্র্যাকিং পোর্টাল' : 'Public Relief Transparency Portal' ?></span>
    </div>
  </div>
  <div class="top-controls">
    <button type="button" class="theme-toggle-btn" aria-label="Toggle light/dark mode">
      <span class="icon-sun"><?= icon('sun',15) ?></span>
      <span class="icon-moon"><?= icon('moon',15) ?></span>
    </button>
    <div class="lang-toggle">
      <a href="switch_lang.php?lang=bn" class="<?= $lang==='bn'?'active':'' ?>">বাংলা</a>
      <a href="switch_lang.php?lang=en" class="<?= $lang==='en'?'active':'' ?>">EN</a>
    </div>
    <a href="weather_alerts.php" class="btn" style="font-size:11.5px;padding:5px 11px;background:rgba(56,189,248,0.18);color:var(--water);border:1px solid rgba(56,189,248,0.35);text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
      <span class="icon-cloud-rain"><?= icon('cloud-rain',13) ?></span>
      <span><?= $lang==='bn' ? 'আবহাওয়া ও বন্যা সতর্কতা' : 'Weather & Alerts' ?></span>
    </a>
    <a href="sos_portal.php" class="btn" style="font-size:11.5px;padding:5px 11px;background:rgba(255,107,107,0.18);color:var(--alert);border:1px solid rgba(255,107,107,0.35);text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
      <span class="ai-pulse-dot" style="background:var(--alert);width:6px;height:6px;"></span>
      <span>SOS Portal</span>
    </a>
    <?php if ($user): ?>
      <a href="<?= $user['role']==='admin'?'dashboard.php':($user['role']==='citizen'?'citizen_dashboard.php':'distribute.php') ?>" class="btn btn-ghost" style="font-size:11.5px;padding:5px 12px;text-decoration:none;">
        <?= $lang==='bn'?'আমার ড্যাশবোর্ড →':'My Dashboard →' ?>
      </a>
    <?php else: ?>
      <a href="index.php" class="btn" style="font-size:11.5px;padding:5px 12px;background:var(--primary);color:#fff;text-decoration:none;">
        <?= $lang==='bn'?'লগইন':'Login' ?>
      </a>
      <a href="register_user.php" class="btn btn-ghost" style="font-size:11.5px;padding:5px 12px;text-decoration:none;">
        <?= $lang==='bn'?'নাগরিক নিবন্ধন':'Citizen Sign Up' ?>
      </a>
    <?php endif; ?>
  </div>
</div>

<div class="wrap" style="max-width:1120px; margin:24px auto; padding:0 16px;">

  <!-- Hero Transparency Header -->
  <div style="background:linear-gradient(135deg, rgba(110,140,255,0.12) 0%, rgba(56,189,248,0.06) 100%); border:1px solid var(--border); border-radius:14px; padding:24px; margin-bottom:24px;">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
      <div style="max-width:680px;">
        <span style="display:inline-block; font-size:11.5px; font-weight:700; color:var(--water); text-transform:uppercase; letter-spacing:0.8px; margin-bottom:6px;">
          PUBLIC RELIEF AUDIT & TRANSPARENCY
        </span>
        <h2 style="margin:0 0 8px; font-size:24px; color:var(--ink);">
          <?= t('guest_transparency_title') ?>
        </h2>
        <p style="margin:0; font-size:13.5px; color:var(--ink-soft); line-height:1.6;">
          <?= t('guest_transparency_sub') ?>
        </p>
      </div>
      <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a href="sos_portal.php" class="btn" style="background:var(--alert);color:#fff;text-decoration:none;font-size:12.5px;padding:10px 16px;box-shadow:0 4px 14px rgba(255,107,107,0.35);">
          🚨 <?= $lang==='bn'?'জরুরি উদ্ধার ও এসওএস':'Emergency SOS' ?>
        </a>
        <a href="weather_alerts.php" class="btn btn-ghost" style="text-decoration:none;font-size:12.5px;padding:10px 16px;">
          🌊 <?= $lang==='bn'?'লাইভ বন্যা পূর্বাভাস':'Live River Gauges' ?>
        </a>
      </div>
    </div>

    <!-- Quick Stats Grid -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:12px; margin-top:20px; padding-top:18px; border-top:1px solid var(--border);">
      <div style="background:var(--surface); border:1px solid var(--border); border-radius:10px; padding:12px 16px;">
        <div style="font-size:11.5px; color:var(--ink-soft);"><?= $lang==='bn'?'সর্বমোট ত্রাণ বিতরণ':'Total Distributions' ?></div>
        <div style="font-size:22px; font-weight:800; font-family:'Space Grotesk',sans-serif; color:var(--ink);"><?= number_format($totalDistributions) ?>+</div>
      </div>
      <div style="background:var(--surface); border:1px solid var(--border); border-radius:10px; padding:12px 16px;">
        <div style="font-size:11.5px; color:var(--ink-soft);"><?= $lang==='bn'?'আওতাভুক্ত পরিবার':'Covered Families' ?></div>
        <div style="font-size:22px; font-weight:800; font-family:'Space Grotesk',sans-serif; color:var(--safe);"><?= number_format($totalFamilies) ?>+</div>
      </div>
      <div style="background:var(--surface); border:1px solid var(--border); border-radius:10px; padding:12px 16px;">
        <div style="font-size:11.5px; color:var(--ink-soft);"><?= $lang==='bn'?'কার্যক্রমরত এনজিও':'Partner NGOs' ?></div>
        <div style="font-size:22px; font-weight:800; font-family:'Space Grotesk',sans-serif; color:var(--primary);"><?= number_format($totalNgos) ?></div>
      </div>
      <div style="background:var(--surface); border:1px solid var(--border); border-radius:10px; padding:12px 16px;">
        <div style="font-size:11.5px; color:var(--ink-soft);"><?= $lang==='bn'?'সক্রিয় ত্রাণ এলাকা':'Active Areas' ?></div>
        <div style="font-size:22px; font-weight:800; font-family:'Space Grotesk',sans-serif; color:var(--water);"><?= number_format($totalAreas) ?></div>
      </div>
    </div>
  </div>

  <!-- Search & Area Transparency Table -->
  <div class="card" style="margin-bottom:28px;">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:16px;">
      <div>
        <h3 style="margin:0; font-size:18px; color:var(--ink); display:flex; align-items:center; gap:8px;">
          📍 <?= $lang==='bn' ? 'উপজেলা ও এলাকাভিত্তিক ত্রাণ বিতরণের বর্তমান অবস্থা' : 'Area-wise Relief Status & Next Cycle' ?>
        </h3>
        <p style="margin:4px 0 0; font-size:12.5px; color:var(--ink-soft);">
          <?= $lang==='bn' ? 'কোন এলাকায় কবে শেষ ত্রাণ দেওয়া হয়েছে, কোন এনজিও দিয়েছে এবং আবার কবে দেওয়া সম্ভব:' : 'Track when each area received relief, providing NGOs, and estimated next cycle:' ?>
        </p>
      </div>

      <div>
        <input type="text" id="areaSearchInput" placeholder="<?= $lang==='bn' ? '🔍 উপজেলা বা জেলা দিয়ে খুঁজুন...' : '🔍 Search by Upazila or District...' ?>" style="padding:8px 14px; font-size:13px; background:var(--input-bg); color:var(--ink); border:1px solid var(--border); border-radius:8px; width:220px;">
      </div>
    </div>

    <div style="overflow-x:auto;">
      <table style="width:100%; border-collapse:collapse; font-size:13.5px;" id="transparencyTable">
        <thead>
          <tr style="border-bottom:1px solid var(--border); color:var(--ink-soft); text-align:left;">
            <th style="padding:12px 8px;"><?= $lang==='bn'?'এলাকা / উপজেলা':'Area / Upazila' ?></th>
            <th style="padding:12px 8px;"><?= $lang==='bn'?'শেষ ত্রাণ বিতরণের তারিখ':'Last Distribution' ?></th>
            <th style="padding:12px 8px;"><?= $lang==='bn'?'প্রদানকারী এনজিও':'Distributing NGOs' ?></th>
            <th style="padding:12px 8px;"><?= $lang==='bn'?'বিতরণকৃত ক্যাটাগরি':'Categories Delivered' ?></th>
            <th style="padding:12px 8px;"><?= $lang==='bn'?'আবার কবে দেওয়া যাবে (Next Cycle)':'Next Eligible Cycle' ?></th>
            <th style="padding:12px 8px;"><?= $lang==='bn'?'বর্তমান স্ট্যাটাস':'Status' ?></th>
          </tr>
        </thead>
        <tbody>
          <?php
          $now = time();
          foreach ($areaFeed as $row):
            $lastTime = strtotime($row['last_date']);
            $daysAgo = floor(($now - $lastTime) / 86400);
            $nextCycleDate = date('d M Y', strtotime('+7 days', $lastTime));
            $daysUntilNext = max(0, 7 - $daysAgo);

            if ($daysAgo <= 2) {
                $statusPill = '<span class="status-pill approved">🟢 ' . ($lang==='bn' ? 'সম্প্রতি সমাপ্ত' : 'Recently Covered') . '</span>';
                $statusDesc = $lang==='bn' ? 'পর্যাপ্ত ত্রাণ রয়েছে' : 'Adequate supply';
            } elseif ($daysAgo <= 6) {
                $statusPill = '<span class="status-pill pending">🟡 ' . ($lang==='bn' ? 'ত্রাণ সক্রিয়' : 'Active Cycle') . '</span>';
                $statusDesc = sprintf($lang==='bn' ? 'আরও %d দিন পর পরবর্তী বরাদ্দ' : 'Next cycle in %d days', $daysUntilNext);
            } else {
                $statusPill = '<span class="status-pill dispatched">🔵 ' . ($lang==='bn' ? 'পরবর্তী সাইকেল উপযোগী' : 'Eligible for Next Relief') . '</span>';
                $statusDesc = $lang==='bn' ? 'নতুন বরাদ্দের জন্য প্রস্তুত' : 'Ready for next dispatch';
            }
          ?>
          <tr class="area-row" style="border-bottom:1px solid rgba(255,255,255,0.04);">
            <td style="padding:12px 8px;">
              <b><?= htmlspecialchars(tEntity($row['upazila'])) ?></b>
              <div style="font-size:11.5px; color:var(--ink-soft);"><?= htmlspecialchars(tEntity($row['district'])) ?></div>
            </td>
            <td style="padding:12px 8px; font-family:'Space Grotesk',sans-serif;">
              <div><b><?= date('d M Y', $lastTime) ?></b></div>
              <small style="color:var(--ink-soft); font-size:11.5px;">
                <?= sprintf($lang==='bn' ? '(%d দিন আগে)' : '(%d days ago)', $daysAgo) ?>
              </small>
            </td>
            <td style="padding:12px 8px; color:var(--primary); font-size:13px; max-width:200px;">
              <?= htmlspecialchars(tEntity($row['ngos'])) ?>
            </td>
            <td style="padding:12px 8px; font-size:12.5px;">
              <?= htmlspecialchars($row['categories'] ?: 'Food, Medical') ?>
            </td>
            <td style="padding:12px 8px;">
              <div style="font-family:'Space Grotesk',sans-serif; font-weight:700; color:var(--water);">
                <?= $nextCycleDate ?>
              </div>
              <small style="color:var(--ink-soft); font-size:11px;">
                <?= $statusDesc ?>
              </small>
            </td>
            <td style="padding:12px 8px;">
              <?= $statusPill ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Live Recent Public Stream -->
  <div class="card" style="margin-bottom:28px;">
    <h3 style="margin:0 0 14px; font-size:17px; color:var(--ink); display:flex; align-items:center; gap:8px;">
      📡 <?= $lang==='bn' ? 'সর্বশেষ সরাসরি ত্রাণ বিতরণ স্ট্রিম' : 'Live Relief Distribution Stream' ?>
    </h3>
    <div style="overflow-x:auto;">
      <table style="width:100%; border-collapse:collapse; font-size:13px;">
        <thead>
          <tr style="border-bottom:1px solid var(--border); color:var(--ink-soft); text-align:left;">
            <th style="padding:8px;"><?= t('th_date') ?></th>
            <th style="padding:8px;"><?= $lang==='bn'?'এলাকা':'Area' ?></th>
            <th style="padding:8px;"><?= t('th_item') ?></th>
            <th style="padding:8px;"><?= t('th_quantity') ?></th>
            <th style="padding:8px;"><?= $lang==='bn'?'এনজিও':'NGO' ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentStream as $r): ?>
          <tr style="border-bottom:1px solid rgba(255,255,255,0.03);">
            <td style="padding:10px 8px; color:var(--ink-soft); font-family:'Space Grotesk',sans-serif;">
              <?= date('d M Y, h:i A', strtotime($r['dist_date'])) ?>
            </td>
            <td style="padding:10px 8px;">
              <?= htmlspecialchars(tEntity($r['upazila'])) ?>, <small><?= htmlspecialchars(tEntity($r['district'])) ?></small>
            </td>
            <td style="padding:10px 8px;">
              <b><?= htmlspecialchars(tEntity($r['item_name'])) ?></b>
              <span class="tag tag-<?= strtolower($r['category'] ?? 'food') ?>" style="margin-left:4px; font-size:10px; padding:1px 5px;">
                <?= htmlspecialchars(tCategory($r['category'])) ?>
              </span>
            </td>
            <td style="padding:10px 8px; font-family:'Space Grotesk',sans-serif; font-weight:700;">
              <?= (float)$r['quantity'] ?> <?= htmlspecialchars(tEntity($r['unit'])) ?>
            </td>
            <td style="padding:10px 8px; color:var(--primary);">
              <?= htmlspecialchars(tEntity($r['ngo_name'])) ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<script>
// Filter area transparency table
document.getElementById('areaSearchInput')?.addEventListener('input', function(e) {
  const q = e.target.value.toLowerCase().trim();
  document.querySelectorAll('#transparencyTable tbody .area-row').forEach(row => {
    const text = row.textContent.toLowerCase();
    row.style.display = text.includes(q) ? '' : 'none';
  });
});
</script>

<?php require 'includes/footer.php'; ?>
