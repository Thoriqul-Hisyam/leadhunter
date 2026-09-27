{{-- Tulis template dengan AI: membaca niche/channel/bahasa/nada dari form, lalu mengisi subjek & isi. --}}
<div class="p-4 rounded-xl border border-indigo-500/20 bg-indigo-500/5 space-y-2.5">
    <div class="flex items-center gap-2">
        <x-icon name="sparkles" class="w-4 h-4 text-indigo-500" />
        <span class="text-xs font-bold text-indigo-700 dark:text-indigo-300">Tulis dengan AI</span>
        <span class="text-[10px] text-slate-500 dark:text-slate-400">Memakai niche, channel, bahasa, dan nada di atas.</span>
    </div>
    <div class="flex flex-col md:flex-row gap-2">
        <input type="text" id="ai-template-instruction" class="form-input text-xs flex-1" maxlength="1000"
               placeholder="Opsional: arahan tambahan, mis. 'tekankan booking online', 'lebih santai', 'sebut promo gratis desain'">
        <button type="button" id="ai-template-btn" class="btn-primary py-2 px-4 text-xs font-bold shrink-0">
            <x-icon name="sparkles" class="w-3.5 h-3.5" />
            <span>Generate</span>
        </button>
    </div>
    <p id="ai-template-status" class="hidden text-[10px] font-semibold"></p>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('ai-template-btn');
    const status = document.getElementById('ai-template-status');
    if (!btn) return;

    const value = (id) => (document.getElementById(id)?.value || '').trim();
    const setStatus = (text, ok) => {
        status.textContent = text;
        status.className = 'text-[10px] font-semibold ' + (ok ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400');
    };

    btn.addEventListener('click', async () => {
        const niche = value('niche');
        if (!niche) {
            setStatus('Isi niche terlebih dahulu (misalnya: klinik gigi, cafe).', false);
            document.getElementById('niche')?.focus();
            return;
        }

        const body = document.getElementById('body');
        if (body.value.trim() && !confirm('Isi template saat ini akan diganti dengan hasil AI. Lanjutkan?')) {
            return;
        }

        const label = btn.querySelector('span');
        btn.disabled = true;
        label.textContent = 'Menulis...';
        setStatus('AI sedang menulis template, bisa memakan waktu hingga 1–2 menit.', true);

        try {
            const response = await window.postJson(@js(route('templates.generate')), {
                channel: value('channel') || 'email',
                niche: niche,
                language: value('language') || 'id',
                tone: value('tone') || 'formal',
                instruction: value('ai-template-instruction'),
            });
            const data = await response.json();

            if (!response.ok || data.status !== 'success') {
                setStatus(data.message || Object.values(data.errors || {})[0]?.[0] || 'Gagal generate template.', false);
                return;
            }

            if (data.subject !== null && document.getElementById('subject')) {
                document.getElementById('subject').value = data.subject;
            }
            body.value = data.body;
            body.dispatchEvent(new Event('input', { bubbles: true }));
            setStatus('Template terisi. Periksa dan sesuaikan sebelum disimpan.', true);
        } catch (e) {
            setStatus('Tidak bisa menghubungi server: ' + e.message, false);
        } finally {
            btn.disabled = false;
            label.textContent = 'Generate';
        }
    });
});
</script>
