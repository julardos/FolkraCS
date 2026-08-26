<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\KnowledgeBase;
use App\Models\PromptBlock;
use Inertia\Inertia;

class OnboardingController extends Controller
{
    public function index()
    {
        $client = Client::where('tenant_id', tenant('id'))->first();

        $waConfigured = $client?->wa_session !== null;
        $kbReady      = $client && KnowledgeBase::where('client_id', $client->id)->exists();
        $aiConfigured = $client && (
            !empty($client->ai_instruction) ||
            PromptBlock::where('client_id', $client->id)->where('is_enabled', true)->exists()
        );

        return Inertia::render('Tenant/Onboarding', [
            'steps' => [
                [
                    'key'    => 'account',
                    'label'  => 'Masuk ke akun Anda',
                    'desc'   => 'Berhasil! Akun FolkraCS Anda sudah aktif.',
                    'done'   => true,
                    'href'   => null,
                    'action' => null,
                ],
                [
                    'key'    => 'whatsapp',
                    'label'  => 'Hubungkan WhatsApp',
                    'desc'   => 'Scan QR code untuk menghubungkan nomor WhatsApp Business Anda. Buka WhatsApp → Perangkat Tertaut → Tautkan Perangkat.',
                    'done'   => $waConfigured,
                    'href'   => '/connections',
                    'action' => 'Buka Koneksi',
                ],
                [
                    'key'    => 'knowledge',
                    'label'  => 'Isi Knowledge Base',
                    'desc'   => 'Masukkan informasi produk, layanan, harga, dan FAQ bisnis Anda. Semakin lengkap datanya, semakin akurat jawaban AI.',
                    'done'   => $kbReady,
                    'href'   => '/knowledge-base',
                    'action' => 'Buka Knowledge Base',
                ],
                [
                    'key'    => 'ai',
                    'label'  => 'Konfigurasi asisten AI',
                    'desc'   => 'Atur nama, gaya bahasa, dan instruksi asisten agar sesuai dengan identitas bisnis Anda.',
                    'done'   => $aiConfigured,
                    'href'   => '/ai-settings',
                    'action' => 'Buka Pengaturan AI',
                ],
                [
                    'key'    => 'test',
                    'label'  => 'Uji coba — kirim pesan pertama',
                    'desc'   => 'Kirim pesan WhatsApp ke nomor Anda dan lihat AI membalas secara otomatis. Pantau percakapannya di halaman Percakapan.',
                    'done'   => false,
                    'href'   => '/conversations',
                    'action' => 'Lihat Percakapan',
                ],
            ],
        ]);
    }
}
