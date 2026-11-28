<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Электроника'     => [
                'Компьютеры' => [
                    'Ноутбуки',
                    'Комплектующие',
                ],
                'Телефоны'   => [],
                'Периферия'  => [],
            ],
            'Офисные товары'  => [
                'Канцелярия'          => [],
                'Расходные материалы' => [],
            ],
            'Товары для дома' => [
                'Освещение'   => [],
                'Инструменты' => [],
            ],
        ];

        foreach ($categories as $parentName => $children) {
            $parent = $this->createCategory($parentName);

            foreach ($children as $childName => $grandchildren) {
                $child = $this->createCategory($childName, $parent->id);

                foreach ($grandchildren as $grandchildName) {
                    $this->createCategory($grandchildName, $child->id);
                }
            }
        }
    }

    private function createCategory(string $name, ?int $parentId = null): Category
    {
        return Category::withTrashed()->updateOrCreate(
            [
                'name'      => $name,
                'parent_id' => $parentId,
            ],
            [
                'description' => null,
                'is_active'   => true,
                'deleted_at'  => null,
            ],
        );
    }
}
