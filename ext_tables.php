<?php
(function () {
    /** @noinspection TranslationMissingInspection */
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'Helhum.TYPO3.Crontab',
        'system',
        'Module',
        'after:BeuserTxBeuser',
        [
            'CrontabModule' => 'list, toggleSchedule, terminate, edit, delete, scheduleForImmediateExecution',
        ],
        [
            'access' => 'admin',
            'icon' => 'EXT:crontab/Resources/Public/Icons/module-crontab.svg',
            'labels' => 'LLL:EXT:crontab/Resources/Private/Language/locallang_mod.xlf'
        ]
    );
    // this is only relevant for TYPO3 8.7
    if (isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['crontab'])) {
        $extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['crontab'], [false]);
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crontab'] = $extensionConfiguration;
    }
    if (!empty($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crontab']['hideSchedulerModule'])) {
        $GLOBALS['TBE_MODULES']['system'] = str_replace([',txschedulerM1', ',,'], ['',','], $GLOBALS['TBE_MODULES']['system']);
    }
})();
