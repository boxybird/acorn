<script lang="ts">
    type CompletedForm = {
        key: string;
        title: string;
    };

    type NextForm = {
        key: string;
        title: string;
    } | null;

    type Progress = {
        completed: number;
        total: number;
    };

    let {
        completedForm,
        nextForm,
        progress,
    }: { completedForm: CompletedForm; nextForm: NextForm; progress: Progress } = $props();
</script>

<div>
    <h1>{completedForm.title} Complete</h1>
    <p>{progress.completed} of {progress.total} forms completed</p>
    {#if nextForm}
        <p>Next: {nextForm.title}</p>
    {/if}
</div>
