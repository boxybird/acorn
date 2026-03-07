<script lang="ts">
    import FormRenderer from '@/components/intake/FormRenderer.svelte';
    import AppLogoIcon from '@/components/AppLogoIcon.svelte';
    import { save, complete } from '@/routes/intake/form';
    import { dashboard } from '@/routes/intake';

    let { schema, savedData }: { schema: Record<string, any>; savedData: Record<string, any> } = $props();

    const locale = 'en';
    const schemaKey = schema.key as string;
</script>

<div class="flex min-h-screen flex-col bg-background">
    <header class="border-b px-4 py-4">
        <div class="mx-auto flex max-w-2xl items-center gap-3">
            <AppLogoIcon class="size-8" />
            <span class="text-lg font-bold text-foreground">Acorn</span>
        </div>
    </header>

    <main class="mx-auto w-full max-w-2xl flex-1 p-4 py-8">
        <FormRenderer
            {schema}
            {savedData}
            {locale}
            saveUrl={save.url(schemaKey)}
            completeUrl={complete.url(schemaKey)}
            dashboardUrl={dashboard.url()}
        />
    </main>
</div>
