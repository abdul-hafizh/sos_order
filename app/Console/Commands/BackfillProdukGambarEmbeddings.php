<?php

namespace App\Console\Commands;

use App\Models\MasterProdukDetailGambar;
use App\Services\ImageEmbeddingService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

#[Signature('embeddings:backfill-produk-gambar {--limit=0} {--force}')]
#[Description('Generate OpenAI embeddings for master_produk_detail_gambar rows missing one')]
class BackfillProdukGambarEmbeddings extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ImageEmbeddingService $embeddingService): int
    {
        $query = MasterProdukDetailGambar::query();

        if (!$this->option('force')) {
            $query->whereNull('embedding');
        }

        if ((int) $this->option('limit') > 0) {
            $query->limit((int) $this->option('limit'));
        }

        $rows = $query->get();

        $this->info("Processing {$rows->count()} image(s)...");
        $bar = $this->output->createProgressBar($rows->count());

        foreach ($rows as $gambar) {
            $absolutePath = Storage::disk('public')->path($gambar->path_file);

            if (!is_file($absolutePath)) {
                Log::warning('Backfill: file not found', ['path' => $gambar->path_file]);
                $bar->advance();
                continue;
            }

            $mime = File::mimeType($absolutePath) ?: 'image/jpeg';
            $result = $embeddingService->generateImageEmbedding($absolutePath, $mime);

            if ($result) {
                $gambar->update([
                    'description' => $result['description'],
                    'embedding' => $result['embedding'],
                    'embedding_model' => $result['model'],
                    'embedding_generated_at' => now(),
                ]);
            } else {
                Log::warning('Backfill: embedding generation failed', ['id_produk_gambar' => $gambar->id_produk_gambar]);
            }

            $bar->advance();
            usleep(200000);
        }

        $bar->finish();
        $this->newLine();
        $this->info('Done.');

        return self::SUCCESS;
    }
}
