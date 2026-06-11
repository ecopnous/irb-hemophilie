<div class="mb-4 flex flex-col gap-2 px-1 sm:flex-row sm:items-center sm:justify-end">
    <div
        class="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
        <span class="text-slate-500 dark:text-slate-400">Total filtré :</span>
        <span class="ml-1 font-black text-slate-900 dark:text-white">{{ number_format($this->total ?? 0, 0, ',', ' ') }}</span>
    </div>
</div>
