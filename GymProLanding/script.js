'use strict';

// ---- Theme Toggle ----
function toggleTheme() {
  document.body.classList.toggle('light-mode');
  const isDark = !document.body.classList.contains('light-mode');
  localStorage.setItem('theme-mode', isDark ? 'dark' : 'light');
  updateThemeToggleIcon();
}

function updateThemeToggleIcon() {
  const btn = document.getElementById('theme-toggle');
  const isDark = !document.body.classList.contains('light-mode');
  btn.textContent = isDark ? '🌙' : '☀️';
}

// Load saved theme on page load
if (localStorage.getItem('theme-mode') === 'light') {
  document.body.classList.add('light-mode');
}
updateThemeToggleIcon();

// ---- FAQ Accordion ----
function toggleFaq(button) {
  const item = button.closest('.faq-item');
  const isOpen = item.classList.contains('open');
  
  // Close all FAQ items
  document.querySelectorAll('.faq-item').forEach(el => {
    el.classList.remove('open');
  });
  
  // Open clicked item if it was closed
  if (!isOpen) {
    item.classList.add('open');
  }
}

// ---- Modal ----
const modal = document.getElementById('modal');

function openModal(mode = 'trial', plan = null) {
  modal.classList.add('open');
  document.body.style.overflow = 'hidden';
  // Set min dates to today for both forms
  document.getElementById('trial-date').min = new Date().toISOString().split('T')[0];
  document.getElementById('daily-date').min = new Date().toISOString().split('T')[0];

  // If opened from a plan 'Get Started', preselect plan
  if (mode === 'plan' && plan) {
    // Preselect plan on both forms
    const trialPlan = document.getElementById('trial-plan');
    const dailyPlan = document.getElementById('daily-plan');
    if (trialPlan) trialPlan.value = plan;
    if (dailyPlan) dailyPlan.value = plan;
    // Default to trial tab
    switchTab('trial');
  } else {
    // Default tab when opening normally
    switchTab('trial');
  }

  // Focus first field
  setTimeout(() => document.getElementById('trial-name')?.focus(), 100);
}

function closeModal() {
  modal.classList.remove('open');
  document.body.style.overflow = '';
  clearAllMessages();
}

function handleOverlay(e) {
  if (e.target === modal) closeModal();
}

// Close on Escape
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    if (modal.classList.contains('open')) closeModal();
    if (document.getElementById('mobile-nav')?.classList.contains('open')) closeMobileNav();
  }
});

// ---- Mobile nav helpers ----
function openMobileNav() {
  const mn = document.getElementById('mobile-nav');
  const btn = document.querySelector('.nav-hamburger');
  if (!mn) return;
  mn.classList.add('open');
  mn.setAttribute('aria-hidden','false');
  document.body.style.overflow = 'hidden';
  if (btn) btn.setAttribute('aria-expanded','true');
}

function closeMobileNav() {
  const mn = document.getElementById('mobile-nav');
  const btn = document.querySelector('.nav-hamburger');
  if (!mn) return;
  mn.classList.remove('open');
  mn.setAttribute('aria-hidden','true');
  document.body.style.overflow = '';
  if (btn) btn.setAttribute('aria-expanded','false');
}

function handleMobileNavOverlay(e) {
  if (e.target === document.getElementById('mobile-nav')) closeMobileNav();
}

// ---- Tab switching ----
function switchTab(tabName) {
  // Hide all contents
  document.querySelectorAll('.modal-content').forEach(el => el.classList.remove('active'));
  document.querySelectorAll('.modal-tab').forEach(el => el.classList.remove('active'));
  
  // Show selected tab
  document.getElementById(tabName + '-content').classList.add('active');
  document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
  
  // Clear all messages when switching tabs
  clearAllMessages();
  
  // Focus first field of new tab
  const firstInput = document.getElementById(tabName + '-name');
  setTimeout(() => firstInput?.focus(), 100);
}

// ---- Clear all error/success messages ----
function clearAllMessages() {
  document.querySelectorAll('.form-msg').forEach(el => {
    el.textContent = '';
    el.className = 'form-msg';
  });
}

