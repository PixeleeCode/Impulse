import { showImpulseError } from './error';
import { initImpulse } from "./events";

function collectStates(componentId: string): Record<string, any>
{
  const componentElement = document.querySelector(`[data-impulse-id="${componentId}"]`);
  let states: Record<string, any> = {};

  if (componentElement && componentElement.hasAttribute('data-states')) {
    try {
      states = JSON.parse(componentElement.getAttribute('data-states') || '{}');
    } catch (e) {
      states = {};
    }
  }

  if (componentElement) {
    componentElement.querySelectorAll<HTMLInputElement | HTMLTextAreaElement>('[impulse\\:input], [impulse\\:change]')
      .forEach((el) => {
        const stateName = el.getAttribute('name') || el.getAttribute('id');
        if (stateName) {
          states[stateName] = el.value;
        }
      });
  }

  return states;
}

async function sendUpdateRequest(payload: any): Promise<string>
{
  const res = await fetch("impulse.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify(payload)
  });

  const text = await res.text();
  if (
    res.headers.get("content-type")?.includes("application/json") ||
    (text.trim().startsWith("{") && text.trim().endsWith("}"))
  ) {
    try {
      const data = JSON.parse(text);
      if (data.error) {
        showImpulseError(data.message);
        return Promise.reject(data.error);
      }
    } catch (e) {
      console.warn("Impulse: failed to parse error JSON.", e);
    }
  }

  return text;
}

function findFocusElement(newComponent: Element, focusInfo: any): HTMLElement | null
{
  let elementToFocus: HTMLElement | null = null;

  if (focusInfo.activeElementId) {
    elementToFocus = document.getElementById(focusInfo.activeElementId);
  }

  if (!elementToFocus && focusInfo.activeElementSelector) {
    try {
      if (focusInfo.activeElementId) {
        elementToFocus = newComponent.querySelector(`#${focusInfo.activeElementId}`);
      }

      if (!elementToFocus) {
        elementToFocus = newComponent.querySelector(focusInfo.activeElementSelector);
      }
    } catch (e) {
      console.warn("Impossible de trouver l'élément avec le sélecteur:", focusInfo.activeElementSelector, ". Ajouter un ID à votre élément pour le rendre accessible. Erreur:", e);
    }
  }

  return elementToFocus;
}

function restoreFocus(newComponent: Element, focusInfo: any)
{
  setTimeout(() => {
    const elementToFocus = findFocusElement(newComponent, focusInfo);
    if (elementToFocus) {
      if (elementToFocus.tagName === 'SELECT' && typeof focusInfo.selectedIndex === 'number') {
        (elementToFocus as HTMLSelectElement).selectedIndex = focusInfo.selectedIndex;
      }

      if ('focus' in elementToFocus) {
        elementToFocus.focus();
      }

      if ('setSelectionRange' in elementToFocus &&
        typeof focusInfo.selectionStart === 'number' &&
        typeof focusInfo.selectionEnd === 'number') {
        requestAnimationFrame(() => {
          (elementToFocus as HTMLInputElement).setSelectionRange(
            focusInfo.selectionStart,
            focusInfo.selectionEnd
          );
        });
      }
    }
  }, 0);
}

