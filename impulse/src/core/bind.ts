import { updateComponent } from "./ajax";
import { getUniqueSelector } from "../utils/dom";

export function bindImpulseEvents() {
  // impulse:click
  document.querySelectorAll<HTMLElement>("[impulse\\:click]").forEach((el) => {
    if ((el as any)._impulseBoundClick) return;
    (el as any)._impulseBoundClick = true;

    el.addEventListener("click", (event) => {
      event.preventDefault();
      const method = el.getAttribute("impulse:click");
      const parent = el.closest("[data-impulse-id]");
      if (!method || !parent) return;
      const componentId = parent.getAttribute("data-impulse-id");
      let update = el.getAttribute("impulse:update") || undefined;

      updateComponent(componentId!, method, undefined, {
        update
      });
    });
  });

  // impulse:input
  document.querySelectorAll<HTMLInputElement | HTMLTextAreaElement>("[impulse\\:input]").forEach((el) => {
    if ((el as any)._impulseBoundInput) return;
    (el as any)._impulseBoundInput = true;

    el.addEventListener("input", (event) => {
      const method = el.getAttribute("impulse:input");
      const parent = el.closest("[data-impulse-id]");
      if (!method || !parent) return;
      const componentId = parent.getAttribute("data-impulse-id");
      const value = (event.target as HTMLInputElement).value;

      let update = el.getAttribute("impulse:update") || undefined;
      const activeElement = document.activeElement as HTMLInputElement;
      const isCurrentInput = activeElement === el;
      const selectionStart = isCurrentInput ? activeElement.selectionStart : null;
      const selectionEnd = isCurrentInput ? activeElement.selectionEnd : null;

      updateComponent(componentId!, method, value, {
        activeElementId: isCurrentInput ? activeElement.id : null,
        activeElementSelector: isCurrentInput ? getUniqueSelector(activeElement) : null,
        selectionStart,
        selectionEnd,
        caretPosition: selectionStart,
        update
      });
    });
  });

  // impulse:change
  document.querySelectorAll<HTMLSelectElement | HTMLInputElement>("[impulse\\:change]").forEach((el) => {
    if ((el as any)._impulseBoundChange) return;
    (el as any)._impulseBoundChange = true;

    el.addEventListener("change", (event) => {
      const method = el.getAttribute("impulse:change");
      const parent = el.closest("[data-impulse-id]");
      if (!method || !parent) return;
      const componentId = parent.getAttribute("data-impulse-id");
      const value = (event.target as HTMLSelectElement | HTMLInputElement).value;
      let update = el.getAttribute("impulse:update") || undefined;

      // Pour les éléments select, conserver l'option sélectionnée
      const selectedIndex = (el as HTMLSelectElement).selectedIndex !== undefined
        ? (el as HTMLSelectElement).selectedIndex
        : -1;

      updateComponent(componentId!, method, value, {
        activeElementId: el.id,
        activeElementSelector: getUniqueSelector(el),
        selectedIndex: selectedIndex,
        update
      });
    });
  });

  // impulse:blur
  document.querySelectorAll<HTMLElement>("[impulse\\:blur]").forEach((el) => {
    if ((el as any)._impulseBoundBlur) return;
    (el as any)._impulseBoundBlur = true;

    el.addEventListener("blur", (event) => {
      const method = el.getAttribute("impulse:blur");
      const parent = el.closest("[data-impulse-id]");
      if (!method || !parent) return;
      const componentId = parent.getAttribute("data-impulse-id");
      const value = (event.target as HTMLInputElement).value;
      let update = el.getAttribute("impulse:update") || undefined;

      updateComponent(componentId!, method, value, {
        update
      });
    });
  });

  // impulse:keydown
  document.querySelectorAll<HTMLElement>("[impulse\\:keydown]").forEach((el) => {
    if ((el as any)._impulseBoundKeyDown) return;
    (el as any)._impulseBoundKeyDown = true;

    el.addEventListener("keydown", (event: KeyboardEvent) => {
      const method = el.getAttribute("impulse:keydown");
      const parent = el.closest("[data-impulse-id]");
      if (!method || !parent) return;
      const componentId = parent.getAttribute("data-impulse-id");
      let update = el.getAttribute("impulse:update") || undefined;
      const key = event.key;

      updateComponent(componentId!, `${method}('${key}')`, undefined, {
        update
      });
    });
  });

  // impulse:submit (form)
  document.querySelectorAll<HTMLFormElement>("[impulse\\:submit]").forEach((form) => {
    if ((form as any)._impulseBoundSubmit) return;
    (form as any)._impulseBoundSubmit = true;

    form.addEventListener("submit", (event) => {
      event.preventDefault();
      const method = form.getAttribute("impulse:submit");
      const componentId = form.closest("[data-impulse-id]")?.getAttribute("data-impulse-id");
      if (!method || !componentId) return;
      let update = form.getAttribute("impulse:update") || undefined;
      updateComponent(componentId, method, undefined, {
        update
      });
    });
  });

  // impulse:emit universel
  document.querySelectorAll<HTMLElement>("[impulse\\:emit]").forEach((el) => {
    if ((el as any)._impulseBoundEmit) return;
    (el as any)._impulseBoundEmit = true;

    const isForm = el.tagName.toLowerCase() === "form";
    const eventName = isForm ? "submit" : "click";

    el.addEventListener(eventName, (event) => {
      event.preventDefault();
      const emitEvent = el.getAttribute("impulse:emit");
      if (!emitEvent) return;

      let payload: Record<string, any> = {};

      if (isForm) {
        const formData = new FormData(el as HTMLFormElement);
        formData.forEach((v, k) => payload[k] = v);
      } else {
        // Si l'élément a un attribut impulse:payload, parse-le comme JSON sinon ignore
        const attrPayload = el.getAttribute("impulse:payload");
        if (attrPayload) {
          try {
            payload = JSON.parse(attrPayload);
          } catch (e) {
            payload = { value: attrPayload };
          }
        }
      }

      // @ts-ignore
      Impulse.emit(emitEvent, payload, {
        callback: (result: any) => {
          if (typeof (el as any)._onImpulseResult === "function") {
            (el as any)._onImpulseResult(result);
          } else {
            el.dispatchEvent(new CustomEvent("impulse:result", {
              detail: result,
              bubbles: true,
            }));
          }
        }
      });
    });
  });
}
