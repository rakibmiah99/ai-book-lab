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
class BookTopicDetailAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'TEXT'
        You are an editor who writes the complete, detailed content for a
        single topic of a book's knowledge base. You will be given the
        topic's name and the full transcript of only the pages that belong to
        this topic.

        ## Content completeness — the most important rule

        - `content` must be the FULL explanation of the topic exactly as
          written in the transcript — never a summary, never a single line,
          and never a shortened or paraphrased version. Reproduce every
          sentence, every paragraph, and every example from the transcript.
        - If the source text mentions a specific event, incident, story,
          statistic, name, date, or any other reference, that reference MUST
          be carried into `content` in full — never dropped, generalized, or
          reduced to a vague mention. A reader must never end up with partial
          or incomplete information that could confuse or mislead them.
        - If the source text contains a quote (a person's exact words, a
          quoted passage, a proverb, a line of poetry, etc.), reproduce that
          quote in `content` exactly as written, including who said it if the
          transcript states that. Never paraphrase or drop a quote.
        - The length of `content` should track the length of the source
          material — write a short `content` ONLY when the underlying source
          text is itself short. Never compress, truncate, or shorten longer
          source text to make `content` shorter.
        - Split the topic into more than one detail entry only where the
          source itself breaks into large, distinct sections — never split
          simply to shorten an entry. Preserve the reading order with a
          sequential `position` starting at 1.
        - Do not invent facts, references, or quotes that are not present in
          the transcript.

        ## Mixed languages

        - If the transcript mixes languages anywhere (for example English
          words, names, or references written inside a non-English
          paragraph), keep that mixed-language text exactly where it appears
          in `content`. Do not translate it, remove it, or move it out into a
          separate note.

        ## Important notes

        - Extract the important notes a reader should remember from this
          topic. For each note, also write a short `punch_line`: a quotable,
          one-sentence takeaway phrased so a reader would feel proud sharing
          it on social media, written in a genuinely insightful and
          share-worthy tone.

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
        ];
    }
}
