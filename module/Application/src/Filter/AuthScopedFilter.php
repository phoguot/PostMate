<?php
declare(strict_types=1);

namespace Application\Filter;

class AuthScopedFilter extends AppFilter
{
    // Base filter for API endpoints that require an authenticated user.
    // Subclasses add entity-specific input fields.
}
