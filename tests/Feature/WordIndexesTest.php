<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WordIndexesTest extends TestCase
{
    use RefreshDatabase;

    public function test_words_table_has_indexes_needed_for_letters_generation(): void
    {
        $indexColumns = collect(DB::select("PRAGMA index_list('words')"))
            ->flatMap(function (object $index): array {
                $indexName = $index->name ?? null;

                if (! is_string($indexName) || $indexName === '') {
                    return [];
                }

                return array_map(
                    static fn (object $column): string => (string) ($column->name ?? ''),
                    DB::select("PRAGMA index_info('{$indexName}')"),
                );
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        $this->assertContains('normalized_word', $indexColumns);
        $this->assertContains('length', $indexColumns);
        $this->assertContains('is_active', $indexColumns);
    }
}
