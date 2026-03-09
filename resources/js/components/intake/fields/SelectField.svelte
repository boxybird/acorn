<script lang="ts">
    import { Label } from '@/components/ui/label';
    import * as Select from '@/components/ui/select';

    let {
        field,
        value = $bindable(''),
        error = '',
        onblur,
    }: {
        field: Record<string, any>;
        value: string;
        error: string;
        onblur?: () => void;
    } = $props();

    let selectedLabel = $derived(
        field.options?.find((o: any) => o.value === value)?.label ?? '',
    );
</script>

<div class="space-y-2">
    <Label for={field.key}>{field.label}</Label>
    <Select.Root bind:value onOpenChange={(open) => { if (!open && onblur) onblur(); }}>
        <Select.Trigger class="w-full {error ? 'border-destructive' : ''}">
            {#if value}
                {selectedLabel}
            {:else}
                <span class="text-muted-foreground">—</span>
            {/if}
        </Select.Trigger>
        <Select.Content>
            {#each field.options ?? [] as option (option.value)}
                <Select.Item value={option.value}>{option.label}</Select.Item>
            {/each}
        </Select.Content>
    </Select.Root>
    {#if error}
        <p class="text-sm text-destructive">{error}</p>
    {/if}
</div>
