export function showImpulseError(message: string) {
  let container = document.getElementById("impulse-error-container") as HTMLDivElement | null;
  if (!container) {
    container = document.createElement("div");
    container.id = "impulse-error-container";
    container.style.position = "fixed";
    container.style.top = "24px";
    container.style.right = "24px";
    container.style.zIndex = "9999";
    container.style.display = "flex";
    container.style.flexDirection = "column";
    container.style.alignItems = "flex-end";
    container.style.gap = "12px";
    container.style.pointerEvents = "none";

    document.body.appendChild(container);
  }

  const alreadyDisplayed = Array.from(container.children).some((child) =>
    child.textContent?.replace(/\s+/g, ' ').includes(message)
  );

  if (alreadyDisplayed) {
    return;
  }

  const banner = document.createElement("div");
  banner.style.background = "#b91c1c";
  banner.style.color = "#fff";
  banner.style.padding = "12px";
  banner.style.fontFamily = "sans-serif";
  banner.style.borderRadius = "8px";
  banner.style.boxShadow = "0 2px 12px #0003";
  banner.style.opacity = "1";
  banner.style.minWidth = "280px";
  banner.style.pointerEvents = "auto";
  banner.style.transition = "opacity 0.4s, transform 0.4s";
  banner.style.marginTop = "0";
  banner.style.marginBottom = "0";

  banner.innerHTML = `
    <span style="vertical-align:middle;">${message}</span>
    <button style="margin-left:24px;background:transparent;border:none;color:#fff;font-size:22px;cursor:pointer;vertical-align:middle;" aria-label="Fermer">&times;</button>
  `;

  banner.querySelector("button")?.addEventListener("click", () => {
    banner.style.opacity = "0";
    banner.style.transform = "translateX(60px)";
    setTimeout(() => banner.remove(), 400);
  });

  container.appendChild(banner);

  setTimeout(() => {
    if (banner.parentNode) {
      banner.style.opacity = "0";
      banner.style.transform = "translateX(60px)";
      setTimeout(() => banner.remove(), 400);
    }
  }, 6000);
}
