<?php

namespace Tests;

use SRL\Builder;
use SRL\Language\Helpers\Cache;
use SRL\SRL;

class CacheTest extends TestCase
{
    public function testCacheStorage()
    {
        $srl = 'literally "justacachetest"';

        $this->assertFalse(Cache::has($srl));

        $query = new SRL($srl);

        $this->assertTrue(Cache::has($srl));

        // If the object wouldn't have been cached, they would not be the same object any more.
        $this->assertTrue($query->getBuilder() === (new SRL($srl))->getBuilder());
    }
}