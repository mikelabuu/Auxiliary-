@props([
    'latestActivity' => [],
    'offices' => [],
])

<section style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:18px;margin-top:24px;">
    <div class="card" style="overflow:hidden;">
        <x-aais.ui.card-header
            title="Latest Activity"
            icon="<svg fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><circle cx='12' cy='12' r='10'/><polyline points='12 6 12 12 16 14'/></svg>"
        />
        <div class="card-body" style="padding:16px 20px;">
            @foreach ($latestActivity as $item)
                <x-aais.ui.activity-item
                    :text="$item['text']"
                    :time="$item['time'] . ' today'"
                    :status="$item['status']"
                    :status-label="ucfirst($item['status'])"
                    :last="$loop->last"
                />
            @endforeach
        </div>
    </div>

    <div class="card" style="overflow:hidden;">
        <x-aais.ui.card-header
            title="Offices &amp; Hours"
            icon="<svg fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><path d='M3 21V8l9-5 9 5v13M9 21v-6h6v6'/></svg>"
        />
        <div class="card-body" style="padding:16px 20px;">
            @foreach ($offices as $office)
                <x-aais.home.office-row
                    :name="$office['name']"
                    :location="$office['location']"
                    :hours="$office['hours']"
                    :open="$office['open']"
                    :last="$loop->last"
                />
            @endforeach
        </div>
    </div>
</section>
