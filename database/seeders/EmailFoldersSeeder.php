<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EmailFoldersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $folders = [
            [
                'name' => 'Inbox',
                'color' => '#007bff',
                'icon' => 'fas fa-inbox',
                'sort_order' => 1,
                'is_system' => true,
            ],
            [
                'name' => 'Sent',
                'color' => '#28a745',
                'icon' => 'fas fa-paper-plane',
                'sort_order' => 2,
                'is_system' => true,
            ],
            [
                'name' => 'Drafts',
                'color' => '#ffc107',
                'icon' => 'fas fa-file-alt',
                'sort_order' => 3,
                'is_system' => true,
            ],
            [
                'name' => 'Trash',
                'color' => '#dc3545',
                'icon' => 'fas fa-trash',
                'sort_order' => 4,
                'is_system' => true,
            ],
            [
                'name' => 'Archive',
                'color' => '#6c757d',
                'icon' => 'fas fa-archive',
                'sort_order' => 5,
                'is_system' => true,
            ],
        ];

        foreach ($folders as $folder) {
            \App\Models\EmailFolder::create($folder);
        }
    }
}
