(function () {
  document.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) { if (!confirm(f.dataset.confirm)) e.preventDefault(); });
  });
  // tiny HTML toolbar for textarea.html fields
  document.querySelectorAll('textarea.html').forEach(function (ta) {
    var tools = [['B', 'strong'], ['I', 'em'], ['H2', 'h2'], ['H3', 'h3'], ['P', 'p'], ['• List', 'ul'], ['Link', 'a']];
    var bar = document.createElement('div'); bar.className = 'toolbar';
    tools.forEach(function (t) {
      var b = document.createElement('button'); b.type = 'button'; b.textContent = t[0];
      b.onclick = function () {
        var s = ta.selectionStart, e = ta.selectionEnd, sel = ta.value.slice(s, e), out;
        if (t[1] === 'ul') out = '<ul>\n' + (sel || 'Item').split('\n').map(function (l) { return '  <li>' + l + '</li>'; }).join('\n') + '\n</ul>';
        else if (t[1] === 'a') { var u = prompt('Link URL', 'https://'); if (!u) return; out = '<a href="' + u + '">' + (sel || 'link text') + '</a>'; }
        else out = '<' + t[1] + '>' + (sel || 'text') + '</' + t[1] + '>';
        ta.value = ta.value.slice(0, s) + out + ta.value.slice(e); ta.focus();
      };
      bar.appendChild(b);
    });
    ta.parentNode.insertBefore(bar, ta);
  });
})();
