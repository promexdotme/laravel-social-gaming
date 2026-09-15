<?php

namespace VanguardLTE\Console\Commands;

use Illuminate\Console\Command;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class InjectMockWebSocket extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'games:inject-mock {--dir= : Custom directory to scan under the public root}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Injects the mock-websocket.js script tag into all game HTML entry files.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $customDir = $this->option('dir');
        $gamesPath = $customDir ? base_path('../' . $customDir) : base_path('../games');

        $this->info("Scanning directory: " . $gamesPath);

        if (!file_exists($gamesPath)) {
            $this->error("Directory does not exist: " . $gamesPath);
            return 1;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($gamesPath, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $injectedCount = 0;
        $skippedCount = 0;

        foreach ($files as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'html') {
                $filePath = $file->getRealPath();
                $content = file_get_contents($filePath);

                // Check if already injected
                if (strpos($content, 'mock-websocket.js') !== false) {
                    $skippedCount++;
                    continue;
                }

                // Inject tag as the very first script in head, body, or top of file
                $scriptTag = "\n\t<!-- Mock WebSocket Bridge Injection -->\n\t<script src=\"/js/mock-websocket.js\"></script>\n";

                if (strpos($content, '<head>') !== false) {
                    $content = str_replace('<head>', "<head>" . $scriptTag, $content);
                } elseif (strpos($content, '<HEAD>') !== false) {
                    $content = str_replace('<HEAD>', "<HEAD>" . $scriptTag, $content);
                } elseif (strpos($content, '<body>') !== false) {
                    $content = str_replace('<body>', "<body>" . $scriptTag, $content);
                } elseif (strpos($content, '<BODY>') !== false) {
                    $content = str_replace('<BODY>', "<BODY>" . $scriptTag, $content);
                } else {
                    $content = $scriptTag . $content;
                }

                file_put_contents($filePath, $content);
                $this->line("Injected: " . str_replace(base_path('../'), '', $filePath));
                $injectedCount++;
            }
        }

        $this->info("Injection complete! Injected: {$injectedCount}, Skipped: {$skippedCount}");
        return 0;
    }
}
