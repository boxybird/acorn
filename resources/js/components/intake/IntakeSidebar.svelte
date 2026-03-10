<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import { Separator } from '@/components/ui/separator';
    import { show } from '@/routes/intake/form';
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
        forms,
        progress,
        activeFormKey,
        activeSectionIndex = 0,
        onSectionClick,
    }: {
        forms: FormItem[];
        progress: Progress;
        activeFormKey: string;
        activeSectionIndex?: number;
        onSectionClick?: (index: number) => void;
    } = $props();

    let t = $derived($page.props.translations as Record<string, string>);

    let progressPercent = $derived(
        progress.total > 0 ? Math.round((progress.completed / progress.total) * 100) : 0,
    );

    const circumference = 2 * Math.PI * 18;
    let strokeDashoffset = $derived(circumference - (progressPercent / 100) * circumference);
</script>

<aside class="sticky top-14 flex h-[calc(100vh-3.5rem)] w-[280px] shrink-0 flex-col border-r bg-sidebar">
    <!-- Progress Ring -->
    <div class="flex items-center gap-3 px-5 py-4">
        <svg class="size-10 -rotate-90" viewBox="0 0 40 40">
            <circle cx="20" cy="20" r="18" fill="none" stroke="currentColor" stroke-width="3" class="text-border" />
            <circle
                cx="20" cy="20" r="18" fill="none" stroke="currentColor" stroke-width="3"
                class="text-primary transition-all duration-500"
                stroke-dasharray={circumference}
                stroke-dashoffset={strokeDashoffset}
                stroke-linecap="round"
            />
        </svg>
        <div>
            <p class="text-sm font-medium text-foreground">{progress.completed} {t.of} {progress.total}</p>
            <p class="text-xs text-muted-foreground">{t.forms_complete}</p>
        </div>
    </div>

    <Separator />

    <!-- Form List -->
    <nav class="flex-1 overflow-y-auto px-3 py-3">
        <ul class="space-y-1">
            {#each forms as form (form.key)}
                {@const isActive = form.key === activeFormKey}
                {@const isCompleted = form.status === 'completed'}
                <li>
                    <Link
                        href={show.url(form.key)}
                        class="flex items-center gap-3 rounded-md px-3 py-2 text-sm transition-colors
                            {isActive ? 'bg-primary/10 font-medium text-foreground' : 'text-muted-foreground hover:bg-muted hover:text-foreground'}"
                    >
                        <!-- Status indicator -->
                        {#if isCompleted}
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4 shrink-0 text-primary" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                            </svg>
                        {:else if isActive}
                            <div class="size-4 shrink-0 rounded-full border-2 border-primary bg-primary/20"></div>
                        {:else}
                            <div class="size-4 shrink-0 rounded-full border-2 border-muted-foreground/30"></div>
                        {/if}

                        <span class="truncate">{form.title}</span>
                    </Link>

                    <!-- Section sub-steps (only for active form) -->
                    {#if isActive && form.sections.length > 1}
                        <ul class="ml-5 mt-1 space-y-0.5 border-l-2 border-border pl-4">
                            {#each form.sections as section, i (section.key)}
                                <li>
                                    <button
                                        type="button"
                                        onclick={() => onSectionClick?.(i)}
                                        class="w-full rounded-md px-2 py-1.5 text-left text-xs transition-colors
                                            {i === activeSectionIndex ? 'font-medium text-primary' : 'text-muted-foreground hover:text-foreground'}"
                                    >
                                        {section.title}
                                    </button>
                                </li>
                            {/each}
                        </ul>
                    {/if}
                </li>
            {/each}
        </ul>
    </nav>

    <Separator />

    <!-- Footer -->
    <div class="px-5 py-4">
        <Link
            href={dashboard.url()}
            class="text-xs text-muted-foreground transition-colors hover:text-foreground"
        >
            &larr; {t.back_to_dashboard}
        </Link>
    </div>
</aside>
