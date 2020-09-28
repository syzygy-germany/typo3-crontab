<?php

return [
    'has_running_processes' => [
        'path' => '/crontab/hasRunningProcesses',
        'target' => \Helhum\TYPO3\Crontab\Controller\CrontabModuleController::class . '::hasRunningProcesses'
    ],
];
