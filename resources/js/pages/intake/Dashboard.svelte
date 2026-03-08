<script lang="ts">
    import { onMount } from 'svelte';
    import { router } from '@inertiajs/svelte';
    import { Button } from '@/components/ui/button';
    import { Card, CardContent } from '@/components/ui/card';
    import IntakeHeader from '@/components/intake/IntakeHeader.svelte';
    import { show } from '@/routes/intake/form';
    import { choose, newMethod as create } from '@/routes/intake/select';

    type FormItem = {
        key: string;
        title: Record<string, string>;
        icon: string | null;
        estimated_minutes: number | null;
        status: 'not_started' | 'in_progress' | 'completed';
    };

    type Progress = {
        completed: number;
        total: number;
    };

    type IntakeCard = {
        id: number;
        child_name: string | null;
        status: string;
        completed_forms_count: number;
        is_current: boolean;
    };

    type IntakeContext = {
        id: number;
        child_name: string | null;
    };

    let { forms, progress, intake, allIntakes, timeEstimate, locale = 'en' }: {
        forms: FormItem[];
        progress: Progress;
        intake: IntakeContext;
        allIntakes: IntakeCard[];
        timeEstimate: number;
        locale?: string;
    } = $props();

    const t = {
        welcome: { en: 'Welcome!', es: '¡Bienvenido!' },
        welcomeDesc: {
            en: 'short forms at your own pace. Your progress saves automatically — come back anytime.',
            es: 'formularios cortos a su propio ritmo. Su progreso se guarda automáticamente — regrese cuando quiera.',
        },
        estimatedTime: { en: 'Estimated time:', es: 'Tiempo estimado:' },
        minutes: { en: 'minutes', es: 'minutos' },
        getStarted: { en: 'Get Started', es: 'Comenzar' },
        allDone: { en: 'All Done!', es: '¡Todo Listo!' },
        allDoneDesc: {
            en: "forms are complete. Your information is being processed — we'll be in touch soon.",
            es: 'formularios están completos. Su información está siendo procesada — nos pondremos en contacto pronto.',
        },
        intakeSuffix: { en: "'s Intake", es: ' — Admisión' },
        yourIntake: { en: 'Your Intake', es: 'Su Admisión' },
        pickUp: { en: 'Pick up where you left off.', es: 'Continúe donde lo dejó.' },
        of: { en: 'of', es: 'de' },
        formsComplete: { en: 'forms complete', es: 'formularios completos' },
        minRemaining: { en: 'min remaining', es: 'min restantes' },
        continue_: { en: 'Continue', es: 'Continuar' },
        yourChildren: { en: 'Your Children', es: 'Sus Hijos' },
        complete: { en: 'Complete', es: 'Completo' },
        switchTo: { en: 'Switch to', es: 'Cambiar a' },
        addChild: { en: '+ Add another child', es: '+ Agregar otro hijo' },
    } as const;

    let progressPercent = $derived(
        progress.total > 0 ? Math.round((progress.completed / progress.total) * 100) : 0,
    );

    let allNotStarted = $derived(forms.every(f => f.status === 'not_started'));
    let allCompleted = $derived(progressPercent === 100);

    let nextFormKey = $derived(
        forms.find(f => f.status === 'in_progress')?.key
        ?? forms.find(f => f.status === 'not_started')?.key,
    );

    function childLabel(intakeCard: IntakeCard, index: number): string {
        return intakeCard.child_name ?? `Child #${index + 1}`;
    }

    function childProgress(intakeCard: IntakeCard): number {
        return progress.total > 0
            ? Math.round((intakeCard.completed_forms_count / progress.total) * 100)
            : 0;
    }

    let mounted = $state(false);
    onMount(() => { mounted = true; });
</script>

