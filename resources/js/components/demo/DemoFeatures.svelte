<script lang="ts">
    import Badge from '@/components/ui/badge/Badge.svelte';

    type Feature = {
        title: string;
        description: string;
        icon: string;
    };

    const liveFeatures: Feature[] = [
        {
            title: 'Magic Link Auth',
            description: 'No passwords, no friction',
            icon: 'link',
        },
        {
            title: 'Bilingual',
            description: 'Full English & Spanish support',
            icon: 'globe',
        },
        {
            title: 'Auto-Save',
            description: 'Parents can stop and resume anytime',
            icon: 'cloud-save',
        },
        {
            title: 'Staff Dashboard',
            description: 'Review, flag, and approve intakes',
            icon: 'clipboard-check',
        },
        {
            title: 'Monday.com Sync',
            description: 'Intakes flow directly into JumpStart\'s Monday.com boards',
            icon: 'refresh-sync',
        },
    ];

    const comingSoonFeatures: Feature[] = [
        {
            title: 'Insurance Verification',
            description: 'Automated eligibility checks',
            icon: 'shield-check',
        },
        {
            title: 'SMS Notifications',
            description: 'Text reminders and status updates',
            icon: 'message-circle',
        },
        {
            title: 'Software Integrations',
            description: 'Connect with JumpStart\'s existing tools',
            icon: 'puzzle',
        },
        {
            title: 'Analytics & Reporting',
            description: 'Track intake metrics and outcomes',
            icon: 'bar-chart',
        },
    ];
</script>

{#snippet featureIcon(icon: string)}
    {#if icon === 'link'}
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
            <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
        </svg>
    {:else if icon === 'globe'}
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10" />
            <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20" />
            <path d="M2 12h20" />
        </svg>
    {:else if icon === 'cloud-save'}
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 13v8" />
            <path d="m8 17 4 4 4-4" />
            <path d="M20.16 9.35A5 5 0 0 0 15 4H9.8A7 7 0 1 0 7 17.94" />
        </svg>
    {:else if icon === 'clipboard-check'}
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect width="8" height="4" x="8" y="2" rx="1" ry="1" />
            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2" />
            <path d="m9 14 2 2 4-4" />
        </svg>
    {:else if icon === 'refresh-sync'}
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" />
            <path d="M3 3v5h5" />
            <path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16" />
            <path d="M16 16h5v5" />
        </svg>
    {:else if icon === 'shield-check'}
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
            <path d="m9 12 2 2 4-4" />
        </svg>
    {:else if icon === 'message-circle'}
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M7.9 20A9 9 0 1 0 4 16.1L2 22z" />
        </svg>
    {:else if icon === 'puzzle'}
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M15.39 4.39a1 1 0 0 0 .61.22h2a2 2 0 0 1 2 2v2a1 1 0 0 0 .22.61 2.5 2.5 0 0 1-3.44 3.44A1 1 0 0 0 16 13v2a2 2 0 0 1-2 2h-2a1 1 0 0 0-.61.22 2.5 2.5 0 0 1-3.44-3.44A1 1 0 0 0 8 13.24V11a2 2 0 0 1 2-2h2.24a1 1 0 0 0 .76-.34 2.5 2.5 0 0 1 2.39-4.27z" />
            <path d="M5.37 5.37a2.5 2.5 0 0 0 3.44 3.44" />
        </svg>
    {:else if icon === 'bar-chart'}
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" x2="12" y1="20" y2="10" />
            <line x1="18" x2="18" y1="20" y2="4" />
            <line x1="6" x2="6" y1="20" y2="14" />
        </svg>
    {/if}
{/snippet}

<div class="space-y-6">
    <div>
        <h4 class="mb-3 text-sm font-semibold uppercase tracking-wide text-muted-foreground">Features</h4>
        <div class="space-y-3">
            {#each liveFeatures as feature}
                <div class="flex items-center gap-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary">
                        {@render featureIcon(feature.icon)}
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium">{feature.title}</span>
                            <Badge variant="default" class="text-[10px] px-1.5 py-0">Live</Badge>
                        </div>
                        <p class="text-xs text-muted-foreground">{feature.description}</p>
                    </div>
                </div>
            {/each}
        </div>
    </div>

    <div>
        <h4 class="mb-3 text-sm font-semibold uppercase tracking-wide text-muted-foreground">Coming Soon</h4>
        <div class="space-y-3">
            {#each comingSoonFeatures as feature}
                <div class="flex items-center gap-3 opacity-60">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-muted text-muted-foreground">
                        {@render featureIcon(feature.icon)}
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium">{feature.title}</span>
                            <Badge variant="outline" class="text-[10px] px-1.5 py-0">Coming Soon</Badge>
                        </div>
                        <p class="text-xs text-muted-foreground">{feature.description}</p>
                    </div>
                </div>
            {/each}
        </div>
    </div>
</div>
