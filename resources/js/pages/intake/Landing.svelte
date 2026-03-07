<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import { Form } from '@inertiajs/svelte';
    import { Button } from '@/components/ui/button';
    import { Label } from '@/components/ui/label';
    import AppLogoIcon from '@/components/AppLogoIcon.svelte';
    import { requestLink } from '@/routes/intake';
</script>

<div class="flex min-h-screen items-center justify-center bg-background px-4">
    <div class="w-full max-w-md space-y-8">
        <div class="flex flex-col items-center gap-3">
            <AppLogoIcon class="size-16" />
            <h1 class="text-2xl font-bold text-foreground">Welcome to Acorn</h1>
            <p class="text-center text-muted-foreground">
                Enter your email to get started with your intake forms.
            </p>
        </div>

        {#if $page.props.flash?.status}
            <div class="rounded-md border border-primary/20 bg-primary/5 p-4">
                <p class="text-sm text-primary">{$page.props.flash.status}</p>
            </div>
        {/if}

        {#if $page.props.flash?.error}
            <div class="rounded-md border border-destructive/20 bg-destructive/5 p-4">
                <p class="text-sm text-destructive">{$page.props.flash.error}</p>
            </div>
        {/if}

        <Form action={requestLink.url()} method="post" class="space-y-4" let:errors let:processing>
            <div class="space-y-2">
                <Label for="email">Email Address</Label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    placeholder="parent@example.com"
                    required
                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                />
                {#if errors.email}
                    <p class="text-sm text-destructive">{errors.email}</p>
                {/if}
            </div>
            <Button type="submit" class="w-full" disabled={processing}>
                {processing ? 'Sending...' : 'Get Started'}
            </Button>
        </Form>
    </div>
</div>
