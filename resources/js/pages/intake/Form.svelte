<script lang="ts">
    import { onMount } from 'svelte';
    import { page } from '@inertiajs/svelte';
    import FormRenderer from '@/components/intake/FormRenderer.svelte';
    import IntakeSidebar from '@/components/intake/IntakeSidebar.svelte';
    import IntakeBottomNav from '@/components/intake/IntakeBottomNav.svelte';
    import IntakeHeader from '@/components/intake/IntakeHeader.svelte';
    import { save, complete } from '@/routes/intake/form';
    import { dashboard } from '@/routes/intake';

    type FormItem = {
        key: string;
        title: string;
        sections: { key: string; title: string }[];
        status: 'not_started' | 'in_progress' | 'completed';
    };

    type Progress = {
        completed: number;
        total: number;
    };

    let {
        schema,
        savedData,
        forms,
        progress,
    }: {
        schema: Record<string, any>;
        savedData: Record<string, any>;
        forms: FormItem[];
        progress: Progress;
    } = $props();

    let t = $derived($page.props.translations as Record<string, string>);

    const schemaKey = schema.key as string;
    const totalSections = (schema.sections ?? []).length;

    let currentSectionIndex = $state(0);
    let progressPercent = $derived(
        progress.total > 0 ? Math.round((progress.completed / progress.total) * 100) : 0,
    );

    let formRenderer: FormRenderer;

    let mounted = $state(false);
    onMount(() => { mounted = true; });
</script>

<div class="flex min-h-screen flex-col bg-background">
    <!-- Global Header -->
    <IntakeHeader
        breadcrumbs={[
            { label: t.dashboard, href: dashboard.url() },
            { label: schema.title },
        ]}
    />

    <div class="flex flex-1">
        <!-- Desktop Sidebar -->
        <div class="hidden lg:block">
            <IntakeSidebar
                {forms}
                {progress}
                activeFormKey={schemaKey}
                activeSectionIndex={currentSectionIndex}
                onSectionClick={(index) => formRenderer?.navigateToSection(index)}
            />
        </div>

        <!-- Main Content -->
        <div class="flex min-h-0 flex-1 flex-col bg-background" style="background-image: url('/texture.svg'); background-size: 200px 200px;">
            <!-- Form Content -->
            <main class="float-up mx-auto w-full max-w-2xl flex-1 px-4 py-8 pb-24 sm:px-6 lg:px-8 lg:pb-8" class:visible={mounted}>
                <FormRenderer
                    bind:this={formRenderer}
                    {schema}
                    {savedData}
                    saveUrl={save.url(schemaKey)}
                    completeUrl={complete.url(schemaKey)}
                    dashboardUrl={dashboard.url()}
                    bind:currentSectionIndex
                    onSectionChange={(index) => { currentSectionIndex = index; }}
                />
            </main>
        </div>
    </div>

    <!-- Mobile Bottom Nav -->
    <IntakeBottomNav
        currentStep={currentSectionIndex + 1}
        totalSteps={totalSections}
        {progressPercent}
        isLastSection={currentSectionIndex === totalSections - 1}
        onPrevious={() => formRenderer?.handlePrevious()}
        onNext={() => formRenderer?.handleNext()}
        onComplete={() => formRenderer?.handleComplete()}
    />
</div>
