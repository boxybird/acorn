<script lang="ts">
    import { Button } from '@/components/ui/button';

    let {
        currentStep,
        totalSteps,
        progressPercent = 0,
        isLastSection = false,
        locale = 'en',
        onPrevious,
        onNext,
        onComplete,
    }: {
        currentStep: number;
        totalSteps: number;
        progressPercent?: number;
        isLastSection?: boolean;
        locale?: string;
        onPrevious?: () => void;
        onNext?: () => void;
        onComplete?: () => void;
    } = $props();

    const circumference = 2 * Math.PI * 10;
    let strokeDashoffset = $derived(circumference - (progressPercent / 100) * circumference);
</script>

<div class="fixed inset-x-0 bottom-0 z-50 border-t bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/80 lg:hidden">
    <div class="flex items-center justify-between px-4 py-3">
        <!-- Previous -->
        <div class="w-24">
            {#if currentStep > 1}
                <Button variant="ghost" size="sm" onclick={onPrevious}>
                    {{ en: 'Previous', es: 'Anterior' }[locale]}
                </Button>
            {/if}
        </div>

        <!-- Center: Step indicator + progress ring -->
        <div class="flex items-center gap-2">
            <svg class="size-6 -rotate-90" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2" class="text-border" />
                <circle
                    cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"
                    class="text-primary transition-all duration-500"
                    stroke-dasharray={circumference}
                    stroke-dashoffset={strokeDashoffset}
                    stroke-linecap="round"
                />
            </svg>
            <span class="text-xs text-muted-foreground">
                {{ en: 'Step', es: 'Paso' }[locale]} {currentStep} / {totalSteps}
            </span>
        </div>

        <!-- Next / Complete -->
        <div class="w-24 text-right">
            {#if isLastSection}
                <Button size="sm" onclick={onComplete}>
                    {{ en: 'Complete', es: 'Completar' }[locale]}
                </Button>
            {:else}
                <Button variant="outline" size="sm" onclick={onNext}>
                    {{ en: 'Next', es: 'Siguiente' }[locale]}
                </Button>
            {/if}
        </div>
    </div>
</div>
