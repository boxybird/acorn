<script lang="ts">
    import { Label } from '@/components/ui/label';

    let {
        field,
        value = $bindable<File | null>(null),
        locale = 'en',
        error = '',
        onblur,
    }: {
        field: Record<string, any>;
        value: File | null;
        locale: string;
        error: string;
        onblur?: () => void;
    } = $props();

    function handleChange(event: Event) {
        const input = event.target as HTMLInputElement;
        value = input.files?.[0] ?? null;
        if (onblur) onblur();
    }
</script>

<div class="space-y-2">
    <Label for={field.key}>{field.label[locale]}</Label>
    <input
        id={field.key}
        type="file"
        onchange={handleChange}
        accept={field.accept ?? ''}
        class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 {error ? 'border-destructive' : ''}"
    />
    {#if error}
        <p class="text-sm text-destructive">{error}</p>
    {/if}
</div>
