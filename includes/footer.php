</div><!-- /.wrap -->

<div class="app-footer">
  <b>ReliefX</b> — Flood Relief Management System · Developed by Team <b>StratifyX</b> · Section 67_F1<br>
  Gulam Sakaria (242-15-218) · MD. Ashraful Islam (242-15-487) ·
  Mir Samiul Haque (242-15-350) · Nafil Ardul Ridin (242-15-705) ·
  Zidne Hasan (242-15-356)
</div>

<!-- =========================================================================
     ReliefX v3.0: Citizen-Facing AI Helpline Chatbot Widget (Globally Accessible)
     ========================================================================= -->
<button type="button" class="chat-toggle-btn" id="reliefxChatToggle" aria-label="Open AI Helpline Assistant">
  <span class="chat-toggle-badge"></span>
  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
  </svg>
</button>

<div class="chat-widget-box" id="reliefxChatWidget" role="dialog" aria-label="ReliefX AI Helpline">
  <div class="chat-widget-header">
    <div class="chat-widget-title">
      <div class="chat-avatar">AI</div>
      <div>
        <h3>ReliefX AI Helpline</h3>
        <small><span class="ai-pulse-dot" style="background:var(--safe);"></span> Active & Ready</small>
      </div>
    </div>
    <button type="button" class="chat-close-btn" id="reliefxChatClose" aria-label="Close Chat">&times;</button>
  </div>
  <div class="chat-messages" id="reliefxChatMessages">
    <div class="chat-msg bot">
      <?= currentLang() === 'bn' 
        ? "নমস্কার/সালাম! আমি <b>ReliefX AI হেল্পলাইন</b>। জরুরি বন্যা উদ্ধার বা ত্রাণ সংক্রান্ত যেকোনো তথ্য জানতে এখানে লিখুন। বিপদে পড়লে আপনার নাম ও অবস্থান জানালে আমি উদ্ধার টিকেট খুলে দেব।"
        : "Hello! I am the <b>ReliefX AI Helpline</b>. Ask any question about flood relief or emergency rescue. If trapped, provide your name and location for immediate rescue triage." 
      ?>
    </div>
  </div>
  <div class="chat-suggestions" id="reliefxChatSuggestions">
    <button type="button" class="chat-chip" data-query="ত্রাণ পাওয়ার যোগ্যতা কীভাবে চেক করব এবং ৭ দিনের নিয়ম কী?">📦 <?= currentLang() === 'bn' ? 'ত্রাণ পাওয়ার যোগ্যতা ও নিয়ম' : 'Relief Eligibility & Rules' ?></button>
    <button type="button" class="chat-chip" data-query="জরুরি উদ্ধার প্রয়োজন, পানিতে আটকা পড়েছি!">🚨 <?= currentLang() === 'bn' ? 'জরুরি উদ্ধার (Emergency Rescue)' : 'Emergency Rescue / SOS' ?></button>
    <button type="button" class="chat-chip" data-query="লাইভ আবহাওয়া ও নদীর পানির বিপদসীমা পরিস্থিতি কেমন?">🌊 <?= currentLang() === 'bn' ? 'আবহাওয়া ও বন্যা পরিস্থিতি' : 'Weather & Flood Alerts' ?></button>
    <button type="button" class="chat-chip" data-query="নাগরিক একাউন্ট কীভাবে খুলব এবং কী কী লাগবে?">👤 <?= currentLang() === 'bn' ? 'নাগরিক একাউন্ট রেজিস্ট্রেশন' : 'Citizen Account Signup' ?></button>
    <button type="button" class="chat-chip" data-query="৫ দিন পর জরুরি ত্রাণের আবেদন করার নিয়ম কী?">⏳ <?= currentLang() === 'bn' ? '৫ দিন পর জরুরি আবেদন' : '5-Day Emergency Protocol' ?></button>
    <button type="button" class="chat-chip" data-query="জরুরি হটলাইন নম্বরগুলো কী কী?">📞 <?= currentLang() === 'bn' ? 'জরুরি হটলাইন নম্বরসমূহ' : 'Emergency Hotlines' ?></button>
  </div>
  <div class="chat-input-bar">
    <input type="text" class="chat-input" id="reliefxChatInput" placeholder="<?= currentLang() === 'bn' ? 'বার্তা লিখুন বা SOS পাঠান...' : 'Type message or send SOS...' ?>" autocomplete="off">
    <button type="button" class="chat-send-btn" id="reliefxChatSend" aria-label="Send Message">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="22" y1="2" x2="11" y2="13"></line>
        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
      </svg>
    </button>
  </div>
</div>

<script src="js/theme.js"></script>
<script src="js/app.js"></script>
<script src="js/chatbot.js"></script>
</body>
</html>
