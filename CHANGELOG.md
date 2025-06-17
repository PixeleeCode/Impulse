[Unreleased]  
✨ Nouveautés

Support natif des moteurs de template externes :
  * Compatibilité avec Twig (twig/twig) et Blade (illuminate/view)
  * Détection automatique via l’attribut #[Renderer(name: ..., bundle: ...)]

* Ajout de la commande renderer:configure :
  * Permet de choisir un moteur de rendu
  * Permet de définir le dossier des templates (ex: resources/views)
  * Installe automatiquement les dépendances si nécessaire

* Ajout de la commande make:renderer :
  * Génère une classe de moteur personnalisée avec l’attribut #[Renderer]

✅ Améliorations

* Refactorisation des composants pour permettre un view() dynamique selon le moteur sélectionné
* Les moteurs de template peuvent désormais être entièrement désactivés si souhaité (template() retourne du HTML)
* Documentation enrichie :
  * Ajout d’un guide sur les moteurs de templates
    * README mis à jour pour inclure le support des moteurs externes

🛡 Sécurité & Robustesse

* Avertissement automatique dans les logs si une méthode décorée avec #[Action] n’est pas publique
