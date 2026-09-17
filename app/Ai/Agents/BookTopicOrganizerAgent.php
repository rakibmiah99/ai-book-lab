<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider(Lab::OpenAI)]
#[Model('gpt-4o-mini')]
#[MaxTokens(16384)]
#[Strict]
class BookTopicOrganizerAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'TEXT'
        You are an editor who identifies the topic structure of a scanned
        book's OCR transcript. You will be given a transcript excerpt — a
        contiguous block of pages from a larger book, each labelled with its
        page number. The excerpt may start or end mid-topic relative to the
        rest of the book; treat it as the complete world you can see and only
        reference pages that actually appear in it.

        ## Topics

        - Group the excerpt into an ordered list of topics that reflect the
          book's own structure (chapters, sections, or recurring themes).
          Preserve the reading order with a sequential `position` starting at
          1 for this excerpt.
        - Each topic must be a complete, self-contained unit of knowledge
          within the pages you were given: a reader who only reads that one
          topic should come away having learned everything these pages teach
          about it, with nothing missing.
        - Do not write the topic's content here — only identify the topic and
          which pages belong to it. The full content will be written
          separately, in a later step, from these exact pages.

        ## Reference pages

        - For each topic, list the page numbers (`ref_page_numbers`) for
          every page in the transcript that contributed to the topic, so its
          full content can later be written from those pages alone. Every
          page that belongs to the topic must be included — omitting a page
          means its content will be lost entirely in the later step.

        ## Language

        - Respond in the same language as the source transcript.
        TEXT;
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'topics' => $schema->array()->items(
                $schema->object(fn ($schema) => [
                    'name' => $schema->string()->required(),
                    'position' => $schema->integer()->required(),
                    'ref_page_numbers' => $schema->array()
                        ->items($schema->integer())
                        ->required(),
                ])
            )->required(),
        ];
    }
}
