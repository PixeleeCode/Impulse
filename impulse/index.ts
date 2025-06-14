import { emit } from './src/core/ajax';
import { initImpulse } from "./src/core/events";

declare global {
  interface Window {
    Impulse: {
      emit: typeof emit;
    };
  }
}

(function() {
  const Impulse = {
    emit: emit
  };

  if (typeof window !== 'undefined') {
    (window as any).Impulse = Impulse;

    setTimeout(() => {
      if (typeof document !== 'undefined') {
        document.dispatchEvent(new CustomEvent('impulse:ready', {
          detail: { Impulse: (window as any).Impulse }
        }));
      }
    }, 0);
  }

  if (typeof module !== 'undefined' && module.exports) {
    module.exports = { Impulse };
  }
})();

function initialize() {
  initImpulse();
  setTimeout(() => {
    initImpulse();
  }, 100);
}

if (typeof document !== 'undefined') {
  if (document.readyState === 'loading') {
    document.addEventListener("DOMContentLoaded", initialize);
  } else {
    initialize();
  }
}
