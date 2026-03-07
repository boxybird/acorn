<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import AppLogoAnimated from '@/components/AppLogoAnimated.svelte';
    import { toUrl } from '@/lib/utils';
    import { dashboard, login } from '@/routes';
    import { landing } from '@/routes/intake';

    const auth = $derived($page.props.auth);

    let textVisible = $state(false);
    let ctaVisible = $state(false);

    function onLogoComplete() {
        textVisible = true;
        setTimeout(() => { ctaVisible = true; }, 600);
    }
</script>

<AppHead title="Welcome" />

<div class="flex min-h-screen flex-col items-center justify-center bg-background">
    <header class="absolute top-0 right-0 p-6">
        <nav class="flex items-center gap-4">
            {#if auth.user}
                <Link
                    href={toUrl(dashboard())}
                    class="text-sm text-muted-foreground transition-colors hover:text-foreground"
                >
                    Dashboard
                </Link>
            {:else}
                <Link
                    href={toUrl(login())}
                    class="text-sm text-muted-foreground transition-colors hover:text-foreground"
                >
                    Staff Login
                </Link>
            {/if}
        </nav>
    </header>

    <div class="flex flex-col items-center gap-6">
        <div class="size-40 sm:size-48 md:size-56 drop-shadow-2xl">
            <AppLogoAnimated class="size-full" onanimationend={onLogoComplete} />
        </div>

        <div
            class="flex flex-col items-center gap-3 transition-all duration-700 ease-out"
            class:opacity-0={!textVisible}
            class:translate-y-4={!textVisible}
            class:opacity-100={textVisible}
            class:translate-y-0={textVisible}
        >
            <h1 class="text-4xl font-bold tracking-tight text-foreground sm:text-5xl">Acorn</h1>
            <p class="text-lg text-muted-foreground">
                JumpStart Autism Collective
            </p>
        </div>

        <div
            class="mt-4 flex flex-col items-center gap-3 transition-all duration-700 ease-out"
            class:opacity-0={!ctaVisible}
            class:translate-y-4={!ctaVisible}
            class:opacity-100={ctaVisible}
            class:translate-y-0={ctaVisible}
        >
            <Link
                href={landing.url()}
                class="inline-flex h-11 items-center justify-center rounded-md bg-primary px-8 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90"
            >
                Begin Intake Forms
            </Link>
            <p class="text-sm text-muted-foreground">
                New families, start here
            </p>
        </div>
    </div>
</div>
