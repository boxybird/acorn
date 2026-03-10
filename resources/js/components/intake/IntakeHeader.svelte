<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import AppLogoIcon from '@/components/AppLogoIcon.svelte';
    import LocaleToggle from '@/components/intake/LocaleToggle.svelte';

    type Breadcrumb = {
        label: string;
        href?: string;
    };

    let {
        breadcrumbs = [],
    }: {
        breadcrumbs?: Breadcrumb[];
    } = $props();

    let t = $derived($page.props.translations as Record<string, string>);
    let locale = $derived($page.props.locale as string);
</script>

<header class="sticky top-0 z-40 border-b bg-background">
    <div class="flex h-14 items-center justify-between px-4 lg:px-6">
        <!-- Left: Logo + Breadcrumb -->
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2">
                <AppLogoIcon class="size-6" />
                <span class="text-sm leading-none font-bold text-foreground">Acorn</span>
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
                                {crumb.label}
                            </Link>
                        {:else}
                            <span class="font-medium text-foreground">{crumb.label}</span>
                        {/if}
                    {/each}
                </nav>
            {/if}
        </div>

        <!-- Right: Locale -->
        <div class="flex items-center gap-4">
            <LocaleToggle {locale} />
        </div>
    </div>
</header>
