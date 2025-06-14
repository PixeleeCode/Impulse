declare global {
  interface Window {
    initImpulse?: () => void;
    Impulse: {
      emit: (event: string, payload?: any, options?: ImpulseEmitOptions) => Promise<any>;
    };
  }
}

interface ImpulseEmitOptions {
  // Fonction appelée avec le résultat de la requête AJAX
  callback?: (result: any) => void;

  // Headers additionnels à envoyer dans la requête
  headers?: Record<string, string>;

  // Données supplémentaires à inclure dans la requête
  extra?: Record<string, any>;

  // Liste des IDs de composants ciblés (ou un seul ID). Si non fourni, cible tous les composants du DOM
  components?: string | string[];
}

export {};
