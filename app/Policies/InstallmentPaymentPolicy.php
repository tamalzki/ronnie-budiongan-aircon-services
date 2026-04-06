<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesStaff;

class InstallmentPaymentPolicy
{
    use AuthorizesStaff;
}
