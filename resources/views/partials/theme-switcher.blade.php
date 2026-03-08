@php
    $floating = $floating ?? false;
    $palette = [
        ['key' => 'cyan', 'label' => 'Cyan', 'swatch' => '#1497c7'],
        ['key' => 'sky', 'label' => 'Sky', 'swatch' => '#1698db'],
        ['key' => 'teal', 'label' => 'Teal', 'swatch' => '#139c92'],
        ['key' => 'mint', 'label' => 'Mint', 'swatch' => '#11b983'],
        ['key' => 'green', 'label' => 'Green', 'swatch' => '#1fae52'],
        ['key' => 'lime', 'label' => 'Lime', 'swatch' => '#8acc18'],
        ['key' => 'yellow', 'label' => 'Yellow', 'swatch' => '#e7b40d'],
        ['key' => 'orange', 'label' => 'Orange', 'swatch' => '#f1721c'],
        ['key' => 'coral', 'label' => 'Coral', 'swatch' => '#fb8a3f'],
        ['key' => 'red', 'label' => 'Red', 'swatch' => '#e14141'],
        ['key' => 'rose', 'label' => 'Rose', 'swatch' => '#eb4264'],
        ['key' => 'pink', 'label' => 'Pink', 'swatch' => '#ec4f9d'],
        ['key' => 'magenta', 'label' => 'Magenta', 'swatch' => '#cf31df'],
        ['key' => 'blue', 'label' => 'Blue', 'swatch' => '#3367e6'],
        ['key' => 'indigo', 'label' => 'Indigo', 'swatch' => '#5a56ec'],
        ['key' => 'violet', 'label' => 'Violet', 'swatch' => '#7d45ec'],
    ];
@endphp

<div class="theme-switcher {{ $floating ? 'theme-switcher-floating' : '' }}" data-theme-switcher>
    <button
        type="button"
        class="theme-switcher-trigger"
        data-theme-panel-toggle
        aria-expanded="false"
        aria-label="Open appearance settings"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317a1.724 1.724 0 013.35 0 1.724 1.724 0 002.573 1.066 1.724 1.724 0 012.9 1.675 1.724 1.724 0 001.066 2.573 1.724 1.724 0 010 3.35 1.724 1.724 0 00-1.066 2.573 1.724 1.724 0 01-2.9 1.675 1.724 1.724 0 00-2.573 1.066 1.724 1.724 0 01-3.35 0 1.724 1.724 0 00-2.573-1.066 1.724 1.724 0 01-2.9-1.675 1.724 1.724 0 00-1.066-2.573 1.724 1.724 0 010-3.35 1.724 1.724 0 001.066-2.573 1.724 1.724 0 012.9-1.675 1.724 1.724 0 002.573-1.066z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
    </button>

    <div class="theme-switcher-panel hidden" data-theme-panel>
        <div>
            <p class="theme-switcher-title">Appearance</p>
            <p class="theme-switcher-subtitle">Choose mode and accent for the full dashboard.</p>
        </div>

        <div class="theme-switcher-section">
            <p class="theme-switcher-label">Mode</p>
            <div class="theme-mode-grid">
                <button type="button" class="theme-mode-button" data-theme-mode-target="light" aria-pressed="false">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25M12 18.75V21M4.97 4.97l1.59 1.59M17.44 17.44l1.59 1.59M3 12h2.25M18.75 12H21M4.97 19.03l1.59-1.59M17.44 6.56l1.59-1.59" />
                        <circle cx="12" cy="12" r="4" />
                    </svg>
                    <span>Light</span>
                </button>
                <button type="button" class="theme-mode-button" data-theme-mode-target="dark" aria-pressed="false">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3c0 .2-.01.4-.01.6A7.2 7.2 0 0018.4 10.8c.2 0 .4 0 .6-.01z" />
                    </svg>
                    <span>Dark</span>
                </button>
            </div>
        </div>

        <div class="theme-switcher-section">
            <p class="theme-switcher-label">Colors</p>
            <div class="theme-color-grid">
                @foreach ($palette as $item)
                    <button
                        type="button"
                        class="theme-color-button"
                        data-theme-accent-target="{{ $item['key'] }}"
                        aria-label="{{ $item['label'] }} theme"
                        aria-pressed="false"
                        style="--swatch: {{ $item['swatch'] }}"
                    ></button>
                @endforeach
            </div>
        </div>

        <div class="theme-preview-card">
            <p class="theme-preview-kicker">Live Preview</p>
            <p class="theme-preview-title">Your dashboard updates instantly</p>
            <p class="theme-preview-copy">Saved in your browser, so the same mode and color stay active across pages.</p>
        </div>
    </div>
</div>