<div class="flex min-h-screen flex-col bg-primary/5">
    <IntakeHeader
        {locale}
        {progress}
        breadcrumbs={[
            { label: { en: 'Dashboard', es: 'Panel' } },
        ]}
    />

    <main class="mx-auto w-full max-w-2xl flex-1 p-4 py-8">
        {#if allNotStarted}
            <div class="flex flex-col items-center space-y-6 py-8 text-center">
                <h1 class="float-up text-2xl font-bold text-foreground" class:visible={mounted}>{t.welcome[locale]}</h1>
                <p class="float-up max-w-md text-muted-foreground" class:visible={mounted} style="transition-delay: 60ms">
                    {locale === 'en' ? `Complete ${forms.length}` : `Complete ${forms.length}`} {t.welcomeDesc[locale]}
                </p>
                {#if timeEstimate > 0}
                    <p class="float-up text-sm text-muted-foreground" class:visible={mounted} style="transition-delay: 120ms">{t.estimatedTime[locale]} ~{timeEstimate} {t.minutes[locale]}</p>
                {/if}
                {#if nextFormKey}
                    <div class="pop-in" class:visible={mounted} style="transition-delay: 180ms">
                        <Button size="lg" onclick={() => router.visit(show.url(nextFormKey))}>
                            {t.getStarted[locale]}
                        </Button>
                    </div>
                {/if}
            </div>
        {:else if allCompleted}
            <div class="flex flex-col items-center space-y-4 py-8 text-center">
                <div class="pop-in flex size-20 items-center justify-center rounded-full bg-primary/10" class:visible={mounted}>
                    <svg class="size-10 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h1 class="float-up text-2xl font-bold text-foreground" class:visible={mounted} style="transition-delay: 60ms">{t.allDone[locale]}</h1>
                <p class="float-up max-w-md text-muted-foreground" class:visible={mounted} style="transition-delay: 120ms">
                    {locale === 'en' ? `All ${progress.total}` : `Los ${progress.total}`} {t.allDoneDesc[locale]}
                </p>
            </div>
        {:else}
            <div class="space-y-6">
                <div class="float-up" class:visible={mounted}>
                    <h1 class="text-2xl font-bold text-foreground">
                        {#if intake.child_name}
                            {intake.child_name}{t.intakeSuffix[locale]}
                        {:else}
                            {t.yourIntake[locale]}
                        {/if}
                    </h1>
                    <p class="mt-1 text-muted-foreground">{t.pickUp[locale]}</p>
                </div>

                <Card class="pop-in {mounted ? 'visible' : ''}" style="transition-delay: 60ms">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div class="space-y-1">
                                <p class="text-3xl font-bold text-foreground">{progressPercent}%</p>
                                <p class="text-sm text-muted-foreground">
                                    {progress.completed} {t.of[locale]} {progress.total} {t.formsComplete[locale]}
                                </p>
                                {#if timeEstimate > 0}
                                    <p class="text-sm text-muted-foreground">~{timeEstimate} {t.minRemaining[locale]}</p>
                                {/if}
                            </div>
                            {#if nextFormKey}
                                <Button size="lg" onclick={() => router.visit(show.url(nextFormKey))}>
                                    {t.continue_[locale]}
                                </Button>
                            {/if}
                        </div>
                        <div class="mt-4 h-2 w-full overflow-hidden rounded-full bg-muted">
                            <div
                                class="h-full rounded-full bg-primary transition-all duration-500"
                                style="width: {progressPercent}%"
                            ></div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        {/if}

        <div class="mt-8 space-y-3">
            <h2 class="float-up text-sm font-medium text-muted-foreground" class:visible={mounted} style="transition-delay: 120ms">{t.yourChildren[locale]}</h2>
                <div class="grid gap-3 sm:grid-cols-2">
                    {#each allIntakes as intakeCard, i (intakeCard.id)}
                        <Card class="pop-in transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md {mounted ? 'visible' : ''} {intakeCard.is_current ? 'ring-2 ring-primary' : ''}" style="transition-delay: {180 + i * 60}ms">
                            <CardContent class="p-4">
                                <div class="flex items-center justify-between">
                                    <h3 class="font-semibold text-foreground">{childLabel(intakeCard, i)}</h3>
                                    {#if intakeCard.completed_forms_count === progress.total}
                                        <span class="text-xs font-medium text-primary">{t.complete[locale]}</span>
                                    {:else}
                                        <span class="text-xs text-muted-foreground">
                                            {intakeCard.completed_forms_count}/{progress.total}
                                        </span>
                                    {/if}
                                </div>
                                <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-muted">
                                    <div
                                        class="h-full rounded-full bg-primary transition-all duration-500"
                                        style="width: {childProgress(intakeCard)}%"
                                    ></div>
                                </div>
                                <div class="mt-3">
                                    {#if intakeCard.is_current}
                                        {#if nextFormKey}
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                class="w-full"
                                                onclick={() => router.visit(show.url(nextFormKey))}
                                            >
                                                {t.continue_[locale]}
                                            </Button>
                                        {/if}
                                    {:else}
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            class="w-full"
                                            onclick={() => router.post(choose.url(intakeCard.id))}
                                        >
                                            {t.switchTo[locale]} {childLabel(intakeCard, i)}
                                        </Button>
                                    {/if}
                                </div>
                            </CardContent>
                        </Card>
                    {/each}
                    <button
                        class="pop-in flex items-center justify-center rounded-lg border border-dashed border-border p-4 text-sm text-muted-foreground transition-all duration-200 hover:-translate-y-0.5 hover:border-primary/50 hover:text-primary"
                        class:visible={mounted}
                        style="transition-delay: {180 + allIntakes.length * 60}ms"
                        onclick={() => router.post(create.url())}
                    >
                        {t.addChild[locale]}
                    </button>
                </div>
        </div>
    </main>
</div>
