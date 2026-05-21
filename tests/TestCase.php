<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\File;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $compiledPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'chronomots-tests'.DIRECTORY_SEPARATOR.str_replace('\\', '-', static::class).'-'.spl_object_id($this);

        File::ensureDirectoryExists($compiledPath);
        config(['view.compiled' => $compiledPath]);
    }
}
