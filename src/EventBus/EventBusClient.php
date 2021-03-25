<?php

namespace RL\EventBus;

use LS\Apollo\Connect\Entity\Workflow;
use LS\Apollo\Connect\Entity\WorkflowWorkflowAction;

interface EventBusClient
{
    public function putEvent(int $app, string $name, string $detail);

    public function putRule(Workflow $workflow);

    public function putTargets(Workflow $workflow, WorkflowWorkflowAction $workflowAction, array $inputTransformers);

    public function deleteRule(Workflow $workflow);

    public function deleteTargets(Workflow $workflow);
}
