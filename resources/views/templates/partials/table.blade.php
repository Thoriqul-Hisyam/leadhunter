<div class="glass-card p-6" id="templates-table-wrap" style="animation: fadeInUp 0.4s ease backwards; animation-delay: 0.1s;">
    <div class="overflow-x-auto">
        <table class="fancy-table min-w-full">
            <thead>
                <tr>
                    <th style="width: 25%">Template Name</th>
                    <th style="width: 15%">Niche / Channel</th>
                    <th style="width: 15%">Tone / Lang</th>
                    <th style="width: 30%">Message Snippet</th>
                    <th style="width: 15%" class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($templates as $tpl)
                    <tr class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-all duration-200">
                        <td class="align-top py-4">
                            <div class="font-bold text-slate-900 dark:text-white leading-snug">{{ $tpl->name }}</div>
                            <div class="flex items-center gap-2 mt-2">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer" {{ $tpl->is_active ? 'checked' : '' }} onchange="toggleTemplateActive({{ $tpl->id }}, this)">
                                    <div class="w-7 h-4 bg-slate-200 dark:bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all dark:border-slate-600 peer-checked:bg-indigo-500"></div>
                                    <span class="ml-1.5 text-[9px] font-bold tracking-wider text-slate-400 dark:text-slate-500 uppercase status-label">{{ $tpl->is_active ? 'Active' : 'Inactive' }}</span>
                                </label>
                            </div>
                        </td>
                        <td class="align-top py-4">
                            <div class="flex flex-col gap-1.5 items-start">
                                <span class="px-2 py-0.5 text-[9px] font-bold rounded-md bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/15 capitalize">{{ $tpl->niche }}</span>
                                @if($tpl->channel === 'email')
                                    <span class="px-2 py-0.5 text-[9px] font-bold rounded-md bg-sky-500/10 text-sky-600 dark:text-sky-400 border border-sky-500/15 flex items-center gap-1">Email</span>
                                @else
                                    <span class="px-2 py-0.5 text-[9px] font-bold rounded-md bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/15 flex items-center gap-1">WhatsApp</span>
                                @endif
                            </div>
                        </td>
                        <td class="align-top py-4">
                            <div class="flex flex-col gap-1.5 items-start">
                                <span class="px-2 py-0.5 text-[9px] font-bold rounded-md bg-pink-500/10 text-pink-600 dark:text-pink-400 border border-pink-500/15 capitalize">{{ $tpl->tone }}</span>
                                <span class="px-2 py-0.5 text-[9px] font-bold rounded-md bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/15 uppercase">{{ $tpl->language }}</span>
                            </div>
                        </td>
                        <td class="align-top py-4 pr-4">
                            <div class="flex flex-col gap-1">
                                @if($tpl->subject)
                                    <div class="text-[10px] font-bold text-slate-800 dark:text-slate-200 truncate">Sbj: {{ $tpl->subject }}</div>
                                @endif
                                <div class="text-[11px] text-slate-500 dark:text-slate-400 leading-normal line-clamp-2 whitespace-pre-wrap">{{ $tpl->body }}</div>
                            </div>
                        </td>
                        <td class="align-top py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('templates.edit', $tpl->id) }}" class="p-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-indigo-500/20 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 transition" title="Edit Template">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                </a>
                                <form action="{{ route('templates.destroy', $tpl->id) }}" method="POST" class="inline" onsubmit="return handleConfirm(event, this, 'Delete Template?', 'Are you sure you want to permanently delete this template? Any pending outreach using this might lose its reference.', 'Yes, Delete')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-rose-500/25 hover:text-rose-500 text-slate-500 border border-slate-200 dark:border-slate-700 transition" title="Delete Template">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-12">
                            <div class="text-center flex flex-col items-center justify-center text-slate-400 dark:text-slate-500">
                                <svg class="w-12 h-12 mb-3 opacity-50 text-slate-350 dark:text-slate-750" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="font-bold text-sm">No Templates Found</p>
                                <p class="text-xs text-slate-400 dark:text-slate-500 mt-1 max-w-sm">Build your template library or seed prebuilt ones to speed up your marketing outreach flows.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $templates->links() }}
    </div>
</div>
