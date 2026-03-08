<script lang="ts">
    import { onMount } from 'svelte';
    import { fly } from 'svelte/transition';
    import { page } from '@inertiajs/svelte';
    import { Form } from '@inertiajs/svelte';
    import { Button } from '@/components/ui/button';
    import { Label } from '@/components/ui/label';
    import { Separator } from '@/components/ui/separator';
    import AppLogoAnimated from '@/components/AppLogoAnimated.svelte';
    import AppLogoIcon from '@/components/AppLogoIcon.svelte';
    import LocaleToggle from '@/components/intake/LocaleToggle.svelte';
    import { requestLink } from '@/routes/intake';

    let { locale = 'en' }: { locale?: string } = $props();

    const t = {
        getStarted: { en: 'Get Started', es: 'Comenzar' },
        enterEmail: {
            en: "Enter your email address to begin your intake forms. We'll send you a secure link to access your paperwork.",
            es: 'Ingrese su correo electrónico para comenzar sus formularios. Le enviaremos un enlace seguro para acceder a su documentación.',
        },
        emailAddress: { en: 'Email Address', es: 'Correo Electrónico' },
        placeholder: { en: 'parent@example.com', es: 'padre@ejemplo.com' },
        sending: { en: 'Sending...', es: 'Enviando...' },
        sendLink: { en: 'Send Secure Link', es: 'Enviar Enlace Seguro' },
        alreadyHaveLink: {
            en: 'Already have a link? Check your email for a previous access link.',
            es: '¿Ya tiene un enlace? Revise su correo electrónico para un enlace de acceso anterior.',
        },
        welcomeHeading: {
            en: 'Welcome to Your Intake Journey',
            es: 'Bienvenido a Su Proceso de Admisión',
        },
        welcomeBody: {
            en: "We've made the intake process simple and secure. Complete your forms at your own pace from any device.",
            es: 'Hemos simplificado el proceso de admisión. Complete sus formularios a su propio ritmo desde cualquier dispositivo.',
        },
        secureTitle: { en: 'Secure & Private', es: 'Seguro y Privado' },
        secureDesc: {
            en: 'Your information is encrypted and HIPAA compliant',
            es: 'Su información está encriptada y cumple con HIPAA',
        },
        saveTitle: { en: 'Save & Resume', es: 'Guardar y Continuar' },
        saveDesc: {
            en: 'Your progress is saved automatically — pick up where you left off',
            es: 'Su progreso se guarda automáticamente — continúe donde lo dejó',
        },
        bilingualTitle: { en: 'Bilingual Support', es: 'Soporte Bilingüe' },
        bilingualDesc: {
            en: 'Available in English and Spanish',
            es: 'Disponible en inglés y español',
        },
    } as const;

    let contentVisible = $state(false);
    let desktopTextVisible = $state(false);

    onMount(() => {
        setTimeout(() => { contentVisible = true; }, 200);
        setTimeout(() => { desktopTextVisible = true; }, 500);
    });
</script>

