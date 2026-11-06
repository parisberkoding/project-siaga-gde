{{-- Dialog keputusan Setujui/Tolak untuk satu atau beberapa permohonan; diisi oleh resources/js/lib/decisions.js. --}}
<x-ui.dialog id="decisionDialog" title="Keputusan permohonan" size="md">
    <div class="grid gap-4">
        <div data-decision-banner class="flex items-center gap-3 rounded-lg p-3 text-sm">
            <span data-decision-icon class="flex size-9 shrink-0 items-center justify-center rounded-full text-base"></span>
            <p data-decision-text></p>
        </div>

        <ul data-decision-list class="grid max-h-60 gap-2 overflow-y-auto"></ul>

        <x-ui.field label="Komentar admin" for="decisionNote" hint="Opsional. Tampil ke pemohon bersama keputusan ini.">
            <x-ui.textarea id="decisionNote" data-decision-note rows="3" maxlength="500" class="h-20 resize-none" placeholder="Contoh: voucher berlaku 3 hari" />
        </x-ui.field>
    </div>
    <x-slot:footer>
        <x-ui.button variant="outline" data-dialog-close>Batal</x-ui.button>
        <x-ui.button data-decision-confirm class="data-[variant=destructive]:bg-destructive data-[variant=destructive]:hover:bg-destructive/90 data-[variant=success]:bg-success data-[variant=success]:hover:bg-success/90"></x-ui.button>
    </x-slot:footer>
</x-ui.dialog>
