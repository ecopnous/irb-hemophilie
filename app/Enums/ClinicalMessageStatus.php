<?php

namespace App\Enums;

enum ClinicalMessageStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Archived = 'archived';
}
