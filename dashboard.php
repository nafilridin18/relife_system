<?php
$pageTitle = 'Fairness Dashboard — Relief Distribution System';
require 'config.php';
require 'includes/auth.php';
requireRole(['admin', 'ngo_operator']);
$user = currentUser();

$totalFamilies = $pdo->query("SELECT COUNT(*) c FROM family")->fetch()['c'];

if ($user['role'] === 'admin') {
    $totalDist     = $pdo->query("SELECT COUNT(*) c FROM distribution")->fetch()['c'];
    $totalBlocked  = $pdo->query("SELECT COUNT(*) c FROM duplicate_log")->fetch()['c'];
    $kpi4Count     = $pdo->query("SELECT COUNT(*) c FROM ngo")->fetch()['c'];
    $kpi4Label     = t('kpi_ngo');
} else {
    $stmt = $pdo->prepare("SELECT COUNT(*) c FROM distribution WHERE ngo_id = ?");
    $stmt->execute([$user['ngo_id']]);
    $totalDist     = $stmt->fetch()['c'];

    $stmt = $pdo->prepare("SELECT COUNT(*) c FROM duplicate_log WHERE attempted_ngo_id = ?");
    $stmt->execute([$user['ngo_id']]);
    $totalBlocked  = $stmt->fetch()['c'];

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity_available), 0) c FROM ngo_stock WHERE ngo_id = ?");
    $stmt->execute([$user['ngo_id']]);
    $kpi4Count     = (int)$stmt->fetch()['c'];
    $kpi4Label     = t('kpi_my_stock');
}

$fairness = $pdo->query("SELECT * FROM v_area_fairness")->fetchAll();

