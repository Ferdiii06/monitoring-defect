<?php

namespace Database\Factories;

use App\Models\NameplateSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NameplateSetting>
 */
class NameplateSettingFactory extends Factory
{
    protected $model = NameplateSetting::class;

    public function definition(): array
    {
        return [
            'carline'        => 'LINE-' . fake()->unique()->numerify('###'),
            'assy_customer'  => false,
            'name'           => fake()->words(3, true),
            'set'            => fake()->randomElement(NameplateSetting::SET_OPTIONS),
            'date_format'    => 'YYMMDD',
            'assy_no'        => '1,8',
            'suffix'         => '9,2',
            'level'          => null,
            'date_field'     => '11,6',
            'sequence_label' => '17,4',
        ];
    }
}
