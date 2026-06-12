<?php

namespace App\Enums;

enum TestStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Closed = 'closed';
}
