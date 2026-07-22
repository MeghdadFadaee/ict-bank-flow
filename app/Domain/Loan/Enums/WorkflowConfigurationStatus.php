<?php

namespace App\Domain\Loan\Enums;

enum WorkflowConfigurationStatus: string
{
    case Draft = 'DRAFT';
    case Published = 'PUBLISHED';
    case Archived = 'ARCHIVED';
}
