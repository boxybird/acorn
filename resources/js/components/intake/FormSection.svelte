<script lang="ts">
    import type { Component } from 'svelte';
    import {
        TextField,
        EmailField,
        PhoneField,
        AddressField,
        TextareaField,
        SelectField,
        CheckboxField,
        RadioField,
        DateField,
        FileField,
        SignatureField,
    } from './fields';

    let {
        section,
        formData = $bindable({}),
        errors = {},
        onFieldBlur,
    }: {
        section: Record<string, any>;
        formData: Record<string, any>;
        errors: Record<string, string>;
        onFieldBlur?: (fieldKey: string) => void;
    } = $props();

    const fieldComponents: Record<string, Component> = {
        text: TextField,
        email: EmailField,
        phone: PhoneField,
        address: AddressField,
        textarea: TextareaField,
        select: SelectField,
        checkbox: CheckboxField,
        radio: RadioField,
        date: DateField,
        file: FileField,
        signature: SignatureField,
    };

    function shouldShow(field: Record<string, any>): boolean {
        if (!field.conditions) return true;
        return field.conditions.every((condition: any) => {
            return formData[condition.field] === condition.equals;
        });
    }

    function getComponent(type: string): Component {
        return fieldComponents[type] ?? TextField;
    }

</script>

<div class="space-y-6">
    {#each section.fields as field (field.key)}
        {#if shouldShow(field)}
            {@const FieldComponent = getComponent(field.type)}
            <FieldComponent
                {field}
                bind:value={formData[field.key]}
                error={errors[field.key] ?? ''}
                onblur={() => onFieldBlur?.(field.key)}
            />
        {/if}
    {/each}
</div>