function applyUpdate(componentId: string, html: string, focusInfo?: any)
{
  const wrapper = document.createElement("div");
  wrapper.innerHTML = html;
  const newComponent = wrapper.querySelector("[data-impulse-id]");

  try {
    const parsed = JSON.parse(html);
    if (parsed && typeof parsed === 'object' && parsed.fragments) {
      // Rendu groupé avec plusieurs fragments nommés
      Object.entries(parsed.fragments as Record<string, string>).forEach(([key, content]) => {
        // key = "groupName::title"
        const [group, state] = key.split("@");
        const selector = `[data-impulse-update^="${group}@"]`;
        const targets = document.querySelectorAll(selector);

        targets.forEach(el => {
          const attr = el.getAttribute("data-impulse-update");
          if (!attr) return;
          const stateKey = attr.split("@")[1];
          if (!stateKey) return;
          if (stateKey === state) {
            const wrapper = document.createElement("div");
            wrapper.innerHTML = content.trim();
            const newNode = wrapper.firstElementChild;
            if (newNode) {
              el.replaceWith(newNode);
            } else {
              el.innerHTML = content;
            }
          }
        });
      });

      if (parsed.states) {
        const currentComponent = document.querySelector(`[data-impulse-id="${componentId}"]`);
        if (currentComponent) {
          currentComponent.setAttribute('data-states', JSON.stringify(parsed.states));
        }
      }

      return;
    }
  } catch (e) {
    // pas du JSON, on continue normalement
  }

  if (focusInfo?.update) {
    let states = null;

    try {
      const parsed = JSON.parse(html);
      if (parsed && typeof parsed === 'object' && parsed.result) {
        html = parsed.result;
        states = parsed.states;
      }
    } catch (e) {
      // pas du JSON, on continue normalement
    }

    const currentComponent = document.querySelector(`[data-impulse-id="${componentId}"]`);
    const target = currentComponent?.querySelector(`[data-impulse-update="${focusInfo.update}"]`);
    if (target) {
      const wrapper = document.createElement("div");
      wrapper.innerHTML = html.trim();
      const fragment = wrapper.firstElementChild;
      if (fragment) {
        target.replaceWith(fragment);
        if (states && currentComponent) {
          currentComponent.setAttribute('data-states', JSON.stringify(states));
        }
      } else {
        target.innerHTML = html;
        if (states && currentComponent) {
          currentComponent.setAttribute('data-states', JSON.stringify(states));
        }
      }

      // PAS de initImpulse() ici (sinon double binding sur input)
      return;
    }
  }

  const currentComponent = document.querySelector(`[data-impulse-id="${componentId}"]`);
  if (newComponent && currentComponent && currentComponent.parentNode) {
    currentComponent.parentNode.replaceChild(newComponent, currentComponent);

    initImpulse();

    if (focusInfo) {
      restoreFocus(newComponent, focusInfo);
    }
  }
}

// Met à jour un composant par appel AJAX (action impulsée depuis un event)
export async function updateComponent(componentId: string, action: string, value?: string, focusInfo?: any)
{
  const states = collectStates(componentId);
  const payload: any = {
    id: componentId,
    action: action,
    states: states
  };

  if (focusInfo?.update) {
    payload.update = focusInfo.update;
  }

  if (value !== undefined) {
    payload.value = value;
  }

  const componentElement = document.querySelector(`[data-impulse-id="${componentId}"]`);
  const slotAttr = componentElement?.getAttribute('data-impulse-slot');
  if (slotAttr) {
    payload.slot = atob(slotAttr);
  }

  try {
    const html = await sendUpdateRequest(payload);
    applyUpdate(componentId, html, focusInfo);
  } catch (err) {
    console.error("Impulse error:", err);
  }
}

function getComponentIds(componentsOption?: string | string[]): string[]
{
  if (componentsOption) {
    return Array.isArray(componentsOption) ? componentsOption : [componentsOption];
  }

  return Array.from(document.querySelectorAll('[data-impulse-id]'))
    .map(el => el.getAttribute('data-impulse-id') as string)
    .filter(Boolean);
}

export async function emit(event: string, payload: any = {}, options: any = {}) {
  const components = getComponentIds(options.components);

  // Par défaut, envoie vers impulse.php (adaptable si besoin)
  const response = await fetch('/impulse.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...(options.headers || {}),
    },
    body: JSON.stringify({
      emit: event,
      payload: payload,
      components: components,
      ...options.extra // Permet d'ajouter ce que l'on veut
    }),
    credentials: 'same-origin'
  });
  let result = null;
  const rawText = await response.text();

  try {
    result = JSON.parse(rawText);

    // Déclenche un event DOM JS natif pour que tout script JS puisse écouter
    document.dispatchEvent(new CustomEvent('impulse:emit', {
      detail: {
        event: event,
        payload: payload,
      }
    }));

    if (result.updates) {
      result.updates.forEach((update: {component: string, html: string}) => {
        const dom = document.createElement('div');
        dom.innerHTML = update.html.trim();
        const newComp = dom.querySelector('[data-impulse-id]');
        const oldComp = document.querySelector(`[data-impulse-id="${update.component}"]`);
        if (newComp && oldComp && oldComp.parentNode) {
          oldComp.parentNode.replaceChild(newComp, oldComp);
        }
      });

      // Réinitialiser les events
      initImpulse();
    }
  } catch (e) {
    result = rawText;
  }

  if (typeof options.callback === 'function') {
    options.callback(result);
  }

  return result;
}
