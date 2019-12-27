<?php
declare(strict_types=1);

/*
 * 2019 - EXT:fal_quota
 *
 * This file is subject to the terms and conditions defined in
 * file 'LICENSE.md', which is part of this source code package.
 */

use Mehrwert\FalQuota\Command\QuotaCommand;

return [
    'fal_quota:quota:update' => [
        'class' => QuotaCommand::class,
    ],
];
