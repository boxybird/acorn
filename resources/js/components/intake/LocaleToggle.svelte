<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import { setLocale, setLocaleGuest } from '@/routes/intake';

    let {
        locale = 'en',
        authenticated = true,
    }: {
        locale?: string;
        authenticated?: boolean;
    } = $props();

    function switchLocale(newLocale: string): void {
        if (newLocale === locale) {
            return;
        }

        const endpoint = authenticated ? setLocale : setLocaleGuest;

        router.post(endpoint.url(), { locale: newLocale }, {
            preserveScroll: true,
        });
    }
</script>

<div class="inline-flex items-center gap-1 rounded-full border px-2 py-1" role="radiogroup" aria-label="Language selector">
    <button
        type="button"
        role="radio"
        aria-checked={locale === 'en'}
        aria-label="Switch to English"
        class="cursor-pointer rounded-full px-2 py-0.5 text-xs font-medium transition-colors {locale === 'en' ? 'text-foreground' : 'text-muted-foreground hover:text-foreground'}"
        onclick={() => switchLocale('en')}
    >
        EN
    </button>
    <span class="text-xs text-muted-foreground">|</span>
    <button
        type="button"
        role="radio"
        aria-checked={locale === 'es'}
        aria-label="Cambiar a Español"
        class="cursor-pointer rounded-full px-2 py-0.5 text-xs font-medium transition-colors {locale === 'es' ? 'text-foreground' : 'text-muted-foreground hover:text-foreground'}"
        onclick={() => switchLocale('es')}
    >
        ES
    </button>
</div>
