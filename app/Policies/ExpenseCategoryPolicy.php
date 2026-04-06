<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesStaff;

class ExpenseCategoryPolicy
{
    use AuthorizesStaff;
}
