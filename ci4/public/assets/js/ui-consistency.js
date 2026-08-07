(function () {
  function normalize(path) {
    if (!path) return "/";
    return path.replace(/\/+$/, "") || "/";
  }

  var currentPath = normalize(window.location.pathname);
  var currentHash = window.location.hash || "";
  var navLinks = document.querySelectorAll(".sidebar-wrapper .nav-link[href]");
  var activeLink = null;

  navLinks.forEach(function (link) {
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

  var sidebarToggleButtons = document.querySelectorAll(".sidebar-toggle, .sidebar-toggle-mobile");
  var sidebarBackdrop = document.querySelector(".sidebar-backdrop");
  var bodyElement = document.body;

  function isMobile() {
    return window.innerWidth < 992;
  }

  function setSidebarCollapsed(collapsed) {
    bodyElement.classList.toggle("sidebar-collapsed", collapsed);
    sidebarToggleButtons.forEach(function (button) {
      button.setAttribute("aria-expanded", collapsed ? "false" : "true");
    });
    window.localStorage.setItem("sidebarCollapsed", collapsed ? "1" : "0");
  }

  function setSidebarOpen(open) {
    bodyElement.classList.toggle("sidebar-open", open);
    if (sidebarBackdrop) {
      sidebarBackdrop.classList.toggle("visible", open);
    }
  }

  function closeMobileSidebar() {
    setSidebarOpen(false);
  }

  function toggleSidebar() {
    if (isMobile()) {
      setSidebarOpen(!bodyElement.classList.contains("sidebar-open"));
    } else {
      setSidebarCollapsed(!bodyElement.classList.contains("sidebar-collapsed"));
    }
  }

  sidebarToggleButtons.forEach(function (button) {
    button.addEventListener("click", toggleSidebar);
  });

  if (sidebarBackdrop) {
    sidebarBackdrop.addEventListener("click", closeMobileSidebar);
  }

  navLinks.forEach(function (link) {
    link.addEventListener("click", function () {
      if (isMobile()) {
        closeMobileSidebar();
      }
    });
  });

  document.addEventListener("keydown", function (event) {
    if (event.key === "Escape" && bodyElement.classList.contains("sidebar-open")) {
      closeMobileSidebar();
    }
  });

  window.addEventListener("resize", function () {
    if (!isMobile() && bodyElement.classList.contains("sidebar-open")) {
      closeMobileSidebar();
    }
  });

  if (window.localStorage.getItem("sidebarCollapsed") === "1") {
    setSidebarCollapsed(true);
  }
})();
