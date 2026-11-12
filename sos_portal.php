<?php
// Public-facing Emergency SOS Portal (Does NOT require authentication)
$pageTitle = 'Emergency SOS Portal — ReliefX';
require 'config.php';
$lang = currentLang();
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script>(function(){try{if(localStorage.getItem('reliefx_theme')==='light'){document.documentElement.setAttribute('data-theme','light');}}catch(e){}})();</script>
<title><?= $lang === 'bn' ? 'জরুরি উদ্ধার ও SOS পোর্টাল — ReliefX' : 'Emergency SOS Rescue Portal — ReliefX' ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
<link rel="icon" type="image/png" href="img/logo-icon.png">
</head>
<body>

<div class="topbar" style="border-bottom:2px solid rgba(255,107,107,0.35);">
  <div class="brand">
    <div class="brand-mark"><img src="img/logo-icon.png" alt="ReliefX logo"></div>
    <div class="brand-text">
      <h1>ReliefX Emergency SOS</h1>
      <span><?= $lang === 'bn' ? 'বন্যা ও দুর্যোগকালীন তাৎক্ষণিক নাগরিক উদ্ধার সেল' : 'Citizen Emergency Distress & Rescue Cell' ?></span>
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
    <a href="weather_alerts.php" class="btn" style="font-size:11.5px;padding:5px 11px;background:rgba(56,189,248,0.18);color:var(--water);border:1px solid rgba(56,189,248,0.35);text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
      <span class="icon-cloud-rain"><?= icon('cloud-rain',13) ?></span>
      <span><?= $lang==='bn' ? 'আবহাওয়া ও বন্যা সতর্কতা' : 'Weather & Alerts' ?></span>
    </a>
    <a href="index.php" class="btn btn-ghost" style="font-size:12.5px;padding:6px 12px;"><?= $lang === 'bn' ? 'লগইন পোর্টাল →' : 'Staff Login →' ?></a>
  </div>
</div>

