<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { Card, CardContent } from '@/components/ui/card';
    import { Separator } from '@/components/ui/separator';
    import AppLogoIcon from '@/components/AppLogoIcon.svelte';
    import { choose, newMethod as create } from '@/routes/intake/select';

    type IntakeItem = {
        id: number;
        child_name: string | null;
        status: 'active' | 'completed';
        progress: {
            completed: number;
            total: number;
        };
        created_at: string;
        updated_at: string;
    };

    let { intakes }: { intakes: IntakeItem[] } = $props();

    let processing = $state(false);

    function chooseIntake(id: number) {
        if (processing) return;
        processing = true;
        router.post(choose.url(id), {}, {
            onFinish: () => processing = false,
        });
    }

    function createNewIntake() {
        if (processing) return;
        processing = true;
        router.post(create.url(), {}, {
            onFinish: () => processing = false,
        });
    }

    function childLabel(intake: IntakeItem, index: number): string {
        return intake.child_name ?? `Child #${index + 1}`;
    }

    function progressPercent(intake: IntakeItem): number {
        return intake.progress.total > 0
            ? Math.round((intake.progress.completed / intake.progress.total) * 100)
            : 0;
    }

    const statusConfig: Record<string, { label: string; variant: 'default' | 'secondary' | 'outline' }> = {
        active: { label: 'Active', variant: 'secondary' },
        completed: { label: 'Completed', variant: 'default' },
    };
</script>

<div class="flex min-h-screen flex-col bg-primary/5">
    <header class="border-b bg-background px-4 py-4">
        <div class="mx-auto flex max-w-2xl items-center gap-3">
            <AppLogoIcon class="size-8" />
            <span class="text-lg font-bold text-foreground">Acorn</span>
        </div>
    </header>

    <main class="mx-auto w-full max-w-2xl flex-1 space-y-8 p-4 py-8">
        <div>
            <h1 class="text-2xl font-bold text-foreground">Select an Intake</h1>
            <p class="mt-1 text-muted-foreground">Choose which child's intake you'd like to work on.</p>
        </div>

        <div class="space-y-3">
            {#each intakes as intake, index (intake.id)}
                <Card class="transition-shadow hover:shadow-md">
                    <CardContent class="p-5">
                        <div class="flex items-start justify-between">
                            <div class="space-y-1">
                                <h3 class="text-lg font-semibold text-foreground">
                                    {childLabel(intake, index)}
                                </h3>
                                <p class="text-sm text-muted-foreground">
                                    {intake.progress.completed} of {intake.progress.total} forms complete
                                </p>
                            </div>
                            <Badge variant={statusConfig[intake.status]?.variant ?? 'outline'}>
                                {statusConfig[intake.status]?.label ?? intake.status}
                            </Badge>
                        </div>

                        <div class="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-muted">
                            <div
                                class="h-full rounded-full bg-primary transition-all duration-500"
                                style="width: {progressPercent(intake)}%"
                            ></div>
                        </div>

                        <div class="mt-4 flex items-center justify-between">
                            <span class="text-xs text-muted-foreground">Last updated {intake.updated_at}</span>
                            <Button
                                size="sm"
                                disabled={processing}
                                onclick={() => chooseIntake(intake.id)}
                            >
                                Continue
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            {/each}
        </div>

        <Separator />

        <div class="flex justify-center">
            <Button
                variant="outline"
                disabled={processing}
                onclick={createNewIntake}
            >
                Start intake for another child
            </Button>
        </div>
    </main>
</div>
