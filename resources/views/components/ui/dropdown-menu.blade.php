{{--
  shadcn/ui DropdownMenu di atas Popover API: menu tampil di top layer, jadi tidak terpotong wadah
  overflow (mis. tabel yang bisa digeser). Posisi diatur resources/js/ui/dropdown.js.
  Isi dengan <x-ui.dropdown-item> dan <x-ui.dropdown-separator />.
--}}
@props(['id', 'label' => 'Aksi', 'icon' => 'bi bi-three-dots'])

<button type="button" popovertarget="{{ $id }}" aria-haspopup="menu" aria-label="{{ $label }}" title="{{ $label }}"
        {{ $attributes->class('inline-flex size-8 cursor-pointer items-center justify-center rounded-md border bg-card text-muted-foreground shadow-xs transition-colors hover:bg-accent hover:text-foreground focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50') }}>
    <i class="{{ $icon }}"></i>
</button>
<div id="{{ $id }}" popover role="menu" data-dropdown
     class="m-0 min-w-48 rounded-lg border bg-popover p-1 text-left text-popover-foreground shadow-lg">
    {{ $slot }}
</div>
