<?php

namespace App\Services;

use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Process\ExecutableFinder;

/**
 * Menjalankan script Node di folder scripts/ (Puppeteer scraper & crawler).
 */
class NodeScriptRunner
{
    /**
     * @param  callable(string $line): void|null  $onLine  dipanggil untuk setiap baris stdout
     */
    public function run(string $script, array $arguments, int $timeout, ?callable $onLine = null): ProcessResult
    {
        $command = array_merge([$this->nodeBinary(), base_path('scripts/'.$script)], array_map('strval', $arguments));

        $buffer = '';
        $streamed = false;

        $result = Process::path(base_path())
            ->timeout($timeout)
            ->env($this->environment())
            ->run($command, function (string $type, string $output) use (&$buffer, &$streamed, $onLine) {
                if ($type !== 'out' || ! $onLine) {
                    return;
                }

                $streamed = true;
                $buffer .= $output;
                $lines = explode("\n", $buffer);
                $buffer = array_pop($lines);

                foreach ($lines as $line) {
                    $onLine(trim($line));
                }
            });

        if ($onLine) {
            if ($streamed) {
                if (trim($buffer) !== '') {
                    $onLine(trim($buffer));
                }
            } else {
                // Process::fake() tidak memanggil callback output, jadi baca dari hasil akhirnya.
                foreach (explode("\n", $result->output()) as $line) {
                    if (trim($line) !== '') {
                        $onLine(trim($line));
                    }
                }
            }
        }

        return $result;
    }

    public function nodeBinary(): string
    {
        $configured = config('leadhunter.node_binary') ?: 'node';

        if (is_file($configured)) {
            return $configured;
        }

        return (new ExecutableFinder)->find($configured) ?? $configured;
    }

    /**
     * Environment untuk proses Node. Proses anak otomatis mewarisi environment PHP,
     * termasuk isi .env, jadi variabel rahasia dihapus secara eksplisit (false = unset).
     */
    public function environment(): array
    {
        $env = [];

        foreach (array_merge(array_keys($_ENV), array_keys($_SERVER)) as $key) {
            if (is_string($key) && preg_match('/(KEY|SECRET|PASSWORD|TOKEN)/i', $key)) {
                $env[$key] = false;
            }
        }

        if ($chrome = config('leadhunter.chrome_path')) {
            $env['CHROME_PATH'] = $chrome;
        }

        $env['PUPPETEER_PROFILE_DIR'] = storage_path('framework/puppeteer');

        if (PHP_OS_FAMILY === 'Windows') {
            // Chrome gagal start di Windows tanpa SystemRoot (terjadi jika worker dijalankan dari service).
            if (! getenv('SystemRoot')) {
                $env['SystemRoot'] = 'C:\\Windows';
            }
        }

        if (! getenv('TEMP') && ! getenv('TMPDIR')) {
            $env['TEMP'] = $env['TMP'] = $env['TMPDIR'] = sys_get_temp_dir();
        }

        return $env;
    }
}