<div class="flex min-h-screen flex-col lg:flex-row">
    <div class="fixed top-4 right-4 z-50">
        <LocaleToggle {locale} authenticated={false} />
    </div>

    <!-- Left Panel: Form -->
    <div class="flex min-h-screen w-full flex-col justify-center px-6 py-12 sm:px-12 lg:min-h-0 lg:w-[40%] lg:px-16 xl:px-24">
        <div class="mx-auto w-full max-w-md space-y-8">
            <!-- Branding - stagger 0 -->
            <div
                class="stagger-item space-y-2"
                style="transition-delay: 0ms;"
                class:visible={contentVisible}
            >
                <div class="flex items-center gap-3">
                    <AppLogoIcon class="size-10" />
                    <h1 class="text-2xl font-bold tracking-tight text-foreground">Acorn</h1>
                </div>
                <p class="text-sm text-muted-foreground">
                    JumpStart Autism Collective
                </p>
            </div>

            <!-- Separator - stagger 1 -->
            <div
                class="stagger-item"
                style="transition-delay: 80ms;"
                class:visible={contentVisible}
            >
                <Separator />
            </div>

            <!-- Heading - stagger 2 -->
            <div
                class="stagger-item space-y-2"
                style="transition-delay: 160ms;"
                class:visible={contentVisible}
            >
                <h2 class="text-xl font-semibold text-foreground">{t.getStarted[locale]}</h2>
                <p class="text-sm text-muted-foreground">
                    {t.enterEmail[locale]}
                </p>
            </div>

            <!-- Flash Messages -->
            {#if $page.props.flash?.status}
                <div
                    in:fly={{ y: -8, duration: 300 }}
                    class="flex items-start gap-3 rounded-lg border border-primary/20 bg-primary/5 p-4"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 size-4 shrink-0 text-primary" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                    </svg>
                    <p class="text-sm text-primary">{$page.props.flash.status}</p>
                </div>
            {/if}

            {#if $page.props.flash?.error}
                <div
                    in:fly={{ y: -8, duration: 300 }}
                    class="flex items-start gap-3 rounded-lg border border-destructive/20 bg-destructive/5 p-4"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 size-4 shrink-0 text-destructive" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                    </svg>
                    <p class="text-sm text-destructive">{$page.props.flash.error}</p>
                </div>
            {/if}

            <!-- Email Label - stagger 3 -->
            <Form action={requestLink.url()} method="post" class="space-y-4" let:errors let:processing>
                <div
                    class="stagger-item space-y-2"
                    style="transition-delay: 240ms;"
                    class:visible={contentVisible}
                >
                    <Label for="email">{t.emailAddress[locale]}</Label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        placeholder={t.placeholder[locale]}
                        required
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    />
                    {#if errors.email}
                        <p class="text-sm text-destructive">{errors.email}</p>
                    {/if}
                </div>

                <!-- Button - stagger 4 -->
                <div
                    class="stagger-item"
                    style="transition-delay: 320ms;"
                    class:visible={contentVisible}
                >
                    <Button type="submit" class="w-full" size="lg" disabled={processing}>
                        {processing ? t.sending[locale] : t.sendLink[locale]}
                    </Button>
                </div>
            </Form>

            <!-- Helper text - stagger 5 -->
            <p
                class="stagger-item text-center text-xs text-muted-foreground"
                style="transition-delay: 400ms;"
                class:visible={contentVisible}
            >
                {t.alreadyHaveLink[locale]}
            </p>
        </div>
    </div>

    <!-- Right Panel: Design / Messaging -->
    <div class="relative hidden flex-1 flex-col items-center justify-center overflow-hidden bg-primary/5 lg:flex">
        <div class="absolute -top-24 -right-24 size-96 rounded-full bg-accent/20"></div>
        <div class="absolute -bottom-32 -left-32 size-[28rem] rounded-full bg-primary/10"></div>

        <div class="relative z-10 flex flex-col items-center gap-10 px-12">
            <div class="size-48 xl:size-56 drop-shadow-2xl">
                <AppLogoAnimated class="size-full" />
            </div>

            <!-- Desktop messaging - staggers after animation -->
            <div class="flex max-w-sm flex-col items-center gap-6 text-center">
                <h2
                    class="stagger-item text-2xl font-bold tracking-tight text-foreground xl:text-3xl"
                    style="transition-delay: 0ms;"
                    class:visible={desktopTextVisible}
                >
                    {t.welcomeHeading[locale]}
                </h2>
                <p
                    class="stagger-item text-sm leading-relaxed text-muted-foreground"
                    style="transition-delay: 100ms;"
                    class:visible={desktopTextVisible}
                >
                    {t.welcomeBody[locale]}
                </p>

                <div class="grid w-full gap-4 text-left">
                    <div
                        class="stagger-item flex items-start gap-3"
                        style="transition-delay: 200ms;"
                        class:visible={desktopTextVisible}
                    >
                        <div class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full bg-primary/10">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5 text-primary" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-foreground">{t.secureTitle[locale]}</p>
                            <p class="text-xs text-muted-foreground">{t.secureDesc[locale]}</p>
                        </div>
                    </div>
                    <div
                        class="stagger-item flex items-start gap-3"
                        style="transition-delay: 300ms;"
                        class:visible={desktopTextVisible}
                    >
                        <div class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full bg-primary/10">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5 text-primary" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-foreground">{t.saveTitle[locale]}</p>
                            <p class="text-xs text-muted-foreground">{t.saveDesc[locale]}</p>
                        </div>
                    </div>
                    <div
                        class="stagger-item flex items-start gap-3"
                        style="transition-delay: 400ms;"
                        class:visible={desktopTextVisible}
                    >
                        <div class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full bg-primary/10">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5 text-primary" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-foreground">{t.bilingualTitle[locale]}</p>
                            <p class="text-xs text-muted-foreground">{t.bilingualDesc[locale]}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .stagger-item {
        opacity: 0;
        transform: translateY(12px);
        transition: opacity 400ms ease-out, transform 400ms ease-out;
    }
    .stagger-item.visible {
        opacity: 1;
        transform: translateY(0);
    }
</style>
