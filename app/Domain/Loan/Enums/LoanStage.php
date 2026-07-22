<?php

namespace App\Domain\Loan\Enums;

enum LoanStage: string
{
    case Validation = 'VALIDATION';
    case FraudCheck = 'FRAUD_CHECK';
    case GuarantorCheck = 'GUARANTOR_CHECK';
    case CreditCheck = 'CREDIT_CHECK';
    case ManagerApproval = 'MANAGER_APPROVAL';
}
