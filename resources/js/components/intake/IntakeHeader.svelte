<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import AppLogoIcon from '@/components/AppLogoIcon.svelte';
    import LocaleToggle from '@/components/intake/LocaleToggle.svelte';

    type Breadcrumb = {
        label: Record<string, string>;
        href?: string;
    };

    let {
        locale = 'en',
        progress,
        breadcrumbs = [],
    }: {
        locale?: string;
        progress: { completed: number; total: number };
        breadcrumbs?: Breadcrumb[];
    } = $props();

    const t = {
        of: { en: 'of', es: 'de' },
        complete: { en: 'complete', es: 'completos' },
    } as const;

    const circumference = 2 * Math.PI * 8;
    let progressPercent = $derived(
        progress.total > 0 ? Math.round((progress.completed / progress.total) * 100) : 0,
    );
    let strokeDashoffset = $derived(circumference - (progressPercent / 100) * circumference);
</script>

<header class="sticky top-0 z-40 border-b bg-background">
    <div class="flex h-14 items-center justify-between px-4 lg:px-6">
        <!-- Left: Logo + Breadcrumb -->
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2">
                <AppLogoIcon class="size-6" />
                <span class="text-sm font-bold text-foreground">Acorn</span>
            </div>

            {#if breadcrumbs.length > 0}
                <nav class="hidden items-center gap-1 text-sm sm:flex" aria-label="Breadcrumb">
                    {#each breadcrumbs as crumb, i (i)}
                        <span class="text-muted-foreground">/</span>
                        {#if crumb.href}
                            <Link
                                href={crumb.href}
                                class="text-muted-foreground transition-colors hover:text-foreground"
                            >
                                {crumb.label[locale]}
                            </Link>
                        {:else}
                            <span class="font-medium text-foreground">{crumb.label[locale]}</span>
                        {/if}
                    {/each}
                </nav>
            {/if}
        </div>

        <!-- Right: Progress + Locale -->
        <div class="flex items-center gap-4">
            <div class="flex items-center gap-2">
                <svg class="size-5 -rotate-90" viewBox="0 0 20 20">
                    <circle cx="10" cy="10" r="8" fill="none" stroke="currentColor" stroke-width="2" class="text-border" />
                    <circle
                        cx="10" cy="10" r="8" fill="none" stroke="currentColor" stroke-width="2"
                        class="text-primary transition-all duration-500"
                        stroke-dasharray={circumference}
                        stroke-dashoffset={strokeDashoffset}
                        stroke-linecap="round"
                    />
                </svg>
                <span class="hidden text-xs text-muted-foreground sm:inline">
                    {progress.completed} {t.of[locale]} {progress.total} {t.complete[locale]}
                </span>
            </div>
            <LocaleToggle {locale} />
        </div>
    </div>
</header>
