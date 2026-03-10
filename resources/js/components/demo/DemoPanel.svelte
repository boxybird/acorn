<script lang="ts">
    import DemoAbout from './DemoAbout.svelte';
    import DemoAccountSwitcher from './DemoAccountSwitcher.svelte';
    import DemoFeatures from './DemoFeatures.svelte';

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

    let open = $state(false);

    function toggle() {
        open = !open;
    }

    function handleKeydown(event: KeyboardEvent) {
        if (event.key === 'Escape' && open) {
            open = false;
        }
    }
</script>

<svelte:window onkeydown={handleKeydown} />

<!-- Floating Action Button -->
<button
    type="button"
    class="fixed bottom-6 left-6 z-[9999] flex h-12 w-12 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-lg transition-transform hover:scale-110 active:scale-95"
    onclick={toggle}
    aria-label="Open demo panel"
>
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M9 3h6l1 7H8L9 3z" />
        <path d="M8 10h8v2a4 4 0 0 1-8 0v-2z" />
        <path d="M12 14v8" />
        <path d="M8 22h8" />
    </svg>
</button>

<!-- Modal Overlay -->
{#if open}
    <div class="fixed inset-0 z-[10000]">
        <!-- Backdrop -->
        <!-- svelte-ignore a11y_no_static_element_interactions -->
        <div
            class="absolute inset-0 bg-black/60 backdrop-blur-sm animate-in fade-in duration-300"
            onclick={() => (open = false)}
            onkeydown={(e) => e.key === 'Enter' && (open = false)}
        ></div>

        <!-- Panel -->
        <div
            class="absolute inset-4 md:inset-8 lg:inset-12 z-10 flex flex-col overflow-hidden rounded-2xl border bg-background shadow-2xl animate-in zoom-in-95 fade-in duration-300"
            role="dialog"
            aria-modal="true"
            aria-label="Demo Panel"
        >
            <!-- Header -->
            <div class="flex items-center justify-between border-b px-6 py-4">
                <div>
                    <h2 class="text-xl font-semibold">Acorn Demo Panel</h2>
                    <p class="text-sm text-muted-foreground">Explore the intake portal from any perspective</p>
                </div>
                <button
                    type="button"
                    class="rounded-md p-2 hover:bg-muted transition-colors"
                    onclick={() => (open = false)}
                    aria-label="Close demo panel"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18" />
                        <path d="m6 6 12 12" />
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <div class="grid flex-1 grid-cols-1 overflow-hidden md:grid-cols-3">
                <!-- Left: The Pitch -->
                <div class="overflow-y-auto border-r p-6">
                    <DemoAbout />
                </div>

                <!-- Middle: Features & Integrations -->
                <div class="overflow-y-auto border-r p-6">
                    <DemoFeatures />
                </div>

                <!-- Right: Account Switcher -->
                <div class="overflow-y-auto p-6">
                    <DemoAccountSwitcher {patients} {users} />
                </div>
            </div>
        </div>
    </div>
{/if}
