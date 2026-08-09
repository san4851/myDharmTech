const rootElement = document.documentElement;
const themeToggle = document.getElementById("themeToggle");
const themeStorageKey = "aarohThemePreference";

function applyTheme(theme) {
  rootElement.setAttribute("data-theme", theme);

  if (themeToggle) {
    const isDark = theme === "dark";
    themeToggle.setAttribute("aria-pressed", String(isDark));
  }
}

const savedTheme = localStorage.getItem(themeStorageKey);
if (savedTheme === "light" || savedTheme === "dark") {
  applyTheme(savedTheme);
}

if (themeToggle) {
  themeToggle.addEventListener("click", () => {
    const nextTheme = rootElement.getAttribute("data-theme") === "dark" ? "light" : "dark";
    applyTheme(nextTheme);
    localStorage.setItem(themeStorageKey, nextTheme);
  });
}
