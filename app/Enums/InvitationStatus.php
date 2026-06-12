<?php

namespace App\Enums;

enum InvitationStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Accepted = 'accepted';
    case Expired = 'expired';
    case Revoked = 'revoked';
}
