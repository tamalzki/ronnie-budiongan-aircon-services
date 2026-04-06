<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesStaff;

class SalePolicy
{
    use AuthorizesStaff;
}
