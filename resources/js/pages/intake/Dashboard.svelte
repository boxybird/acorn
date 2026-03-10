<script lang="ts">
    import { onMount } from 'svelte';
    import { page, router } from '@inertiajs/svelte';
    import IntakeHeader from '@/components/intake/IntakeHeader.svelte';
    import IntakeCard from '@/components/intake/IntakeCard.svelte';
    import { newMethod as create } from '@/routes/intake/select';

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

    type IntakeData = {
        id: number;
        child_name: string | null;
        status: string;
        is_current: boolean;
        forms: FormItem[];
        progress: { completed: number; total: number };
        time_estimate: number;
        flags: FlagItem[];
    };

    let { intakes }: {
        intakes: IntakeData[];
    } = $props();

    let t = $derived($page.props.translations as Record<string, string>);

    let mounted = $state(false);
    onMount(() => { mounted = true; });
</script>

<div class="flex min-h-screen flex-col bg-background" style="background-image: url('/texture.svg'); background-size: 200px 200px;">
    <IntakeHeader
        breadcrumbs={[
            { label: t.dashboard },
        ]}
    />

    <main class="mx-auto w-full max-w-2xl flex-1 p-4 py-8">
        <!-- Intake Cards -->
        <div class="space-y-3">
            {#each intakes as intake, i (intake.id)}
                <div class="float-up" class:visible={mounted} style="transition-delay: {i * 60}ms">
                    <IntakeCard
                        intakeId={intake.id}
                        childName={intake.child_name}
                        isCurrent={intake.is_current}
                        forms={intake.forms}
                        progress={intake.progress}
                        timeEstimate={intake.time_estimate}
                        flags={intake.flags}
                        index={i}
                        expanded={intakes.length === 1 || intake.is_current}
                    />
                </div>
            {/each}

            <!-- Add Child -->
            <div class="float-up" class:visible={mounted} style="transition-delay: {intakes.length * 60}ms">
                <button
                    class="flex w-full items-center justify-center rounded-lg border border-dashed border-border p-4 text-sm text-muted-foreground transition-all duration-200 hover:-translate-y-0.5 hover:border-primary/50 hover:text-primary"
                    onclick={() => router.post(create.url())}
                >
                    {t.add_child ?? 'Add another child'}
                </button>
            </div>
        </div>
    </main>
</div>
