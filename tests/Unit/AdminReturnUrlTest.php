<?php

namespace Tests\Unit;

use App\Support\AdminReturnUrl;
use PHPUnit\Framework\TestCase;

class AdminReturnUrlTest extends TestCase
{
    public function test_to_redirect_target_strips_base_path_once_when_followed_by_slash(): void
    {
        $this->assertSame(
            '/final-assy',
            AdminReturnUrl::toRedirectTarget('/monitoring-defect/public/final-assy', '/monitoring-defect/public')
        );
    }

    public function test_to_redirect_target_preserves_query_string_and_fragment(): void
    {
        $this->assertSame(
            '/final-assy?page=2&category=all#section',
            AdminReturnUrl::toRedirectTarget(
                '/monitoring-defect/public/final-assy?page=2&category=all#section',
                '/monitoring-defect/public'
            )
        );
    }

    public function test_to_redirect_target_without_base_path_returns_unchanged(): void
    {
        $this->assertSame(
            '/final-assy?page=2',
            AdminReturnUrl::toRedirectTarget('/final-assy?page=2', '')
        );
        $this->assertSame(
            '/final-assy?page=2',
            AdminReturnUrl::toRedirectTarget('/final-assy?page=2', '/')
        );
    }

    public function test_to_redirect_target_returns_null_for_null_input(): void
    {
        $this->assertNull(AdminReturnUrl::toRedirectTarget(null, '/monitoring-defect/public'));
    }

    public function test_to_redirect_target_does_not_strip_partial_base_path_match(): void
    {
        $this->assertSame(
            '/monitoring-defect-other/final-assy',
            AdminReturnUrl::toRedirectTarget('/monitoring-defect-other/final-assy', '/monitoring-defect')
        );
    }
}
