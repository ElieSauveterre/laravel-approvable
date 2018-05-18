<?php

namespace Victorlap\Approvable\Tests\Models;

class PostCannotBeApproved extends Post
{
    protected function currentUserCanApprove(): bool
    {
        return false;
    }
}
