/**
 * assets/js/chatbot.js
 * Chatbot Airbnb-style — recherche logements + réservation
 * Sans API externe — mémoire de session côté client
 */
(function () {
  'use strict';

  /* ── Config ── */
  const API_URL = 'api/chatbot.php';
  const MAX_MEMORY = 20; // messages conservés en mémoire

  // Clé unique par utilisateur : rôle + id  (ex: "cb_client_42", "cb_proprietaire_7", "cb_guest")
  const _u = window.CB_USER || { id: null, role: 'guest' };
  const STORAGE_KEY = 'cb_' + (_u.role || 'guest') + (_u.id ? '_' + _u.id : '');

  /* ── État ── */
  let isOpen = false;
  let isTyping = false;
  let conversationHistory = []; // [{role:'bot'|'user', text, logement_id?}]

  /* ── Init mémoire session ── */
  try {
    const saved = sessionStorage.getItem(STORAGE_KEY);
    if (saved) conversationHistory = JSON.parse(saved);
  } catch (e) {}

  /* ── Construire le DOM ── */
  function buildUI() {
    // Toggle bouton
    const toggle = document.createElement('button');
    toggle.id = 'chatbot-toggle';
    toggle.setAttribute('aria-label', 'Ouvrir le chat');
    toggle.innerHTML = `
      <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
      </svg>
      <span class="cb-badge">1</span>`;
    document.body.appendChild(toggle);

    // Fenêtre
    const win = document.createElement('div');
    win.id = 'chatbot-window';
    win.classList.add('cb-hidden');
    win.setAttribute('role', 'dialog');
    win.setAttribute('aria-label', 'Assistant logement');
    win.innerHTML = `
      <div class="cb-header">
        <div class="cb-header-avatar">🏠</div>
        <div class="cb-header-info">
          <div class="cb-header-name">Assistant Logement</div>
          <div class="cb-header-status">En ligne maintenant</div>
        </div>
        <button class="cb-close-btn" aria-label="Fermer">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </button>
      </div>
      <div class="cb-messages" id="cb-messages" role="log" aria-live="polite"></div>
      <div class="cb-input-zone">
        <textarea class="cb-input" id="cb-input" rows="1"
          placeholder="Cherchez un logement..." maxlength="400"
          aria-label="Votre message"></textarea>
        <button class="cb-send-btn" id="cb-send" aria-label="Envoyer">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="22" y1="2" x2="11" y2="13"/>
            <polygon points="22 2 15 22 11 13 2 9 22 2"/>
          </svg>
        </button>
      </div>`;
    document.body.appendChild(win);

    // Références
    const msgZone = win.querySelector('#cb-messages');
    const input   = win.querySelector('#cb-input');
    const sendBtn = win.querySelector('#cb-send');
    const closeBtn= win.querySelector('.cb-close-btn');

    /* ── Événements ── */
    toggle.addEventListener('click', () => toggleChat(win, toggle));
    closeBtn.addEventListener('click', () => closeChat(win, toggle));
    sendBtn.addEventListener('click', () => handleSend(input, msgZone, sendBtn));
    input.addEventListener('keydown', e => {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        handleSend(input, msgZone, sendBtn);
      }
    });
    // Auto-resize textarea
    input.addEventListener('input', () => {
      input.style.height = 'auto';
      input.style.height = Math.min(input.scrollHeight, 100) + 'px';
    });

    // Restore history ou afficher message de bienvenue
    if (conversationHistory.length > 0) {
      conversationHistory.forEach(m => renderMessage(m, msgZone, false));
      scrollBottom(msgZone);
    } else {
      setTimeout(() => {
        showBotMessage({
          type: 'greeting',
          text: 'Bonjour ! 👋 Je suis votre assistant logement.\nDites-moi ce que vous cherchez : ville, type, budget…',
          suggestions: ['Logements à Djerba','Studio pas cher','Villa bord de mer','Comment ça marche ?'],
        }, msgZone);
      }, 400);
    }
  }

  /* ── Ouvrir/Fermer ── */
  function toggleChat(win, toggle) {
    isOpen ? closeChat(win, toggle) : openChat(win, toggle);
  }
  function openChat(win, toggle) {
    isOpen = true;
    win.classList.remove('cb-hidden');
    win.classList.add('cb-visible');
    toggle.classList.add('open');
    // Supprimer le badge
    const badge = toggle.querySelector('.cb-badge');
    if (badge) badge.remove();
    win.querySelector('#cb-input').focus();
  }
  function closeChat(win, toggle) {
    isOpen = false;
    win.classList.add('cb-hidden');
    win.classList.remove('cb-visible');
    toggle.classList.remove('open');
  }

  /* ── Envoyer ── */
  async function handleSend(input, msgZone, sendBtn) {
    const text = input.value.trim();
    if (!text || isTyping) return;

    input.value = '';
    input.style.height = 'auto';
    sendBtn.disabled = true;
    isTyping = true;

    // Afficher message utilisateur
    const userMsg = { role: 'user', text };
    addToHistory(userMsg);
    renderMessage(userMsg, msgZone);
    scrollBottom(msgZone);

    // Typing indicator
    const typingEl = showTyping(msgZone);

    try {
      const res = await fetch(API_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          message: text,
          context: conversationHistory.slice(-MAX_MEMORY),
        }),
      });
      const data = await res.json();
      typingEl.remove();

      if (data.ok) {
        showBotMessage(data, msgZone);
      } else {
        showBotMessage({ type: 'error', text: data.error || 'Erreur serveur.' }, msgZone);
      }
    } catch (e) {
      typingEl.remove();
      showBotMessage({ type: 'error', text: 'Connexion impossible. Réessayez.' }, msgZone);
    }

    sendBtn.disabled = false;
    isTyping = false;
    scrollBottom(msgZone);
  }

  /* ── Afficher message bot ── */
  function showBotMessage(data, msgZone) {
    const msg = { role: 'bot', text: data.text, logement_id: null };

    // Conteneur
    const wrap = document.createElement('div');
    wrap.style.display = 'flex';
    wrap.style.flexDirection = 'column';
    wrap.style.gap = '8px';
    wrap.style.alignSelf = 'flex-start';
    wrap.style.width = '100%';

    // Bulle texte
    if (data.text) {
      const bubble = document.createElement('div');
      bubble.className = 'cb-bubble bot';
      bubble.innerHTML = formatText(data.text);
      wrap.appendChild(bubble);
    }

    // Cartes logements
    if (data.logements && data.logements.length > 0) {
      const cards = buildCards(data.logements, msgZone, msg);
      wrap.appendChild(cards);
      msg.logement_id = data.logements[0].id; // premier résultat comme contexte
    }

    // Action bouton (connexion etc.)
    if (data.action) {
      const btn = document.createElement('a');
      btn.href = data.action.url;
      btn.className = 'cb-action-btn';
      btn.textContent = data.action.label;
      wrap.appendChild(btn);
    }

    // Redirect réservation
    if (data.redirect_url) {
      setTimeout(() => { window.location.href = data.redirect_url; }, 1800);
      msg.logement_id = data.logement_id;
    }

    // Suggestions
    if (data.suggestions && data.suggestions.length > 0) {
      const sugg = buildSuggestions(data.suggestions, msgZone);
      wrap.appendChild(sugg);
    }

    msgZone.appendChild(wrap);
    addToHistory(msg);
    scrollBottom(msgZone);
  }

  /* ── Restore un message ── */
  function renderMessage(msg, msgZone, animate = true) {
    const bubble = document.createElement('div');
    bubble.className = `cb-bubble ${msg.role}`;
    bubble.innerHTML = formatText(msg.text);
    if (!animate) bubble.style.animation = 'none';
    msgZone.appendChild(bubble);
  }

  /* ── Construire cartes ── */
  function buildCards(logements, msgZone, contextMsg) {
    const wrap = document.createElement('div');
    wrap.className = 'cb-cards';

    logements.forEach(l => {
      const card = document.createElement('a');
      card.className = 'cb-card';
      card.href = l.url;
      card.innerHTML = `
        <img class="cb-card-img" src="${escHtml(l.image)}" alt="${escHtml(l.titre)}" loading="lazy" onerror="this.src='https://images.unsplash.com/photo-1566073771259-6a8506099945?w=400&q=70'">
        <div class="cb-card-info">
          <div class="cb-card-title">${escHtml(l.titre)}</div>
          <div class="cb-card-ville">📍 ${escHtml(l.ville)}</div>
          <div class="cb-card-prix">${l.prix.toFixed(0)} DT <span>/ nuit</span></div>
        </div>
        <button class="cb-card-btn" data-id="${l.id}" data-titre="${escHtml(l.titre)}">Réserver</button>`;

      // Clic sur bouton réserver
      card.querySelector('.cb-card-btn').addEventListener('click', e => {
        e.preventDefault();
        e.stopPropagation();
        contextMsg.logement_id = l.id;
        addToHistory({ role: 'bot', text: '', logement_id: l.id });
        simulateUserMessage(`Réserver le logement ${l.id}`, msgZone);
      });

      wrap.appendChild(card);
    });
    return wrap;
  }

  /* ── Construire suggestions ── */
  function buildSuggestions(suggestions, msgZone) {
    const wrap = document.createElement('div');
    wrap.className = 'cb-suggestions';
    suggestions.forEach(s => {
      const btn = document.createElement('button');
      btn.className = 'cb-suggestion-btn';
      btn.textContent = s;
      btn.addEventListener('click', () => simulateUserMessage(s, msgZone));
      wrap.appendChild(btn);
    });
    return wrap;
  }

  /* ── Simuler envoi ── */
  function simulateUserMessage(text, msgZone) {
    const input = document.querySelector('#cb-input');
    const sendBtn = document.querySelector('#cb-send');
    if (input && sendBtn) {
      input.value = text;
      handleSend(input, msgZone, sendBtn);
    }
  }

  /* ── Typing indicator ── */
  function showTyping(msgZone) {
    const el = document.createElement('div');
    el.className = 'cb-typing';
    el.innerHTML = '<span></span><span></span><span></span>';
    msgZone.appendChild(el);
    scrollBottom(msgZone);
    return el;
  }

  /* ── Helpers ── */
  function scrollBottom(el) {
    requestAnimationFrame(() => { el.scrollTop = el.scrollHeight; });
  }

  function formatText(text) {
    if (!text) return '';
    return text
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
      .replace(/\n/g, '<br>');
  }

  function escHtml(str) {
    return String(str || '').replace(/[&<>"']/g, m =>
      ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m])
    );
  }

  function addToHistory(msg) {
    conversationHistory.push(msg);
    if (conversationHistory.length > MAX_MEMORY * 2) {
      conversationHistory = conversationHistory.slice(-MAX_MEMORY);
    }
    try { sessionStorage.setItem(STORAGE_KEY, JSON.stringify(conversationHistory)); } catch (e) {}
  }

  /* ── Démarrage ── */
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', buildUI);
  } else {
    buildUI();
  }
})();