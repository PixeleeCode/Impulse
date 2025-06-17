<div class="multi-select-wrap">
    <label for="msel">What sounds tasty?</label>
    <div class="multi-select-box" tabindex="0" id="msel">
        <div class="multi-chips">
            @foreach ($selected as $idx => $opt)
            <span class="multi-chip">
                    {{ $opt['label'] }} {{ $opt['emoji'] }}
                    <button type="button" class="multi-remove" impulse:click="removeOption({{ $idx }})">&times;</button>
                </span>
            @endforeach
            <input type="text" class="multi-input" tabindex="0">
        </div>
        @php
            $selectedLabels = array_column($selected, 'label');
        @endphp
        <div class="multi-dropdown">
            @foreach ($options as $i => $opt)
                @if (!in_array($opt['label'], $selectedLabels, true))
                <div class="multi-option" impulse:click="addOption({{ $i }})">
                    {{ $opt['label'] }} {{ $opt['emoji'] ?? '' }}
                </div>
                @endif
            @endforeach
        </div>
    </div>
</div>

<style scoped>
    .multi-select-wrap { margin:20px 0; }
    .multi-select-box { border:2px solid #bad2f7; border-radius:9px; padding:5px 10px; min-height:42px; position:relative; background:#fff;}
    .multi-chips { display:flex; flex-wrap:wrap; align-items:center; gap:7px;}
    .multi-chip { background:#eef6fd; border-radius:7px; padding:2px 8px; display:inline-flex; align-items:center; }
    .multi-remove { margin-left:4px; border:none; background:none; color:#555; font-size:18px; cursor:pointer; }
    .multi-dropdown { display:none; position:absolute; left:0; right:0; top:100%; background:#fff; border:1px solid #cde0fa; border-radius:0 0 8px 8px; z-index:99; max-height:170px; overflow-y:auto;}
    .multi-select-box:focus-within .multi-dropdown { display:block; }
    .multi-option { padding:8px 16px; cursor:pointer;}
    .multi-option:hover { background:#edf6ff; }
    .multi-input {
        border: none;
        outline: none;
        flex: 1;
        min-width: 40px;
        background: transparent;
    }
</style>
