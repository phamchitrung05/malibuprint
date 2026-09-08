<?php

namespace Tests\Unit;

use Filament\Facades\Filament;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class FilamentResourceConventionTest extends TestCase
{
    public function test_all_resource_forms_redirect_to_their_list_after_create_or_update(): void
    {
        $panel = Filament::getPanel('admin');

        $this->assertSame('index', $panel->getResourceCreatePageRedirect());
        $this->assertSame('index', $panel->getResourceEditPageRedirect());
    }

    public function test_table_actions_do_not_define_tooltips(): void
    {
        $tableFiles = collect(File::allFiles(app_path('Filament/Resources')))
            ->filter(fn ($file): bool => str_contains(
                str_replace('\\', '/', $file->getPathname()),
                '/Tables/',
            ))
            ->map(fn ($file): string => $file->getPathname())
            ->push(app_path('Filament/Pages/ProductInventory.php'));

        foreach ($tableFiles as $file) {
            $this->assertStringNotContainsString(
                '->tooltip(',
                File::get($file),
                "Table action trong {$file} không được khai báo tooltip.",
            );
        }
    }
}
