<script lang="ts">
    import { Label } from '@/components/ui/label';
    import { Button } from '@/components/ui/button';

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

    let canvas: HTMLCanvasElement;
    let isDrawing = false;

    function startDrawing(event: MouseEvent | TouchEvent) {
        isDrawing = true;
        const ctx = canvas.getContext('2d');
        if (!ctx) return;
        const rect = canvas.getBoundingClientRect();
        const point = 'touches' in event ? event.touches[0] : event;
        ctx.beginPath();
        ctx.moveTo(point.clientX - rect.left, point.clientY - rect.top);
    }

    function draw(event: MouseEvent | TouchEvent) {
        if (!isDrawing) return;
        const ctx = canvas.getContext('2d');
        if (!ctx) return;
        const rect = canvas.getBoundingClientRect();
        const point = 'touches' in event ? event.touches[0] : event;
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
        ctx.strokeStyle = 'var(--foreground, #000)';
        ctx.lineTo(point.clientX - rect.left, point.clientY - rect.top);
        ctx.stroke();
    }

    function stopDrawing() {
        if (!isDrawing) return;
        isDrawing = false;
        value = canvas.toDataURL('image/png');
        if (onblur) onblur();
    }

    function clear() {
        const ctx = canvas.getContext('2d');
        if (!ctx) return;
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        value = '';
    }
</script>

<div class="space-y-2">
    <Label for={field.key}>{field.label[locale]}</Label>
    <div class="rounded-md border border-input bg-background p-1">
        <canvas
            bind:this={canvas}
            width="400"
            height="150"
            class="w-full cursor-crosshair touch-none"
            onmousedown={startDrawing}
            onmousemove={draw}
            onmouseup={stopDrawing}
            onmouseleave={stopDrawing}
            ontouchstart={startDrawing}
            ontouchmove={draw}
            ontouchend={stopDrawing}
        ></canvas>
    </div>
    <Button variant="outline" size="sm" onclick={clear}>
        {{ en: 'Clear', es: 'Borrar' }[locale]}
    </Button>
    {#if error}
        <p class="text-sm text-destructive">{error}</p>
    {/if}
</div>
