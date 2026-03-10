<script lang="ts">
    import { onMount } from 'svelte';
    import { page, router, useForm } from '@inertiajs/svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { Card, CardContent } from '@/components/ui/card';
    import IntakeHeader from '@/components/intake/IntakeHeader.svelte';
    import IntakeCard from '@/components/intake/IntakeCard.svelte';
    import { newMethod as create } from '@/routes/intake/select';
    import { store as storeNote } from '@/routes/intake/notes';

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

    type NoteItem = {
        id: number;
        body: string;
        created_at: string;
        user_id: number | null;
        patient_id: number | null;
        user?: { id: number; name: string } | null;
        patient?: { id: number; name: string | null; email: string } | null;
    };

    let { intakes, notes }: {
        intakes: IntakeData[];
        notes: NoteItem[];
    } = $props();

    let t = $derived($page.props.translations as Record<string, string>);

    const noteForm = useForm({ body: '' });

    function submitNote(): void {
        $noteForm.post(storeNote.url(), {
            onSuccess: () => $noteForm.reset(),
        });
    }

    function getNoteAuthor(note: NoteItem): { name: string; role: 'Staff' | 'Parent' } {
        if (note.user) {
            return { name: note.user.name, role: 'Staff' };
        }
        if (note.patient) {
            return { name: note.patient.name ?? note.patient.email, role: 'Parent' };
        }
        return { name: 'Unknown', role: 'Parent' };
    }

    let mounted = $state(false);
    onMount(() => { mounted = true; });
</script>

<div class="flex min-h-screen flex-col bg-primary/5" style="background-image: url('/texture.svg'); background-size: 200px 200px;">
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

        <!-- Notes Section -->
        <div class="mt-8 space-y-3">
            <h2 class="text-sm font-medium text-muted-foreground">{t.notes ?? 'Notes'}</h2>

            {#each notes as note (note.id)}
                {@const author = getNoteAuthor(note)}
                <Card>
                    <CardContent class="p-4">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium">{author.name}</span>
                            <Badge variant={author.role === 'Staff' ? 'secondary' : 'outline'}>
                                {author.role}
                            </Badge>
                            <span class="text-xs text-muted-foreground">
                                {new Date(note.created_at).toLocaleString()}
                            </span>
                        </div>
                        <p class="mt-2 text-sm text-foreground">{note.body}</p>
                    </CardContent>
                </Card>
            {/each}

            <Card>
                <CardContent class="p-4">
                    <form onsubmit={(e) => { e.preventDefault(); submitNote(); }}>
                        <label for="note-body" class="mb-1 block text-sm font-medium">
                            {t.add_note ?? 'Add a note'}
                        </label>
                        <textarea
                            id="note-body"
                            bind:value={$noteForm.body}
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                            rows="3"
                            placeholder={t.note_placeholder ?? 'Write a note...'}
                        ></textarea>
                        {#if $noteForm.errors.body}
                            <p class="mt-1 text-sm text-destructive">{$noteForm.errors.body}</p>
                        {/if}
                        <div class="mt-2">
                            <Button type="submit" size="sm" disabled={$noteForm.processing}>
                                {$noteForm.processing ? (t.adding ?? 'Adding...') : (t.add_note_button ?? 'Add Note')}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </main>
</div>
