<?php
// Public-facing Live Weather Condition & Flood Early Warning Alert Portal
// Accessible to Everyone: Citizens, Beneficiaries, NGO Field Staff, and Admin
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
$pageTitle = 'Weather & Flood Early Warning Alerts — ReliefX';
require 'config.php';
require 'includes/auth.php';
require_once __DIR__ . '/includes/weather_data.php';
$lang = currentLang();

// Load stations list
$stations = getFloodMonitoringStations();

$defaultKey = 'kurigram';
$currentStation = $stations[$defaultKey] ?? reset($stations);

$isUserLoggedIn = !empty($_SESSION['user']);
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script>(function(){try{if(localStorage.getItem('reliefx_theme')==='light'){document.documentElement.setAttribute('data-theme','light');}}catch(e){}})();</script>
<title><?= $lang === 'bn' ? 'লাইভ আবহাওয়া ও বন্যা সতর্কতা পোর্টাল — ReliefX' : 'Live Weather & Flood Alert Portal — ReliefX' ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
<link rel="icon" type="image/png" href="img/logo-icon.png">
</head>
<body>

<div class="topbar" style="border-bottom:2px solid rgba(56,189,248,0.35);">
  <div class="brand">
    <div class="brand-mark"><img src="img/logo-icon.png" alt="ReliefX logo"></div>
    <div class="brand-text">
      <h1>ReliefX Weather & Flood Alerts</h1>
      <span><?= $lang === 'bn' ? 'জাতীয় বন্যা পূর্বাভাস ও সার্বক্ষণিক আবহাওয়া ট্র্যাকিং সেল' : 'National Hydrological Monitoring & Flood Warning Cell' ?></span>
    </div>
  </div>
  <div class="top-controls">
    <button type="button" class="theme-toggle-btn" aria-label="Toggle theme">
      <span class="icon-sun"><?= icon('sun',15) ?></span>
      <span class="icon-moon"><?= icon('moon',15) ?></span>
    </button>
    <div class="lang-toggle">
      <a href="switch_lang.php?lang=bn" class="<?= $lang==='bn'?'active':'' ?>">বাংলা</a>
      <a href="switch_lang.php?lang=en" class="<?= $lang==='en'?'active':'' ?>">EN</a>
    </div>
    <a href="sos_portal.php" class="btn" style="font-size:11.5px;padding:5px 11px;background:rgba(255,107,107,0.18);color:var(--alert);border:1px solid rgba(255,107,107,0.35);text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
      <span class="ai-pulse-dot" style="background:var(--alert);width:6px;height:6px;"></span>
      <span>SOS Portal</span>
    </a>
    <?php if ($isUserLoggedIn): ?>
      <a href="dashboard.php" class="btn btn-ghost" style="font-size:12px;padding:5px 12px;"><?= $lang==='bn'?'ড্যাশবোর্ড →':'Dashboard →' ?></a>
    <?php else: ?>
      <a href="index.php" class="btn" style="font-size:11.5px;padding:5px 12px;background:var(--primary);color:#fff;text-decoration:none;"><?= $lang==='bn'?'লগইন':'Login' ?></a>
    <?php endif; ?>
  </div>
</div>

<?php if ($isUserLoggedIn): 
  $user = currentUser();
  $currentPage = 'weather_alerts.php';
