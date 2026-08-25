(() => {
  const header = document.querySelector("[data-jp-header]");
  const menuButton = document.querySelector("[data-jp-menu-toggle]");
  const navigation = document.querySelector("[data-jp-navigation]");

  if (header && window.matchMedia("(min-width: 821px)").matches) {
    const updateHeader = () =>
      header.classList.toggle("is-sticky", window.scrollY > 120);
    updateHeader();
    window.addEventListener("scroll", updateHeader, { passive: true });
  }

  if (menuButton && navigation) {
    const closeMenu = () => {
      navigation.classList.remove("is-open");
      menuButton.setAttribute("aria-expanded", "false");
    };
    menuButton.addEventListener("click", () => {
      const open = menuButton.getAttribute("aria-expanded") === "true";
      menuButton.setAttribute("aria-expanded", String(!open));
      navigation.classList.toggle("is-open", !open);
    });
    navigation.addEventListener("keydown", (event) => {
      if (event.key === "Escape") {
        closeMenu();
        menuButton.focus();
      }
    });
    window.addEventListener("resize", () => {
      if (window.innerWidth > 1080) closeMenu();
    });
  }

  document.addEventListener("click", (event) => {
    const link = event.target.closest("a[href]");
    if (!link) return;
    const href = link.getAttribute("href") || "";
    const eventName = href.startsWith("tel:")
      ? "justicepoint_phone_click"
      : href.includes("/consultation")
        ? "justicepoint_consultation_click"
        : "";
    if (!eventName) return;
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({
      event: eventName,
      link_context:
        link.closest("header, main, footer")?.tagName.toLowerCase() || "page",
    });
  });
})();
