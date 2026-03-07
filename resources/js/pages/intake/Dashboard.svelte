<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { Card, CardContent } from '@/components/ui/card';
    import AppLogoIcon from '@/components/AppLogoIcon.svelte';
    import { show } from '@/routes/intake/form';

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

    let { forms, progress }: { forms: FormItem[]; progress: Progress } = $props();

    const locale = 'en';

    const statusConfig: Record<string, { label: string; variant: 'default' | 'secondary' | 'outline' }> = {
        not_started: { label: 'Not Started', variant: 'outline' },
        in_progress: { label: 'In Progress', variant: 'secondary' },
        completed: { label: 'Completed', variant: 'default' },
    };

    let progressPercent = $derived(
        progress.total > 0 ? Math.round((progress.completed / progress.total) * 100) : 0,
    );
</script>

<div class="flex min-h-screen flex-col bg-background">
    <header class="border-b px-4 py-4">
        <div class="mx-auto flex max-w-2xl items-center gap-3">
            <AppLogoIcon class="size-8" />
            <span class="text-lg font-bold text-foreground">Acorn</span>
        </div>
    </header>

    <main class="mx-auto w-full max-w-2xl flex-1 space-y-8 p-4 py-8">
        <div>
            <h1 class="text-2xl font-bold text-foreground">Your Intake Dashboard</h1>
            <p class="mt-1 text-muted-foreground">Complete the sections below to finish your intake.</p>
        </div>

        <div class="space-y-2">
            <div class="flex items-center justify-between text-sm">
                <span class="text-muted-foreground">Progress</span>
                <span class="font-medium text-foreground">{progress.completed} of {progress.total} complete</span>
            </div>
            <div class="h-2 w-full overflow-hidden rounded-full bg-muted">
                <div
                    class="h-full rounded-full bg-primary transition-all duration-500"
                    style="width: {progressPercent}%"
                ></div>
            </div>
        </div>

        {#if progressPercent === 100}
            <div class="rounded-md border border-primary/20 bg-primary/5 p-4">
                <p class="font-medium text-primary">All sections complete! Your information is being processed.</p>
            </div>
        {/if}

        <div class="space-y-3">
            {#each forms as form (form.key)}
                <Link href={show.url(form.key)} class="block">
                    <Card class="transition-shadow hover:shadow-md {form.status === 'completed' ? 'opacity-75' : ''}">
                        <CardContent class="flex items-center justify-between p-4">
                            <div class="space-y-1">
                                <h3 class="font-semibold text-foreground">{form.title[locale]}</h3>
                                {#if form.estimated_minutes}
                                    <p class="text-sm text-muted-foreground">~{form.estimated_minutes} min</p>
                                {/if}
                            </div>
                            <Badge variant={statusConfig[form.status].variant}>
                                {statusConfig[form.status].label}
                            </Badge>
                        </CardContent>
                    </Card>
                </Link>
            {/each}
        </div>
    </main>
</div>
