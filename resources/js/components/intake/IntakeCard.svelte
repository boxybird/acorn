<script lang="ts">
    import { router, page } from '@inertiajs/svelte';
    import { Alert, AlertTitle, AlertDescription } from '@/components/ui/alert';
    import { Button } from '@/components/ui/button';
    import { Card, CardContent } from '@/components/ui/card';
    import { show } from '@/routes/intake/form';
    import { choose } from '@/routes/intake/select';

    type FormItem = {
        key: string;
        title: string;
        icon: string | null;
        estimated_minutes: number | null;
        status: 'not_started' | 'in_progress' | 'completed';
    };

    type FlagItem = {
        id: number;
        reason: string;
        form_response: {
            id: number;
            schema_key: string;
        } | null;
    };

    let {
        intakeId,
        childName,
        isCurrent,
        forms,
        progress,
        timeEstimate,
        flags,
        index,
        expanded = false,
    }: {
        intakeId: number;
        childName: string | null;
        isCurrent: boolean;
        forms: FormItem[];
        progress: { completed: number; total: number };
        timeEstimate: number;
        flags: FlagItem[];
        index: number;
        expanded?: boolean;
    } = $props();

    let t = $derived($page.props.translations as Record<string, string>);

    let isExpanded = $state(expanded);

    let progressPercent = $derived(
        progress.total > 0 ? Math.round((progress.completed / progress.total) * 100) : 0,
    );

    let allCompleted = $derived(progressPercent === 100);

    let nextFormKey = $derived(
        forms.find(f => f.status === 'in_progress')?.key
        ?? forms.find(f => f.status === 'not_started')?.key,
    );

    let label = $derived(childName ?? `Child #${index + 1}`);

    function navigateToForm(schemaKey: string): void {
        if (isCurrent) {
            router.visit(show.url(schemaKey));
        } else {
            router.post(choose.url(intakeId), {}, {
                onSuccess: () => router.visit(show.url(schemaKey)),
            });
        }
    }

    function continueIntake(): void {
        if (nextFormKey) {
            navigateToForm(nextFormKey);
        }
    }

</script>

<Card class="overflow-hidden transition-all duration-200">
    <!-- Collapsed Header (always visible) -->
    <button
        class="flex w-full items-center justify-between p-4 text-left transition-colors hover:bg-muted/50"
        aria-expanded={isExpanded}
        onclick={() => isExpanded = !isExpanded}
    >
        <div class="flex items-center gap-3">
            <h3 class="font-semibold text-foreground">{label}</h3>
            {#if allCompleted}
                <span class="text-xs font-medium text-primary">{t.complete ?? 'Complete'}</span>
            {:else}
                <span class="text-xs text-muted-foreground">
                    {progress.completed}/{progress.total} {t.forms ?? 'forms'}
                </span>
            {/if}
        </div>
        <div class="flex items-center gap-3">
            <div class="h-1.5 w-24 overflow-hidden rounded-full bg-muted">
                <div
                    class="h-full rounded-full bg-primary transition-all duration-500"
                    style="width: {progressPercent}%"
                ></div>
            </div>
            <svg
                class="size-4 text-muted-foreground transition-transform duration-200"
                class:rotate-180={isExpanded}
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
        </div>
    </button>

    <!-- Expanded Body -->
    {#if isExpanded}
        <CardContent class="border-t px-4 pb-4 pt-3">
            {#if !allCompleted && timeEstimate > 0}
                <p class="mb-3 text-sm text-muted-foreground">
                    ~{timeEstimate} {t.min_remaining ?? 'minutes remaining'}
                </p>
            {/if}

            <!-- Flags -->
            {#if flags.length > 0}
                <Alert variant="destructive" class="mb-3">
                    <AlertTitle>{t.action_needed ?? 'Action needed'}</AlertTitle>
                    <AlertDescription>
                        <ul class="mt-1 list-inside list-disc space-y-1">
                            {#each flags as flag (flag.id)}
                                <li>
                                    {#if flag.form_response}
                                        <button
                                            class="font-medium underline underline-offset-2 hover:text-destructive/80"
                                            onclick={() => navigateToForm(flag.form_response.schema_key)}
                                        >
                                            {forms.find(f => f.key === flag.form_response?.schema_key)?.title ?? flag.form_response.schema_key}
                                        </button>
                                        {#if flag.reason}
                                            &mdash; {flag.reason}
                                        {/if}
                                    {:else}
                                        {flag.reason}
                                    {/if}
                                </li>
                            {/each}
                        </ul>
                    </AlertDescription>
                </Alert>
            {/if}

            <!-- Form Checklist -->
            <div class="space-y-1">
                {#each forms as form (form.key)}
                    <button
                        class="flex w-full items-center gap-3 rounded-md px-3 py-2 text-left text-sm transition-colors hover:bg-muted/50"
                        onclick={() => navigateToForm(form.key)}
                    >
                        {#if form.status === 'completed'}
                            <svg class="size-4 shrink-0 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" /><polyline points="22 4 12 14.01 9 11.01" />
                            </svg>
                        {:else if form.status === 'in_progress'}
                            <svg class="size-4 shrink-0 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10" /><polyline points="12 6 12 12 16 14" />
                            </svg>
                        {:else}
                            <div class="size-4 shrink-0 rounded-full border-2 border-muted-foreground/30"></div>
                        {/if}
                        <span class="flex-1" class:text-muted-foreground={form.status === 'not_started'}>
                            {form.title}
                        </span>
                        {#if form.estimated_minutes && form.status !== 'completed'}
                            <span class="text-xs text-muted-foreground">~{form.estimated_minutes}m</span>
                        {/if}
                    </button>
                {/each}
            </div>

            <!-- Continue Button -->
            {#if !allCompleted && nextFormKey}
                <div class="mt-3">
                    <Button class="w-full" onclick={continueIntake}>
                        {t.continue ?? 'Continue'}
                    </Button>
                </div>
            {/if}

            <!-- Completed State -->
            {#if allCompleted}
                <div class="mt-3 flex items-center justify-center gap-2 rounded-md bg-accent py-3 text-sm text-primary">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    {t.all_done ?? 'All done!'}
                </div>
            {/if}
        </CardContent>
    {/if}
</Card>
