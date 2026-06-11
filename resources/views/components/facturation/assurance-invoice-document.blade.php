@props([
    'invoice',
    'hopital',
    'showToolbar' => true,
])

@php
    $assurance = $invoice['assurance'];
    $meta = $invoice['meta'];
    $totals = $invoice['totals'];
    $periodLabel = $invoice['period']['label'];
    $money = fn (float $amount) => number_format($amount, 2, '.', ' ') . ' $';
@endphp

<div {{ $attributes->merge(['class' => 'space-y-6']) }}>
    @if ($showToolbar)
        <div class="print:hidden flex flex-wrap items-center justify-between gap-3">
            <div class="text-sm text-slate-500 dark:text-slate-400">
                Document genere le {{ now()->format('d/m/Y H:i') }}
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" onclick="window.print()"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                    Imprimer
                </button>
            </div>
        </div>
    @endif

    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm print:border-0 print:shadow-none dark:border-slate-800 dark:bg-slate-950">
        <div class="grid gap-6 border-b border-slate-200 pb-6 md:grid-cols-2 dark:border-slate-800">
            <div>
                <p class="text-lg font-black uppercase tracking-wide text-slate-900 dark:text-white">{{ $hopital['name'] }}</p>
                <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $hopital['address'] }}</p>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Ref. {{ $hopital['reference'] }}</p>
                @if ($hopital['site_web'])
                    <p class="mt-1 text-sm text-sky-600 dark:text-sky-300">{{ $hopital['site_web'] }}</p>
                @endif
            </div>
            <div class="text-left md:text-right">
                <p class="text-2xl font-black uppercase tracking-[0.18em] text-slate-900 dark:text-white">Facture mensuelle</p>
                <p class="mt-2 text-xl font-bold text-sky-700 dark:text-sky-300">{{ strtoupper($assurance->name) }}</p>
                <p class="mt-1 text-sm font-semibold capitalize text-slate-600 dark:text-slate-300">{{ $periodLabel }}</p>
            </div>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2">
            <div class="rounded-2xl border border-slate-100 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/50">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Assurance</p>
                <dl class="mt-3 space-y-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Assurance</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white">{{ $assurance->name }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Forfait disponible</dt>
                        <dd class="font-semibold {{ $meta['forfait_disponible'] ? 'text-emerald-600' : 'text-slate-700 dark:text-slate-200' }}">
                            {{ $meta['forfait_disponible'] ? 'OUI' : 'NON' }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Categorie</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white">{{ $meta['categorie'] }}</dd>
                    </div>
                </dl>
            </div>
            <div class="rounded-2xl border border-slate-100 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/50">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Details paiements</p>
                <dl class="mt-3 space-y-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Nombre de patients</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white">{{ $meta['patients_count'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Prix par patient</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white">{{ $money((float) $meta['prix_patient']) }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Estimation total</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white">
                            {{ $meta['estimation_forfait'] > 0 ? $money((float) $meta['estimation_forfait']) : $money((float) $totals['a_payer']) }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="mt-8 overflow-x-auto">
            <table class="min-w-full border-collapse text-sm">
                <thead>
                    <tr class="border-b-2 border-slate-300 text-left text-xs font-black uppercase tracking-[0.16em] text-slate-500 dark:border-slate-700">
                        <th class="px-3 py-3">Reference</th>
                        <th class="px-3 py-3">Actes</th>
                        <th class="px-3 py-3">Departement</th>
                        <th class="px-3 py-3">Date</th>
                        <th class="px-3 py-3 text-right">Prix</th>
                        <th class="px-3 py-3 text-center">Forfaitise</th>
                        <th class="px-3 py-3 text-right">A payer</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoice['projets'] as $projet)
                        <tr class="bg-sky-50/80 dark:bg-sky-500/10">
                            <td colspan="7" class="px-3 py-3 text-xs font-black uppercase tracking-[0.18em] text-sky-800 dark:text-sky-200">
                                Projet : {{ $projet['name'] }}
                                <span class="ml-2 font-mono text-[11px] font-semibold text-sky-600 dark:text-sky-300">{{ $projet['reference'] }}</span>
                                <span class="float-right text-[11px] normal-case tracking-normal text-sky-700 dark:text-sky-300">
                                    Total projet : {{ $money((float) $projet['total']) }} | A payer : {{ $money((float) $projet['a_payer']) }}
                                </span>
                            </td>
                        </tr>

                        @foreach ($projet['patients'] as $patient)
                            <tr class="bg-slate-100/90 dark:bg-slate-900/70">
                                <td colspan="4" class="px-3 py-2 font-bold uppercase text-slate-800 dark:text-slate-100">
                                    N° {{ $patient['reference'] }} - {{ $patient['name'] }}
                                </td>
                                <td class="px-3 py-2 text-right font-bold text-slate-800 dark:text-slate-100">
                                    TOTAL : {{ $money((float) $patient['total']) }}
                                </td>
                                <td></td>
                                <td class="px-3 py-2 text-right font-bold text-slate-800 dark:text-slate-100">
                                    A PAYER : {{ $money((float) $patient['a_payer']) }}
                                </td>
                            </tr>

                            @foreach ($patient['lines'] as $line)
                                <tr class="border-b border-slate-100 dark:border-slate-800">
                                    <td class="px-3 py-2 font-mono text-xs text-slate-600 dark:text-slate-300">{{ $line['reference'] }}</td>
                                    <td class="px-3 py-2 text-slate-800 dark:text-slate-100">{{ $line['acte'] }}</td>
                                    <td class="px-3 py-2 text-slate-600 dark:text-slate-300">{{ $line['departement'] }}</td>
                                    <td class="px-3 py-2 text-slate-600 dark:text-slate-300">{{ $line['date']->format('d M, Y') }}</td>
                                    <td class="px-3 py-2 text-right font-semibold text-slate-900 dark:text-white">{{ $money((float) $line['prix']) }}</td>
                                    <td class="px-3 py-2 text-center">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-bold {{ $line['forfaitise'] ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300' }}">
                                            {{ $line['forfaitise'] ? 'OUI' : 'NON' }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-right font-semibold text-slate-900 dark:text-white">{{ $money((float) $line['a_payer']) }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-10 text-center text-slate-500 dark:text-slate-400">
                                Aucune prestation facturable pour cette periode.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-8 flex justify-end">
            <div class="w-full max-w-sm space-y-2 rounded-2xl border border-slate-200 bg-slate-50/80 p-4 text-sm dark:border-slate-800 dark:bg-slate-900/50">
                <div class="flex items-center justify-between gap-4">
                    <span class="font-semibold text-slate-600 dark:text-slate-300">Total general</span>
                    <span class="font-black text-slate-900 dark:text-white">{{ $money((float) $totals['general']) }}</span>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <span class="font-semibold text-emerald-700 dark:text-emerald-300">Total forfaitise</span>
                    <span class="font-black text-emerald-700 dark:text-emerald-300">- {{ $money((float) $totals['forfaitise']) }}</span>
                </div>
                <div class="flex items-center justify-between gap-4 border-t border-slate-200 pt-2 dark:border-slate-700">
                    <span class="font-black uppercase tracking-[0.12em] text-slate-700 dark:text-slate-200">Total a payer</span>
                    <span class="text-lg font-black text-slate-900 dark:text-white">{{ $money((float) $totals['a_payer']) }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
