<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesStaff;

class OperationExpensePolicy
{
    use AuthorizesStaff;
}
