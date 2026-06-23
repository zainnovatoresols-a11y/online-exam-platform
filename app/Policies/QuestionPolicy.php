<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Question;
use App\Models\Test;
use App\Models\User;

class QuestionPolicy
{
    public function viewAny(User $user, Test $test): bool
    {
        return $this->ownsTest($user, $test);
    }

    public function create(User $user, Test $test): bool
    {
        return $this->ownsTest($user, $test)
            && ($test->isDraft() || $test->isClosed());
    }

    public function reorder(User $user, Test $test): bool
    {
        return $this->create($user, $test);
    }

    public function update(User $user, Question $question): bool
    {
        return $this->ownsQuestionTest($user, $question)
            && ($question->test->isDraft() || $question->test->isClosed());
    }

    public function delete(User $user, Question $question): bool
    {
        return $this->ownsQuestionTest($user, $question)
            && ($question->test->isDraft() || $question->test->isClosed());
    }

    private function ownsQuestionTest(User $user, Question $question): bool
    {
        return $this->ownsTest($user, $question->test);
    }

    private function ownsTest(User $user, Test $test): bool
    {
        if (! $user->hasRole(UserRole::Admin->value)) {
            return false;
        }

        return $test->belongsToAdminScope($user);
    }
}
