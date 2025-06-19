var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
import { showImpulseError } from './error';
import { initImpulse } from "./events";
// Met à jour un composant par appel AJAX (action impulsée depuis un event)
export function updateComponent(componentId, action, value, focusInfo) {
    const componentElement = document.querySelector(`[data-impulse-id="${componentId}"]`);
    let states = {};
    if (componentElement && componentElement.hasAttribute('data-states')) {
        try {
            states = JSON.parse(componentElement.getAttribute('data-states') || '{}');
        }
        catch (e) {
            states = {};
        }
    }
    if (componentElement) {
        componentElement.querySelectorAll('[impulse\\:input], [impulse\\:change]')
            .forEach((el) => {
            const stateName = el.getAttribute('name') || el.getAttribute('id');
            if (stateName) {
                states[stateName] = el.value;
            }
        });
    }
    const payload = {
        id: componentId,
        action: action,
        states: states
    };
    if (focusInfo === null || focusInfo === void 0 ? void 0 : focusInfo.update) {
        payload.update = focusInfo.update;
    }
    // Ajouter la valeur si elle est fournie (pour les inputs)
    if (value !== undefined) {
        payload.value = value;
    }
    fetch("impulse.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(payload)
    })
        .then((res) => __awaiter(this, void 0, void 0, function* () {
        var _a;
        const text = yield res.text();
        if (((_a = res.headers.get("content-type")) === null || _a === void 0 ? void 0 : _a.includes("application/json")) ||
            (text.trim().startsWith("{") && text.trim().endsWith("}"))) {
            try {
                const data = JSON.parse(text);
                if (data.error) {
                    showImpulseError(data.message);
                    throw new Error(data.message);
                }
            }
            catch (e) {
                console.warn("Impulse: failed to parse error JSON.", e);
            }
        }
        return text;
    }))
        .then((html) => {
        const wrapper = document.createElement("div");
        wrapper.innerHTML = html;
        const newComponent = wrapper.querySelector("[data-impulse-id]");
        if (focusInfo === null || focusInfo === void 0 ? void 0 : focusInfo.update) {
            let states = null;
            try {
                const parsed = JSON.parse(html);
                if (parsed && typeof parsed === 'object' && parsed.result) {
                    html = parsed.result;
                    states = parsed.states;
                }
            }
            catch (e) {
                // pas du JSON, on continue normalement
            }
            const currentComponent = document.querySelector(`[data-impulse-id="${componentId}"]`);
            const target = currentComponent === null || currentComponent === void 0 ? void 0 : currentComponent.querySelector(`[data-impulse-update="${focusInfo.update}"]`);
            if (target) {
                const wrapper = document.createElement("div");
                wrapper.innerHTML = html.trim();
                const fragment = wrapper.firstElementChild;
                if (fragment) {
                    target.replaceWith(fragment);
                    if (states && currentComponent) {
                        currentComponent.setAttribute('data-states', JSON.stringify(states));
                    }
                }
                else {
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
                setTimeout(() => {
                    let elementToFocus = null;
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
                        }
                        catch (e) {
                            console.warn("Impossible de trouver l'élément avec le sélecteur:", focusInfo.activeElementSelector, ". Ajouter un ID à votre élément pour le rendre accessible. Erreur:", e);
                        }
                    }
                    if (elementToFocus) {
                        if (elementToFocus.tagName === 'SELECT' && typeof focusInfo.selectedIndex === 'number') {
                            elementToFocus.selectedIndex = focusInfo.selectedIndex;
                        }
                        // Focus l'élément
                        if ('focus' in elementToFocus) {
                            elementToFocus.focus();
                        }
                        if ('setSelectionRange' in elementToFocus &&
                            typeof focusInfo.selectionStart === 'number' &&
                            typeof focusInfo.selectionEnd === 'number') {
                            requestAnimationFrame(() => {
                                elementToFocus.setSelectionRange(focusInfo.selectionStart, focusInfo.selectionEnd);
                            });
                        }
                    }
                }, 0);
            }
        }
    })
        .catch((err) => console.error("Impulse error:", err));
}
function getComponentIds(componentsOption) {
    if (componentsOption) {
        return Array.isArray(componentsOption) ? componentsOption : [componentsOption];
    }
    return Array.from(document.querySelectorAll('[data-impulse-id]'))
        .map(el => el.getAttribute('data-impulse-id'))
        .filter(Boolean);
}
export function emit(event_1) {
    return __awaiter(this, arguments, void 0, function* (event, payload = {}, options = {}) {
        const components = getComponentIds(options.components);
        // Par défaut, envoie vers impulse.php (adaptable si besoin)
        const response = yield fetch('/impulse.php', {
            method: 'POST',
            headers: Object.assign({ 'Content-Type': 'application/json' }, (options.headers || {})),
            body: JSON.stringify(Object.assign({ emit: event, payload: payload, components: components }, options.extra // Permet d'ajouter ce que l'on veut
            )),
            credentials: 'same-origin'
        });
        let result = null;
        const rawText = yield response.text();
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
                result.updates.forEach((update) => {
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
        }
        catch (e) {
            result = rawText;
        }
        if (typeof options.callback === 'function') {
            options.callback(result);
        }
        return result;
    });
}
//# sourceMappingURL=ajax.js.map