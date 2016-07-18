<?php
/**
 * @copyright (c) 2016 Jacob Martin
 * @license MIT https://opensource.org/licenses/MIT
 */

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AdminAuthTest extends TestCase
{
	use DatabaseMigrations;

	private $dashboardString = 'Angel Dashboard';

	/**
	 * Before signing in, the admin panel should not be visible.
	 */
	public function testAdminRouteNotSignedIn()
	{
		$this->visit('/admin')
			->see('Sign In')
			->dontSee('Sign Out')
			->dontSee($this->dashboardString);
	}

	/**
	 * Signed in users (non-administrators) should not be able to see the admin panel.
	 */
	public function testAdminSignedInAsUser()
	{
		$user = factory(App\Models\User::class, 'user')->create();

		$this->actingAs($user)
			->visit('/admin')
			->see('You must be signed in as an administrator.')
			->dontSee($this->dashboardString);
	}

	/**
	 * Signed in admins should be able to see the admin panel.
	 */
	public function testAdminSignedInAsAdmin()
	{
		$admin = factory(App\Models\User::class, 'admin')->create();

		$this->actingAs($admin)
			->visit('/admin')
			->see($this->dashboardString);
	}

	/**
	 * Signed in superadmins should be able to see the admin panel.
	 */
	public function testAdminSignedInAsSuperAdmin()
	{
		$superAdmin = factory(App\Models\User::class, 'superadmin')->create();

		$this->actingAs($superAdmin)
			->visit('/admin')
			->see($this->dashboardString);
	}

	/**
	 * Test the usage of the sign-in form.
	 */
	public function testSignInForm()
	{
		$userPass  = str_random(10);
		$adminPass = str_random(10);

		factory(App\Models\User::class, 'user')->create([
			'email' => 'user@test',
			'password' => bcrypt($userPass)
		]);
		factory(App\Models\User::class, 'admin')->create([
			'email' => 'admin@test',
			'password' => bcrypt($adminPass)
		]);

		// Users can't get in.
		$this->visit('/admin')
			->type('user@test', 'email')
			->type($userPass, 'password')
			->press('Sign In')
			->seePageIs('/admin')
			->see('You must be signed in as an administrator.');

		// But administrators can.
		$this->visit('/admin')
			->type('admin@test', 'email')
			->type($adminPass, 'password')
			->press('Sign In')
			->seePageIs('/admin')
			->see($this->dashboardString);
	}

	/**
	 * Test that entering the wrong password forbids access to the admin panel.
	 */
	public function testSignInFormWrongPass()
	{
		factory(App\Models\User::class, 'user')->create([
			'email' => 'user@test'
		]);
		factory(App\Models\User::class, 'admin')->create([
			'email' => 'admin@test'
		]);

		$this->visit('/admin')
			->type('user@test', 'email')
			->type('abc123', 'password')
			->press('Sign In')
			->seePageIs('/admin')
			->see('These credentials do not match our records.');

		// But administrators can.
		$this->visit('/admin')
			->type('admin@test', 'email')
			->type('abc123', 'password')
			->press('Sign In')
			->seePageIs('/admin')
			->see('These credentials do not match our records.');
	}
}