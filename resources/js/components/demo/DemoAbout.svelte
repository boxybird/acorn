<script lang="ts">
    const sections = [
        {
            title: 'What is Acorn?',
            content:
                'Acorn is a parent intake portal for JumpStart Autism Collective. It replaces the long, paper-based intake process with a guided digital experience that parents can complete at their own pace — from any device, in English or Spanish.',
        },
        {
            title: 'The Problem',
            content:
                'Only about 1 in 3 referred families complete the intake process. The current paperwork is overwhelming, especially for families already navigating a new diagnosis. Acorn reduces friction so more children get into services faster.',
        },
        {
            title: 'How It Works',
            content:
                'Parents receive a magic link via email — no passwords needed. They land on a dashboard showing all required forms as a checklist. Each form saves progress automatically. When everything is complete, the intake is submitted for staff review.',
        },
        {
            title: 'Staff Experience',
            content:
                'Staff see submitted intakes in their dashboard. They can review each form, flag items that need correction, add notes, and approve completed intakes. Approved intakes will eventually sync to Monday.com.',
        },
        {
            title: 'Current Status',
            content:
                'This is a working prototype with test data. Magic link emails are not yet configured — use the account switcher on the right to explore as different users. All data is test data and can be freely modified.',
        },
    ];
</script>

<div class="space-y-6">
    <div class="flex items-center gap-3">
        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 3h6l1 7H8L9 3z" />
                <path d="M8 10h8v2a4 4 0 0 1-8 0v-2z" />
                <path d="M12 14v8" />
                <path d="M8 22h8" />
            </svg>
        </div>
        <div>
            <h3 class="font-semibold">About This Tool</h3>
            <p class="text-xs text-muted-foreground">Last updated: March 2026</p>
        </div>
    </div>

    {#each sections as section}
        <div>
            <h4 class="mb-1.5 text-sm font-semibold">{section.title}</h4>
            <p class="text-sm leading-relaxed text-muted-foreground">{section.content}</p>
        </div>
    {/each}
</div>
