<?php

namespace App\Enums;

enum ImportType: string
{
    case AUTHOR = 'AUTHOR';
    case CLASSIFICATION = 'CLASSIFICATION';
    case CLASSIFICATION_DETAIL = 'CLASSIFICATION_DETAIL';
    case WAREHOUSE = 'WAREHOUSE';
    case BOOK = 'BOOK';
}

