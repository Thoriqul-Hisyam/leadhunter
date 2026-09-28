<?php

namespace App\Services\WhatsApp;

class WhatsAppManager
{
    public const DRIVERS = [
        'manual' => 'Manual (click-to-chat wa.me)',
        'fonnte' => 'Fonnte',
        'wablas' => 'Wablas',
    ];

    public function driver(?string $name = null): WhatsAppGateway
    {
        $config = config('leadhunter.whatsapp');

        return match ($name ?? $config['driver'] ?? 'manual') {
            'fonnte' => new FonnteGateway($config['token'] ?? null),
            'wablas' => new WablasGateway($config['token'] ?? null, $config['base_url'] ?? null),
            default => new ManualGateway,
        };
    }

    public function isAutomatic(): bool
    {
        return $this->driver()->isAutomatic();
    }
}
