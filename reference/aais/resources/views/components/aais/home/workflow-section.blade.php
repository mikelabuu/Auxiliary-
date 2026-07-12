@props([
    'workflow' => [],
    'title' => 'Document Workflow',
    'processLabel' => '4-Step Process',
])

<section class="card" style="margin-top:24px;overflow:hidden;">
    <x-aais.ui.card-header
        :title="$title"
        icon="<svg fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><path d='M13 10V3L4 14h7v7l9-11h-7z'/></svg>"
    >
        <x-slot:actions>
            <span class="chip chip-muted">{{ $processLabel }}</span>
        </x-slot:actions>
    </x-aais.ui.card-header>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:0;">
        @foreach ($workflow as $i => $wf)
            <x-aais.home.workflow-step
                :number="$wf['number']"
                :title="$wf['title']"
                :description="$wf['description']"
                :bg="$wf['bg']"
                :border-color="$wf['borderColor']"
                :title-color="$wf['titleColor']"
                :show-right-border="$i < count($workflow) - 1"
            />
        @endforeach
    </div>
</section>
