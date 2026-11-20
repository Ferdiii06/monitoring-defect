<?php

namespace Tests\Feature;

use App\Models\Carline;
use App\Models\PreAssyMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Once;
use Tests\TestCase;

class PreAssyMachineMasterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (class_exists(Once::class)) {
            Once::flush();
        }

        \Illuminate\Support\Facades\Http::fake([
            '*/users*' => \Illuminate\Support\Facades\Http::response(['data' => []], 200),
            '*' => \Illuminate\Support\Facades\Http::response([], 200),
        ]);
    }

    private function actingAsAdmin()
    {
        return $this->withSession([
            'logged_in' => true,
            'user_name' => 'Admin QA',
            'user_role' => 'Administrator',
        ]);
    }

    private function actingAsOperator()
    {
        return $this->withSession([
            'logged_in' => true,
            'user_name' => 'Operator QA',
            'user_role' => 'User',
            'current_shift' => '1A',
        ]);
    }

    private function actingAsRework()
    {
        return $this->withSession([
            'logged_in'     => true,
            'user_name'     => 'Rework QA',
            'user_role'     => 'Rework',
            'current_shift' => '1A',
        ]);
    }

    public function test_guest_cannot_access_pre_assy_machines(): void
    {
        $response = $this->get(route('admin.master.pre_assy_machines.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_operator_and_rework_roles_are_forbidden(): void
    {
        $resOp = $this->actingAsOperator()->get(route('admin.master.pre_assy_machines.index'));
        $resOp->assertRedirect(route('operator.home'));

        $resRw = $this->actingAsRework()->get(route('admin.master.pre_assy_machines.index'));
        $resRw->assertRedirect(route('rework.index'));
    }

    public function test_admin_can_access_pre_assy_machines_page(): void
    {
        $carline = Carline::create(['name' => 'PA-LINE-A', 'car_type_id' => null]);
        PreAssyMachine::create(['carline_id' => $carline->id, 'name' => 'MC-01', 'is_active' => true]);

        $response = $this->actingAsAdmin()->get(route('admin.master.pre_assy_machines.index'));
        $response->assertOk();
        $response->assertSee('No Mesin (Pre Assy)');
        $response->assertSee('PA-LINE-A');
        $response->assertSee('MC-01');
    }

    public function test_admin_can_create_machine_for_carline(): void
    {
        $carline = Carline::create(['name' => 'PA-LINE-B', 'car_type_id' => null]);

        $response = $this->actingAsAdmin()->post(route('admin.master.pre_assy_machines.store'), [
            'carline_id' => $carline->id,
            'name'       => 'MC-101',
        ]);

        $response->assertRedirect(route('admin.master.pre_assy_machines.index', ['carline_id' => $carline->id]));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('pre_assy_machines', [
            'carline_id' => $carline->id,
            'name'       => 'MC-101',
            'is_active'  => true,
        ]);
    }

    public function test_duplicate_machine_name_on_same_carline_is_rejected(): void
    {
        $carline = Carline::create(['name' => 'PA-LINE-C', 'car_type_id' => null]);
        PreAssyMachine::create(['carline_id' => $carline->id, 'name' => 'MC-DUPLICATE']);

        $response = $this->actingAsAdmin()->post(route('admin.master.pre_assy_machines.store'), [
            'carline_id' => $carline->id,
            'name'       => 'MC-DUPLICATE',
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertEquals(1, PreAssyMachine::where('carline_id', $carline->id)->where('name', 'MC-DUPLICATE')->count());
    }

    public function test_same_machine_name_on_different_carline_is_allowed(): void
    {
        $cl1 = Carline::create(['name' => 'PA-LINE-D1', 'car_type_id' => null]);
        $cl2 = Carline::create(['name' => 'PA-LINE-D2', 'car_type_id' => null]);

        PreAssyMachine::create(['carline_id' => $cl1->id, 'name' => 'MC-SHARED']);

        $response = $this->actingAsAdmin()->post(route('admin.master.pre_assy_machines.store'), [
            'carline_id' => $cl2->id,
            'name'       => 'MC-SHARED',
        ]);

        $response->assertRedirect(route('admin.master.pre_assy_machines.index', ['carline_id' => $cl2->id]));
        $this->assertEquals(2, PreAssyMachine::where('name', 'MC-SHARED')->count());
    }

    public function test_admin_can_update_machine_name(): void
    {
        $carline = Carline::create(['name' => 'PA-LINE-E', 'car_type_id' => null]);
        $machine = PreAssyMachine::create(['carline_id' => $carline->id, 'name' => 'MC-OLD']);

        $response = $this->actingAsAdmin()->put(route('admin.master.pre_assy_machines.update', $machine->id), [
            'name' => 'MC-RENAMED',
        ]);

        $response->assertRedirect();
        $this->assertEquals('MC-RENAMED', $machine->fresh()->name);
    }

    public function test_admin_can_toggle_machine_status(): void
    {
        $carline = Carline::create(['name' => 'PA-LINE-F', 'car_type_id' => null]);
        $machine = PreAssyMachine::create(['carline_id' => $carline->id, 'name' => 'MC-TOGGLE', 'is_active' => true]);

        $response = $this->actingAsAdmin()->patch(route('admin.master.pre_assy_machines.toggle', $machine->id));
        $response->assertRedirect();
        $this->assertFalse($machine->fresh()->is_active);

        $response2 = $this->actingAsAdmin()->patch(route('admin.master.pre_assy_machines.toggle', $machine->id));
        $response2->assertRedirect();
        $this->assertTrue($machine->fresh()->is_active);
    }

    public function test_admin_can_delete_machine(): void
    {
        $carline = Carline::create(['name' => 'PA-LINE-G', 'car_type_id' => null]);
        $machine = PreAssyMachine::create(['carline_id' => $carline->id, 'name' => 'MC-DELETE']);

        $response = $this->actingAsAdmin()->delete(route('admin.master.pre_assy_machines.destroy', $machine->id));
        $response->assertRedirect();

        $this->assertDatabaseMissing('pre_assy_machines', [
            'id' => $machine->id,
        ]);
    }
}
