<?php

namespace App\Core\Domain;

enum DocumentStatus: string
{
    case Draft = 'draft';
    case Issued = 'issued';
    case Cancelled = 'cancelled';
}
