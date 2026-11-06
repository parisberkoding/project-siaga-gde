{{--
  shadcn/ui Table. Gaya thead/tr/th/td diturunkan dari tabel, jadi isi cukup ditulis dengan tag HTML biasa.
  :datatable="[...]" mengaktifkan pencarian/urutan/halaman (simple-datatables), [] untuk opsi bawaan.
  dense: baris lebih rapat (py-2) untuk tabel padat data.
--}}
@props(['datatable' => null, 'dense' => false])

<div data-slot="table-container" class="relative w-full overflow-x-auto">
    <table data-slot="table" @if (! is_null($datatable)) data-datatable="{{ json_encode((object) $datatable) }}" @endif {{ $attributes->class(
        'w-full caption-bottom text-sm
         [&_thead_tr]:border-b [&_thead]:bg-muted/50
         [&_tbody_tr:last-child]:border-0
         [&_tr]:border-b [&_tbody_tr]:transition-colors [&_tbody_tr:hover]:bg-muted/40
         [&_th]:h-10 [&_th]:whitespace-nowrap [&_th]:px-3 [&_th]:align-middle [&_th]:font-medium [&_th]:text-muted-foreground
         [&_td]:px-3 [&_td]:align-middle ' . ($dense ? '[&_td]:py-2' : '[&_td]:py-2.5')
    ) }}>
        {{ $slot }}
    </table>
</div>
