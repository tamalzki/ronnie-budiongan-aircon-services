<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesStaff;

class PurchaseOrderPolicy
{
    use AuthorizesStaff;
}
