<script lang="ts">
    import { router, useForm } from '@inertiajs/svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
    import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
    import { Separator } from '@/components/ui/separator';
    import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.svelte';
    import { approve, flag, pdf } from '@/routes/staff/intakes';
    import { resolve } from '@/routes/staff/intakes/flags';
    import { store as storeNote } from '@/routes/staff/intakes/notes';

    type Patient = {
        id: number;
        name: string | null;
        email: string;
    };

    type FormResponseItem = {
        id: number;
        schema_key: string;
        data: Record<string, any>;
        status: string;
        created_at: string;
        updated_at: string;
    };

    type NoteItem = {
        id: number;
        user_id: number | null;
        patient_id: number | null;
        body: string;
        created_at: string;
        user?: { id: number; name: string };
        patient?: { id: number; name: string | null; email: string };
    };

    type FlagItem = {
        id: number;
        form_response_id: number;
        reason: string;
        resolved_at: string | null;
        user?: { id: number; name: string };
    };

    type Schema = {
        key: string;
        title: string;
        order: number;
    };

    type IntakeDetail = {
        id: number;
        child_name: string | null;
        status: string;
        patient: Patient;
    };

    let {
        intake,
        formResponses,
        notes,
        flags,
        schemas,
    }: {
        intake: IntakeDetail;
        formResponses: FormResponseItem[];
        notes: NoteItem[];
        flags: FlagItem[];
        schemas: Schema[];
    } = $props();

    const statusConfig: Record<string, { label: string; variant: 'default' | 'secondary' | 'outline' | 'destructive' }> = {
        active: { label: 'In Progress', variant: 'outline' },
        submitted: { label: 'Submitted', variant: 'default' },
        under_review: { label: 'Under Review', variant: 'secondary' },
        flagged: { label: 'Flagged', variant: 'destructive' },
        correction_submitted: { label: 'Corrections Submitted', variant: 'outline' },
        approved: { label: 'Approved', variant: 'default' },
        synced_to_monday: { label: 'Synced', variant: 'secondary' },
    };

    let isActive = $derived(intake.status === 'active');

    let openForms = $state<Record<number, boolean>>(
        Object.fromEntries(formResponses.map((r) => [r.id, false])),
    );
    let flaggingFormId = $state<number | null>(null);

    const approveForm = useForm({});
    const noteForm = useForm({ body: '' });
    const flagForm = useForm({ form_response_id: 0, reason: '' });

    function getSchemaTitle(schemaKey: string): string {
        const schema = schemas.find((s) => s.key === schemaKey);
        return schema?.title ?? schemaKey;
    }

    function getFlagsForResponse(responseId: number): FlagItem[] {
        return flags.filter((f) => f.form_response_id === responseId);
    }

    function hasUnresolvedFlag(responseId: number): boolean {
        return flags.some((f) => f.form_response_id === responseId && f.resolved_at === null);
    }

    function toggleForm(responseId: number): void {
        openForms[responseId] = !openForms[responseId];
    }

    function submitApprove(): void {
        $approveForm.post(approve.url(intake.id));
    }

    function submitNote(): void {
        $noteForm.post(storeNote.url(intake.id), {
            onSuccess: () => $noteForm.reset(),
        });
    }

    function startFlagging(responseId: number): void {
        flaggingFormId = responseId;
        $flagForm.form_response_id = responseId;
        $flagForm.reason = '';
    }

    function cancelFlagging(): void {
        flaggingFormId = null;
        $flagForm.reset();
    }

    function submitFlag(): void {
        $flagForm.post(flag.url(intake.id), {
            onSuccess: () => {
                flaggingFormId = null;
                $flagForm.reset();
            },
        });
    }

    function resolveFlag(flagId: number): void {
        router.post(resolve.url({ intake: intake.id, intakeFlag: flagId }));
    }

    function getNoteAuthor(note: NoteItem): { name: string; role: string } {
        if (note.user) {
            return { name: note.user.name, role: 'Staff' };
        }
        if (note.patient) {
            return { name: note.patient.name ?? note.patient.email, role: 'Parent' };
        }
        return { name: 'Unknown', role: 'Staff' };
    }

    function formatValue(value: any): string {
        if (value === null || value === undefined) {
            return '—';
        }
        if (typeof value === 'boolean') {
            return value ? 'Yes' : 'No';
        }
        if (Array.isArray(value)) {
            return value.join(', ');
        }
        if (typeof value === 'object') {
            return JSON.stringify(value);
        }
        return String(value);
    }
</script>

