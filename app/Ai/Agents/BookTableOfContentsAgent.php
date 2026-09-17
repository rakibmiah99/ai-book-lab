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
class BookTableOfContentsAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'TEXT'
        You are an editor who locates and transcribes a scanned book's own
        printed table of contents (also called সূচিপত্র, index, or contents
        page). You will be given the transcript of the book's opening pages,
        each labelled with its page number.

        ## Table of contents

        - Find the page(s) that contain the book's own printed table of
          contents and read every entry listed there, in the order printed.
        - For each entry, record its exact title (`name`), its reading order
          (`position`, starting at 1), and the page number printed next to it
          in the table of contents (`page_number`) — this is the page where
          that topic begins in the book, exactly as printed, never the page
          number of the table of contents itself.
        - Do not invent, merge, split, reorder, or reword entries. Reproduce
          the table of contents faithfully.
        - If none of the given pages contain a table of contents, return an
          empty `entries` list. Do not guess a structure from chapter
          headings if no table of contents itself is present.

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
            'entries' => $schema->array()->items(
                $schema->object(fn ($schema) => [
                    'name' => $schema->string()->required(),
                    'position' => $schema->integer()->required(),
                    'page_number' => $schema->integer()->required(),
                ])
            )->required(),
        ];
    }
}
