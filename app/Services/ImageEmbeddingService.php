<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImageEmbeddingService
{
    private const DESCRIPTION_PROMPT = <<<'PROMPT'
        Deskripsikan secara detail dan objektif produk yang terlihat pada gambar ini
        dalam 3-5 kalimat berbahasa Indonesia. Fokus pada: bentuk/siluet, warna dominan
        dan warna sekunder, motif/pola (polos, bergaris, kotak-kotak, bermotif bunga, dll),
        bahan/tekstur permukaan jika terlihat (kain, plastik, logam, kayu, kulit, kaca, dll),
        dan kategori/jenis produk secara umum. Jangan menyebutkan merek, teks pada gambar,
        atau nama produk spesifik dari database mana pun - cukup gambarkan ciri visualnya
        secara umum agar bisa dibandingkan dengan produk lain yang tampilan visualnya mirip.
        PROMPT;

    private string $apiKey;
    private string $visionModel;
    private string $embeddingModel;

    public function __construct()
    {
        $this->apiKey = (string) config('services.openai.api_key');
        $this->visionModel = (string) config('services.openai.model');
        $this->embeddingModel = (string) config('services.openai.embedding_model');
    }

    /**
     * Full pipeline: image file -> visual description -> embedding vector.
     *
     * @return array{description:string, embedding:array<int,float>, model:string}|null
     */
    public function generateImageEmbedding(string $absolutePath, string $mime): ?array
    {
        $description = $this->describeImage($absolutePath, $mime);

        if (!$description) {
            return null;
        }

        $embedding = $this->embedText($description);

        if (!$embedding) {
            return null;
        }

        return [
            'description' => $description,
            'embedding' => $embedding,
            'model' => $this->embeddingModel,
        ];
    }

    public function describeImage(string $absolutePath, string $mime): ?string
    {
        try {
            $base64 = base64_encode(file_get_contents($absolutePath));

            $response = Http::withToken($this->apiKey)
                ->timeout(60)
                ->post('https://api.openai.com/v1/responses', [
                    'model' => $this->visionModel,
                    'input' => [
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'input_text',
                                    'text' => self::DESCRIPTION_PROMPT,
                                ],
                                [
                                    'type' => 'input_image',
                                    'image_url' => "data:{$mime};base64,{$base64}",
                                ],
                            ],
                        ],
                    ],
                ]);

            if (!$response->successful()) {
                Log::warning('OpenAI vision request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $text = trim((string) $response->json('output.0.content.0.text'));

            return $text !== '' ? $text : null;
        } catch (\Throwable $e) {
            Log::error('OpenAI vision request threw exception', ['message' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @return array<int,float>|null
     */
    public function embedText(string $text): ?array
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(30)
                ->post('https://api.openai.com/v1/embeddings', [
                    'model' => $this->embeddingModel,
                    'input' => $text,
                ]);

            if (!$response->successful()) {
                Log::warning('OpenAI embeddings request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $embedding = $response->json('data.0.embedding');

            return is_array($embedding) ? array_map('floatval', $embedding) : null;
        } catch (\Throwable $e) {
            Log::error('OpenAI embeddings request threw exception', ['message' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @param array<int,float> $a
     * @param array<int,float> $b
     */
    public static function cosineSimilarity(array $a, array $b): float
    {
        $len = min(count($a), count($b));

        if ($len === 0) {
            return 0.0;
        }

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0; $i < $len; $i++) {
            $dot += $a[$i] * $b[$i];
            $normA += $a[$i] ** 2;
            $normB += $b[$i] ** 2;
        }

        if ($normA <= 0.0 || $normB <= 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }
}
