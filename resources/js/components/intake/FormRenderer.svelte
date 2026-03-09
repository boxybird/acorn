<script lang="ts">
    import { page, router } from '@inertiajs/svelte';
    import { Button } from '@/components/ui/button';
    import { Card, CardContent } from '@/components/ui/card';
    import FormSection from './FormSection.svelte';

    let {
        schema,
        savedData = {},
        saveUrl,
        completeUrl,
        dashboardUrl,
        currentSectionIndex = $bindable(0),
        onSectionChange,
    }: {
        schema: Record<string, any>;
        savedData: Record<string, any>;
        saveUrl: string;
        completeUrl: string;
        dashboardUrl: string;
        currentSectionIndex?: number;
        onSectionChange?: (index: number) => void;
    } = $props();

    let t = $derived($page.props.translations as Record<string, string>);

    let sections: any[] = $derived(schema.sections ?? []);
    let currentSection = $derived(sections[currentSectionIndex]);
    let isFirstSection = $derived(currentSectionIndex === 0);
    let isLastSection = $derived(currentSectionIndex === sections.length - 1);
    let transitioning = $state(false);

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

    function goToSection(index: number) {
        if (index === currentSectionIndex || index < 0 || index >= sections.length) return;

        transitioning = true;
        setTimeout(() => {
            currentSectionIndex = index;
            onSectionChange?.(index);
            transitioning = false;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }, 150);

        autoSave();
    }

    export function handleNext() {
        if (!isLastSection) {
            goToSection(currentSectionIndex + 1);
        }
    }

    export function handlePrevious() {
        if (!isFirstSection) {
            goToSection(currentSectionIndex - 1);
        }
    }

    export function handleComplete() {
        router.post(completeUrl, { data: formData }, {
            onError: (formErrors) => {
                errors = {};
                for (const [key, message] of Object.entries(formErrors)) {
                    const fieldKey = key.replace('data.', '');
                    errors[fieldKey] = message as string;
                }

                // Navigate to the first section that has errors
                const currentSectionFields = currentSection.fields.map((f: any) => f.key);
                const hasErrorInCurrent = currentSectionFields.some((k: string) => errors[k]);
                if (!hasErrorInCurrent) {
                    for (let i = 0; i < sections.length; i++) {
                        const sectionFields = sections[i].fields.map((f: any) => f.key);
                        if (sectionFields.some((k: string) => errors[k])) {
                            goToSection(i);
                            break;
                        }
                    }
                }
            },
        });
    }

    export function navigateToSection(index: number) {
        goToSection(index);
    }
</script>

<div class="space-y-6">
    <!-- Header with title and save status -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-foreground">{schema.title}</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                {currentSection?.title}
            </p>
        </div>
        <div class="flex items-center gap-3">
            {#if saveStatus === 'saving'}
                <span class="text-xs text-muted-foreground">
                    {t.saving}
                </span>
            {:else if saveStatus === 'saved'}
                <span class="flex items-center gap-1 text-xs text-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                    </svg>
                    {t.saved}
                </span>
            {/if}
        </div>
    </div>

    <!-- Section content with fade transition -->
    <Card>
        <CardContent class="p-6 sm:p-8">
            <div
                class="transition-opacity duration-150"
                class:opacity-0={transitioning}
                class:opacity-100={!transitioning}
            >
                {#if currentSection}
                    {#key currentSection.key}
                        <FormSection
                            section={currentSection}
                            bind:formData
                            {errors}
                            onFieldBlur={() => autoSave()}
                        />
                    {/key}
                {/if}
            </div>
        </CardContent>
    </Card>

    <!-- Desktop navigation buttons -->
    <div class="hidden items-center justify-between lg:flex">
        <div>
            {#if isFirstSection}
                <Button variant="outline" onclick={() => router.visit(dashboardUrl)}>
                    {t.save_and_exit}
                </Button>
            {:else}
                <Button variant="outline" onclick={handlePrevious}>
                    {t.previous}
                </Button>
            {/if}
        </div>
        <div>
            {#if isLastSection}
                <Button onclick={handleComplete}>
                    {t.complete_form}
                </Button>
            {:else}
                <Button onclick={handleNext}>
                    {t.next}
                </Button>
            {/if}
        </div>
    </div>
</div>
