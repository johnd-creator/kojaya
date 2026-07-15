<?php

namespace App\Contracts;

interface OrganizationScopedModel
{
    public function organizationScopePath(): string;
}
