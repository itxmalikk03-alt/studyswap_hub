/* ============================================================
   StudySwap Hub — main.js
   All shared JS: navbar, filters, forms, tabs, toasts, etc.
   ============================================================ */

'use strict';

/* ── Toast notification (Updated for Live & Multiple) ───── */
function toast(msg, icon = 'fa-check-circle') {
  let el = document.getElementById('toast');
  if (!el) {
    el = document.createElement('div');
    el.id = 'toast';
    document.body.appendChild(el);
  }
  
  const toastItem = document.createElement('div');
  toastItem.className = 'toast-notification-item';
  toastItem.innerHTML = `<i class="fas ${icon}"></i> <span>${msg}</span>`;
  
  el.appendChild(toastItem);
  el.classList.add('show');

  setTimeout(() => {
    toastItem.style.opacity = '0';
    setTimeout(() => {
        toastItem.remove();
        if (el.children.length === 0) el.classList.remove('show');
    }, 500);
  }, 3200);
}

/* ── NEW: Live Notification Poller ──────────────────────── */
(function initLiveNotifications() {
    setInterval(() => {
        fetch('api-check-notifications.php')
            .then(response => response.json())
            .then(data => {
                if (data.new) {
                    toast(data.message, 'fa-bell');
                }
            })
            .catch(err => console.log('Notification sync offline.'));
    }, 8000);
})();

/* ── Navbar scroll shadow ───────────────────────────────── */
(function initNavbar() {
  const nav = document.getElementById('navbar');
  if (!nav) return;
  const onScroll = () => nav.classList.toggle('scrolled', window.scrollY > 8);
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
})();

/* ── Hamburger menu ─────────────────────────────────────── */
(function initHamburger() {
  const btn  = document.getElementById('hamburger');
  const menu = document.getElementById('mobileNav');
  if (!btn || !menu) return;

  btn.addEventListener('click', () => {
    const open = menu.classList.toggle('open');
    btn.innerHTML = open
      ? '<i class="fas fa-times"></i>'
      : '<i class="fas fa-bars"></i>';
  });

  menu.querySelectorAll('a').forEach(a => {
    a.addEventListener('click', () => {
      menu.classList.remove('open');
      btn.innerHTML = '<i class="fas fa-bars"></i>';
    });
  });
})();

/* ── Active nav link ────────────────────────────────────── */
(function highlightNav() {
  const page = location.pathname.split('/').pop() || 'index.php';
  document.querySelectorAll('.nav-links a, .mobile-nav a').forEach(a => {
    if (a.getAttribute('href') === page) a.classList.add('active');
  });
})();

/* ── Scroll fade-up ─────────────────────────────────────── */
(function initFadeUp() {
  const els = document.querySelectorAll('.fade-up');
  if (!els.length) return;
  const io = new IntersectionObserver(entries => {
    entries.forEach(e => {
      if (e.isIntersecting) { e.target.classList.add('visible'); io.unobserve(e.target); }
    });
  }, { threshold: 0.1 });
  els.forEach(el => io.observe(el));
})();

/* ── University filter tabs ─────────────────────────────── */
(function initUniTabs() {
  const tabs   = document.querySelectorAll('.uni-tab');
  const panels = document.querySelectorAll('.uni-panel');
  if (!tabs.length) return;

  function activate(idx) {
    tabs.forEach((t, i) => {
      t.classList.toggle('active', i === idx);
      if (panels[i]) panels[i].classList.toggle('show', i === idx);
    });
  }

  tabs.forEach((tab, i) => tab.addEventListener('click', () => activate(i)));
  activate(0); 
})();

/* ── Browse search + category filter ───────────────────── */
(function initBrowseFilter() {
  const input    = document.getElementById('browseSearch');
  const catSel   = document.getElementById('catFilter');
  const typeSel  = document.getElementById('typeFilter');
  const uniSel   = document.getElementById('uniFilter');
  const cards    = document.querySelectorAll('.book-card-wrap');
  if (!cards.length) return;

  function filter() {
    const q   = (input   ? input.value.toLowerCase()   : '');
    const cat = (catSel  ? catSel.value.toLowerCase()  : '');
    const typ = (typeSel ? typeSel.value.toLowerCase() : '');
    const uni = (uniSel  ? uniSel.value.toLowerCase()  : '');

    let visible = 0;
    cards.forEach(wrap => {
      const data  = wrap.dataset;
      const title = (data.title    || '').toLowerCase();
      const dc    = (data.category || '').toLowerCase();
      const dt    = (data.type     || '').toLowerCase();
      const du    = (data.uni      || '').toLowerCase();

      const match =
        (!q   || title.includes(q))   &&
        (!cat || dc === cat)           &&
        (!typ || dt === typ)           &&
        (!uni || du.includes(uni));

      wrap.style.display = match ? '' : 'none';
      if (match) visible++;
    });

    const counter = document.getElementById('resultCount');
    if (counter) counter.textContent = `Showing ${visible} book${visible !== 1 ? 's' : ''}`;
  }

  if (input)   input.addEventListener('input', filter);
  if (catSel)  catSel.addEventListener('change', filter);
  if (typeSel) typeSel.addEventListener('change', filter);
  if (uniSel)  uniSel.addEventListener('change', filter);
})();

