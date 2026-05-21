(function () {
  function normalize(path) {
    if (!path) return "/";
    return path.replace(/\/+$/, "") || "/";
  }

  var currentPath = normalize(window.location.pathname);
  var currentHash = window.location.hash || "";
  var links = document.querySelectorAll(".sidebar-wrapper .nav-link[href]");
  var activeLink = null;

  links.forEach(function (link) {
    var href = link.getAttribute("href");
    if (!href || href === "#" || link.classList.contains("text-danger")) {
      return;
    }

    var target;
    try {
      target = new URL(href, window.location.origin);
    } catch (err) {
      return;
    }

    var targetPath = normalize(target.pathname);
    var targetHash = target.hash || "";
    var hashMatch = !targetHash || targetHash === currentHash;

    if (targetPath === currentPath && hashMatch) {
      activeLink = link;
    }
  });

  if (activeLink) {
    activeLink.classList.add("active");
  }
})();
