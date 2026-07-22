<?php

use App\Domain\Loan\Workflow\Stages\CreditCheckStage;
use App\Domain\Loan\Workflow\Stages\FraudCheckStage;
use App\Domain\Loan\Workflow\Stages\GuarantorCheckStage;
use App\Domain\Loan\Workflow\Stages\ManagerApprovalStage;
use App\Domain\Loan\Workflow\Stages\ValidationStage;

return [
    'VALIDATION' => ValidationStage::class,
    'FRAUD_CHECK' => FraudCheckStage::class,
    'GUARANTOR_CHECK' => GuarantorCheckStage::class,
    'CREDIT_CHECK' => CreditCheckStage::class,
    'MANAGER_APPROVAL' => ManagerApprovalStage::class,
];
