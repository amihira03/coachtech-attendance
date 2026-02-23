<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceDateTest extends TestCase
{
    use RefreshDatabase;

    public function testCurrentDateTimeIsDisplayedOnAttendancePage(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 21, 10, 35, 0));

        $user = $this->createVerifiedUser();

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('2026年2月21日');
        $response->assertSee('（土）');
        $response->assertSee('10:35');
    }
}
