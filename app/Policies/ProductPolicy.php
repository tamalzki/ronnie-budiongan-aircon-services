<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesStaff;

class ProductPolicy
{
    use AuthorizesStaff;
}
