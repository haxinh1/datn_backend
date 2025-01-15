<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::table('attributes')->insert([
            'name' => 'Thuộc tính 1',
            'slug' => 'thuoc-tinh-1',
            'is_active' => 1,
        ]);

        for ($i = 0; $i < 5; $i++) {
            DB::table('brands')->insert([
                'name' => 'Brand ' . ($i + 1),
                'slug' => 'brand-' . ($i + 1),
                'logo' => 'logo' . ($i + 1) . '.jpg',
                'is_active' => 1,
            ]);

            DB::table('attribute_values')->insert([
                'attribute_id' => 1,
                'value' => 'Giá trị thuộc tính ' . ($i + 1),
                'is_active' => 1,
            ]);

            DB::table('categories')->insert([
                'name' => 'Danh mục ' . ($i + 1),
                'slug' => 'danh-muc-' . ($i + 1),
                'is_active' => 1,
            ]);
        }

        $this->command->info('Seed data đã được chèn thành công!');
    }
}
