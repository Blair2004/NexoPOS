<?php

namespace Modules\NsOxen\Contracts;

use Modules\NsOxen\Data\OxenExecutionContext;
use Modules\NsOxen\Data\OxenToolDefinition;
use Modules\NsOxen\Data\OxenToolResult;

interface OxenToolContract
{
    public function definition(): OxenToolDefinition;

    /** @param array<string, mixed> $input */
    public function execute( OxenExecutionContext $context, array $input ): OxenToolResult;
}
