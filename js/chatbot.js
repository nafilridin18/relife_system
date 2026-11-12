/**
 * ReliefX AI Helpline Chatbot (v3.0)
 * Handles global citizen floating chatbot UI, interactions, and FastAPI webhook calls.
 */

// Auto-detect FastAPI microservice port (default 8000 or fallback 8001 if PHP is on 8000)
const AI_API_HOST = window.AI_API_HOST || (window.location.port === '8000' ? 'http://localhost:8001' : 'http://localhost:8000');

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
      console.warn('AI Microservice call failed, showing simulated fallback response:', err);
      removeTypingIndicator(typingId);
      
      // Fallback heuristic response if backend is offline
      const isBn = typeof CUR_LANG !== 'undefined' ? CUR_LANG === 'bn' : true;
      const fallbackData = {
        reply: isBn 
          ? "🚨 আপনার বার্তাটি প্রাপ্ত হয়েছে। জরুরি প্রয়োজনে অনুগ্রহ করে সরাসরি জাতীয় জরুরি সেবা **৯৯৯** অথবা দুর্যোগ হেল্পলাইন **১০৯০** এ যোগাযোগ করুন। AI মাইক্রোসার্ভিস সংযোগ যাচাই করা হচ্ছে।"
          : "🚨 Distress message received. For immediate flood emergencies, please dial **999** or disaster cell **1090**. Checking AI microservice connectivity.",
        is_emergency: text.toLowerCase().includes('rescue') || text.includes('বাঁচাও') || text.includes('আটকা'),
        suggested_actions: ["🚨 জরুরি হটলাইন ৯৯৯", "📍 নিকটস্থ ত্রাণ কেন্দ্র", "❓ কীভাবে কাজ করে"]
      };
      appendBotResponse(fallbackData);
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

