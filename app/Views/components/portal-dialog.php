<dialog id="portal-dialog" aria-labelledby="dialog-title" aria-describedby="dialog-description" class="fixed inset-0 m-auto w-[calc(100%-2.5rem)] max-w-md rounded-2xl border border-slate-200 bg-white p-6 text-slate-800 shadow-xl">
    <div class="flex items-start justify-between gap-4">
        <span class="flex size-11 items-center justify-center rounded-xl bg-teal-50 text-teal-700"><?= icon('info', 'size-6') ?></span>
        <button type="button" data-close-dialog aria-label="Tutup dialog" class="-mr-2 -mt-2 flex size-11 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100"><?= icon('close', 'size-5') ?></button>
    </div>
    <p class="mt-4 text-xs font-semibold uppercase tracking-wide text-teal-700">Informasi portal</p>
    <h2 id="dialog-title" class="mt-1 text-lg font-bold"></h2>
    <p id="dialog-description" class="mt-3 text-sm leading-relaxed text-slate-600"></p>
    <form method="dialog" class="mt-6"><button class="min-h-11 rounded-lg bg-teal-800 px-5 text-xs font-semibold text-white hover:bg-teal-900">Kembali ke portal</button></form>
</dialog>