// ---- Show message below input ----
function showMessage(fieldId, message, isError = true) {
  const msgEl = document.getElementById(fieldId + '-msg');
  if (!msgEl) return;
  msgEl.textContent = message;
  msgEl.className = isError ? 'form-msg error' : 'form-msg success';
}

// ---- Validate Kenyan phone ----
function validatePhone(phone) {
  return /^((07|01|\+2547|\+2541)\d{8}|2547\d{8})$/.test(phone.replace(/\s+/g, ''));
}

// ---- Free Trial Submit ----
function handleTrialSubmit(e) {
  e.preventDefault();
  clearAllMessages();
  
  const name = document.getElementById('trial-name').value.trim();
  const phone = document.getElementById('trial-phone').value.trim();
  const service = document.getElementById('trial-service').value;
  const plan = document.getElementById('trial-plan')?.value;
  const date = document.getElementById('trial-date').value;
  const time = document.getElementById('trial-time').value;
  
  let isValid = true;

  // Validate name
  if (!name) {
    showMessage('trial-name', 'Please enter your name', true);
    isValid = false;
  } else if (name.length < 2) {
    showMessage('trial-name', 'Name must be at least 2 characters', true);
    isValid = false;
  } else {
    showMessage('trial-name', 'Name looks good', false);
  }

  // Validate phone
  if (!phone) {
    showMessage('trial-phone', 'Please enter your phone number', true);
    isValid = false;
  } else if (!validatePhone(phone)) {
    showMessage('trial-phone', 'Invalid Kenyan number (e.g. 0712345678)', true);
    isValid = false;
  } else {
    showMessage('trial-phone', 'Phone number valid', false);
  }

  // Validate service
  if (!service) {
    showMessage('trial-service', 'Please select a service', true);
    isValid = false;
  } else {
    showMessage('trial-service', service + ' selected', false);
  }

  // Validate plan
  if (!plan) {
    showMessage('trial-plan', 'Please select a membership plan', true);
    isValid = false;
  } else {
    showMessage('trial-plan', plan + ' selected', false);
  }

  // Validate date
  if (!date) {
    showMessage('trial-date', 'Please select a date', true);
    isValid = false;
  } else {
    showMessage('trial-date', 'Date selected', false);
  }

  // Validate time
  if (!time) {
    showMessage('trial-time', 'Please select a time slot', true);
    isValid = false;
  } else {
    showMessage('trial-time', 'Time selected', false);
  }

  if (!isValid) return;

  // All valid — send to WhatsApp (include plan)
  sendTrialToWhatsApp(name, phone, service, plan, date, time);
}

// ---- Daily Booking Submit ----
function handleDailySubmit(e) {
  e.preventDefault();
  clearAllMessages();
  
  const name = document.getElementById('daily-name').value.trim();
  const phone = document.getElementById('daily-phone').value.trim();
  const service = document.getElementById('daily-service').value;
  const plan = document.getElementById('daily-plan')?.value;
  const date = document.getElementById('daily-date').value;
  const time = document.getElementById('daily-time').value;
  
  let isValid = true;

  // Validate name
  if (!name) {
    showMessage('daily-name', 'Please enter your name', true);
    isValid = false;
  } else if (name.length < 2) {
    showMessage('daily-name', 'Name must be at least 2 characters', true);
    isValid = false;
  } else {
    showMessage('daily-name', 'Name looks good', false);
  }

  // Validate phone
  if (!phone) {
    showMessage('daily-phone', 'Please enter your phone number', true);
    isValid = false;
  } else if (!validatePhone(phone)) {
    showMessage('daily-phone', 'Invalid Kenyan number (e.g. 0712345678)', true);
    isValid = false;
  } else {
    showMessage('daily-phone', 'Phone number valid', false);
  }

  // Validate service
  if (!service) {
    showMessage('daily-service', 'Please select a service', true);
    isValid = false;
  } else {
    showMessage('daily-service', service + ' selected', false);
  }

  // Validate plan
  if (!plan) {
    showMessage('daily-plan', 'Please select a membership plan', true);
    isValid = false;
  } else {
    showMessage('daily-plan', plan + ' selected', false);
  }

  // Validate date
  if (!date) {
    showMessage('daily-date', 'Please select a date', true);
    isValid = false;
  } else {
    showMessage('daily-date', 'Date selected', false);
  }

  // Validate time
  if (!time) {
    showMessage('daily-time', 'Please select a time', true);
    isValid = false;
  } else {
    showMessage('daily-time', 'Time selected', false);
  }

  if (!isValid) return;

  // All valid — send to WhatsApp
  sendDailyToWhatsApp(name, phone, service, plan, date, time);
}