?>
<div class="tabs">
  <?php if ($user['role'] === 'citizen'): ?>
    <a class="tab-btn" href="citizen_dashboard.php"><?= icon('package',16) ?><span><?= t('tab_citizen_dashboard') ?></span></a>
    <a class="tab-btn active" href="weather_alerts.php"><?= icon('cloud-rain',16) ?><span><?= t('tab_weather_alerts') ?></span></a>
    <a class="tab-btn" href="sos_portal.php"><?= icon('shield-check',16) ?><span>SOS Portal</span></a>
  <?php else: ?>
    <a class="tab-btn" href="dashboard.php"><?= icon('scale',16) ?><span><?= t('tab_dashboard') ?></span></a>
    <a class="tab-btn active" href="weather_alerts.php"><?= icon('cloud-rain',16) ?><span><?= t('tab_weather_alerts') ?></span></a>
    <a class="tab-btn" href="distribute.php"><?= icon('package',16) ?><span><?= t('tab_distribute') ?></span></a>
    <a class="tab-btn" href="register_family.php"><?= icon('users',16) ?><span><?= t('tab_register_family') ?></span></a>
    <a class="tab-btn" href="stock.php"><?= icon('box',16) ?><span><?= t('tab_stock') ?></span></a>
    <a class="tab-btn" href="profile.php"><?= icon('user',16) ?><span><?= t('tab_profile') ?></span></a>
    <?php if ($user['role']==='admin'): ?>
    <a class="tab-btn" href="family_list.php"><?= icon('users',16) ?><span><?= t('tab_family_list') ?></span></a>
    <a class="tab-btn" href="duplicate_log.php"><?= icon('ban',16) ?><span><?= t('tab_duplicate_log') ?></span></a>
    <?php endif; ?>
  <?php endif; ?>
</div>
<?php endif; ?>

