<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import { Button } from '@/components/ui/button';
    import FormSection from './FormSection.svelte';

    let {
        schema,
        savedData = {},
        locale = 'en',
        saveUrl,
        completeUrl,
        dashboardUrl,
    }: {
        schema: Record<string, any>;
        savedData: Record<string, any>;
        locale: string;
        saveUrl: string;
        completeUrl: string;
        dashboardUrl: string;
    } = $props();

    function initializeFormData(): Record<string, any> {
        const data: Record<string, any> = { ...savedData };
        for (const section of schema.sections ?? []) {
            for (const field of section.fields ?? []) {
                if (data[field.key] === undefined) {
                    data[field.key] = field.type === 'checkbox' ? false : '';
                }
            }
        }
        return data;
    }

    let formData = $state<Record<string, any>>(initializeFormData());
    let errors = $state<Record<string, string>>({});
    let saveStatus = $state<'idle' | 'saving' | 'saved'>('idle');
    let saveTimeout: ReturnType<typeof setTimeout>;

    function autoSave() {
        clearTimeout(saveTimeout);
        saveStatus = 'saving';

        saveTimeout = setTimeout(() => {
            fetch(saveUrl, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify({ data: formData }),
                credentials: 'same-origin',
            }).then(() => {
                saveStatus = 'saved';
                setTimeout(() => { saveStatus = 'idle'; }, 2000);
            }).catch(() => {
                saveStatus = 'idle';
            });
        }, 1000);
    }

    function getCsrfToken(): string {
        const cookie = document.cookie
            .split('; ')
            .find((row) => row.startsWith('XSRF-TOKEN='));
        return cookie ? decodeURIComponent(cookie.split('=')[1]) : '';
    }

    function handleComplete() {
        router.post(completeUrl, { data: formData }, {
            onError: (formErrors) => {
                errors = {};
                for (const [key, message] of Object.entries(formErrors)) {
                    const fieldKey = key.replace('data.', '');
                    errors[fieldKey] = message as string;
                }
            },
        });
    }
</script>

<div class="space-y-8">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-foreground">{schema.title[locale]}</h1>
        <div class="flex items-center gap-3">
            {#if saveStatus === 'saving'}
                <span class="text-sm text-muted-foreground">
                    {locale === 'es' ? 'Guardando...' : 'Saving...'}
                </span>
            {:else if saveStatus === 'saved'}
                <span class="text-sm text-primary">
                    {locale === 'es' ? 'Guardado' : 'Saved'}
                </span>
            {/if}
        </div>
    </div>

    {#each schema.sections as section (section.key)}
        <FormSection
            {section}
            bind:formData
            {locale}
            {errors}
            onFieldBlur={() => autoSave()}
        />
    {/each}

    <div class="flex items-center justify-between border-t pt-6">
        <Button variant="outline" onclick={() => router.visit(dashboardUrl)}>
            {locale === 'es' ? 'Guardar y Salir' : 'Save & Exit'}
        </Button>
        <Button onclick={handleComplete}>
            {locale === 'es' ? 'Marcar como Completo' : 'Mark as Complete'}
        </Button>
    </div>
</div>
