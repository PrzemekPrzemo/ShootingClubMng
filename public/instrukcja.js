// ── i18n: tłumaczenia kluczy i sekcji ──
const I18N = {
  pl: {
    title: 'Instrukcja obsługi — Shootero',
    brand_tag: 'Instrukcja obsługi',
    demo_link: '▶ Otwórz demo',
    nav_intro: 'Wstęp',
    nav_about: 'O systemie',
    nav_login: 'Logowanie',
    nav_roles: 'Role',
    nav_admin: 'Administrator klubu',
    nav_zarzad: 'Zarząd',
    nav_instruktor: 'Instruktor',
    nav_sedzia: 'Sędzia',
    nav_zawodnik: 'Zawodnik',
    nav_scenarios: 'Scenariusze',
    nav_new_member: 'Nowy zawodnik',
    nav_add_license: 'Dodanie licencji',
    nav_payment: 'Rejestracja wpłaty',
    nav_competition: 'Zawody + listy',
    nav_security: '2FA i hasła'
  },
  en: {
    title: 'User Manual — Shootero',
    brand_tag: 'User Manual',
    demo_link: '▶ Open demo',
    nav_intro: 'Introduction',
    nav_about: 'About the system',
    nav_login: 'Login',
    nav_roles: 'Roles',
    nav_admin: 'Club Administrator',
    nav_zarzad: 'Board',
    nav_instruktor: 'Instructor',
    nav_sedzia: 'Judge',
    nav_zawodnik: 'Athlete',
    nav_scenarios: 'Scenarios',
    nav_new_member: 'New athlete',
    nav_add_license: 'Add license',
    nav_payment: 'Register payment',
    nav_competition: 'Competitions + lists',
    nav_security: '2FA & passwords'
  }
};

function applyI18n(lang) {
  document.documentElement.lang = lang;
  // Proste klucze
  document.querySelectorAll('[data-i18n]').forEach(el => {
    const key = el.dataset.i18n;
    const val = (I18N[lang] && I18N[lang][key]) || I18N.pl[key];
    if (val != null) {
      if (el.tagName === 'TITLE') el.textContent = val;
      else el.innerHTML = val;
    }
  });
  // Bloki sekcji (duże obszary HTML)
  if (window.SECTIONS && window.SECTIONS[lang]) {
    document.querySelectorAll('[data-section]').forEach(el => {
      const id = el.dataset.section;
      if (window.SECTIONS[lang][id]) el.innerHTML = window.SECTIONS[lang][id];
    });
  }
  // Przycisk języka
  document.querySelectorAll('.lang-btn').forEach(b => {
    b.classList.toggle('active', b.dataset.lang === lang);
  });
  try { localStorage.setItem('lang', lang); } catch(e) {}
  bindTabs();
}

function bindTabs() {
  document.querySelectorAll('.tabs').forEach(group => {
    if (group.dataset.bound) return;
    group.dataset.bound = '1';
    const btns = group.querySelectorAll('.tab-btn');
    btns.forEach(btn => {
      btn.addEventListener('click', () => {
        const parent = group.parentElement;
        parent.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        parent.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        btn.classList.add('active');
        const target = btn.dataset.tab;
        const tc = parent.querySelector('.tab-content[data-tab="' + target + '"]');
        if (tc) tc.classList.add('active');
      });
    });
  });
}

// ── Nawigacja między sekcjami ──
document.addEventListener('DOMContentLoaded', function () {
  const links = document.querySelectorAll('.nav-link');
  const pages = document.querySelectorAll('.page');

  function show(id) {
    pages.forEach(p => p.classList.toggle('active', p.id === id));
    links.forEach(l => l.classList.toggle('active', l.dataset.target === id));
    window.scrollTo(0, 0);
    bindTabs();
  }

  links.forEach(link => {
    link.addEventListener('click', e => {
      e.preventDefault();
      show(link.dataset.target);
      history.replaceState(null, '', '#' + link.dataset.target);
    });
  });

  document.querySelectorAll('.lang-btn').forEach(b => {
    b.addEventListener('click', () => applyI18n(b.dataset.lang));
  });

  let savedLang = 'pl';
  try { savedLang = localStorage.getItem('lang') || 'pl'; } catch(e) {}
  applyI18n(savedLang);

  const initial = location.hash.replace('#', '') || 'intro';
  if (document.getElementById(initial)) show(initial);
  else bindTabs();
});