<div class="wrap" style="max-width:1080px; margin:22px auto; padding:0 16px;">

  <!-- Live Emergency Broadcast Marquee Ticker -->
  <div class="alert-ticker-wrap">
    <div class="alert-ticker-badge">
      <span class="ai-pulse-dot" style="background:#fff;width:6px;height:6px;"></span>
      LIVE ALERT
    </div>
    <div class="alert-ticker-text" id="liveAlertTicker">
      <?= $lang === 'bn' 
        ? '🚨 জরুরি বন্যা সতর্কতা: ধরলা, সুরমা ও মুহুরী নদীর পানি বিপদসীমার উপর দিয়ে প্রবাহিত হচ্ছে। নিচু চরাঞ্চলের বাসিন্দাদের নিরাপদ আশ্রয়ে যাওয়ার অনুরোধ করা হলো।' 
        : '🚨 CRITICAL FLOOD WARNING: Dharla, Surma, and Muhuri river stages are flowing significantly above danger marks. Char basin residents advised to seek shelter.' ?>
    </div>
  </div>

  <!-- Hero Header -->
  <div class="weather-hero-banner">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:14px;">
      <div>
        <h1 style="margin:0 0 6px;font-size:26px;font-weight:800;color:var(--ink);display:flex;align-items:center;gap:10px;">
          <span style="color:var(--water);"><?= icon('cloud-rain', 26) ?></span>
          <?= $lang === 'bn' ? 'লাইভ আবহাওয়া ও নদী প্রবাহ সতর্কতা' : 'Live Hydrological & Meteorological Monitoring' ?>
        </h1>
        <p style="margin:0;color:var(--ink-soft);font-size:14px;max-width:680px;">
          <?= $lang === 'bn' 
            ? 'বাংলাদেশ বন্যা পূর্বাভাস ও সতর্কীকরণ কেন্দ্র (FFWC) এবং রিয়েল-টাইম সেন্সর ট্র্যাকিং। এই তথ্য সাধারণ নাগরিক, স্বেচ্ছাসেবক এবং এনজিওদের জন্য উন্মুক্ত।' 
            : 'Continuous water level gauges, danger delta monitoring, and precipitation tracking for vulnerable districts. Publicly open for citizens and humanitarian teams.' ?>
        </p>
      </div>
      <div style="display:flex;gap:10px;align-items:center;">
        <span class="tag safe" style="padding:6px 12px;font-size:12px;">
          <span class="ai-pulse-dot" style="background:var(--safe);width:6px;height:6px;"></span>
          <?= $lang === 'bn' ? 'লাইভ সেন্সর সক্রিয়' : 'Live Sensors Active' ?>
        </span>
        <button type="button" class="btn btn-ghost" onclick="refreshStationData()" style="font-size:12px;padding:6px 12px;">
          🔄 <?= $lang === 'bn' ? 'রিফ্রেশ' : 'Refresh' ?>
        </button>
      </div>
    </div>
  </div>

  <!-- Station Selection Pills -->
  <div style="margin-bottom:8px;">
    <div style="font-size:12px;font-weight:700;color:var(--ink-soft);text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;">
      📍 <?= $lang === 'bn' ? 'পর্যবেক্ষণ কেন্দ্র নির্বাচন করুন (Select Monitoring Station):' : 'Select Monitoring Station / River Basin:' ?>
    </div>
    <div class="weather-pills-scroll" id="stationPills">
      <?php foreach ($stations as $key => $stn): 
        $isActive = ($key === $defaultKey);
        $lvl = strtolower($stn['alert_level']);
        $label = $lang === 'bn' ? $stn['district_bn'] : $stn['district_en'];
        $river = $lang === 'bn' ? $stn['river_bn'] : $stn['river_en'];
      ?>
        <button type="button" class="weather-station-btn <?= $isActive ? 'active' : '' ?>" data-station="<?= $key ?>" onclick="selectStation('<?= $key ?>')">
          <span><?= htmlspecialchars($label) ?></span>
          <span class="station-status-pill <?= $lvl ?>"><?= $stn['alert_level'] ?></span>
        </button>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Main Active Station View -->
  <div id="stationDetailsContainer">
    <!-- Rendered dynamically by JavaScript with initial PHP fallback -->
    <?php
      $deltaColor = $currentStation['delta_m'] > 0 ? 'var(--alert)' : 'var(--safe)';
      $deltaPrefix = $currentStation['delta_m'] > 0 ? '+' : '';
      $deltaText = $currentStation['delta_m'] > 0 
        ? ($lang === 'bn' ? 'বিপদসীমার উপরে' : 'Above Danger Level')
        : ($lang === 'bn' ? 'বিপদসীমার নিচে' : 'Below Danger Level');
      $lvlClass = strtolower($currentStation['alert_level']);
    ?>

    <!-- River Hydrograph Gauge Card -->
    <div class="hydrograph-box">
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;border-bottom:1px solid var(--border);padding-bottom:12px;margin-bottom:16px;">
        <div>
          <span class="station-status-pill <?= $lvlClass ?>" style="font-size:12px;padding:4px 12px;margin-right:8px;">
            <?= $currentStation['alert_level'] ?> ALERT
          </span>
          <span style="font-size:14px;color:var(--ink-soft);font-weight:600;">
            Station: <b><?= htmlspecialchars($currentStation['station_id']) ?></b>
          </span>
        </div>
        <div style="font-size:13px;color:var(--ink-soft);">
          <?= $lang === 'bn' ? 'নদী প্রবাহ গতি:' : 'River Trend:' ?> 
          <b style="color:var(--alert);"><?= $lang === 'bn' ? 'উর্ধ্বমুখী ↗ (পানি বাড়ছে)' : 'Rapidly Rising ↗' ?></b>
        </div>
      </div>

      <div class="river-level-display">
        <div>
          <div style="font-size:13px;color:var(--ink-soft);font-weight:600;margin-bottom:4px;">
            <?= $lang === 'bn' ? $currentStation['station_name_bn'] : $currentStation['station_name_en'] ?>
          </div>
          <div class="river-level-big">
            <span id="dispWaterLevel"><?= number_format($currentStation['water_level_m'], 2) ?></span> <small>m</small>
          </div>
          <div style="font-size:13px;color:var(--ink-soft);margin-top:4px;">
            <?= $lang === 'bn' ? 'নদী:' : 'River Basin:' ?> <b><?= $lang === 'bn' ? $currentStation['river_bn'] : $currentStation['river_en'] ?></b>
          </div>
        </div>

        <div style="text-align:right;">
          <div style="font-size:12px;color:var(--ink-soft);font-weight:600;"><?= $lang === 'bn' ? 'অফিসিয়াল বিপদসীমা' : 'OFFICIAL DANGER MARK' ?></div>
          <div style="font-size:22px;font-weight:700;color:var(--ink);font-family:'Space Grotesk',sans-serif;">
            <?= number_format($currentStation['danger_level_m'], 2) ?>m
          </div>
          <div style="font-size:16px;font-weight:800;color:<?= $deltaColor ?>;margin-top:4px;font-family:'Space Grotesk',sans-serif;">
            <?= $deltaPrefix ?><?= number_format($currentStation['delta_m'], 2) ?>m <span style="font-size:12px;"><?= $deltaText ?></span>
          </div>
        </div>
      </div>

      <!-- Graphical River Stage Visualizer -->
      <?php
        $maxScale = max($currentStation['danger_level_m'] * 1.25, $currentStation['water_level_m'] * 1.15);
        $waterPct = min(100, max(5, ($currentStation['water_level_m'] / $maxScale) * 100));
        $dangerPct = min(100, max(5, ($currentStation['danger_level_m'] / $maxScale) * 100));
      ?>
      <div style="position:relative; margin-top:20px;">
        <div class="river-gauge-visual">
          <div class="river-water-track" style="width:<?= $waterPct ?>%;"></div>
          <div class="river-danger-marker" style="left:<?= $dangerPct ?>%;">
            <div class="river-danger-text">⚠️ <?= $lang === 'bn' ? 'বিপদসীমা' : 'Danger Mark' ?> (<?= $currentStation['danger_level_m'] ?>m)</div>
          </div>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--ink-soft);margin-top:6px;">
          <span>0m (Bed)</span>
          <span><?= $lang === 'bn' ? 'পানির স্তর পরিমাপক স্কেল' : 'Relative Hydrological Stage' ?></span>
          <span><?= number_format($maxScale, 1) ?>m</span>
        </div>
      </div>
    </div>

    <!-- Weather Stats Grid -->
    <div class="weather-kpi-grid">
      <div class="weather-kpi-card">
        <div class="kpi-label"><?= icon('thermometer', 15) ?> <?= $lang === 'bn' ? 'তাপমাত্রা ও আকাশ' : 'Temperature' ?></div>
        <div class="kpi-val"><?= $currentStation['weather']['temp_c'] ?>°C</div>
        <div class="kpi-sub"><?= $lang === 'bn' ? $currentStation['weather']['condition_bn'] : $currentStation['weather']['condition_en'] ?></div>
      </div>
      <div class="weather-kpi-card">
        <div class="kpi-label"><?= icon('droplet', 15) ?> <?= $lang === 'bn' ? '২৪ ঘণ্টার বৃষ্টিপাত' : '24h Rainfall' ?></div>
        <div class="kpi-val" style="color:var(--water);"><?= $currentStation['weather']['rainfall_24h_mm'] ?> <small style="font-size:14px;">mm</small></div>
        <div class="kpi-sub"><?= $lang === 'bn' ? '৭২ ঘণ্টায় প্রক্ষেপণ: ' . $currentStation['weather']['rainfall_72h_mm'] . ' mm' : '72h Influx: ' . $currentStation['weather']['rainfall_72h_mm'] . ' mm' ?></div>
      </div>
      <div class="weather-kpi-card">
        <div class="kpi-label"><?= icon('wind', 15) ?> <?= $lang === 'bn' ? 'বাতাসের গতি ও দিক' : 'Wind Speed & Dir' ?></div>
        <div class="kpi-val"><?= $currentStation['weather']['wind_kmh'] ?> <small style="font-size:14px;">km/h</small></div>
        <div class="kpi-sub"><?= $currentStation['weather']['wind_dir'] ?></div>
      </div>
      <div class="weather-kpi-card">
        <div class="kpi-label"><?= icon('zap', 15) ?> <?= $lang === 'bn' ? 'আর্দ্রতা ও বায়ুচাপ' : 'Humidity & Pressure' ?></div>
        <div class="kpi-val"><?= $currentStation['weather']['humidity_pct'] ?>%</div>
        <div class="kpi-sub"><?= $currentStation['weather']['pressure_hpa'] ?> hPa · Vis: <?= $currentStation['weather']['visibility_km'] ?> km</div>
      </div>
    </div>

    <!-- 3-Day Forecast Cards -->
    <div class="card" style="padding:20px;margin-bottom:20px;">
      <h3 style="margin:0 0 14px;font-size:16px;display:flex;align-items:center;gap:8px;">
        <?= icon('cloud-rain', 18) ?>
        <?= $lang === 'bn' ? '৩ দিনের আবহাওয়া ও বৃষ্টিপাত পূর্বাভাস' : '3-Day Weather & Precipitation Outlook' ?>
      </h3>
      <div class="forecast-grid">
        <?php foreach ($currentStation['forecast_3days'] as $fc): ?>
          <div class="forecast-card">
            <div class="f-day"><?= $lang === 'bn' ? $fc['day_bn'] : $fc['day'] ?></div>
            <div style="font-size:24px;margin:4px 0;">🌧️</div>
            <div class="f-rain"><?= $fc['rain_mm'] ?> mm</div>
            <div class="f-cond"><?= $lang === 'bn' ? $fc['condition_bn'] : $fc['condition_en'] ?> · <b><?= $fc['temp'] ?></b></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Local Community Evacuation Advisory & Nearest Shelters -->
    <div class="grid2">
      <!-- Advisory Box -->
      <div class="card" style="border-left:4px solid var(--alert);">
        <h3 style="margin:0 0 8px;font-size:16px;color:var(--alert);display:flex;align-items:center;gap:8px;">
          <?= icon('alert-triangle', 18) ?>
          <?= $lang === 'bn' ? 'স্থানীয় প্রশাসন ও আবহাওয়া নির্দেশনা' : 'Evacuation Advisory & Warning' ?>
        </h3>
        <p style="font-size:13.5px;line-height:1.55;color:var(--ink);margin:0 0 14px;">
          <?= $lang === 'bn' ? $currentStation['advisory_bn'] : $currentStation['advisory_en'] ?>
        </p>

        <div style="background:rgba(0,0,0,0.25);border-radius:8px;padding:12px;font-size:12.5px;color:var(--ink-soft);line-height:1.5;">
          <b><?= $lang === 'bn' ? 'জরুরি পরামর্শ:' : 'Emergency Precautions:' ?></b><br>
          <?= $lang === 'bn'
            ? '• বিদ্যুৎ সংযোগ বিচ্ছিন্ন রাখুন এবং মোবাইল ফোনে পর্যাপ্ত চার্জ রাখুন।<br>• পানি ফুটিয়ে বা হ্যালোজেন ট্যাবলেট ব্যবহার করে পান করুন।<br>• গবাদিপশু ও জরুরি দলিলপত্র পলিথিনে মুড়িয়ে সাথে রাখুন।'
            : '• Disconnect residential electrical mains if water enters homestead.<br>• Always use boiled or chlorinated water.<br>• Keep vital documents wrapped in sealed plastic.' ?>
        </div>
      </div>

      <!-- Nearest Flood Shelters -->
      <div class="card">
        <h3 style="margin:0 0 12px;font-size:16px;display:flex;align-items:center;gap:8px;">
          <?= icon('shield-check', 18) ?>
          <?= $lang === 'bn' ? 'নিকটস্থ আশ্রয়কেন্দ্রসমূহ (Designated Shelters)' : 'Designated Flood Shelters' ?>
        </h3>
        <div id="sheltersListContainer">
          <?php foreach ($currentStation['shelters'] as $sh): ?>
            <div class="shelter-card-item">
              <div>
                <b style="font-size:13.5px;color:var(--ink);"><?= $lang === 'bn' ? $sh['name_bn'] : $sh['name_en'] ?></b>
                <div style="font-size:12px;color:var(--ink-soft);">
                  <?= $lang === 'bn' ? 'ধারণক্ষমতা:' : 'Capacity:' ?> <b><?= $sh['capacity'] ?> <?= $lang === 'bn' ? 'জন' : 'people' ?></b>
                </div>
              </div>
              <a href="tel:<?= $sh['contact'] ?>" class="btn btn-ghost" style="font-size:11.5px;padding:5px 10px;">
                📞 <?= $sh['contact'] ?>
              </a>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

  </div><!-- /#stationDetailsContainer -->

  <!-- Emergency Actions & Direct SOS Trigger -->
  <div class="card" style="margin-top:24px;background:linear-gradient(135deg, rgba(255,107,107,0.12), rgba(255,148,87,0.1));border:1px solid rgba(255,107,107,0.35);text-align:center;padding:24px;">
    <h2 style="margin:0 0 8px;color:var(--alert);font-size:22px;">
      <?= $lang === 'bn' ? 'আপনি বা আপনার পরিবার কি পানিবন্দী অবস্থায় আছেন?' : 'Are You or Your Family Stranded in Floodwaters?' ?>
    </h2>
    <p style="margin:0 auto 16px;max-width:620px;font-size:14px;color:var(--ink-soft);">
      <?= $lang === 'bn'
        ? 'বিলম্ব না করে আমাদের পাবলিক SOS পোর্টালে আপনার GPS লোকেশন পাঠিয়ে উদ্ধারকারী দলের সাহায্য নিন।'
        : 'Do not wait. Transmit your live GPS distress signal to tactical armed forces and rescue teams immediately.' ?>
    </p>
    <div style="display:flex;justify-content:center;gap:12px;flex-wrap:wrap;">
      <a href="sos_portal.php" class="btn" style="background:linear-gradient(135deg, var(--alert), var(--accent));font-size:15px;padding:12px 24px;font-weight:700;text-decoration:none;">
        🚨 <?= $lang === 'bn' ? 'জরুরি উদ্ধার সংকেত পাঠান (Open SOS Portal)' : 'Transmit Distress Signal (SOS Portal)' ?>
      </a>
      <a href="tel:999" class="btn btn-ghost" style="font-size:14px;padding:12px 20px;border-color:var(--alert);color:var(--alert);text-decoration:none;font-weight:700;">
        📞 ৯৯৯ জাতীয় জরুরি সেবা
      </a>
      <a href="tel:1090" class="btn btn-ghost" style="font-size:14px;padding:12px 20px;border-color:var(--warn);color:var(--warn);text-decoration:none;">
        📞 ১০৯০ দুর্যোগ হেল্পলাইন
      </a>
    </div>
  </div>

