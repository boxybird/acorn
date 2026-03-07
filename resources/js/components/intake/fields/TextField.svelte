<script lang="ts">
    import { Input } from '@/components/ui/input';
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
    <Input
        id={field.key}
        type="text"
        bind:value
        onblur={onblur}
        class={error ? 'border-destructive' : ''}
    />
    {#if error}
        <p class="text-sm text-destructive">{error}</p>
    {/if}
</div>
