<?php

namespace Tests\Feature;

use Tests\TestCase;

class GuestAccessTest extends TestCase
{
    public function test_guest_is_redirected_from_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_installments_index(): void
    {
        $this->get(route('installments.index'))->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_reports(): void
    {
        $this->get(route('reports.index'))->assertRedirect(route('login'));
    }
}
