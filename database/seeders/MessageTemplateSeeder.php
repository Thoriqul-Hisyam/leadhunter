<?php

namespace Database\Seeders;

use App\Models\MessageTemplate;
use Illuminate\Database\Seeder;

class MessageTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            // DENTIST - Email (Indonesian)
            [
                'name' => 'Penawaran Website Klinik Gigi (Formal)',
                'channel' => 'email',
                'niche' => 'dentist',
                'language' => 'id',
                'tone' => 'formal',
                'subject' => 'Peluang Meningkatkan Kredibilitas Online {{business_name}}',
                'body' => "Yth. Pimpinan {{business_name}},\n\nPerkenalkan, nama saya {{sender_name}} ({{company_website}}). Kami adalah tim pengembang digital yang berfokus membantu profesional medis meningkatkan kehadiran digital mereka.\n\nKami sangat mengagumi dedikasi klinik Anda di {{city}} dalam memberikan layanan kesehatan gigi terbaik. Namun, kami melihat bahwa {{business_name}} belum memiliki platform website resmi yang dapat memudahkan calon pasien untuk melihat jadwal praktik, berkonsultasi secara online, atau memesan janji temu secara mandiri.\n\nMelalui {{offer}}, kami ingin membantu klinik Anda memiliki website modern yang dilengkapi sistem reservasi janji temu dan informasi layanan lengkap. Platform ini akan meningkatkan efisiensi administrasi klinik Anda sekaligus memudahkan pasien baru menemukan layanan Anda di mesin pencari Google.\n\nApakah Anda bersedia meluangkan waktu 10-15 menit untuk diskusi santai via Zoom/WhatsApp minggu ini?\n\nHormat kami,\n{{sender_name}}",
                'is_active' => true,
            ],
            // DENTIST - WhatsApp (Indonesian)
            [
                'name' => 'Outreach WA Klinik Gigi (Formal)',
                'channel' => 'whatsapp',
                'niche' => 'dentist',
                'language' => 'id',
                'tone' => 'formal',
                'subject' => null,
                'body' => "Selamat siang, Yth. Pimpinan {{business_name}} di {{city}}.\n\nPerkenalkan saya {{sender_name}}. Kami adalah penyedia solusi digitalisasi bisnis.\n\nKami memperhatikan reputasi pelayanan luar biasa dari {{business_name}}. Agar memudahkan pasien di {{city}} melakukan pendaftaran online tanpa harus antre lama, kami ingin menawarkan solusi pembuatan website klinik profesional yang dilengkapi fitur booking system terintegrasi melalui {{offer}} kami.\n\nJika Bapak/Ibu pimpinan berkenan, bolehkah saya mengirimkan proposal singkat mengenai kerja sama pembuatan website ini via WhatsApp?\n\nTerima kasih atas waktu dan perhatiannya.",
                'is_active' => true,
            ],
            // CAFE - Email (Indonesian)
            [
                'name' => 'Cafe Menu & Booking System Pitch (Casual)',
                'channel' => 'email',
                'niche' => 'cafe',
                'language' => 'id',
                'tone' => 'casual',
                'subject' => 'Kolaborasi Seru: Bikin Menu & Reservasi {{business_name}} Jadi Digital!',
                'body' => "Halo Tim {{business_name}}!\n\nSemoga hari kalian menyenangkan ya. Salam kenal, aku {{sender_name}}.\n\nSebagai penikmat kopi, aku suka banget sama vibes {{business_name}} yang ada di {{city}}. Tempatnya estetik dan menunya juga kelihatan lezat banget!\n\nBiar pelanggan makin gampang buat lihat menu terupdate, booking tempat buat event, atau bahkan pesan delivery langsung tanpa potongan biaya aplikasi pihak ketiga, kami mau bantu buatkan website cafe yang kekinian dan super interaktif lewat {{offer}}.\n\nDengan website cafe ini, {{business_name}} bisa punya branding yang lebih kuat dan pastinya mempermudah cafe enthusiasts di {{city}} buat nemuin tempat nongkrong kalian.\n\nKira-kira kapan nih ada waktu luang buat ngobrol santai lewat WhatsApp? Aku pengen share beberapa ide seru buat digitalisasi cafe kalian.\n\nCheers,\n{{sender_name}}",
                'is_active' => true,
            ],
            // CAFE - WhatsApp (Indonesian)
            [
                'name' => 'Outreach WA Cafe (Friendly)',
                'channel' => 'whatsapp',
                'niche' => 'cafe',
                'language' => 'id',
                'tone' => 'friendly',
                'subject' => null,
                'body' => "Halo Kak! Salam hangat untuk tim {{business_name}} di {{city}} 👋\n\nAku {{sender_name}}. Suka banget deh lihat postingan menu dan suasana estetik di {{business_name}}!\n\nBiar pelanggan makin gampang pesan meja atau cek menu favorit mereka lewat handphone, kami punya solusi {{offer}} yang praktis khusus untuk cafe.\n\nBoleh aku kirim brosur website cafe yang menarik dan harga spesial khusus bulan ini untuk {{business_name}}? Siapa tahu cocok Kak! Terima kasih banyak ya.",
                'is_active' => true,
            ],
            // AGENCY - Email (English)
            [
                'name' => 'Agency Partnership & Digital Scaling (Formal)',
                'channel' => 'email',
                'niche' => 'digital agency',
                'language' => 'en',
                'tone' => 'formal',
                'subject' => 'Scaling Up {{business_name}} with Premium Web Capabilities',
                'body' => "Dear Founder of {{business_name}},\n\nI hope this email finds you well.\n\nMy name is {{sender_name}}. We specialize in engineering high-performance, conversion-optimized websites for growing brands.\n\nWe have been following {{business_name}}'s agency journey in {{city}} and deeply respect the creative campaigns you run. To help you offer even more value to your clients, we would love to act as your technical web development partner.\n\nThrough our premium {{offer}}, we can build state-of-the-art landing pages, corporate websites, or custom e-commerce solutions for your agency, letting you focus fully on creative strategy and client relations while we handle the technical heavy lifting.\n\nWould you be open to a quick 10-minute partnership discovery call this Thursday at 2 PM?\n\nSincerely,\n{{sender_name}}",
                'is_active' => true,
            ],
            // DENTIST - English WhatsApp
            [
                'name' => 'Dentist Booking System (English WhatsApp)',
                'channel' => 'whatsapp',
                'niche' => 'dentist',
                'language' => 'en',
                'tone' => 'friendly',
                'subject' => null,
                'body' => "Hello team {{business_name}}! Hope you are having a productive day.\n\nI'm {{sender_name}}. We build professional patient booking websites for dental clinics.\n\nWe noticed {{business_name}} in {{city}} is highly rated. To make it extremely easy for your patients to schedule their appointments online 24/7, we want to offer our premium {{offer}}.\n\nWould you be interested in a short PDF demo of our booking system? Have a wonderful day!",
                'is_active' => true,
            ]
        ];

        foreach ($templates as $tpl) {
            // Idempotent: seeding ulang tidak membuat duplikat dan tidak menimpa hasil edit.
            MessageTemplate::firstOrCreate(
                ['name' => $tpl['name'], 'channel' => $tpl['channel']],
                $tpl
            );
        }
    }
}
