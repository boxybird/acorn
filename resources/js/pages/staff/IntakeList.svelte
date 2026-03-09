<script lang="ts">
    import { Link, router } from '@inertiajs/svelte';
    import { Badge } from '@/components/ui/badge';
    import Input from '@/components/ui/input/Input.svelte';
    import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.svelte';
    import { index, show } from '@/routes/staff/intakes';

    type IntakeItem = {
        id: number;
        child_name: string | null;
        status: string;
        created_at: string;
        updated_at: string;
        patient: {
            id: number;
            name: string | null;
            email: string;
        };
    };

    type StatusCounts = Record<string, number>;

    type Filters = {
        status: string;
        search: string;
    };

    type PaginatedIntakes = {
        data: IntakeItem[];
        links: { url: string | null; label: string; active: boolean }[];
    };

    let {
        intakes,
        statusCounts,
        filters,
    }: {
        intakes: PaginatedIntakes;
        statusCounts: StatusCounts;
        filters: Filters;
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

    let search = $state(filters.search);

    let totalCount = $derived(Object.values(statusCounts).reduce((sum, count) => sum + count, 0));

    function submitSearch(): void {
        const params: Record<string, string> = {};

        if (search) {
            params.search = search;
        }

        if (filters.status) {
            params.status = filters.status;
        }

        router.get(index.url(), params, { preserveState: true });
    }

    function handleSearchKeydown(event: KeyboardEvent): void {
        if (event.key === 'Enter') {
            submitSearch();
        }
    }
</script>

<AppSidebarLayout>
    <div class="staff space-y-6 p-6">
        <h1 class="text-2xl font-bold text-foreground">Intakes</h1>

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <nav class="flex flex-wrap gap-2">
                <Link
                    href={index.url(filters.search ? { query: { search: filters.search } } : undefined)}
                    class="inline-flex"
                >
                    <Badge variant={!filters.status ? 'default' : 'outline'}>
                        All ({totalCount})
                    </Badge>
                </Link>
                {#each Object.entries(statusCounts) as [status, count] (status)}
                    <Link
                        href={index.url({ query: { status, ...(filters.search ? { search: filters.search } : {}) } })}
                        class="inline-flex"
                    >
                        <Badge variant={filters.status === status ? (statusConfig[status]?.variant ?? 'outline') : 'outline'}>
                            {statusConfig[status]?.label ?? status} ({count})
                        </Badge>
                    </Link>
                {/each}
            </nav>

            <div class="w-full sm:w-64">
                <Input
                    type="text"
                    placeholder="Search by email..."
                    bind:value={search}
                    onkeydown={handleSearchKeydown}
                    onblur={submitSearch}
                />
            </div>
        </div>

        <div class="overflow-x-auto rounded-md border">
            <table class="w-full text-sm">
                <thead class="border-b bg-muted/50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Child Name</th>
                        <th class="px-4 py-3 text-left font-medium">Parent Name</th>
                        <th class="px-4 py-3 text-left font-medium">Submitted Date</th>
                        <th class="px-4 py-3 text-left font-medium">Status</th>
                        <th class="px-4 py-3 text-left font-medium">Last Updated</th>
                    </tr>
                </thead>
                <tbody>
                    {#each intakes.data as intake (intake.id)}
                        <tr class="border-b hover:bg-muted/30">
                            <td class="px-4 py-3">
                                <Link href={show.url(intake.id)} class="font-medium text-primary hover:underline">
                                    {intake.child_name ?? '—'}
                                </Link>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {intake.patient.name ?? intake.patient.email}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {new Date(intake.created_at).toLocaleDateString()}
                            </td>
                            <td class="px-4 py-3">
                                <Badge variant={statusConfig[intake.status]?.variant ?? 'outline'}>
                                    {statusConfig[intake.status]?.label ?? intake.status}
                                </Badge>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {new Date(intake.updated_at).toLocaleDateString()}
                            </td>
                        </tr>
                    {:else}
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">
                                No intakes found.
                            </td>
                        </tr>
                    {/each}
                </tbody>
            </table>
        </div>

        <nav class="flex gap-1">
            {#each intakes.links as link}
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
