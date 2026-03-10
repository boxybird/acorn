<script lang="ts">
    import { onMount } from 'svelte';
    import { Link, page } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import AppLogoAnimated from '@/components/AppLogoAnimated.svelte';
    import { Button } from '@/components/ui/button';
    import { Separator } from '@/components/ui/separator';
    import { toUrl } from '@/lib/utils';
    import { dashboard, login } from '@/routes';
    import { landing } from '@/routes/intake';

    const auth = $derived($page.props.auth);

    let contentVisible = $state(false);
    let desktopTextVisible = $state(false);

    onMount(() => {
        setTimeout(() => { contentVisible = true; }, 300);
        setTimeout(() => { desktopTextVisible = true; }, 500);
    });
</script>

<AppHead title="Welcome" />

<div class="flex min-h-screen flex-col lg:flex-row">
    <!-- Left Panel: Branding & CTA -->
    <div class="flex min-h-screen w-full flex-col justify-center px-6 py-12 sm:px-12 lg:min-h-0 lg:w-[40%] lg:px-16 xl:px-24">
        <div class="mx-auto w-full max-w-md space-y-8">
            <!-- Logo visible on mobile -->
            <div class="flex justify-center lg:hidden">
                <div class="size-20 drop-shadow-2xl">
                    <AppLogoAnimated class="size-full" />
                </div>
            </div>

            <!-- Branding - stagger index 0 -->
            <div
                class="stagger-item space-y-3"
                style="transition-delay: 0ms;"
                class:visible={contentVisible}
            >
                <h1 class="text-4xl font-bold tracking-tight text-foreground sm:text-5xl">Acorn</h1>
            </div>

            <!-- Subtitle - stagger index 1 -->
            <p
                class="stagger-item text-lg text-muted-foreground"
                style="transition-delay: 100ms;"
                class:visible={contentVisible}
            >
                JumpStart Autism Collective
            </p>

            <!-- Separator - stagger index 2 -->
            <div
                class="stagger-item"
                style="transition-delay: 200ms;"
                class:visible={contentVisible}
            >
                <Separator />
            </div>

            <!-- Heading - stagger index 3 -->
            <div
                class="stagger-item space-y-2"
                style="transition-delay: 300ms;"
                class:visible={contentVisible}
            >
                <h2 class="text-xl font-semibold text-foreground">New Families</h2>
                <p class="text-sm text-muted-foreground">
                    Begin your intake paperwork online. It's simple, secure, and can be completed at your own pace.
                </p>
            </div>

            <!-- CTA Button - stagger index 4 -->
            <div
                class="stagger-item"
                style="transition-delay: 400ms;"
                class:visible={contentVisible}
            >
                <Button asChild size="lg" class="w-full">
                    {#snippet children(props)}
                        <Link href={landing.url()} {...props}>
                            Begin Intake Forms
                        </Link>
                    {/snippet}
                </Button>
            </div>

            <!-- Staff link - stagger index 5 -->
            <div
                class="stagger-item"
                style="transition-delay: 500ms;"
                class:visible={contentVisible}
            >
                <Separator class="mb-6" />
                <div class="flex justify-center">
                    {#if auth.user}
                        <Link
                            href={toUrl(dashboard())}
                            class="text-sm text-muted-foreground transition-colors hover:text-foreground"
                        >
                            Go to Dashboard
                        </Link>
                    {:else}
                        <Link
                            href={toUrl(login())}
                            class="text-sm text-muted-foreground transition-colors hover:text-foreground"
                        >
                            Staff Login
                        </Link>
                    {/if}
                </div>
            </div>
        </div>
    </div>

    <!-- Right Panel: Animated Logo & Design -->
    <div class="relative hidden flex-1 flex-col items-center justify-center overflow-hidden bg-primary/5 lg:flex" style="background-image: url('/texture.svg'); background-size: 200px 200px;">
        <div class="absolute -top-24 -right-24 size-96 rounded-full bg-accent/20"></div>
        <div class="absolute -bottom-32 -left-32 size-[28rem] rounded-full bg-primary/10"></div>

        <div class="relative z-10 flex flex-col items-center gap-8 px-12">
            <div class="size-56 xl:size-64 drop-shadow-2xl">
                <AppLogoAnimated class="size-full" />
            </div>

            <div class="flex max-w-xs flex-col items-center gap-3 text-center">
                <p
                    class="stagger-item text-lg font-medium text-foreground"
                    style="transition-delay: 0ms;"
                    class:visible={desktopTextVisible}
                >
                    Personalized care starts here.
                </p>
                <p
                    class="stagger-item text-sm text-muted-foreground"
                    style="transition-delay: 100ms;"
                    class:visible={desktopTextVisible}
                >
                    Supporting families through every step of the autism care journey.
                </p>
            </div>
        </div>
    </div>
</div>

<style>
    .stagger-item {
        opacity: 0;
        transform: translateY(12px);
        transition: opacity 400ms ease-out, transform 400ms ease-out;
    }
    .stagger-item.visible {
        opacity: 1;
        transform: translateY(0);
    }
</style>
