<div>
    <input type="text"
           name="name"
           impulse:input="setName"
           impulse:update="preview"
           placeholder="Votre prénom..." />

    <p>Bonjour, <strong data-impulse-update="preview">{{ $name }}</strong>, tu es sous Blade !</p>

    <!-- Bouton pour vider le champ -->
    <button type="button" impulse:click="clearName" impulse:update="preview">
        Effacer
    </button>
</div>
