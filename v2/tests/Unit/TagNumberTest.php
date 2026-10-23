<?php

namespace Tests\Unit;

use App\Domain\Tag\TagNumber;
use InvalidArgumentException;
use Tests\TestCase;

/** タグ番号 (旧 Utility::convertTagFormat / OrdersController::addTag / Order::isConsecutiveTag)。 */
class TagNumberTest extends TestCase
{
    public function test_format(): void
    {
        $this->assertSame('3-592', TagNumber::fromInt(3592)->toString());
        $this->assertSame('10-005', TagNumber::fromInt(10005)->toString());
        $this->assertSame('1-000', TagNumber::fromInt(1000)->toString());
        $this->assertSame('9-999', TagNumber::fromInt(9999)->toString());
        $this->assertSame('10-999', TagNumber::fromInt(10999)->toString());
    }

    public function test_next_wraps_10999_to_1000(): void
    {
        $this->assertSame(1000, TagNumber::fromInt(10999)->next()->toInt());
        $this->assertSame('2-000', TagNumber::fromInt(1999)->next()->toString());
        $this->assertSame(10000, TagNumber::fromInt(9999)->next()->toInt());
    }

    public function test_from_string(): void
    {
        $this->assertSame(9580, TagNumber::fromString('9-580')->toInt());
        $this->assertSame(10999, TagNumber::fromString('10-999')->toInt());
        $this->assertSame('3-591', TagNumber::fromString('3-591')->toString());
    }

    public function test_is_followed_by(): void
    {
        $this->assertTrue(TagNumber::fromString('9-580')->isFollowedBy(TagNumber::fromString('9-581')));
        $this->assertTrue(TagNumber::fromInt(10999)->isFollowedBy(TagNumber::fromInt(1000)));
        $this->assertFalse(TagNumber::fromString('9-580')->isFollowedBy(TagNumber::fromString('9-582')));
        $this->assertFalse(TagNumber::fromString('9-581')->isFollowedBy(TagNumber::fromString('9-580')));
        // 旧の isConsecutiveTag は 9999 → 1000 を連続とみなしたが、v2 は 10999 → 1000 (e2e/allowed-diffs.md)
        $this->assertFalse(TagNumber::fromInt(9999)->isFollowedBy(TagNumber::fromInt(1000)));
        $this->assertTrue(TagNumber::fromInt(9999)->isFollowedBy(TagNumber::fromInt(10000)));
    }

    public function test_range_matches_config(): void
    {
        $this->assertSame(config('pos.tag.min'), TagNumber::MIN);
        $this->assertSame(config('pos.tag.max'), TagNumber::MAX);
    }

    public function test_out_of_range_is_rejected(): void
    {
        foreach ([999, 11000, 0] as $n) {
            try {
                TagNumber::fromInt($n);
                $this->fail("{$n} should be rejected");
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
        // '0-000' は旧の「タグを発行しない」印。タグ番号ではない
        foreach (['0-000', '11-000', 'abc', '3-59', '3-5910', ''] as $s) {
            try {
                TagNumber::fromString($s);
                $this->fail("'{$s}' should be rejected");
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}
