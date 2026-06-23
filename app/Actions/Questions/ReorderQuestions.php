<?php

namespace App\Actions\Questions;

use App\Models\Test;
use Illuminate\Support\Facades\DB;

class ReorderQuestions
{
    /**
     * @param  array<int, int>  $questionIds
     */
    public function handle(Test $test, array $questionIds): void
    {
        DB::transaction(function () use ($test, $questionIds): void {
            foreach (array_values($questionIds) as $index => $questionId) {
                $test->questions()
                    ->whereKey($questionId)
                    ->update([
                        'order' => $index + 1,
                    ]);
            }
        });
    }
}
