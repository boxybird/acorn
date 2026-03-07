import type { Appearance, ResolvedAppearance } from '@/types';

export type { Appearance, ResolvedAppearance };

export type ThemeState = {
    appearance: {
        value: Appearance;
    };
    resolvedAppearance: () => ResolvedAppearance;
    updateAppearance: (value: Appearance) => void;
};

const appearance = $state<{ value: Appearance }>({ value: 'light' });

export function initializeTheme(): () => void {
    return () => {};
}

export function updateAppearance(value: Appearance): void {
    appearance.value = value;
}

export function themeState(): ThemeState {
    return {
        appearance,
        resolvedAppearance: () => 'light',
        updateAppearance,
    };
}
