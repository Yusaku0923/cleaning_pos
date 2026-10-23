<?php

namespace App\Domain\Tag;

use InvalidArgumentException;

/**
 * タグ番号。数値で 1000〜10999、表示は 'X-XXX' (10000 以上は 'XX-XXX')。10999 の次は 1000。
 * 旧: Utility::convertTagFormat (表示)、OrdersController::addTag (次の番号)、Order::isConsecutiveTag (連続判定)。
 * 旧の '0-000' は「タグを発行しない」印でタグ番号ではないので、ここでは受け付けない。
 */
final class TagNumber
{
    public const MIN = 1000;

    public const MAX = 10999;

    private function __construct(private readonly int $value) {}

    public static function fromInt(int $n): self
    {
        if ($n < self::MIN || $n > self::MAX) {
            throw new InvalidArgumentException('タグ番号は '.self::MIN.'〜'.self::MAX." です: {$n}");
        }

        return new self($n);
    }

    /** '3-591' / '10-005' → TagNumber */
    public static function fromString(string $s): self
    {
        if (! preg_match('/^(\d{1,2})-(\d{3})$/', $s, $m)) {
            throw new InvalidArgumentException("タグ番号の形式ではありません: '{$s}'");
        }

        return self::fromInt((int) $m[1] * 1000 + (int) $m[2]);
    }

    /** 次の番号 (旧 addTag: 10999 以上なら 1000、それ以外は +1)。 */
    public function next(): self
    {
        return new self($this->value >= self::MAX ? self::MIN : $this->value + 1);
    }

    public function toInt(): int
    {
        return $this->value;
    }

    public function toString(): string
    {
        return intdiv($this->value, 1000).'-'.str_pad((string) ($this->value % 1000), 3, '0', STR_PAD_LEFT);
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    /** $b がこの番号のすぐ次か。10999 → 1000 も連続 (旧は 9999 → 1000。e2e/allowed-diffs.md)。 */
    public function isFollowedBy(self $b): bool
    {
        return $this->next()->value === $b->value;
    }

    public function equals(self $b): bool
    {
        return $this->value === $b->value;
    }
}
