<script lang="ts">
    import { page } from '@inertiajs/svelte';
</script>

<div class="flex min-h-screen items-center justify-center bg-background">
    <div class="w-full max-w-md space-y-6 p-8">
        <h1 class="text-2xl font-bold text-foreground">Welcome to Acorn</h1>
        <p class="text-muted-foreground">Enter your email to get started with your intake forms.</p>

        {#if $page.props.flash?.status}
            <p class="text-sm text-primary">{$page.props.flash.status}</p>
        {/if}

        {#if $page.props.flash?.error}
            <p class="text-sm text-destructive">{$page.props.flash.error}</p>
        {/if}
    </div>
</div>
