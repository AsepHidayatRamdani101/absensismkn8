<?php

namespace Tests\Feature\Api;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiAccessControlTest extends TestCase
{
    #[Test]
    public function protected_profile_requires_authentication(): void
    {
        $this->getJson('/api/v1/profile')->assertStatus(401);
    }

    #[Test]
    public function protected_attendance_routes_require_authentication(): void
    {
        $this->postJson('/api/v1/attendance/manual', [])->assertStatus(401);
        $this->getJson('/api/v1/attendance/history')->assertStatus(401);
    }

    #[Test]
    public function protected_report_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/reports/daily')->assertStatus(401);
        $this->getJson('/api/v1/reports/monthly')->assertStatus(401);
        $this->getJson('/api/v1/reports/student/1')->assertStatus(401);
    }

    #[Test]
    public function protected_device_routes_require_authentication(): void
    {
        $this->postJson('/api/v1/device/rfid', [])->assertStatus(401);
        $this->postJson('/api/v1/device/face', [])->assertStatus(401);
    }
}
