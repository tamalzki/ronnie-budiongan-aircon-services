<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesStaff;

class BrandPolicy
{
    use AuthorizesStaff;
}
