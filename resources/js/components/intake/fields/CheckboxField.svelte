<script lang="ts">
    import { Checkbox } from '@/components/ui/checkbox';
    import { Label } from '@/components/ui/label';

    let {
        field,
        value = $bindable(false),
        error = '',
        onblur,
    }: {
        field: Record<string, any>;
        value: boolean;
        error: string;
        onblur?: () => void;
    } = $props();
</script>

<div class="flex items-center gap-3 py-2">
    <Checkbox
        id={field.key}
        bind:checked={value}
        onchange={onblur}
    />
    <Label for={field.key} class="cursor-pointer">{field.label}</Label>
</div>
{#if error}
    <p class="text-sm text-destructive">{error}</p>
{/if}
