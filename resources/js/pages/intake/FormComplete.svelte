<script lang="ts">
    import { onMount } from 'svelte';
    import { Link, page } from '@inertiajs/svelte';
    import { Button } from '@/components/ui/button';
    import IntakeHeader from '@/components/intake/IntakeHeader.svelte';
    import { show } from '@/routes/intake/form';
    import { dashboard } from '@/routes/intake';

    let {
        completedForm,
        nextForm,
        progress,
    }: {
        completedForm: { key: string; title: string };
        nextForm: { key: string; title: string } | null;
        progress: { completed: number; total: number };
    } = $props();

    let t = $derived($page.props.translations as Record<string, string>);

    const remaining = progress.total - progress.completed;
    const allDone = remaining === 0;

    let visible = $state(false);
    let checkVisible = $state(false);

    onMount(() => {
        setTimeout(() => { checkVisible = true; }, 100);
        setTimeout(() => { visible = true; }, 400);
    });
</script>

<div class="flex min-h-screen flex-col bg-background">
    <IntakeHeader
        {progress}
        breadcrumbs={[
            { label: t.dashboard, href: dashboard.url() },
            { label: completedForm.title },
        ]}
    />

    <div class="flex flex-1 flex-col items-center justify-center px-6">
        <div class="w-full max-w-md space-y-8 text-center">
            <!-- Animated checkmark -->
            <div class="flex justify-center">
                <div
                    class="flex size-20 items-center justify-center rounded-full bg-primary/10 transition-all duration-500"
                    class:scale-100={checkVisible}
                    class:scale-0={!checkVisible}
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="size-10 text-primary"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                    >
                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                    </svg>
                </div>
            </div>

            <!-- Content -->
            <div
                class="space-y-3 transition-all duration-500"
                class:opacity-0={!visible}
                class:translate-y-4={!visible}
                class:opacity-100={visible}
                class:translate-y-0={visible}
            >
                <h1 class="text-2xl font-bold text-foreground">
                    {completedForm.title}
                </h1>
                <p class="text-lg text-primary">{t.complete_exclaim}</p>

                {#if allDone}
                    <p class="text-sm text-muted-foreground">
                        {t.all_done_final}
                    </p>
                {:else}
                    <p class="text-sm text-muted-foreground">
                        {progress.completed} {t.of} {progress.total} {t.forms_complete} &mdash;
                        {remaining === 1 ? t.just_one_more : `${remaining} ${t.more_to_go}`}
                    </p>
                {/if}
            </div>

            <!-- Actions -->
            <div
                class="flex flex-col gap-3 transition-all duration-500 delay-100"
                class:opacity-0={!visible}
                class:translate-y-4={!visible}
                class:opacity-100={visible}
                class:translate-y-0={visible}
            >
                {#if allDone}
                    <Button asChild size="lg" class="w-full">
                        {#snippet children(props)}
                            <Link href={dashboard.url()} {...props}>
                                {t.back_to_dashboard}
                            </Link>
                        {/snippet}
                    </Button>
                {:else if nextForm}
                    <Button asChild size="lg" class="w-full">
                        {#snippet children(props)}
                            <Link href={show.url(nextForm.key)} {...props}>
                                {t.continue_to} {nextForm.title}
                            </Link>
                        {/snippet}
                    </Button>
                    <Button asChild variant="outline" size="lg" class="w-full">
                        {#snippet children(props)}
                            <Link href={dashboard.url()} {...props}>
                                {t.back_to_dashboard}
                            </Link>
                        {/snippet}
                    </Button>
                {:else}
                    <Button asChild size="lg" class="w-full">
                        {#snippet children(props)}
                            <Link href={dashboard.url()} {...props}>
                                {t.back_to_dashboard}
                            </Link>
                        {/snippet}
                    </Button>
                {/if}
            </div>
        </div>
    </div>
</div>
