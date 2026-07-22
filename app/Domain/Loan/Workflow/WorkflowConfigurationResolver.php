<?php

namespace App\Domain\Loan\Workflow;

use App\Domain\Loan\Contracts\StageInterface;
use App\Domain\Loan\Enums\LoanStage;
use App\Domain\Loan\Exceptions\InvalidWorkflowConfiguration;
use App\Models\WorkflowConfigurationStep;
use Illuminate\Contracts\Container\Container;

class WorkflowConfigurationResolver
{
    public function __construct(private Container $container) {}

    public function resolve(WorkflowConfigurationStep $step): StageInterface
    {
        $stageDefinition = $step->stageDefinition;

        if (! $stageDefinition->is_active) {
            throw new InvalidWorkflowConfiguration("Stage {$stageDefinition->code} is inactive.");
        }

        if (LoanStage::tryFrom($stageDefinition->code) === null) {
            throw new InvalidWorkflowConfiguration("Stage {$stageDefinition->code} is not a known Loan stage.");
        }

        $handlerClass = config("workflow-stages.{$stageDefinition->code}");

        if (! is_string($handlerClass) || ! is_a($handlerClass, StageInterface::class, true)) {
            throw new InvalidWorkflowConfiguration("Stage {$stageDefinition->code} has no trusted handler.");
        }

        $handler = $this->container->make($handlerClass);

        if (! $handler instanceof StageInterface) {
            throw new InvalidWorkflowConfiguration("Stage {$stageDefinition->code} handler is invalid.");
        }

        return $handler;
    }
}
