<?php

namespace Tests\Integration;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Session;
use App\Models\AdminUser;
use Tests\TestCase;

class AuthFinanceTest extends TestCase
{
    private int $financeId;
    private int $staffId;

    public function setUp(): void
    {
        $this->financeId = AdminUser::create([
            'name' => '[TEST] Finance',
            'email' => 'test-finance-' . bin2hex(random_bytes(4)) . '@example.invalid',
            'password_hash' => password_hash('irrelevant', PASSWORD_DEFAULT),
            'role' => 'finance',
        ]);
        $this->staffId = AdminUser::create([
            'name' => '[TEST] Staff',
            'email' => 'test-staff-' . bin2hex(random_bytes(4)) . '@example.invalid',
            'password_hash' => password_hash('irrelevant', PASSWORD_DEFAULT),
            'role' => 'staff',
        ]);
        $this->resetAuthCache();
    }

    public function tearDown(): void
    {
        Session::forget('admin_id');
        $this->resetAuthCache();
        $pdo = Database::connection();
        $pdo->prepare('DELETE FROM admin_users WHERE id IN (?, ?)')->execute([$this->financeId, $this->staffId]);
    }

    public function testIsFinanceIsTrueOnlyForTheFinanceRole(): void
    {
        Session::put('admin_id', $this->financeId);
        $this->resetAuthCache();
        $this->assertTrue(Auth::isFinance());
        $this->assertFalse(Auth::isSuperAdmin());

        Session::put('admin_id', $this->staffId);
        $this->resetAuthCache();
        $this->assertFalse(Auth::isFinance());
    }

    private function resetAuthCache(): void
    {
        $ref = new \ReflectionClass(Auth::class);
        $cache = $ref->getProperty('userCache');
        $cache->setAccessible(true);
        $cache->setValue(null, null);
        $resolved = $ref->getProperty('resolved');
        $resolved->setAccessible(true);
        $resolved->setValue(null, false);
    }
}
