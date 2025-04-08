<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        for ($i = 0; $i < 5; $i++) {
            DB::table('attributes')->insert([
                'name' => 'Thuộc tính '.$i,
                'slug' => 'thuoc-tinh'.$i,
                'is_active' => 1,
            ]);
        }
        DB::table('users')->insert([
            'google_id'     => null,
            'phone_number'  => '098765432',
            'email'         => 'admin@example.com',
            'password'      => Hash::make('password123'), // Mật khẩu đã mã hóa
            'fullname'      => 'Admin User',
            'avatar'        => null,
            'gender'        => 'male',
            'birthday'      => '1990-01-01',
            'loyalty_points'=> 100,
            'role'          => 'admin',
            'status'        => 'active',
            'remember_token'=> null,
            'verified_at'   => now(),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
        for ($i = 0; $i < 5; $i++) {
            DB::table('attribute_values')->insert([
                'attribute_id' => rand(1,4),
                'value' => 'gia tri'.$i,
                'is_active' => 1,
            ]);
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
