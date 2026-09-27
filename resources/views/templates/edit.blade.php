@extends('layouts.app')

@section('title', 'Edit Template - Sandesa')
@section('header', 'Edit Template')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left Form Panel --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="glass-card p-6" style="animation: fadeInUp 0.4s ease backwards;">
                <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-100 dark:border-slate-800">
                    <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white shadow-md shadow-indigo-500/20">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Edit Outreach Template</h3>
                        <p class="text-[10px] text-slate-500 dark:text-slate-400">Modify properties of outreach template: {{ $template->name }}</p>
                    </div>
                </div>

                <form action="{{ route('templates.update', $template->id) }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="form-label text-xs">Template Name</label>
                            <input type="text" name="name" id="name" placeholder="e.g. Cafe Intro (Casual)" required class="form-input text-xs" value="{{ old('name', $template->name) }}">
                            @error('name') <p class="text-2xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="niche" class="form-label text-xs">Target Niche</label>
                            <input type="text" name="niche" id="niche" placeholder="e.g. cafe, dentist, salon, agency" required class="form-input text-xs" value="{{ old('niche', $template->niche) }}">
                            @error('niche') <p class="text-2xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="channel" class="form-label text-xs">Channel</label>
                            <x-searchable-select id="channel-select" inputId="channel" name="channel" :required="true" :selected="old('channel', $template->channel)" :options="['email' => 'Email', 'whatsapp' => 'WhatsApp']" triggerClass="form-select text-xs w-full" />
                            @error('channel') <p class="text-2xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="language" class="form-label text-xs">Language</label>
                            <x-searchable-select id="language-select" inputId="language" name="language" :required="true" :selected="old('language', $template->language)" :options="['id' => 'Indonesian (id)', 'en' => 'English (en)']" triggerClass="form-select text-xs w-full" />
                            @error('language') <p class="text-2xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="tone" class="form-label text-xs">Tone</label>
                            <x-searchable-select id="tone-select" inputId="tone" name="tone" :required="true" :selected="old('tone', $template->tone)" :options="['formal' => 'Formal / Professional', 'casual' => 'Casual / Relaxed', 'friendly' => 'Friendly / Warm']" triggerClass="form-select text-xs w-full" />
                            @error('tone') <p class="text-2xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    @include('templates.partials.ai-generator')

                    <div id="subject-group" class="transition-all duration-300">
                        <label for="subject" class="form-label text-xs">Email Subject Line</label>
                        <input type="text" name="subject" id="subject" placeholder="e.g. Special offer for @{{business_name}}" class="form-input text-xs" value="{{ old('subject', $template->subject) }}">
                        @error('subject') <p class="text-2xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="body" class="form-label text-xs">Message Body</label>
                        <textarea name="body" id="body" rows="12" required placeholder="Write your pitch template here... Hint: click on the placeholder chips on the right to inject variables!" class="form-input text-xs font-sans leading-relaxed resize-y">{{ old('body', $template->body) }}</textarea>
                        @error('body') <p class="text-2xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center gap-2 pt-2">
                        <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $template->is_active) ? 'checked' : '' }} class="form-checkbox h-4.5 w-4.5 rounded text-indigo-600 cursor-pointer">
                        <label for="is_active" class="text-xs font-bold text-slate-700 dark:text-slate-300 cursor-pointer">Active template (available for campaigns)</label>
                    </div>

                    <div class="flex gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                        <button type="submit" class="btn-primary py-2.5 px-6 font-bold rounded-xl shadow-lg shadow-indigo-500/20">
                            Save Changes
                        </button>
                        <a href="{{ route('templates.index') }}" class="btn-secondary py-2.5 px-5 rounded-xl text-xs font-semibold">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Right Placeholders Helper Panel --}}
        <div class="lg:col-span-1 space-y-6">
            <div class="glass-card p-6 sticky top-24" style="animation: fadeInUp 0.4s ease backwards; animation-delay: 0.08s;">
                <h4 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider mb-2 flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5"><x-icon name="light-bulb" class="w-3.5 h-3.5" /> Placeholder Helper</span>
                </h4>
                <p class="text-2xs text-slate-500 dark:text-slate-400 leading-normal mb-5">Click on any chip below to automatically insert the variable tag into your subject or body editor at your cursor position.</p>

                <div class="space-y-3.5">
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2">Lead Information</p>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" onclick="injectPlaceholder('@{{business_name}}')" class="px-2.5 py-1.5 rounded-lg border border-indigo-500/20 bg-indigo-500/5 hover:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 text-2xs font-bold transition flex flex-col items-start w-full">
                                <span class="font-mono text-2xs font-bold">@{{business_name}}</span>
                                <span class="text-[9px] font-normal text-slate-400 dark:text-slate-500 mt-0.5">Nama bisnis dari Google Maps</span>
                            </button>
                            <button type="button" onclick="injectPlaceholder('@{{city}}')" class="px-2.5 py-1.5 rounded-lg border border-indigo-500/20 bg-indigo-500/5 hover:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 text-2xs font-bold transition flex flex-col items-start w-full">
                                <span class="font-mono text-2xs font-bold">@{{city}}</span>
                                <span class="text-[9px] font-normal text-slate-400 dark:text-slate-500 mt-0.5">Kota tempat bisnis terdaftar</span>
                            </button>
                            <button type="button" onclick="injectPlaceholder('@{{niche}}')" class="px-2.5 py-1.5 rounded-lg border border-indigo-500/20 bg-indigo-500/5 hover:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 text-2xs font-bold transition flex flex-col items-start w-full">
                                <span class="font-mono text-2xs font-bold">@{{niche}}</span>
                                <span class="text-[9px] font-normal text-slate-400 dark:text-slate-500 mt-0.5">Kategori / niche bidang bisnis</span>
                            </button>
                            <button type="button" onclick="injectPlaceholder('@{{website}}')" class="px-2.5 py-1.5 rounded-lg border border-indigo-500/20 bg-indigo-500/5 hover:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 text-2xs font-bold transition flex flex-col items-start w-full">
                                <span class="font-mono text-2xs font-bold">@{{website}}</span>
                                <span class="text-[9px] font-normal text-slate-400 dark:text-slate-500 mt-0.5">Alamat website bisnis (jika ada)</span>
                            </button>
                            <button type="button" onclick="injectPlaceholder('@{{phone}}')" class="px-2.5 py-1.5 rounded-lg border border-indigo-500/20 bg-indigo-500/5 hover:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 text-2xs font-bold transition flex flex-col items-start w-full">
                                <span class="font-mono text-2xs font-bold">@{{phone}}</span>
                                <span class="text-[9px] font-normal text-slate-400 dark:text-slate-500 mt-0.5">Nomor telepon kontak bisnis</span>
                            </button>
                        </div>
                    </div>

                    <div>
                        <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2">Campaign Settings</p>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" onclick="injectPlaceholder('@{{offer}}')" class="px-2.5 py-1.5 rounded-lg border border-indigo-500/20 bg-indigo-500/5 hover:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 text-2xs font-bold transition flex flex-col items-start w-full">
                                <span class="font-mono text-2xs font-bold">@{{offer}}</span>
                                <span class="text-[9px] font-normal text-slate-400 dark:text-slate-500 mt-0.5">Layanan penawaran Anda (misal: Website)</span>
                            </button>
                            <button type="button" onclick="injectPlaceholder('@{{sender_name}}')" class="px-2.5 py-1.5 rounded-lg border border-indigo-500/20 bg-indigo-500/5 hover:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 text-2xs font-bold transition flex flex-col items-start w-full">
                                <span class="font-mono text-2xs font-bold">@{{sender_name}}</span>
                                <span class="text-[9px] font-normal text-slate-400 dark:text-slate-500 mt-0.5">Nama Anda selaku pengirim pesan</span>
                            </button>
                            <button type="button" onclick="injectPlaceholder('@{{company_name}}')" class="px-2.5 py-1.5 rounded-lg border border-indigo-500/20 bg-indigo-500/5 hover:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 text-2xs font-bold transition flex flex-col items-start w-full">
                                <span class="font-mono text-2xs font-bold">@{{company_name}}</span>
                                <span class="text-[9px] font-normal text-slate-400 dark:text-slate-500 mt-0.5">Nama usaha Anda (dari Pengaturan)</span>
                            </button>
                            <button type="button" onclick="injectPlaceholder('@{{company_website}}')" class="px-2.5 py-1.5 rounded-lg border border-indigo-500/20 bg-indigo-500/5 hover:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 text-2xs font-bold transition flex flex-col items-start w-full">
                                <span class="font-mono text-2xs font-bold">@{{company_website}}</span>
                                <span class="text-[9px] font-normal text-slate-400 dark:text-slate-500 mt-0.5">Website usaha Anda (dari Pengaturan)</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleSubjectField() {
        const channelSelect = document.getElementById('channel');
        const subjectGroup = document.getElementById('subject-group');
        const subjectInput = document.getElementById('subject');
        
        if (channelSelect.value === 'whatsapp') {
            subjectGroup.classList.add('hidden');
            subjectInput.value = '';
            subjectInput.required = false;
        } else {
            subjectGroup.classList.remove('hidden');
        }
    }

    function injectPlaceholder(tag) {
        const subjectEl = document.getElementById('subject');
        const bodyEl = document.getElementById('body');
        const activeEl = document.activeElement;
        
        // Check if cursor is in subject or body
        if (activeEl === subjectEl || activeEl === bodyEl) {
            const start = activeEl.selectionStart;
            const end = activeEl.selectionEnd;
            const text = activeEl.value;
            
            activeEl.value = text.substring(0, start) + tag + text.substring(end);
            activeEl.focus();
            
            // Reposition cursor
            const newCursor = start + tag.length;
            activeEl.setSelectionRange(newCursor, newCursor);
        } else {
            // Default to appending to body
            const start = bodyEl.selectionStart;
            const end = bodyEl.selectionEnd;
            const text = bodyEl.value;
            
            bodyEl.value = text.substring(0, start) + tag + text.substring(end);
            bodyEl.focus();
            
            const newCursor = start + tag.length;
            bodyEl.setSelectionRange(newCursor, newCursor);
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const channelInput = document.getElementById('channel');
        if (channelInput) {
            channelInput.addEventListener('change', toggleSubjectField);
        }
        toggleSubjectField();
    });
</script>
@endsection


