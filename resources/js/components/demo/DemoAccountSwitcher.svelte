<script lang="ts">
    type Intake = {
        child_name: string;
        status: string;
        form_count: number;
        completed_count: number;
    };

    type PatientAccount = {
        id: number;
        name: string;
        intakes: Intake[];
    };

    type StaffAccount = {
        id: number;
        name: string;
        email: string;
    };

    let { patients = [], users = [] }: { patients: PatientAccount[]; users: StaffAccount[] } = $props();
    let loading = $state<number | null>(null);

    function getCsrfToken(): string {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
    }

    function submitForm(action: string, loadingId: number) {
        loading = loadingId;
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = action;

        const token = document.createElement('input');
        token.type = 'hidden';
        token.name = '_token';
        token.value = getCsrfToken();
        form.appendChild(token);

        document.body.appendChild(form);
        form.submit();
    }
</script>

<div class="space-y-6">
    <!-- Parent Accounts -->
    <div>
        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-muted-foreground">Parent Accounts</h3>
        <div class="space-y-2">
            {#each patients as patient}
                <button
                    type="button"
                    class="w-full rounded-lg border p-3 text-left transition-colors hover:bg-muted disabled:opacity-50"
                    onclick={() => submitForm(`/demo/login/patient/${patient.id}`, patient.id)}
                    disabled={loading !== null}
                >
                    <div class="font-medium">{patient.name}</div>
                    {#each patient.intakes as intake}
                        <div class="mt-1 text-xs text-muted-foreground">
                            {intake.child_name} · {intake.status} ({intake.completed_count}/{intake.form_count} forms)
                        </div>
                    {/each}
                    {#if patient.intakes.length === 0}
                        <div class="mt-1 text-xs text-muted-foreground">No intakes started</div>
                    {/if}
                </button>
            {/each}
        </div>
    </div>

    <!-- Staff Accounts -->
    <div>
        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-muted-foreground">Staff Accounts</h3>
        <div class="space-y-2">
            {#each users as user}
                <button
                    type="button"
                    class="w-full rounded-lg border p-3 text-left transition-colors hover:bg-muted disabled:opacity-50"
                    onclick={() => submitForm(`/demo/login/user/${user.id}`, user.id + 10000)}
                    disabled={loading !== null}
                >
                    <div class="font-medium">{user.name}</div>
                    <div class="text-xs text-muted-foreground">{user.email}</div>
                </button>
            {/each}
        </div>
    </div>

    <!-- Actions -->
    <div class="space-y-2 border-t pt-4">
        <button
            type="button"
            class="w-full rounded-lg border border-destructive/30 p-3 text-center text-sm font-medium text-destructive transition-colors hover:bg-destructive/10 disabled:opacity-50"
            onclick={() => submitForm('/demo/logout', -1)}
            disabled={loading !== null}
        >
            Log Out &amp; Return Home
        </button>
        <button
            type="button"
            class="w-full rounded-lg border p-3 text-center text-sm font-medium text-muted-foreground transition-colors hover:bg-muted disabled:opacity-50"
            onclick={() => { if (confirm('This will reset all data to its original state. Continue?')) submitForm('/demo/reset', -2); }}
            disabled={loading !== null}
        >
            Reset All Data
        </button>
    </div>
</div>