// ---- Send Free Trial to WhatsApp ----
function sendTrialToWhatsApp(name, phone, service, plan, date, time) {
  const dateFormatted = new Date(date).toLocaleDateString('en-KE', {weekday:'short',day:'numeric',month:'short',year:'numeric'});
  
  const msg = [
    '*ManuKE Gym — Free Trial Booking* ',
    '',
    ` *Name:* ${name}`,
    ` *Phone:* ${phone}`,
    ` *Service:* ${service}`,
    ` *Plan:* ${plan}`,
    ` *Date:* ${dateFormatted}`,
    ` *Time:* ${time}`,
    '',
    '_Booked via ManuKE website_'
  ].join('\n');

  const waUrl = `https://wa.me/254711392245?text=${encodeURIComponent(msg)}`;
  closeModal();
  window.open(waUrl, '_blank', 'noopener');
}

// ---- Send Daily Booking to WhatsApp ----
function sendDailyToWhatsApp(name, phone, service, plan, date, time) {
  const dateFormatted = new Date(date).toLocaleDateString('en-KE', {weekday:'short',day:'numeric',month:'short',year:'numeric'});
  
  const msg = [
    '*ManuKE Gym — Daily Session Booking* ',
    '',
    ` *Name:* ${name}`,
    ` *Phone:* ${phone}`,
    ` *Service:* ${service}`,
    ` *Plan:* ${plan}`,
    ` *Date:* ${dateFormatted}`,
    ` *Time:* ${time}`,
    '',
    '_Booked via ManuKE website_'
  ].join('\n');

  const waUrl = `https://wa.me/254711392245?text=${encodeURIComponent(msg)}`;
  closeModal();
  window.open(waUrl, '_blank', 'noopener');
}



// ---- Scroll reveal (lightweight IntersectionObserver) ----
if ('IntersectionObserver' in window) {
  const io = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('on');
        io.unobserve(entry.target); // fire once
      }
    });
  }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

  document.querySelectorAll('.reveal').forEach(el => io.observe(el));
}



// ---- Highlight today's hours ----
(function() {
  const day = new Date().getDay();
  const rows = document.querySelectorAll('.hours-row');
  // Mon–Fri = rows[0], Sat = rows[1], Sun = rows[2], PH = rows[3]
  const map = { 1:0,2:0,3:0,4:0,5:0, 6:1, 0:2 };
  if (rows[map[day]]) {
    rows.forEach(r => r.classList.remove('today'));
    rows[map[day]].classList.add('today');
  }
})();

// ---- Update live-pill open/closed state based on time ----
function updateOpenStatus() {
  const now = new Date();
  const h = now.getHours();
  const livePill = document.querySelector('.live-pill');
  const liveStatusText = document.querySelector('.live-status-text');
  const bookingNote = document.getElementById('booking-note');
  if (!livePill) return;

  // Open hours: 5:00 (inclusive) to 22:00 (exclusive)
  const isOpen = (h >= 5 && h < 22);
  if (isOpen) {
    livePill.classList.remove('closed');
    livePill.classList.add('open');
    if (liveStatusText) liveStatusText.textContent = 'Gym open - 5AM-10PM';
    if (bookingNote) bookingNote.textContent = 'Walk-ins welcome - bookings are prioritized';
  } else {
    livePill.classList.remove('open');
    livePill.classList.add('closed');
    if (liveStatusText) liveStatusText.textContent = 'Closed - bookings prioritized';
    if (bookingNote) bookingNote.textContent = 'Walk-ins welcome, but book ahead for preferred slots.';
  }
}

// Run on load and update every minute
updateOpenStatus();
setInterval(updateOpenStatus, 60 * 1000);

// ---- Testimonial Scrolling ----
function scrollTesti(amount) {
  const grid = document.getElementById('testi-grid');
  if (grid) {
    grid.scrollBy({left: amount, behavior: 'smooth'});
  }
}


