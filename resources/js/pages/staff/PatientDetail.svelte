<script lang="ts">
    import { Badge } from '@/components/ui/badge';
    import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
    import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.svelte';

    type FormResponse = {
        id: number;
        schema_key: string;
        data: Record<string, any>;
        status: string;
        created_at: string;
        updated_at: string;
    };

    type Patient = {
        id: number;
        email: string;
        name: string | null;
        preferred_locale: string;
        sync_status: string;
        created_at: string;
    };

    let { patient, formResponses }: { patient: Patient; formResponses: FormResponse[] } = $props();
</script>

<AppSidebarLayout>
    <div class="staff space-y-6 p-6">
        <div>
            <h1 class="text-2xl font-bold text-foreground">{patient.name ?? patient.email}</h1>
            <p class="text-muted-foreground">{patient.email}</p>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <Card>
                <CardHeader>
                    <CardTitle class="text-sm">Locale</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-lg font-semibold">{patient.preferred_locale.toUpperCase()}</p>
                </CardContent>
            </Card>
            <Card>
                <CardHeader>
                    <CardTitle class="text-sm">Sync Status</CardTitle>
                </CardHeader>
                <CardContent>
                    <Badge>{patient.sync_status}</Badge>
                </CardContent>
            </Card>
            <Card>
                <CardHeader>
                    <CardTitle class="text-sm">Registered</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-lg font-semibold">{new Date(patient.created_at).toLocaleDateString()}</p>
                </CardContent>
            </Card>
        </div>

        <h2 class="text-xl font-bold text-foreground">Form Responses</h2>

        {#if formResponses.length === 0}
            <p class="text-muted-foreground">No form responses yet.</p>
        {:else}
            {#each formResponses as response (response.id)}
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <CardTitle>{response.schema_key}</CardTitle>
                            <Badge variant={response.status === 'completed' ? 'default' : 'secondary'}>
                                {response.status}
                            </Badge>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <dl class="grid gap-2 sm:grid-cols-2">
                            {#each Object.entries(response.data ?? {}) as [key, val]}
                                <div>
                                    <dt class="text-sm font-medium text-muted-foreground">{key}</dt>
                                    <dd class="text-sm text-foreground">{val}</dd>
                                </div>
                            {/each}
                        </dl>
                    </CardContent>
                </Card>
            {/each}
        {/if}
    </div>
</AppSidebarLayout>