// NGO-wise totals, most active first
$ngoTotals = $pdo->query("
    SELECT n.ngo_name, COUNT(*) AS dist_count, COALESCE(SUM(d.quantity), 0) AS total_qty
    FROM ngo n
    LEFT JOIN distribution d ON d.ngo_id = n.ngo_id
    GROUP BY n.ngo_id, n.ngo_name
    ORDER BY dist_count DESC
")->fetchAll();
$maxNgoCount = max(1, ...array_column($ngoTotals, 'dist_count'));

// Weekly trend, last 8 weeks
$weekly = $pdo->query("
    SELECT YEARWEEK(dist_date, 3) AS yw, DATE(MIN(dist_date)) AS week_start, COUNT(*) AS cnt
    FROM distribution
    WHERE dist_date >= NOW() - INTERVAL 56 DAY
    GROUP BY yw
    ORDER BY yw
")->fetchAll();
$maxWeekCount = max(1, ...array_column($weekly, 'cnt'));

// Centroid coordinates for the 8 relief zones
$areaCoords = [
    1 => [25.0658, 91.3950], // সুনামগঞ্জ সদর
    2 => [25.5423, 89.6969], // কুড়িগ্রাম চর (Chilmari)
    3 => [25.4318, 89.5397], // গাইবান্ধা উত্তর (Sundarganj)
    4 => [25.0333, 92.1667], // সিলেট কোম্পানীগঞ্জ
    5 => [25.0500, 90.6833], // নেত্রকোণা দুর্গাপুর
    6 => [25.1500, 89.8333], // জামালপুর ইসলামপুর
    7 => [24.7667, 89.5833], // বগুড়া সারিয়াকান্দি
    8 => [25.7167, 89.4333], // রংপুর কাউনিয়া
];
$mapAreas = [];
foreach ($fairness as $row) {
    if (!isset($areaCoords[$row['area_id']])) continue;
    $ratio = $row['fairness_ratio'] === null ? 0 : (float)$row['fairness_ratio'];
    $mapAreas[] = [
        'name'       => tEntity($row['area_name']),
        'lat'        => $areaCoords[$row['area_id']][0],
        'lng'        => $areaCoords[$row['area_id']][1],
        'population' => (int)$row['population'],
        'received'   => (int)$row['total_received'],
        'ratio'      => $ratio,
    ];
}

$extraHead = '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">';

require 'includes/header.php';
?>

<div class="kpis">
  <div class="kpi"><div class="n" data-count="<?= $totalFamilies ?>">0</div><div class="l"><?= t('kpi_families') ?></div></div>
  <div class="kpi"><div class="n" data-count="<?= $totalDist ?>">0</div><div class="l"><?= t('kpi_distributions') ?></div></div>
  <div class="kpi"><div class="n" data-count="<?= $totalBlocked ?>">0</div><div class="l"><?= t('kpi_blocked') ?></div></div>
  <div class="kpi"><div class="n" data-count="<?= $kpi4Count ?>">0</div><div class="l"><?= $kpi4Label ?></div></div>
</div>

<div class="card">
  <h2><?= icon('map-pin') ?><?= t('map_h2') ?></h2>
  <p class="sub"><?= t('map_sub') ?></p>
  <div id="reliefMap" class="map-box"></div>
  <div class="map-legend">
    <span><i style="background:var(--alert)"></i> <?= t('legend_low') ?></span>
    <span><i style="background:var(--warn)"></i> <?= t('legend_partial') ?></span>
    <span><i style="background:var(--safe)"></i> <?= t('legend_fair') ?></span>
  </div>
</div>

<!-- =========================================================================
     ReliefX v3.0: AI Flood Risk Prediction Widget (FastAPI Microservice)
     ========================================================================= -->
<div class="card" id="aiFloodAlertCard">
  <div class="ai-section-header">
    <h2>
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:8px;color:var(--alert);">
        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
      </svg>
      <?= currentLang() === 'bn' ? 'AI বন্যা ঝুঁকি পূর্বাভাস ও সতর্কতা (Flood Prediction)' : 'AI Flood Risk Early Warning & Prediction' ?>
    </h2>
    <span class="ai-badge-live">
      <span class="ai-pulse-dot"></span>
      FastAPI v3.0 Active
    </span>
  </div>
  <p class="sub">
    <?= currentLang() === 'bn' 
      ? 'হাইড্রোলজিক্যাল সেন্সর ডেটা ও আবহাওয়া পূর্বাভাসের ভিত্তিতে AI মডেল (XGBoost/LSTM) দ্বারা রিয়েল-টাইম পূর্বাভাস।' 
      : 'Real-time hydrological forecasting and risk scoring powered by the ReliefX AI microservice.' ?>
  </p>

  <!-- Zone Selector Pills -->
  <div class="ai-zone-pills" id="aiZonePills">
    <button type="button" class="ai-zone-pill active" data-zone-id="2" data-lat="25.8073" data-lon="89.6295"><?= currentLang() === 'bn' ? 'কুড়িগ্রাম চর' : 'Kurigram Char' ?></button>
    <button type="button" class="ai-zone-pill" data-zone-id="1" data-lat="25.0658" data-lon="91.4073"><?= currentLang() === 'bn' ? 'সুনামগঞ্জ সদর' : 'Sunamganj Sadar' ?></button>
    <button type="button" class="ai-zone-pill" data-zone-id="6" data-lat="25.0833" data-lon="89.7833"><?= currentLang() === 'bn' ? 'জামালপুর ইসলামপুর' : 'Jamalpur Islampur' ?></button>
    <button type="button" class="ai-zone-pill" data-zone-id="4" data-lat="25.0815" data-lon="91.7523"><?= currentLang() === 'bn' ? 'সিলেট কোম্পানীগঞ্জ' : 'Sylhet Companiganj' ?></button>
    <button type="button" class="ai-zone-pill" data-zone-id="3" data-lat="25.3297" data-lon="89.5430"><?= currentLang() === 'bn' ? 'গাইবান্ধা উত্তর' : 'Gaibandha North' ?></button>
  </div>

  <!-- Dynamic Risk Banner -->
  <div id="aiPredictionContent">
    <div style="text-align:center;padding:24px;color:var(--ink-soft);">
      <span class="spinner" style="display:inline-block;width:18px;height:18px;vertical-align:middle;margin-right:8px;"></span>
      AI মাইক্রোসার্ভিস থেকে লাইভ পূর্বাভাস লোড হচ্ছে...
    </div>
  </div>
</div>

<div class="card">
  <h2><?= icon('scale') ?><?= t('fairness_h2') ?></h2>
  <p class="sub"><?= t('fairness_sub') ?></p>

  <?php foreach ($fairness as $row):
      $ratio = $row['fairness_ratio'] === null ? 0 : (float)$row['fairness_ratio'];
      $pct = min($ratio / 1.5, 1) * 100;
      $color = $ratio < 0.5 ? 'var(--alert)' : ($ratio < 1 ? 'var(--warn)' : 'var(--safe)');
  ?>
  <div class="gauge-row">
    <div class="gauge-label"><?= htmlspecialchars(tEntity($row['area_name'])) ?>
      <small><?= t('population') ?>: <?= number_format($row['population']) ?> · <?= t('received') ?>: <?= $row['total_received'] ?> · <?= t('expected') ?>: <?= $row['expected_share'] ?></small>
    </div>
    <div class="gauge-track">
      <div class="gauge-mark" style="left:<?= (1/1.5)*100 ?>%;"></div>
      <div class="gauge-fill" data-width="<?= $pct ?>" style="background:<?= $color ?>;"></div>
    </div>
    <div class="gauge-val" style="color:<?= $color ?>;"><?= number_format($ratio, 2) ?>×</div>
  </div>
  <?php endforeach; ?>

  <div class="note"><?= t('fairness_note') ?></div>
</div>

<!-- =========================================================================
     ReliefX v3.0: AI Geo-Urgency & Prioritized Warehouse Dispatch Widget
     ========================================================================= -->
<div class="card" id="aiGeoUrgencyCard">
  <div class="ai-section-header">
    <h2>
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:8px;color:var(--accent);">
        <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
        <polyline points="2 17 12 22 22 17"></polyline>
        <polyline points="2 12 12 17 22 12"></polyline>
      </svg>
      <?= currentLang() === 'bn' ? 'AI জরুরি অগ্রাধিকার সূচক ও গুদাম বিতরণ তালিকা (Geo-Urgency Dispatch)' : 'AI Geo-Urgency & Warehouse Dispatch Prioritization' ?>
    </h2>
    <span class="ai-badge-live" style="background:rgba(255,148,87,0.16);color:var(--accent);border-color:rgba(255,148,87,0.3);">
      DBSCAN & Optimization Engine
    </span>
  </div>
  <p class="sub">
    <?= currentLang() === 'bn'
      ? 'স্বয়ংক্রিয় অগ্রাধিকার ফর্মুলা দ্বারা কোন দুর্গত এলাকায় অবিলম্বে কোন গুদাম থেকে কোন ত্রাণ সামগ্রী পাঠাতে হবে তার তালিকা।'
      : 'Automated urgency index based on flood severity, SOS density, and historically underserved factors.' ?>
  </p>

  <div class="formula-tag">
    📐 Formula: <b>Urgency Index = (0.45 × Flood_Risk) + (0.35 × SOS_Density) + (0.20 × Underserved_Factor)</b>
  </div>

  <div id="aiDispatchListContainer">
    <div style="text-align:center;padding:24px;color:var(--ink-soft);">
      <span class="spinner" style="display:inline-block;width:18px;height:18px;vertical-align:middle;margin-right:8px;"></span>
      AI জরুরি অগ্রাধিকার সূচক ও গুদাম তালিকা গণনা করা হচ্ছে...
    </div>
  </div>
</div>

<div class="card">
  <h2><?= icon('bar-chart') ?><?= t('ngo_perf_h2') ?></h2>
  <p class="sub"><?= t('ngo_perf_sub') ?></p>
  <?php if (!$ngoTotals): ?>
    <div class="empty"><?= t('no_records_yet') ?></div>
  <?php else: foreach ($ngoTotals as $row):
      $pct = $maxNgoCount > 0 ? ($row['dist_count'] / $maxNgoCount) * 100 : 0;
  ?>
  <div class="bar-row">
    <div class="bar-label"><?= htmlspecialchars(tEntity($row['ngo_name'])) ?></div>
    <div class="bar-track"><div class="bar-fill" data-width="<?= $pct ?>"></div></div>
    <div class="bar-val"><?= (int)$row['dist_count'] ?></div>
  </div>
  <?php endforeach; endif; ?>
</div>

<div class="card">
  <h2><?= icon('trending-up') ?><?= t('weekly_trend_h2') ?></h2>
  <p class="sub"><?= t('weekly_trend_sub') ?></p>
  <?php if (!$weekly): ?>
    <div class="empty"><?= t('no_records_yet') ?></div>
  <?php else: foreach ($weekly as $row):
      $pct = $maxWeekCount > 0 ? ($row['cnt'] / $maxWeekCount) * 100 : 0;
  ?>
  <div class="bar-row">
    <div class="bar-label"><?= (new DateTime($row['week_start']))->format('d M') ?></div>
    <div class="bar-track"><div class="bar-fill trend" data-width="<?= $pct ?>"></div></div>
    <div class="bar-val"><?= (int)$row['cnt'] ?></div>
  </div>
  <?php endforeach; endif; ?>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
const RELIEF_AREAS = <?= json_encode($mapAreas) ?>;
const MAP_I18N = {
  population: <?= json_encode(t('population')) ?>,
  received: <?= json_encode(t('received')) ?>,
  fairness: <?= json_encode(t('fairness_val_label')) ?>,
};
</script>

<?php require 'includes/footer.php'; ?>
