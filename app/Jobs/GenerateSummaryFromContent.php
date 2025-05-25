<?php

namespace App\Jobs;

use App\Models\Article;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class GenerateSummaryFromContent implements ShouldQueue
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $article;

    /**
     * Create a new job instance.
     */
    public function __construct(Article $article)
    {
        $this->article = $article;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $content = $this->article->content;

        $prompt = "Summarize the following content in 2–3 concise sentences, focusing on the main idea and key supporting details:\n\n" . $content;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . env('LLM_API_KEY'), [
            'contents' => [[
                'parts' => [[ 'text' => $prompt ]]
            ]]
        ]);

        $summary = $response->json('candidates.0.content.parts.0.text') ?? null;

        if ($summary) {
            $this->article->update(['summary' => trim($summary)]);
        }
    }

}
