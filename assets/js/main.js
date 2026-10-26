(function () {
  // mobile nav
  var t = document.querySelector('.nav-toggle'), nav = document.querySelector('.main-nav');
  if (t) t.addEventListener('click', function () {
    var o = nav.classList.toggle('open'); t.setAttribute('aria-expanded', o);
  });

  // hero slider
  var slides = document.querySelectorAll('.slide');
  if (slides.length) {
    var dots = document.querySelector('.hero-dots'), i = 0, timer;
    function show(n) {
      i = (n + slides.length) % slides.length;
      slides.forEach(function (s, k) { s.classList.toggle('on', k === i); });
      if (dots) [].forEach.call(dots.children, function (d, k) { d.classList.toggle('on', k === i); });
    }
    function auto() { clearInterval(timer); if (slides.length > 1) timer = setInterval(function () { show(i + 1); }, 6500); }
    if (dots) slides.forEach(function (_, k) {
      var b = document.createElement('button'); b.setAttribute('aria-label', 'Slide ' + (k + 1));
      b.onclick = function () { show(k); auto(); }; dots.appendChild(b);
    });
    show(0); auto();
  }

  // tabs
  document.querySelectorAll('[data-tabs]').forEach(function (box) {
    var btns = box.querySelectorAll('.tabs button'), panes = box.querySelectorAll('.tabpane');
    function go(k) { btns.forEach(function (b, n) { b.classList.toggle('on', n === k); }); panes.forEach(function (p, n) { p.classList.toggle('on', n === k); }); }
    btns.forEach(function (b, k) { b.addEventListener('click', function () { go(k); }); });
    go(0);
  });

  // lightbox
  var lb = document.createElement('div'); lb.className = 'lightbox'; lb.innerHTML = '<img alt="">';
  document.body.appendChild(lb);
  lb.addEventListener('click', function () { lb.classList.remove('on'); });
  document.querySelectorAll('.gal a').forEach(function (a) {
    a.addEventListener('click', function (e) { e.preventDefault(); lb.firstChild.src = a.href; lb.classList.add('on'); });
  });

  // reveal + back-to-top
  var io = 'IntersectionObserver' in window ? new IntersectionObserver(function (es) {
    es.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } });
  }, { threshold: .12 }) : null;
  document.querySelectorAll('.reveal').forEach(function (el) { io ? io.observe(el) : el.classList.add('in'); });
  var up = document.querySelector('.to-top');
  window.addEventListener('scroll', function () { up && up.classList.toggle('show', window.scrollY > 600); }, { passive: true });

  // confirm dialogs for destructive forms
  document.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) { if (!confirm(f.dataset.confirm)) e.preventDefault(); });
  });
})();

// copy-to-clipboard buttons (donation details)
document.addEventListener('click', function (e) {
  var b = e.target.closest && e.target.closest('[data-copy]');
  if (!b) return;
  var text = b.getAttribute('data-copy'), done = function () { var o = b.textContent; b.textContent = 'Copied ✓'; setTimeout(function () { b.textContent = o; }, 1600); };
  if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(text).then(done);
  else { var t = document.createElement('textarea'); t.value = text; t.style.position = 'fixed'; t.style.opacity = '0'; document.body.appendChild(t); t.select(); try { document.execCommand('copy'); done(); } catch (x) {} document.body.removeChild(t); }
});