<div class="wrap" style="max-width:820px; margin:24px auto; padding:0 16px;">

  <!-- Emergency Hotlines Banner -->
  <div class="sos-banner-glow">
    <h1>
      <span class="ai-pulse-dot" style="background:var(--alert);width:12px;height:12px;"></span>
      <?= $lang === 'bn' ? 'জরুরি বন্যা উদ্ধার সংকেত (Citizen SOS Portal)' : 'Emergency Flood Rescue Distress (SOS Portal)' ?>
    </h1>
    <p style="color:var(--ink-soft); font-size:14px; max-width:600px; margin:0 auto;">
      <?= $lang === 'bn' 
        ? 'বন্যা বা দুর্যোগে আপনি বা আপনার পরিবার আটকা পড়ে থাকলে নিচের ফর্মটি পূরণ করে পাঠান। আমাদের AI সিস্টেম আপনার অবস্থানকে নিকটস্থ উদ্ধার ক্লাস্টারে যুক্ত করে উদ্ধারকারী দলকে পাঠাবে।' 
        : 'If you or your family are stranded in floodwaters, submit your details below. Our AI service automatically groups nearby requests using Spatial Clustering to dispatch military & rescue units.' ?>
    </p>
    <div class="sos-hotlines">
      <a href="tel:999" class="sos-hotline-pill" style="border-color:var(--alert);"><span style="color:var(--alert);">📞 ৯৯৯</span> জাতীয় জরুরি সেবা (National Emergency)</a>
      <a href="tel:1090" class="sos-hotline-pill"><span style="color:var(--warn);">📞 ১০৯০</span> দুর্যোগ হেল্পলাইন (Disaster Cell)</a>
      <a href="tel:16163" class="sos-hotline-pill"><span style="color:var(--safe);">📞 ১৬১৬৩</span> ফায়ার সার্ভিস (Fire Service)</a>
    </div>
  </div>

  <!-- SOS Submission Card -->
  <div class="card" style="border:1px solid rgba(255,107,107,0.3); box-shadow:0 8px 32px rgba(255,107,107,0.08);">
    <h2 style="display:flex;align-items:center;gap:10px;">
      <?= icon('alert-triangle') ?>
      <?= $lang === 'bn' ? 'উদ্ধার অনুরোধ ফর্ম (SOS Submission)' : 'Rescue Request Submission' ?>
    </h2>
    <p class="sub"><?= $lang === 'bn' ? 'সঠিক তথ্য দিলে উদ্ধারকারী দল দ্রুত পৌঁছাতে পারবে।' : 'Accurate landmarks and GPS allow rapid tactical team deployment.' ?></p>

    <form id="sosForm" onsubmit="event.preventDefault(); submitSosSignal();">
      <div class="grid2">
        <div class="field">
          <label><?= $lang === 'bn' ? 'আপনার নাম / যোগাযোগের ব্যক্তির নাম *' : 'Reporter / Contact Name *' ?></label>
          <input type="text" id="sosName" required placeholder="<?= $lang === 'bn' ? 'যেমন: মো. রহিম উদ্দিন' : 'e.g. Md. Rahim Uddin' ?>">
        </div>
        <div class="field">
          <label><?= $lang === 'bn' ? 'মোবাইল নম্বর *' : 'Contact Phone Number *' ?></label>
          <input type="tel" id="sosPhone" required placeholder="017XXXXXXXX">
        </div>
      </div>

      <div class="grid2" style="margin-top:10px;">
        <div class="field">
          <label><?= $lang === 'bn' ? 'আটকে থাকা মোট মানুষের সংখ্যা' : 'Number of Stranded People' ?></label>
          <input type="number" id="sosPeople" min="1" max="100" value="4" required>
        </div>
        <div class="field">
          <label><?= $lang === 'bn' ? 'জরুরিতার মাত্রা (Urgency Level)' : 'Urgency Level' ?></label>
          <select id="sosUrgency">
            <option value="critical"><?= $lang === 'bn' ? '🚨 অতি জরুরি (পানি বাড়ছে / ছাদ বা গাছে আশ্রয়)' : '🚨 Critical (Water rapidly rising / on roof)' ?></option>
            <option value="high"><?= $lang === 'bn' ? '⚠️ উচ্চ জরুরি (খাবার-পানি শেষ / বয়োবৃদ্ধ বা শিশু আছে)' : '⚠️ High (Out of food & water / elderly present)' ?></option>
            <option value="moderate"><?= $lang === 'bn' ? 'ℹ️ মাঝারি (ঘরবন্দী / চলাচলের পথ বন্ধ)' : 'ℹ️ Moderate (Homestead isolated / path cut off)' ?></option>
          </select>
        </div>
      </div>

      <!-- GPS Geolocation Tool -->
      <div class="field" style="margin-top:14px; background:var(--surface); border:1px solid var(--border); padding:14px 16px; border-radius:var(--radius);">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:8px;">
          <label style="margin:0;font-weight:700;color:var(--ink);">
            📍 <?= $lang === 'bn' ? 'জিপিএস অবস্থান (GPS Coordinates)' : 'Live GPS Coordinates' ?>
          </label>
          <button type="button" class="btn btn-accent" id="btnGetGps" onclick="fetchLiveGps()" style="font-size:12px;padding:6px 14px;">
            <span class="icon-location"></span> <?= $lang === 'bn' ? 'আমার বর্তমান জিপিএস সনাক্ত করুন' : 'Detect My Live GPS' ?>
          </button>
        </div>
        <div id="gpsStatusBadge" style="font-size:12px;color:var(--ink-soft);margin-bottom:8px;">
          <?= $lang === 'bn' ? 'জিপিএস বাটন চাপলে আপনার মোবাইল বা ডিভাইসের সুনির্দিষ্ট লোকেশন নেয়া হবে।' : 'Click detect to auto-populate accurate latitude & longitude.' ?>
        </div>
        <div class="grid2">
          <div>
            <label style="font-size:11px;"><?= $lang === 'bn' ? 'অক্ষাংশ (Latitude)' : 'Latitude' ?></label>
            <input type="number" step="0.000001" id="sosLat" value="25.8073" required>
          </div>
          <div>
            <label style="font-size:11px;"><?= $lang === 'bn' ? 'দ্রাঘিমাংশ (Longitude)' : 'Longitude' ?></label>
            <input type="number" step="0.000001" id="sosLon" value="89.6295" required>
          </div>
        </div>
      </div>

      <!-- Landmark & Location Description -->
      <div class="field" style="margin-top:12px;">
        <label><?= $lang === 'bn' ? 'সুনির্দিষ্ট ল্যান্ডমার্ক / আশেপাশের চেনা স্থান *' : 'Surrounding Landmark / Detailed Location *' ?></label>
        <textarea id="sosLandmark" rows="3" required placeholder="<?= $lang === 'bn' ? 'যেমন: চিলমারী নদীর বাঁধের পাশে, পাকা প্রাইমারি স্কুলের দুই তলার ছাদে আশ্রয় নিয়েছি, লাল রঙের ত্রিপল টানানো আছে।' : 'e.g. Near Chilmari embankment, 2nd floor roof of primary school, red tarpaulin visible.' ?>"></textarea>
      </div>

      <!-- Urgent Needs Checkboxes -->
      <div class="field" style="margin-top:10px;">
        <label><?= $lang === 'bn' ? 'তাৎক্ষণিক প্রয়োজনসমূহ (যেগুলো প্রযোজ্য)' : 'Immediate Needs (Check all applicable)' ?></label>
        <div class="needs-grid">
          <label class="needs-checkbox">
            <input type="checkbox" name="sosNeeds" value="boat" checked>
            <span>🛥️ <?= $lang === 'bn' ? 'উদ্ধার নৌকা / বোট' : 'Rescue Boat' ?></span>
          </label>
          <label class="needs-checkbox">
            <input type="checkbox" name="sosNeeds" value="medical" checked>
            <span>💊 <?= $lang === 'bn' ? 'স্যালাইন / ঔষধ' : 'Medical & Saline' ?></span>
          </label>
          <label class="needs-checkbox">
            <input type="checkbox" name="sosNeeds" value="food">
            <span>🍞 <?= $lang === 'bn' ? 'শুকনো খাবার ও পানি' : 'Food & Water' ?></span>
          </label>
          <label class="needs-checkbox">
            <input type="checkbox" name="sosNeeds" value="shelter">
            <span>⛺ <?= $lang === 'bn' ? 'ত্রিপল / তাবু' : 'Tarpaulin / Shelter' ?></span>
          </label>
        </div>
      </div>

      <div id="sosAlertBox" class="alert"></div>

      <button type="submit" class="btn" id="btnSubmitSos" style="background:linear-gradient(135deg, var(--alert), var(--accent));font-size:15px;padding:14px;font-weight:700;">
        🚨 <?= $lang === 'bn' ? 'জরুরি উদ্ধার সংকেত পাঠান (Transmit SOS Signal)' : 'Transmit Emergency SOS Signal' ?>
      </button>
    </form>

    <!-- Cluster Result Container -->
    <div id="sosClusterResult" style="display:none;"></div>
  </div>

</div><!-- /.wrap -->

<?php require 'includes/footer.php'; ?>

