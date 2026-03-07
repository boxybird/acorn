<script lang="ts">
    import { Label } from '@/components/ui/label';

    let {
        field,
        value = $bindable(''),
        locale = 'en',
        error = '',
        onblur,
    }: {
        field: Record<string, any>;
        value: string;
        locale: string;
        error: string;
        onblur?: () => void;
    } = $props();
</script>

<div class="space-y-2">
    <Label for={field.key}>{field.label[locale]}</Label>
    <textarea
        id={field.key}
        bind:value
        onblur={onblur}
        rows="4"
        class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 {error ? 'border-destructive' : ''}"
    ></textarea>
    {#if error}
        <p class="text-sm text-destructive">{error}</p>
    {/if}
</div>
