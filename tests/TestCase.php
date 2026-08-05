<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * RoomCatalog memoises the resolved catalog for the life of a request.
     * A test process is one long-lived "request", so without this a catalog
     * read in one test would be served to the next — after RefreshDatabase had
     * already rolled its room_types rows away.
     */
    protected function setUp(): void
    {
        parent::setUp();

        \App\Support\RoomCatalog::flush();
    }
}
