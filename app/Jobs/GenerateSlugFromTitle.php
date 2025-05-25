<?php

namespace App\Jobs;

use App\Models\Article;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class GenerateSlugFromTitle implements ShouldQueue
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
        $title = $this->article->title;

        $prompt = "Create a short, lowercase, URL-friendly SEO slug using hyphens for the blog title: $title. Only return the slug.";

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . env('LLM_API_KEY'), [
            'contents' => [[
                'parts' => [[ 'text' => $prompt ]]
            ]]
        ]);

        $slug = $response->json('candidates.0.content.parts.0.text') ?? null;

        if ($slug) {
            $this->article->update(['slug' => trim($slug)]);
        }
    }
}
