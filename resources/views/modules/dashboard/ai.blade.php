@php
    $canOpen = Auth::user()->hasPermission('ai.view');
@endphp

<x-layouts.app title="Dashboard AI Assistant" :breadcrumbs="['Dashboard' => null]">
    <x-ui.page-header :kicker="$module->name" :icon="$module->icon ?: 'bi bi-robot'" title="Dashboard AI Assistant"
        description="Pemakaian asisten AI, jumlah percakapan, dan sesi terakhir yang bisa dibuka kembali.">
        @if ($canOpen)
            <x-slot:actions>
                <x-ui.button :href="route('ai-assistant.index')"><i class="bi bi-robot"></i> Buka AI Assistant</x-ui.button>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card label="Total Sesi" :value="number_format($totalSesi)" icon="bi bi-chat-dots" hint="Percakapan tersimpan" />
        <x-ui.stat-card label="Sesi Bulan Ini" :value="number_format($sesiBulanIni)" icon="bi bi-calendar-check" tone="success"
            :hint="'Sejak ' . now()->startOfMonth()->translatedFormat('d M Y')" />
        <x-ui.stat-card label="Total Pesan" :value="number_format($totalPesan)" icon="bi bi-chat-square-text" tone="warning" hint="Pesan pengguna & AI" />
        <x-ui.stat-card label="Pengguna Aktif" :value="number_format($penggunaAktif)" icon="bi bi-people" hint="Pernah membuka sesi" />
    </div>

    <x-ui.card class="gap-4">
        <x-ui.card-header title="Sesi Terakhir" icon="bi bi-clock-history" description="8 percakapan terakhir">
            @if ($canOpen && $sesiTerbaru->isNotEmpty())
                <x-slot:action><x-ui.button variant="ghost" size="sm" :href="route('ai-assistant.index')">Buka Assistant <i class="bi bi-arrow-right"></i></x-ui.button></x-slot:action>
            @endif
        </x-ui.card-header>
        <x-ui.card-content>
            @if ($sesiTerbaru->isEmpty())
                <x-ui.empty-state icon="bi bi-chat-square-dots" title="Belum ada percakapan"
                    description="Mulai bertanya ke AI Assistant untuk melihat ringkasan data SIAGA.">
                    @if ($canOpen)
                        <x-ui.button size="sm" class="mt-2" :href="route('ai-assistant.index')"><i class="bi bi-robot"></i> Mulai Percakapan</x-ui.button>
                    @endif
                </x-ui.empty-state>
            @else
                <x-ui.table>
                    <thead>
                        <tr>
                            <th>Judul Sesi</th>
                            <th>Pengguna</th>
                            <th class="text-right">Pesan</th>
                            <th>Terakhir Digunakan</th>
                            <th><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sesiTerbaru as $sesi)
                            <tr>
                                <td class="font-medium">{{ \Illuminate\Support\Str::limit($sesi->title ?: 'Tanpa judul', 48) }}</td>
                                <td>{{ $sesi->user?->nama_lengkap ?? '-' }}</td>
                                <td class="text-right tabular-nums">{{ number_format($sesi->messages_count) }}</td>
                                <td class="whitespace-nowrap text-muted-foreground">{{ $sesi->updated_at?->translatedFormat('d M Y H:i') ?? '-' }}</td>
                                <td class="text-right">
                                    <x-ui.button variant="ghost" size="sm" :href="route('ai-assistant.index', ['session' => $sesi->session_key])">
                                        <i class="bi bi-box-arrow-up-right"></i> Buka
                                    </x-ui.button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-ui.table>
            @endif
        </x-ui.card-content>
    </x-ui.card>
</x-layouts.app>