</div><!-- /.wrap -->

<script>
const STATIONS_DATA = <?= json_encode($stations, JSON_UNESCAPED_UNICODE) ?>;
const CUR_LANG_CODE = <?= json_encode($lang) ?>;

function selectStation(key) {
  // Update active pill
  document.querySelectorAll('.weather-station-btn').forEach(btn => {
    btn.classList.toggle('active', btn.dataset.station === key);
  });

  const station = STATIONS_DATA[key];
  if (!station) return;

  renderStationView(station);
}

function renderStationView(s) {
  const isBn = (CUR_LANG_CODE === 'bn');
  const deltaColor = s.delta_m > 0 ? 'var(--alert)' : 'var(--safe)';
  const deltaPrefix = s.delta_m > 0 ? '+' : '';
  const deltaText = s.delta_m > 0 ? (isBn ? 'বিপদসীমার উপরে' : 'Above Danger Level') : (isBn ? 'বিপদসীমার নিচে' : 'Below Danger Level');
  const lvlClass = s.alert_level.toLowerCase();

  const maxScale = Math.max(s.danger_level_m * 1.25, s.water_level_m * 1.15);
  const waterPct = Math.min(100, Math.max(5, (s.water_level_m / maxScale) * 100));
  const dangerPct = Math.min(100, Math.max(5, (s.danger_level_m / maxScale) * 100));

  const container = document.getElementById('stationDetailsContainer');
  if (!container) return;

  // Build forecast HTML
  const forecastHtml = (s.forecast_3days || []).map(fc => `
    <div class="forecast-card">
      <div class="f-day">${isBn ? fc.day_bn : fc.day}</div>
      <div style="font-size:24px;margin:4px 0;">🌧️</div>
      <div class="f-rain">${fc.rain_mm} mm</div>
      <div class="f-cond">${isBn ? fc.condition_bn : fc.condition_en} · <b>${fc.temp}</b></div>
    </div>
  `).join('');

  // Build shelters HTML
  const sheltersHtml = (s.shelters || []).map(sh => `
    <div class="shelter-card-item">
      <div>
        <b style="font-size:13.5px;color:var(--ink);">${isBn ? escapeHtml(sh.name_bn) : escapeHtml(sh.name_en)}</b>
        <div style="font-size:12px;color:var(--ink-soft);">
          ${isBn ? 'ধারণক্ষমতা:' : 'Capacity:'} <b>${sh.capacity} ${isBn ? 'জন' : 'people'}</b>
        </div>
      </div>
      <a href="tel:${sh.contact}" class="btn btn-ghost" style="font-size:11.5px;padding:5px 10px;">
        📞 ${sh.contact}
      </a>
    </div>
  `).join('');

  container.innerHTML = `
    <!-- River Hydrograph Gauge Card -->
    <div class="hydrograph-box">
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;border-bottom:1px solid var(--border);padding-bottom:12px;margin-bottom:16px;">
        <div>
          <span class="station-status-pill ${lvlClass}" style="font-size:12px;padding:4px 12px;margin-right:8px;">
            ${s.alert_level} ALERT
          </span>
          <span style="font-size:14px;color:var(--ink-soft);font-weight:600;">
            Station: <b>${escapeHtml(s.station_id)}</b>
          </span>
        </div>
        <div style="font-size:13px;color:var(--ink-soft);">
          ${isBn ? 'নদী প্রবাহ গতি:' : 'River Trend:'} 
          <b style="color:var(--alert);">${isBn ? 'উর্ধ্বমুখী ↗ (পানি বাড়ছে)' : 'Rapidly Rising ↗'}</b>
        </div>
      </div>

      <div class="river-level-display">
        <div>
          <div style="font-size:13px;color:var(--ink-soft);font-weight:600;margin-bottom:4px;">
            ${isBn ? escapeHtml(s.station_name_bn) : escapeHtml(s.station_name_en)}
          </div>
          <div class="river-level-big">
            <span>${parseFloat(s.water_level_m).toFixed(2)}</span> <small>m</small>
          </div>
          <div style="font-size:13px;color:var(--ink-soft);margin-top:4px;">
            ${isBn ? 'নদী:' : 'River Basin:'} <b>${isBn ? escapeHtml(s.river_bn) : escapeHtml(s.river_en)}</b>
          </div>
        </div>

        <div style="text-align:right;">
          <div style="font-size:12px;color:var(--ink-soft);font-weight:600;">${isBn ? 'অফিসিয়াল বিপদসীমা' : 'OFFICIAL DANGER MARK'}</div>
          <div style="font-size:22px;font-weight:700;color:var(--ink);font-family:'Space Grotesk',sans-serif;">
            ${parseFloat(s.danger_level_m).toFixed(2)}m
          </div>
          <div style="font-size:16px;font-weight:800;color:${deltaColor};margin-top:4px;font-family:'Space Grotesk',sans-serif;">
            ${deltaPrefix}${parseFloat(s.delta_m).toFixed(2)}m <span style="font-size:12px;">${deltaText}</span>
          </div>
        </div>
      </div>

      <div style="position:relative; margin-top:20px;">
        <div class="river-gauge-visual">
          <div class="river-water-track" style="width:${waterPct}%;"></div>
          <div class="river-danger-marker" style="left:${dangerPct}%;">
            <div class="river-danger-text">⚠️ ${isBn ? 'বিপদসীমা' : 'Danger Mark'} (${s.danger_level_m}m)</div>
          </div>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--ink-soft);margin-top:6px;">
          <span>0m (Bed)</span>
          <span>${isBn ? 'পানির স্তর পরিমাপক স্কেল' : 'Relative Hydrological Stage'}</span>
          <span>${maxScale.toFixed(1)}m</span>
        </div>
      </div>
    </div>

    <!-- Weather Stats Grid -->
    <div class="weather-kpi-grid">
      <div class="weather-kpi-card">
        <div class="kpi-label"><span style="color:var(--warn);">🌡️</span> ${isBn ? 'তাপমাত্রা ও আকাশ' : 'Temperature'}</div>
        <div class="kpi-val">${s.weather.temp_c}°C</div>
        <div class="kpi-sub">${isBn ? escapeHtml(s.weather.condition_bn) : escapeHtml(s.weather.condition_en)}</div>
      </div>
      <div class="weather-kpi-card">
        <div class="kpi-label"><span style="color:var(--water);">💧</span> ${isBn ? '২৪ ঘণ্টার বৃষ্টিপাত' : '24h Rainfall'}</div>
        <div class="kpi-val" style="color:var(--water);">${s.weather.rainfall_24h_mm} <small style="font-size:14px;">mm</small></div>
        <div class="kpi-sub">${isBn ? '৭২ ঘণ্টায় প্রক্ষেপণ: ' + s.weather.rainfall_72h_mm + ' mm' : '72h Influx: ' + s.weather.rainfall_72h_mm + ' mm'}</div>
      </div>
      <div class="weather-kpi-card">
        <div class="kpi-label"><span style="color:var(--accent);">💨</span> ${isBn ? 'বাতাসের গতি ও দিক' : 'Wind Speed & Dir'}</div>
        <div class="kpi-val">${s.weather.wind_kmh} <small style="font-size:14px;">km/h</small></div>
        <div class="kpi-sub">${escapeHtml(s.weather.wind_dir)}</div>
      </div>
      <div class="weather-kpi-card">
        <div class="kpi-label"><span>⚡</span> ${isBn ? 'আর্দ্রতা ও বায়ুচাপ' : 'Humidity & Pressure'}</div>
        <div class="kpi-val">${s.weather.humidity_pct}%</div>
        <div class="kpi-sub">${s.weather.pressure_hpa} hPa · Vis: ${s.weather.visibility_km} km</div>
      </div>
    </div>

    <!-- 3-Day Forecast Cards -->
    <div class="card" style="padding:20px;margin-bottom:20px;">
      <h3 style="margin:0 0 14px;font-size:16px;display:flex;align-items:center;gap:8px;">
        🌧️ ${isBn ? '৩ দিনের আবহাওয়া ও বৃষ্টিপাত পূর্বাভাস' : '3-Day Weather & Precipitation Outlook'}
      </h3>
      <div class="forecast-grid">
        ${forecastHtml}
      </div>
    </div>

    <!-- Local Community Evacuation Advisory & Nearest Shelters -->
    <div class="grid2">
      <div class="card" style="border-left:4px solid var(--alert);">
        <h3 style="margin:0 0 8px;font-size:16px;color:var(--alert);display:flex;align-items:center;gap:8px;">
          ⚠️ ${isBn ? 'স্থানীয় প্রশাসন ও আবহাওয়া নির্দেশনা' : 'Evacuation Advisory & Warning'}
        </h3>
        <p style="font-size:13.5px;line-height:1.55;color:var(--ink);margin:0 0 14px;">
          ${isBn ? escapeHtml(s.advisory_bn) : escapeHtml(s.advisory_en)}
        </p>
        <div style="background:rgba(0,0,0,0.25);border-radius:8px;padding:12px;font-size:12.5px;color:var(--ink-soft);line-height:1.5;">
          <b>${isBn ? 'জরুরি পরামর্শ:' : 'Emergency Precautions:'}</b><br>
          ${isBn
            ? '• বিদ্যুৎ সংযোগ বিচ্ছিন্ন রাখুন এবং মোবাইল ফোনে পর্যাপ্ত চার্জ রাখুন।<br>• পানি ফুটিয়ে বা হ্যালোজেন ট্যাবলেট ব্যবহার করে পান করুন।<br>• গবাদিপশু ও জরুরি দলিলপত্র পলিথিনে মুড়িয়ে সাথে রাখুন।'
            : '• Disconnect residential electrical mains if water enters homestead.<br>• Always use boiled or chlorinated water.<br>• Keep vital documents wrapped in sealed plastic.'}
        </div>
      </div>

      <div class="card">
        <h3 style="margin:0 0 12px;font-size:16px;display:flex;align-items:center;gap:8px;">
          🛡️ ${isBn ? 'নিকটস্থ আশ্রয়কেন্দ্রসমূহ (Designated Shelters)' : 'Designated Flood Shelters'}
        </h3>
        <div id="sheltersListContainer">
          ${sheltersHtml}
        </div>
      </div>
    </div>
  `;
}

function refreshStationData() {
  const activeBtn = document.querySelector('.weather-station-btn.active');
  const stationKey = activeBtn ? activeBtn.dataset.station : 'kurigram';
  selectStation(stationKey);
}

function escapeHtml(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}
</script>

<?php require 'includes/footer.php'; ?>

