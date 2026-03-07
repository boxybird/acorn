<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import { Badge } from '@/components/ui/badge';
    import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.svelte';

    type Patient = {
        id: number;
        email: string;
        name: string | null;
        preferred_locale: string;
        sync_status: string;
        form_responses_count: number;
        created_at: string;
    };

    type PaginatedPatients = {
        data: Patient[];
        links: { url: string | null; label: string; active: boolean }[];
    };

    let { patients }: { patients: PaginatedPatients } = $props();

    const syncStatusConfig: Record<string, { label: string; variant: 'default' | 'secondary' | 'outline' | 'destructive' }> = {
        pending: { label: 'Pending', variant: 'outline' },
        syncing: { label: 'Syncing', variant: 'secondary' },
        synced: { label: 'Synced', variant: 'default' },
        failed: { label: 'Failed', variant: 'destructive' },
    };
</script>

<AppSidebarLayout>
    <div class="staff space-y-6 p-6">
        <h1 class="text-2xl font-bold text-foreground">Patients</h1>

        <div class="overflow-x-auto rounded-md border">
            <table class="w-full text-sm">
                <thead class="border-b bg-muted/50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Name</th>
                        <th class="px-4 py-3 text-left font-medium">Email</th>
                        <th class="px-4 py-3 text-left font-medium">Forms</th>
                        <th class="px-4 py-3 text-left font-medium">Sync</th>
                        <th class="px-4 py-3 text-left font-medium">Date</th>
                    </tr>
                </thead>
                <tbody>
                    {#each patients.data as patient (patient.id)}
                        <tr class="border-b hover:bg-muted/30">
                            <td class="px-4 py-3">
                                <Link href="/staff/patients/{patient.id}" class="font-medium text-primary hover:underline">
                                    {patient.name ?? '—'}
                                </Link>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">{patient.email}</td>
                            <td class="px-4 py-3">{patient.form_responses_count}</td>
                            <td class="px-4 py-3">
                                <Badge variant={syncStatusConfig[patient.sync_status]?.variant ?? 'outline'}>
                                    {syncStatusConfig[patient.sync_status]?.label ?? patient.sync_status}
                                </Badge>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {new Date(patient.created_at).toLocaleDateString()}
                            </td>
                        </tr>
                    {/each}
                </tbody>
            </table>
        </div>

        <nav class="flex gap-1">
            {#each patients.links as link}
                {#if link.url}
                    <Link href={link.url} class="rounded px-3 py-1 text-sm {link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'}">
                        {@html link.label}
                    </Link>
                {:else}
                    <span class="rounded px-3 py-1 text-sm text-muted-foreground">{@html link.label}</span>
                {/if}
            {/each}
        </nav>
    </div>
</AppSidebarLayout>
