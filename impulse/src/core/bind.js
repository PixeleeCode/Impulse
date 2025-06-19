import { updateComponent } from "./ajax";
import { getUniqueSelector } from "../utils/dom";
export function bindImpulseEvents() {
    // impulse:click
    document.querySelectorAll("[impulse\\:click]").forEach((el) => {
        if (el._impulseBoundClick)
            return;
        el._impulseBoundClick = true;
        el.addEventListener("click", (event) => {
            event.preventDefault();
            const method = el.getAttribute("impulse:click");
            const parent = el.closest("[data-impulse-id]");
            if (!method || !parent)
                return;
            const componentId = parent.getAttribute("data-impulse-id");
            let update = el.getAttribute("impulse:update") || undefined;
            updateComponent(componentId, method, undefined, {
                update
            });
        });
    });
    // impulse:input
    document.querySelectorAll("[impulse\\:input]").forEach((el) => {
        if (el._impulseBoundInput)
            return;
        el._impulseBoundInput = true;
        el.addEventListener("input", (event) => {
            const method = el.getAttribute("impulse:input");
            const parent = el.closest("[data-impulse-id]");
            if (!method || !parent)
                return;
            const componentId = parent.getAttribute("data-impulse-id");
            const value = event.target.value;
            let update = el.getAttribute("impulse:update") || undefined;
            const activeElement = document.activeElement;
            const isCurrentInput = activeElement === el;
            const selectionStart = isCurrentInput ? activeElement.selectionStart : null;
            const selectionEnd = isCurrentInput ? activeElement.selectionEnd : null;
            updateComponent(componentId, method, value, {
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
    document.querySelectorAll("[impulse\\:change]").forEach((el) => {
        if (el._impulseBoundChange)
            return;
        el._impulseBoundChange = true;
        el.addEventListener("change", (event) => {
            const method = el.getAttribute("impulse:change");
            const parent = el.closest("[data-impulse-id]");
            if (!method || !parent)
                return;
            const componentId = parent.getAttribute("data-impulse-id");
            const value = event.target.value;
            let update = el.getAttribute("impulse:update") || undefined;
            // Pour les éléments select, conserver l'option sélectionnée
            const selectedIndex = el.selectedIndex !== undefined
                ? el.selectedIndex
                : -1;
            updateComponent(componentId, method, value, {
                activeElementId: el.id,
                activeElementSelector: getUniqueSelector(el),
                selectedIndex: selectedIndex,
                update
            });
        });
    });
    // impulse:blur
    document.querySelectorAll("[impulse\\:blur]").forEach((el) => {
        if (el._impulseBoundBlur)
            return;
        el._impulseBoundBlur = true;
        el.addEventListener("blur", (event) => {
            const method = el.getAttribute("impulse:blur");
            const parent = el.closest("[data-impulse-id]");
            if (!method || !parent)
                return;
            const componentId = parent.getAttribute("data-impulse-id");
            const value = event.target.value;
            let update = el.getAttribute("impulse:update") || undefined;
            updateComponent(componentId, method, value, {
                update
            });
        });
    });
    // impulse:keydown
    document.querySelectorAll("[impulse\\:keydown]").forEach((el) => {
        if (el._impulseBoundKeyDown)
            return;
        el._impulseBoundKeyDown = true;
        el.addEventListener("keydown", (event) => {
            const method = el.getAttribute("impulse:keydown");
            const parent = el.closest("[data-impulse-id]");
            if (!method || !parent)
                return;
            const componentId = parent.getAttribute("data-impulse-id");
            let update = el.getAttribute("impulse:update") || undefined;
            const key = event.key;
            updateComponent(componentId, `${method}('${key}')`, undefined, {
                update
            });
        });
    });
    // impulse:submit (form)
    document.querySelectorAll("[impulse\\:submit]").forEach((form) => {
        if (form._impulseBoundSubmit)
            return;
        form._impulseBoundSubmit = true;
        form.addEventListener("submit", (event) => {
            var _a;
            event.preventDefault();
            const method = form.getAttribute("impulse:submit");
            const componentId = (_a = form.closest("[data-impulse-id]")) === null || _a === void 0 ? void 0 : _a.getAttribute("data-impulse-id");
            if (!method || !componentId)
                return;
            let update = form.getAttribute("impulse:update") || undefined;
            updateComponent(componentId, method, undefined, {
                update
            });
        });
    });
    // impulse:emit universel
    document.querySelectorAll("[impulse\\:emit]").forEach((el) => {
        if (el._impulseBoundEmit)
            return;
        el._impulseBoundEmit = true;
        const isForm = el.tagName.toLowerCase() === "form";
        const eventName = isForm ? "submit" : "click";
        el.addEventListener(eventName, (event) => {
            event.preventDefault();
            const emitEvent = el.getAttribute("impulse:emit");
            if (!emitEvent)
                return;
            let payload = {};
            if (isForm) {
                const formData = new FormData(el);
                formData.forEach((v, k) => payload[k] = v);
            }
            else {
                // Si l'élément a un attribut impulse:payload, parse-le comme JSON sinon ignore
                const attrPayload = el.getAttribute("impulse:payload");
                if (attrPayload) {
                    try {
                        payload = JSON.parse(attrPayload);
                    }
                    catch (e) {
                        payload = { value: attrPayload };
                    }
                }
            }
            // @ts-ignore
            Impulse.emit(emitEvent, payload, {
                callback: (result) => {
                    if (typeof el._onImpulseResult === "function") {
                        el._onImpulseResult(result);
                    }
                    else {
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
//# sourceMappingURL=bind.js.map