import './bootstrap';

const DEFAULT_THEME = {
	mode: 'light',
	accent: 'magenta',
};
const ALLOWED_MODES = new Set(['light', 'dark']);
const ALLOWED_ACCENTS = new Set([
	'cyan',
	'sky',
	'teal',
	'mint',
	'green',
	'lime',
	'yellow',
	'orange',
	'coral',
	'red',
	'rose',
	'pink',
	'magenta',
	'blue',
	'indigo',
	'violet',
]);

let currentTheme = { ...DEFAULT_THEME };

const themeStorageKey = (name) => {
	const scope = document.body?.dataset.themeUser || 'global';
	return `${name}:${scope}`;
};

const normalizeMode = (mode) => (ALLOWED_MODES.has(mode) ? mode : DEFAULT_THEME.mode);
const normalizeAccent = (accent) => (ALLOWED_ACCENTS.has(accent) ? accent : DEFAULT_THEME.accent);

const readStoredTheme = () => ({
	mode: normalizeMode(window.localStorage.getItem(themeStorageKey('themeMode'))),
	accent: normalizeAccent(window.localStorage.getItem(themeStorageKey('themeAccent'))),
});

const syncThemeControls = () => {
	document.querySelectorAll('[data-theme-mode-target]').forEach((button) => {
		const isActive = button.dataset.themeModeTarget === currentTheme.mode;
		button.classList.toggle('is-active', isActive);
		button.setAttribute('aria-pressed', String(isActive));
	});

	document.querySelectorAll('[data-theme-accent-target]').forEach((button) => {
		const isActive = button.dataset.themeAccentTarget === currentTheme.accent;
		button.classList.toggle('is-active', isActive);
		button.setAttribute('aria-pressed', String(isActive));
	});
};

const applyTheme = (theme) => {
	currentTheme = {
		mode: normalizeMode(theme.mode),
		accent: normalizeAccent(theme.accent),
	};

	document.documentElement.dataset.themeMode = currentTheme.mode;
	document.documentElement.dataset.themeAccent = currentTheme.accent;

	if (document.body) {
		document.body.dataset.themeMode = currentTheme.mode;
		document.body.dataset.themeAccent = currentTheme.accent;
		document.body.classList.add('theme-enabled');
	}

	syncThemeControls();
};

const closeThemePanels = (exceptPanel = null) => {
	document.querySelectorAll('[data-theme-panel]').forEach((panel) => {
		const shouldStayOpen = exceptPanel && panel === exceptPanel;
		panel.classList.toggle('hidden', !shouldStayOpen);

		const trigger = panel.closest('[data-theme-switcher]')?.querySelector('[data-theme-panel-toggle]');
		if (trigger) {
			trigger.setAttribute('aria-expanded', String(shouldStayOpen));
		}
	});
};

const persistTheme = (theme) => {
	const nextTheme = {
		mode: normalizeMode(theme.mode ?? currentTheme.mode),
		accent: normalizeAccent(theme.accent ?? currentTheme.accent),
	};

	window.localStorage.setItem(themeStorageKey('themeMode'), nextTheme.mode);
	window.localStorage.setItem(themeStorageKey('themeAccent'), nextTheme.accent);
	applyTheme(nextTheme);
};

const handleThemeClick = (event) => {
	const modeButton = event.target.closest('[data-theme-mode-target]');
	if (modeButton) {
		persistTheme({ mode: modeButton.dataset.themeModeTarget });
		return;
	}

	const accentButton = event.target.closest('[data-theme-accent-target]');
	if (accentButton) {
		persistTheme({ accent: accentButton.dataset.themeAccentTarget });
		return;
	}

	const toggleButton = event.target.closest('[data-theme-panel-toggle]');
	if (toggleButton) {
		const switcher = toggleButton.closest('[data-theme-switcher]');
		const panel = switcher?.querySelector('[data-theme-panel]');

		if (!panel) {
			return;
		}

		const willOpen = panel.classList.contains('hidden');
		closeThemePanels(willOpen ? panel : null);
		return;
	}

	if (!event.target.closest('[data-theme-switcher]')) {
		closeThemePanels();
	}
};

const bootTheme = () => {
	if (!document.body?.classList.contains('theme-enabled')) {
		return;
	}

	applyTheme(readStoredTheme());
};

document.addEventListener('click', handleThemeClick);
window.addEventListener('storage', (event) => {
	if (event.key?.startsWith('themeMode:') || event.key?.startsWith('themeAccent:')) {
		applyTheme(readStoredTheme());
	}
});

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', bootTheme, { once: true });
} else {
	bootTheme();
}

document.addEventListener('livewire:navigated', () => {
	bootTheme();
});
