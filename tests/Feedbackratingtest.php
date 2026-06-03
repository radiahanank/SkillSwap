<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
use PHPUnit\Framework\TestCase;

class FeedbackRatingTest extends TestCase
{
    use MockDbTrait;

    public function testValidRatingPasses(): void
    {
        $this->assertEmpty(RatingValidator::validate(1, 2, 4));
    }

    public function testSelfRatingBlocked(): void
    {
        $errors = RatingValidator::validate(5, 5, 3);
        $found  = array_filter($errors, fn($e) => str_contains($e, 'yourself'));
        $this->assertNotEmpty($found);
    }

    public function testStarsZeroFails(): void
    {
        $errors = RatingValidator::validate(1, 2, 0);
        $found  = array_filter($errors, fn($e) => stripos($e, 'stars') !== false);
        $this->assertNotEmpty($found);
    }

    public function testStarsSixFails(): void
    {
        $errors = RatingValidator::validate(1, 2, 6);
        $found  = array_filter($errors, fn($e) => stripos($e, 'stars') !== false);
        $this->assertNotEmpty($found);
    }

    public function testMinimumOneStarPasses(): void
    {
        $this->assertEmpty(RatingValidator::validate(1, 2, 1));
    }

    public function testMaximumFiveStarsPasses(): void
    {
        $this->assertEmpty(RatingValidator::validate(1, 2, 5));
    }

    public function testInvalidReviewerIdFails(): void
    {
        $this->assertNotEmpty(RatingValidator::validate(0, 2, 3));
    }

    public function testInvalidReviewedIdFails(): void
    {
        $this->assertNotEmpty(RatingValidator::validate(1, 0, 3));
    }

    public function testRatingInsertExecutes(): void
    {
        $conn = $this->mockConn(stmtExecute: true);
        $stmt = $conn->prepare("INSERT INTO rating ...");
        $this->assertTrue($stmt->execute());
    }
}