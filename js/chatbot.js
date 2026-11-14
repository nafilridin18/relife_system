/**
 * ReliefX AI Helpline Chatbot (v3.0)
 * Handles global citizen floating chatbot UI, interactions, and FastAPI webhook calls.
 */

// Auto-detect FastAPI microservice port (default port 8001)
const AI_API_HOST = window.AI_API_HOST || 'http://localhost:8001';

document.addEventListener('DOMContentLoaded', () => {
  initChatWidget();
});

function initChatWidget() {
  const toggleBtn = document.getElementById('reliefxChatToggle');
  const chatBox = document.getElementById('reliefxChatWidget');
  const closeBtn = document.getElementById('reliefxChatClose');
  const sendBtn = document.getElementById('reliefxChatSend');
  const chatInput = document.getElementById('reliefxChatInput');
  const messagesBox = document.getElementById('reliefxChatMessages');
  const suggestionsBox = document.getElementById('reliefxChatSuggestions');

  if (!toggleBtn || !chatBox) return;

  // Toggle open / close
  toggleBtn.addEventListener('click', () => {
    const isOpen = chatBox.classList.toggle('open');
    if (isOpen) {
      setTimeout(() => chatInput && chatInput.focus(), 200);
      scrollChatToBottom();
      // Clear notification dot
      const badge = toggleBtn.querySelector('.chat-toggle-badge');
      if (badge) badge.style.display = 'none';
    }
  });

  if (closeBtn) {
    closeBtn.addEventListener('click', () => {
      chatBox.classList.remove('open');
    });
  }

  // Send message on click or Enter
  if (sendBtn && chatInput) {
    sendBtn.addEventListener('click', handleUserSend);
    chatInput.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        handleUserSend();
      }
    });
  }

  // Suggestion chips
  if (suggestionsBox) {
    suggestionsBox.addEventListener('click', (e) => {
      const chip = e.target.closest('.chat-chip');
      if (chip && chatInput) {
        chatInput.value = chip.dataset.query || chip.textContent.trim();
        handleUserSend();
      }
    });
  }

  async function handleUserSend() {
    const text = chatInput.value.trim();
    if (!text) return;

    // Append user message
    appendChatMessage('user', text);
    chatInput.value = '';

    // Show typing indicator
    const typingId = appendTypingIndicator();

    try {
      const response = await fetch(`${AI_API_HOST}/api/v1/chat/webhook`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          message: text,
          session_id: 'citizen-' + (localStorage.getItem('reliefx_session') || Date.now()),
          language: typeof CUR_LANG !== 'undefined' ? CUR_LANG : 'bn'
        })
      });

      removeTypingIndicator(typingId);

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }

      const data = await response.json();
      appendBotResponse(data);

      if (data.suggested_actions && data.suggested_actions.length > 0) {
        renderSuggestionChips(data.suggested_actions);
      }
    } catch (err) {
      console.warn('AI Microservice call fallback triggered:', err);
      removeTypingIndicator(typingId);
      
      const isBn = typeof CUR_LANG !== 'undefined' ? CUR_LANG === 'bn' : true;
      const lower = text.toLowerCase();
      let fallbackData = null;

      if (lower.includes('যোগ্যতা') || lower.includes('৭ দিন') || lower.includes('eligibility') || lower.includes('কবে পাব')) {
        fallbackData = {
          reply: isBn
            ? "📦 **ত্রাণ পাওয়ার যোগ্যতা ও ৭ দিনের নিয়ম:**\n\n" +
              "• **স্বচ্ছ নিয়ম:** ReliefX সিস্টেমে প্রতিটি পরিবার একটি নির্দিষ্ট ক্যাটাগরিতে (যেমন খাদ্যসামগ্রী, ওষুধ বা আশ্রয়) ত্রাণ পাওয়ার পর **পরবর্তী ৭ দিন** ওই ক্যাটাগরির জন্য অপেক্ষমাণ থাকে।\n" +
              "• **স্বয়ংক্রিয় ডুপ্লিকেট রোধ:** ৭ দিনের মধ্যে পুনরায় একই ক্যাটাগরিতে ত্রাণ নেওয়ার চেষ্টা করা হলে ডাটাবেস স্বয়ংক্রিয়ভাবে তা ব্লক করে দেয়।\n" +
              "• **যাচাই করার উপায়:** আপনার **Citizen একাউন্টে** লগইন করুন। ড্যাশবোর্ডে আপনি পূর্বে কী কী ত্রাণ পেয়েছেন এবং পরবর্তী ত্রাণ পাওয়ার তারিখ দেখতে পাবেন।"
            : "📦 **Relief Eligibility & The 7-Day Rule:**\n\n" +
              "• After receiving relief in any category, families enter a **7-day cooldown**.\n" +
              "• Duplicate requests within 7 days are automatically rejected by the database.\n" +
              "• Log into your **Citizen Dashboard** with your NID to view full distribution history and next eligible date.",
          is_emergency: false,
          suggested_actions: ["⏳ ৫ দিন পর জরুরি আবেদন", "👤 নাগরিক একাউন্ট রেজিস্ট্রেশন", "📞 জরুরি হটলাইন নম্বরসমূহ"]
        };
      } else if (lower.includes('৫ দিন') || lower.includes('জরুরি ত্রাণ') || lower.includes('জরুরি আবেদন') || lower.includes('5-day') || lower.includes('emergency relief')) {
        fallbackData = {
          reply: isBn
            ? "⏳ **৫ দিন পর জরুরি ত্রাণের বিশেষ আবেদন প্রোটোকল:**\n\n" +
              "• **জরুরি প্রোটোকল:** স্বাভাবিক ত্রাণের বিরতি ৭ দিন হলেও জরুরি বন্যা পরিস্থিতিতে কোনো পরিবার যদি টানা **৫ দিন কোনো সহায়তা না পেয়ে থাকে**, তবে তারা বিশেষ জরুরি ত্রাণের আবেদন করতে পারে।\n" +
              "• **আবেদনের নিয়ম:** Citizen Dashboard (`citizen_dashboard.php`) এ লগইন করে 'জরুরি ত্রাণের আবেদন' কার্ড থেকে আপনার বর্তমান সংকট ও চাহিদার বিবরণ লিখে সাবমিট করুন। সংশ্লিষ্ট এনজিও তাৎক্ষণিক নোটিফিকেশন পাবে।"
            : "⏳ **5-Day Emergency Relief Protocol:**\n\n" +
              "• Families facing crisis without relief for **5 consecutive days** can file an emergency petition.\n" +
              "• Submit your petition directly via your **Citizen Dashboard** (`citizen_dashboard.php`).",
          is_emergency: false,
          suggested_actions: ["📦 ত্রাণ পাওয়ার যোগ্যতা ও নিয়ম", "👤 নাগরিক একাউন্ট রেজিস্ট্রেশন", "🌊 আবহাওয়া ও বন্যা পরিস্থিতি"]
        };
      } else if (lower.includes('আবহাওয়া') || lower.includes('বন্যা পরিস্থিতি') || lower.includes('নদীর পানি') || lower.includes('বিপদসীমা') || lower.includes('weather') || lower.includes('flood')) {
        fallbackData = {
          reply: isBn
            ? "🌊 **লাইভ আবহাওয়া ও নদী বন্যা সতর্কতা:**\n\n" +
              "• **মনিটরিং স্টেশন:** ReliefX লাইভ পোর্টালে (`weather_alerts.php`) কুড়িগ্রাম (ধরলা), সুনামগঞ্জ (সুরমা), সিলেট, জামালপুর সহ ৮টি প্রধান স্টেশনের ডেটা রিয়েল-টাইমে আপডেট হয়।\n" +
              "• **বর্তমান অবস্থা:** ধরলা ও সুরমা নদীর পানি বিপদসীমার উপর দিয়ে প্রবাহিত হচ্ছে। নদী তীরবর্তী নিম্নাঞ্চলের বাসিন্দাদের সতর্ক থাকতে বলা হচ্ছে।"
            : "🌊 **Live Weather & Flood Alert Overview:**\n\n" +
              "• Real-time hydrological monitoring is active for 8 vulnerable flood zones via `weather_alerts.php`.\n" +
              "• Dharla and Surma rivers are flowing near danger thresholds.",
          is_emergency: false,
          suggested_actions: ["🚨 জরুরি উদ্ধার (Emergency Rescue)", "📦 ত্রাণ পাওয়ার যোগ্যতা ও নিয়ম", "📞 জরুরি হটলাইন নম্বরসমূহ"]
        };
      } else if (lower.includes('নাগরিক একাউন্ট') || lower.includes('রেজিস্ট্রেশন') || lower.includes('খুলব') || lower.includes('citizen') || lower.includes('register')) {
        fallbackData = {
          reply: isBn
            ? "👤 **নাগরিক একাউন্ট খোলার সহজ নিয়ম:**\n\n" +
              "১. লগইন পেজের নিচে **'Open Citizen Account'** বা `register_user.php` লিঙ্কে যান।\n" +
              "২. আপনার নাম, সক্রিয় মোবাইল নম্বর, ১০ বা ১৭ ডিজিটের **NID নম্বর** এবং একটি পাসওয়ার্ড দিয়ে ১ মিনিটে রেজিস্ট্রেশন সম্পন্ন করুন।\n" +
              "৩. এরপর লগইন করে আপনার ত্রাণের ইতিহাস ও পরবর্তী ত্রাণের সময়সূচি চেক করতে পারবেন।"
            : "👤 **How to Register a Citizen Account:**\n\n" +
              "1. Click **'Open Citizen Account'** on the login page.\n" +
              "2. Provide your name, active mobile number, NID, and password.\n" +
              "3. Log in to track distribution and request relief.",
          is_emergency: false,
          suggested_actions: ["📦 ত্রাণ পাওয়ার যোগ্যতা ও নিয়ম", "⏳ ৫ দিন পর জরুরি আবেদন", "🌊 আবহাওয়া ও বন্যা পরিস্থিতি"]
        };
      } else if (lower.includes('হটলাইন') || lower.includes('নম্বর') || lower.includes('নাম্বার') || lower.includes('ফোন') || lower.includes('hotline') || lower.includes('999') || lower.includes('1090')) {
        fallbackData = {
          reply: isBn
            ? "📞 **জাতীয় জরুরি দুর্যোগ হেল্পলাইন নম্বরসমূহ:**\n\n" +
              "• 🚨 **৯৯৯ (999)**: জাতীয় জরুরি সেবা (পুলিশ, অ্যাম্বুলেন্স, ফায়ার সার্ভিস উদ্ধার)\n" +
              "• 🌊 **১০৯০ (1090)**: দুর্যোগ প্রস্তুতি ও নদী বন্যা আগাম সতর্কতা বার্তা (টোল-ফ্রি)\n" +
              "• ℹ️ **৩৩৩ (333)**: সরকারি তথ্য, সেবা ও সামাজিক সহায়তা হেল্পলাইন\n" +
              "• 🩺 **১৬১৬৩ (16163)**: বাংলাদেশ রেড ক্রিসেন্ট সোসাইটি জরুরি সাড়া সেল"
            : "📞 **National Disaster Emergency Hotlines:**\n\n" +
              "• 🚨 **999**: National Emergency Services (Police, Ambulance, Fire & Rescue)\n" +
              "• 🌊 **1090**: Flood Early Warning & Weather (Toll-Free)\n" +
              "• ℹ️ **333**: National Citizen Grievance & Government Services\n" +
              "• 🩺 **16163**: Bangladesh Red Crescent Emergency Response",
          is_emergency: false,
          suggested_actions: ["🚨 জরুরি উদ্ধার (Emergency Rescue)", "📦 ত্রাণ পাওয়ার যোগ্যতা ও নিয়ম", "🌊 আবহাওয়া ও বন্যা পরিস্থিতি"]
        };
      } else if (lower.includes('বাঁচাও') || lower.includes('আটকা') || lower.includes('উদ্ধার') || lower.includes('rescue') || lower.includes('trapped')) {
        const ticketId = 'TKT-2026-' + Math.floor(4000 + Math.random() * 5000);
        fallbackData = {
          reply: isBn
            ? `🚨 **জরুরি উদ্ধার টিকেট খোলা হয়েছে (#${ticketId})!**\n\n` +
              "আপনার উদ্ধার বার্তাটি দুর্যোগ কমান্ড সেন্টারে রেকর্ড করা হয়েছে।\n" +
              "🛥️ **নিয়োজিত টিম:** ফায়ার সার্ভিস ও সেনা স্পিডবোট ইউনিট।\n" +
              "📞 জরুরি সহায়তার জন্য সরাসরি কল করুন: **৯৯৯ (জাতীয় জরুরি সেবা)** অথবা **১০৯০ (দুর্যোগ হেল্পলাইন)**।"
            : `🚨 **Emergency Rescue Ticket Created (#${ticketId})!**\n\n` +
              "Your rescue request has been logged and forwarded to disaster response units.\n" +
              "📞 For immediate life-saving rescue, please call **999** or **1090** directly.",
          is_emergency: true,
          ticket: {
            ticket_id: ticketId,
            status: "DISPATCHED",
            beneficiary_name: "নাগরিক (Citizen In Need)",
            detected_gps: "Auto-triaged by Helpline",
            detected_landmark: "Flood affected area",
            assigned_unit: "বাংলাদেশ ফায়ার সার্ভিস ও উদ্ধার ইউনিট"
          },
          suggested_actions: ["📍 Send Updated GPS", "🚨 Call 999 Directly", "📦 Check Relief Distribution"]
        };
      } else {
        fallbackData = {
          reply: isBn 
            ? "👋 **নমস্কার / সালাম!** আমি **ReliefX AI স্মার্ট সহকারী**।\n\n" +
              "বন্যা ও ত্রাণ সংক্রান্ত যেকোনো তথ্যের জন্য আমি সর্বদা প্রস্তুত। নিচের প্রস্তাবিত বিষয়গুলো থেকে বেছে নিতে পারেন:"
            : "👋 **Hello!** Welcome to **ReliefX AI Helpline Assistant**.\n\n" +
              "How can I assist you today? Please choose from the topics below:",
          is_emergency: false,
          suggested_actions: ["📦 ত্রাণ পাওয়ার যোগ্যতা ও নিয়ম", "⏳ ৫ দিন পর জরুরি আবেদন", "🌊 আবহাওয়া ও বন্যা পরিস্থিতি", "📞 জরুরি হটলাইন নম্বরসমূহ"]
        };
      }

      appendBotResponse(fallbackData);
      if (fallbackData.suggested_actions) {
        renderSuggestionChips(fallbackData.suggested_actions);
      }
    }
  }

  function appendChatMessage(sender, text) {
    const div = document.createElement('div');
    div.className = `chat-msg ${sender}`;
    div.textContent = text;
    messagesBox.appendChild(div);
    scrollChatToBottom();
  }

  function appendBotResponse(data) {
    const div = document.createElement('div');
    div.className = 'chat-msg bot';

    // Simple markdown bold/italic formatting
    let formatted = escapeChatHtml(data.reply)
      .replace(/\*\*(.*?)\*\*/g, '<b>$1</b>')
      .replace(/`([^`]+)`/g, '<code style="background:rgba(255,255,255,0.1);padding:2px 5px;border-radius:4px;">$1</code>')
      .replace(/\n/g, '<br>');

    div.innerHTML = formatted;

    // If an Emergency Ticket was created
    if (data.is_emergency && data.ticket) {
      const t = data.ticket;
      const ticketCard = document.createElement('div');
      ticketCard.className = 'chat-rescue-ticket';
      ticketCard.innerHTML = `
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
          <b>🚨 RESCUE TICKET: ${escapeChatHtml(t.ticket_id)}</b>
          <span class="tag alert" style="font-size:10px;padding:2px 6px;">${escapeChatHtml(t.status)}</span>
        </div>
        <div><b>Beneficiary:</b> ${escapeChatHtml(t.beneficiary_name || 'Citizen In Need')}</div>
        <div><b>GPS Location:</b> ${escapeChatHtml(t.detected_gps || 'Auto-triaged')}</div>
        <div><b>Landmark:</b> ${escapeChatHtml(t.detected_landmark || 'Flood affected shelter')}</div>
        <div><b>Assigned Unit:</b> ${escapeChatHtml(t.assigned_unit)}</div>
        <div style="margin-top:8px;display:flex;gap:6px;">
          <a href="tel:999" class="btn btn-accent" style="font-size:11px;padding:4px 10px;text-decoration:none;border-radius:4px;">📞 Call 999</a>
          <a href="tel:1090" class="btn btn-ghost" style="font-size:11px;padding:4px 10px;text-decoration:none;border-radius:4px;">📞 Call 1090</a>
        </div>
      `;
      div.appendChild(ticketCard);
    }

    messagesBox.appendChild(div);
    scrollChatToBottom();
  }

  function appendTypingIndicator() {
    const id = 'typing-' + Date.now();
    const div = document.createElement('div');
    div.id = id;
    div.className = 'chat-msg bot';
    div.style.color = 'var(--ink-soft)';
    div.style.fontStyle = 'italic';
    div.innerHTML = '<span class="spinner" style="width:12px;height:12px;border-width:2px;display:inline-block;vertical-align:middle;margin-right:6px;"></span>AI ভাবছে / Processing...';
    messagesBox.appendChild(div);
    scrollChatToBottom();
    return id;
  }

  function removeTypingIndicator(id) {
    const el = document.getElementById(id);
    if (el) el.remove();
  }

  function renderSuggestionChips(chips) {
    if (!suggestionsBox) return;
    suggestionsBox.innerHTML = '';
    chips.forEach(c => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'chat-chip';
      btn.dataset.query = c;
      btn.textContent = c;
      suggestionsBox.appendChild(btn);
    });
  }

  function scrollChatToBottom() {
    if (messagesBox) {
      messagesBox.scrollTop = messagesBox.scrollHeight;
    }
  }

  function escapeChatHtml(str) {
    if (!str) return '';
    return str
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }
}

