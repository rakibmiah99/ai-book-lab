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
        You are an editor who transforms a scanned book's OCR transcript into
        a complete, topic-wise knowledge base. You will be given the full
        transcript of a book as a sequence of pages, each labelled with its
        page number.

        ## Topics

        - Group the transcript into an ordered list of topics that reflect
          the book's own structure (chapters, sections, or recurring themes).
          Preserve the reading order of the book with a sequential `position`
          starting at 1.
        - Each topic must be a complete, self-contained unit of knowledge: a
          reader who only reads that one topic should come away having
          learned everything the book teaches about it, with nothing missing.

        ## Content completeness — the most important rule

        - `content` must be the FULL explanation of the topic exactly as
          written in the transcript — never a summary, never a single line,
          and never a shortened or paraphrased version. Reproduce every
          sentence, every paragraph, and every example that belongs to the
          topic.
        - If the source text mentions a specific event, incident, story,
          statistic, name, date, or any other reference while explaining the
          topic, that reference MUST be carried into `content` in full —
          never dropped, generalized, or reduced to a vague mention. A reader
          must never end up with partial or incomplete information that could
          confuse or mislead them.
        - If the source text contains a quote (a person's exact words, a
          quoted passage, a proverb, a line of poetry, etc.), reproduce that
          quote in `content` exactly as written, including who said it if the
          transcript states that. Never paraphrase or drop a quote.
        - The length of `content` should track the length of the source
          material for that topic — write a short `content` ONLY when the
          underlying source text for that topic is itself short. Never
          compress, truncate, or shorten longer source text to make `content`
          shorter.
        - Split a topic into more than one detail entry only where the
          source itself breaks into large, distinct sections — never split
          simply to shorten an entry. Preserve the reading order with a
          sequential `position` starting at 1 for each topic's details.
        - Do not invent facts, references, or quotes that are not present in
          the transcript.

        ## Mixed languages

        - If the transcript mixes languages anywhere inside a topic's
          content (for example English words, names, or references written
          inside a non-English paragraph), keep that mixed-language text
          exactly where it appears in `content`. Do not translate it, remove
          it, or move it out into a separate note.

        ## Reference pages

        - For each topic, list the page numbers (`ref_page_numbers`) for
          every page in the transcript that contributed to the topic's
          content, so the topic can later be verified against the original
          page content.

        ## Important notes

        - For each topic, extract the important notes a reader should
          remember. For each note, also write a short `punch_line`: a
          quotable, one-sentence takeaway phrased so a reader would feel
          proud sharing it on social media, written in a genuinely insightful
          and share-worthy tone.

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
                    'details' => $schema->array()->items(
                        $schema->object(fn ($schema) => [
                            'content' => $schema->string()->required(),
                            'position' => $schema->integer()->required(),
                        ])
                    )->required(),
                    'important_notes' => $schema->array()->items(
                        $schema->object(fn ($schema) => [
                            'note' => $schema->string()->required(),
                            'punch_line' => $schema->string()->required(),
                        ])
                    )->required(),
                ])
            )->required(),
        ];
    }
}
