<script lang="ts">
    import { onMount } from 'svelte';
    import { Link, page } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import AppLogoAnimated from '@/components/AppLogoAnimated.svelte';
    import AppLogoIcon from '@/components/AppLogoIcon.svelte';
    import { Button } from '@/components/ui/button';
    import { Separator } from '@/components/ui/separator';
    import { toUrl } from '@/lib/utils';
    import { index as staffIntakesIndex } from '@/actions/App/Http/Controllers/Staff/IntakeController';
    import { login } from '@/routes';
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
    <div class="flex min-h-screen w-full flex-col justify-center bg-card px-6 py-12 shadow-lg sm:px-12 lg:min-h-0 lg:w-[40%] lg:px-16 xl:px-24">
        <div class="mx-auto w-full max-w-md space-y-8">
            <!-- Logo visible on mobile -->
            <div class="flex justify-center lg:hidden">
                <div class="size-20 drop-shadow-2xl">
                    <AppLogoAnimated class="size-full" />
                </div>
            </div>

            <!-- Branding - stagger index 0 -->
            <div
                class="stagger-item space-y-2"
                style="transition-delay: 0ms;"
                class:visible={contentVisible}
            >
                <div class="flex items-center gap-3">
                    <AppLogoIcon class="size-10" />
                    <h1 class="text-2xl leading-none font-bold tracking-tight text-foreground">Acorn</h1>
                </div>
                <p class="text-sm text-muted-foreground">
                    JumpStart Autism Collective
                </p>
            </div>

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
                            href={toUrl(staffIntakesIndex())}
                            class="text-sm text-muted-foreground transition-colors hover:text-foreground"
                        >
                            Go to Intakes
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
    <div class="relative hidden flex-1 flex-col items-center justify-center overflow-hidden bg-background lg:flex" style="background-image: url('/texture.svg'); background-size: 200px 200px;">
        <div class="flex flex-col items-center gap-10 px-12">
            <div class="size-48 xl:size-56 drop-shadow-2xl">
                <AppLogoAnimated class="size-full" />
            </div>

            <div class="flex max-w-sm flex-col items-center gap-6 text-center">
                <h2
                    class="stagger-item text-2xl text-balance font-bold tracking-tight text-foreground xl:text-3xl"
                    style="transition-delay: 0ms;"
                    class:visible={desktopTextVisible}
                >
                    Personalized care starts here.
                </h2>
                <p
                    class="stagger-item text-sm text-balance leading-relaxed text-muted-foreground"
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
