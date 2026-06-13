<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesStaff;

class DailyCustomerPolicy
{
    use AuthorizesStaff;
}
