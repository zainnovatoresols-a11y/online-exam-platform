<?php

namespace App\Http\Requests\Concerns;

trait ValidatesBrowserMediaTypes
{
    /**
     * @return array<int, string>
     */
    protected function browserMediaTypeRules(): array
    {
        return [
            'nullable',
            'string',
            'max:100',
            'regex:/\A[a-z0-9!#$&^_.+-]+\/[a-z0-9!#$&^_.+-]+(?:\s*;\s*[a-z0-9_.+-]+=(?:"[^"\r\n]{1,80}"|[a-z0-9_.+,\-]+))*\z/i',
        ];
    }
}
