// Nawigacja między sekcjami
document.addEventListener('DOMContentLoaded', function () {
  const links = document.querySelectorAll('.nav-link');
  const pages = document.querySelectorAll('.page');

  function show(id) {
    pages.forEach(p => p.classList.toggle('active', p.id === id));
    links.forEach(l => l.classList.toggle('active', l.dataset.target === id));
    window.scrollTo(0, 0);
  }

  links.forEach(link => {
    link.addEventListener('click', e => {
      e.preventDefault();
      show(link.dataset.target);
      history.replaceState(null, '', '#' + link.dataset.target);
    });
  });

  // Obsługa hash w URL
  const initial = location.hash.replace('#', '') || 'intro';
  if (document.getElementById(initial)) show(initial);

  // Taby w sekcjach
  document.querySelectorAll('.tabs').forEach(group => {
    const btns = group.querySelectorAll('.tab-btn');
    const contents = group.parentElement.querySelectorAll('.tab-content');
    btns.forEach(btn => {
      btn.addEventListener('click', () => {
        btns.forEach(b => b.classList.remove('active'));
        contents.forEach(c => c.classList.remove('active'));
        btn.classList.add('active');
        const target = btn.dataset.tab;
        group.parentElement.querySelector('.tab-content[data-tab="' + target + '"]').classList.add('active');
      });
    });
  });
});