<AppSidebarLayout>
    <div class="staff space-y-6 p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-foreground">{intake.child_name ?? 'Unnamed Child'}</h1>
                <p class="text-muted-foreground">
                    {intake.patient.name ?? intake.patient.email}
                    {#if intake.patient.name}
                        <span class="ml-1 text-sm">({intake.patient.email})</span>
                    {/if}
                </p>
            </div>
            <div class="flex items-center gap-3">
                <Badge variant={statusConfig[intake.status]?.variant ?? 'outline'}>
                    {statusConfig[intake.status]?.label ?? intake.status}
                </Badge>
                <Button
                    variant="default"
                    onclick={submitApprove}
                    disabled={$approveForm.processing || isActive || intake.status === 'approved'}
                >
                    {$approveForm.processing ? 'Approving...' : 'Approve'}
                </Button>
                <Button variant="outline" asChild>
                    {#snippet children(props)}
                        <a href={pdf.url(intake.id)} {...props}>Export PDF</a>
                    {/snippet}
                </Button>
            </div>
        </div>

        <Separator />

        <div>
            <h2 class="mb-4 text-xl font-bold text-foreground">Form Responses</h2>

            {#if formResponses.length === 0}
                <p class="text-muted-foreground">No form responses yet.</p>
            {:else}
                <div class="space-y-3">
                    {#each formResponses as response (response.id)}
                        {@const responseFlags = getFlagsForResponse(response.id)}
                        {@const isFlagged = hasUnresolvedFlag(response.id)}
                        {@const isOpen = openForms[response.id] ?? false}

                        <Collapsible bind:open={openForms[response.id]}>
                            <Card>
                                <CardHeader>
                                    <div class="flex items-center justify-between">
                                        <CollapsibleTrigger
                                            class="flex items-center gap-2 text-left"
                                            onclick={() => toggleForm(response.id)}
                                        >
                                            <span class="text-lg font-semibold">{getSchemaTitle(response.schema_key)}</span>
                                            <span class="text-muted-foreground">{isOpen ? '−' : '+'}</span>
                                        </CollapsibleTrigger>
                                        <div class="flex items-center gap-2">
                                            {#if isFlagged}
                                                <Badge variant="destructive">Flagged</Badge>
                                            {/if}
                                            <Badge variant={response.status === 'completed' ? 'default' : 'secondary'}>
                                                {response.status}
                                            </Badge>
                                        </div>
                                    </div>
                                </CardHeader>

                                {#if isOpen}
                                    <CollapsibleContent>
                                        <CardContent>
                                            <dl class="grid gap-3 sm:grid-cols-2">
                                                {#each Object.entries(response.data ?? {}) as [key, val]}
                                                    <div>
                                                        <dt class="text-sm font-medium text-muted-foreground">{key}</dt>
                                                        <dd class="text-sm text-foreground">{formatValue(val)}</dd>
                                                    </div>
                                                {/each}
                                            </dl>

                                            <Separator class="my-4" />

                                            {#each responseFlags as flag (flag.id)}
                                                <div class="mb-3 rounded-md border border-destructive/30 bg-destructive/5 p-3">
                                                    <div class="flex items-start justify-between gap-2">
                                                        <div>
                                                            <p class="text-sm font-medium text-destructive">
                                                                Flagged{flag.user ? ` by ${flag.user.name}` : ''}
                                                            </p>
                                                            <p class="mt-1 text-sm text-foreground">{flag.reason}</p>
                                                        </div>
                                                        {#if flag.resolved_at === null}
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                onclick={() => resolveFlag(flag.id)}
                                                            >
                                                                Resolve
                                                            </Button>
                                                        {:else}
                                                            <Badge variant="secondary">Resolved</Badge>
                                                        {/if}
                                                    </div>
                                                </div>
                                            {/each}

                                            {#if flaggingFormId === response.id}
                                                <div class="mt-3 rounded-md border p-3">
                                                    <label for="flag-reason-{response.id}" class="mb-1 block text-sm font-medium">
                                                        Flag Reason
                                                    </label>
                                                    <textarea
                                                        id="flag-reason-{response.id}"
                                                        bind:value={$flagForm.reason}
                                                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                                        rows="3"
                                                        placeholder="Describe the issue..."
                                                    ></textarea>
                                                    {#if $flagForm.errors.reason}
                                                        <p class="mt-1 text-sm text-destructive">{$flagForm.errors.reason}</p>
                                                    {/if}
                                                    <div class="mt-2 flex gap-2">
                                                        <Button
                                                            variant="destructive"
                                                            size="sm"
                                                            onclick={submitFlag}
                                                            disabled={$flagForm.processing}
                                                        >
                                                            {$flagForm.processing ? 'Submitting...' : 'Submit Flag'}
                                                        </Button>
                                                        <Button variant="outline" size="sm" onclick={cancelFlagging}>
                                                            Cancel
                                                        </Button>
                                                    </div>
                                                </div>
                                            {:else}
                                                <div class="mt-3">
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onclick={() => startFlagging(response.id)}
                                                        disabled={isActive}
                                                    >
                                                        Flag this form
                                                    </Button>
                                                </div>
                                            {/if}
                                        </CardContent>
                                    </CollapsibleContent>
                                {/if}
                            </Card>
                        </Collapsible>
                    {/each}
                </div>
            {/if}
        </div>

        <Separator />

        <div>
            <h2 class="mb-4 text-xl font-bold text-foreground">Notes</h2>

            <div class="space-y-3">
                {#each notes as note (note.id)}
                    {@const author = getNoteAuthor(note)}
                    <Card>
                        <CardContent class="pt-4">
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
                {:else}
                    <p class="text-muted-foreground">No notes yet.</p>
                {/each}
            </div>

            <Card class="mt-4">
                <CardContent class="pt-4">
                    <form onsubmit={(e) => { e.preventDefault(); submitNote(); }}>
                        <label for="note-body" class="mb-1 block text-sm font-medium">Add a Note</label>
                        <textarea
                            id="note-body"
                            bind:value={$noteForm.body}
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                            rows="3"
                            placeholder="Write a note..."
                        ></textarea>
                        {#if $noteForm.errors.body}
                            <p class="mt-1 text-sm text-destructive">{$noteForm.errors.body}</p>
                        {/if}
                        <div class="mt-2">
                            <Button type="submit" disabled={$noteForm.processing}>
                                {$noteForm.processing ? 'Adding...' : 'Add Note'}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </div>
</AppSidebarLayout>
