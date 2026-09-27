<?php

namespace App\Services\Replies;

use Illuminate\Support\Carbon;
use RuntimeException;
use Throwable;
use Webklex\PHPIMAP\ClientManager;

/**
 * Baca header email masuk via IMAP (Gmail: imap.gmail.com:993, SSL, App Password).
 */
class ImapMailbox
{
    /**
     * @param  callable(array $email): bool|null  $wantsBody  body hanya diunduh untuk email yang relevan (hemat bandwidth)
     * @return array<int, array{from: ?string, subject: ?string, in_reply_to: ?string, references: string, date: ?Carbon, body: ?string}>
     */
    public function fetchSince(Carbon $since, ?callable $wantsBody = null): array
    {
        $config = config('leadhunter.imap');

        if (empty($config['username']) || empty($config['password'])) {
            throw new RuntimeException('IMAP_USERNAME / IMAP_PASSWORD belum diisi (default memakai MAIL_USERNAME / MAIL_PASSWORD).');
        }

        $client = (new ClientManager)->make([
            'host' => $config['host'],
            'port' => $config['port'],
            'encryption' => $config['encryption'],
            'validate_cert' => true,
            'username' => $config['username'],
            'password' => $config['password'],
            'protocol' => 'imap',
        ]);

        $client->connect();

        try {
            $folder = $client->getFolderByPath($config['folder'] ?: 'INBOX');

            if (! $folder) {
                throw new RuntimeException("Folder IMAP \"{$config['folder']}\" tidak ditemukan.");
            }

            // Hanya header; jangan tandai email sebagai sudah dibaca.
            $messages = $folder->query()
                ->whereSince($since)
                ->setFetchBody(false)
                ->leaveUnread()
                ->get();

            $emails = [];

            foreach ($messages as $message) {
                $email = [
                    'from' => $this->attempt(fn () => $message->getFrom()->first()?->mail),
                    'subject' => $this->attempt(fn () => (string) $message->getSubject()),
                    'in_reply_to' => $this->attempt(fn () => (string) $message->getInReplyTo()),
                    'references' => (string) $this->attempt(fn () => (string) $message->getReferences()),
                    'date' => $this->attempt(fn () => Carbon::instance($message->getDate()->toDate())),
                    'body' => null,
                ];

                if ($wantsBody && $wantsBody($email)) {
                    $email['body'] = $this->attempt(fn () => mb_substr($message->parseBody()->getTextBody(), 0, 5000));
                }

                $emails[] = $email;
            }

            return $emails;
        } finally {
            $client->disconnect();
        }
    }

    protected function attempt(callable $callback)
    {
        try {
            return $callback();
        } catch (Throwable) {
            return null;
        }
    }
}
