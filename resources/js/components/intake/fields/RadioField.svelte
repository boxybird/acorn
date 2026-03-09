<script lang="ts">
    import { Label } from '@/components/ui/label';

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
</script>

<div class="space-y-2">
    <Label>{field.label}</Label>
    <div class="space-y-2">
        {#each field.options ?? [] as option (option.value)}
            <label class="flex items-center gap-3 cursor-pointer">
                <input
                    type="radio"
                    name={field.key}
                    value={option.value}
                    checked={value === option.value}
                    onchange={() => { value = option.value; if (onblur) onblur(); }}
                    class="size-4 border-input text-primary focus:ring-ring"
                />
                <span class="text-sm">{option.label}</span>
            </label>
        {/each}
    </div>
    {#if error}
        <p class="text-sm text-destructive">{error}</p>
    {/if}
</div>
