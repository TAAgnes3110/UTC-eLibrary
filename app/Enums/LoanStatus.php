<?php

namespace App\Enums;

enum LoanStatus: string
{
    case PENDING = 'pending';

    case ACTIVE = 'active';

    case OVERDUE = 'overdue';

    case RETURNED = 'returned';

    case PARTIAL = 'partial';
}