/* ── Hero search redirect ───────────────────────────────── */
(function initHeroSearch() {
  const form = document.getElementById('heroSearch');
  if (!form) return;
  form.addEventListener('submit', e => {
    e.preventDefault();
    const q = form.querySelector('input').value.trim();
    if (q) window.location.href = `browse.php?q=${encodeURIComponent(q)}`;
    else   window.location.href = 'browse.php';
  });
})();

/* ── Pre-fill browse search from URL ────────────────────── */
(function prefillSearch() {
  const input = document.getElementById('browseSearch');
  if (!input) return;
  const params = new URLSearchParams(location.search);
  const q = params.get('q');
  if (q) { input.value = q; input.dispatchEvent(new Event('input')); }
})();

/* ── Profile tabs ───────────────────────────────────────── */
(function initProfileTabs() {
  const tabs   = document.querySelectorAll('.profile-tab');
  const panels = document.querySelectorAll('.tab-panel');
  if (!tabs.length) return;

  function activate(id) {
    tabs.forEach(t => t.classList.toggle('active', t.dataset.tab === id));
    panels.forEach(p => p.classList.toggle('active', p.id === id));
  }

  tabs.forEach(tab => tab.addEventListener('click', () => activate(tab.dataset.tab)));
  if (tabs[0]) activate(tabs[0].dataset.tab);
})();

/* ── Wishlist toggle ────────────────────────────────────── */
document.addEventListener('click', e => {
  const btn = e.target.closest('.wish-btn');
  if (!btn) return;
  e.preventDefault();
  const icon = btn.querySelector('i');
  const active = btn.classList.toggle('active');
  icon.classList.toggle('far', !active);
  icon.classList.toggle('fas', active);
  toast(active ? 'Added to wishlist' : 'Removed from wishlist',
        active ? 'fa-heart' : 'fa-heart-broken');
});

/* ── Request accept / decline ───────────────────────────── */
document.addEventListener('click', e => {
  const btn  = e.target.closest('[data-action]');
  if (!btn) return;
  const action = btn.dataset.action;
  const card   = btn.closest('.req-card');
  if (!card) return;
  const statusEl = card.querySelector('.status');
  const actDiv   = card.querySelector('.req-actions');

  if (action === 'accept') {
    if (statusEl) { statusEl.textContent = 'Accepted'; statusEl.className = 'status status-accepted'; }
    toast('Request accepted!', 'fa-check-circle');
  } else if (action === 'decline') {
    if (statusEl) { statusEl.textContent = 'Declined'; statusEl.className = 'status status-declined'; }
    toast('Request declined', 'fa-times-circle');
  } else if (action === 'swap') {
    toast('Swap request sent!', 'fa-exchange-alt');
  }
  if (actDiv) actDiv.style.display = 'none';
});

/* ── Image upload preview ───────────────────────────────── */
(function initImgPreview() {
  const input   = document.getElementById('bookImageInput');
  const preview = document.getElementById('img-preview');
  if (!input || !preview) return;

  input.addEventListener('change', () => {
    const file = input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = ev => {
      preview.src = ev.target.result;
      preview.style.display = 'block';
    };
    reader.readAsDataURL(file);
  });
})();

/* ── Generic form validation ────────────────────────────── */
function validateForm(formEl) {
  let ok = true;
  formEl.querySelectorAll('[required]').forEach(field => {
    const errEl = field.parentElement.querySelector('.field-error');
    if (!field.value.trim()) {
      field.classList.add('error');
      if (errEl) errEl.classList.add('show');
      ok = false;
      field.addEventListener('input', () => {
        field.classList.remove('error');
        if (errEl) errEl.classList.remove('show');
      }, { once: true });
    }
  });
  return ok;
}

/* ── Login/Register/AddBook Logic (Front-end Sim) ────────── */
// Note: In PHP version, these might be handled by forms, but keeping JS logic as per original
(function initForms() {
    const loginForm = document.getElementById('loginForm');
    if(loginForm) loginForm.addEventListener('submit', (e) => {
        // Validation logic preserved from your original
        if(!validateForm(loginForm)) e.preventDefault();
    });
})();

/* ── Mark all notifications read ────────────────────────── */
(function initNotifications() {
  const btn = document.getElementById('markAllRead');
  if (!btn) return;
  btn.addEventListener('click', () => {
    document.querySelectorAll('.notif-dot.unread').forEach(d => d.classList.remove('unread'));
    btn.disabled = true;
    btn.textContent = 'All read';
    toast('All notifications marked as read', 'fa-check');
  });
})();

/* ── Delete book row ────────────────────────────────────── */
document.addEventListener('click', e => {
  const btn = e.target.closest('[data-action="delete-book"]');
  if (!btn) return;
  if (confirm('Remove this book listing?')) {
    const row = btn.closest('tr');
    if (row) { row.style.transition = 'opacity .3s'; row.style.opacity = '0'; setTimeout(() => row.remove(), 300); }
    toast('Book removed', 'fa-trash');
  }
});

/* ── Smooth scroll ──────────────────────────────────────── */
document.addEventListener('click', e => {
  const a = e.target.closest('a[href^="#"]');
  if (!a) return;
  const id = a.getAttribute('href').slice(1);
  const target = document.getElementById(id);
  if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
});