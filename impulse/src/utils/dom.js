export function getUniqueSelector(element) {
    if (element.id) {
        return `#${element.id}`;
    }
    const attributes = ['name', 'impulse:input', 'impulse:change', 'placeholder', 'type'];
    for (const attr of attributes) {
        const value = element.getAttribute(attr);
        if (value) {
            return `[${attr}="${value}"]`;
        }
    }
    let selector = element.tagName.toLowerCase();
    if (element.parentElement) {
        const siblings = Array.from(element.parentElement.children);
        const index = siblings.indexOf(element);
        if (index > -1) {
            selector += `:nth-child(${index + 1})`;
        }
    }
    return selector;
}
//# sourceMappingURL=dom.js.map