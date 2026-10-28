{{-- shadcn/ui AlertDialog bersama, dipakai confirmDialog()/alertDialog() di resources/js/ui/dialog.js --}}
<dialog id="confirm-dialog" data-static aria-labelledby="confirm-dialog-title" class="w-[calc(100%-2rem)] max-w-md rounded-lg border bg-card p-0 text-card-foreground shadow-lg">
    <div class="grid gap-4 p-6">
        <div class="flex flex-col gap-2">
            <h2 id="confirm-dialog-title" data-confirm-title class="text-lg font-semibold leading-none"></h2>
            <div data-confirm-message class="whitespace-pre-line text-sm text-muted-foreground"></div>
        </div>
        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <x-ui.button variant="outline" data-confirm-cancel data-dialog-close></x-ui.button>
            <x-ui.button data-confirm-ok class="data-[variant=destructive]:bg-destructive data-[variant=destructive]:hover:bg-destructive/90 data-[variant=success]:bg-success data-[variant=success]:hover:bg-success/90"></x-ui.button>
        </div>
    </div>
</dialog>
