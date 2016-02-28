<?php
defined('TYPO3_MODE') or die();

$signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher');

$signalSlotDispatcher->connect(
    'TYPO3\\CMS\\Extensionmanager\\Service\\ExtensionManagementService',
    'hasInstalledExtensions',
    'IchHabRecht\\Integrity\\Slot\\ExtensionInstallationSlot',
    'addExtensionInformation'
);

$signalSlotDispatcher->connect(
    'TYPO3\\CMS\\Extensionmanager\\ViewHelpers\\ProcessAvailableActionsViewHelper',
    'processActions',
    'IchHabRecht\\Integrity\\Slot\\ProcessAvailableActionsSlot',
    'checkExtensionInformation'
);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] =
    'IchHabRecht\\Integrity\\Command\\IntegrityCommandController';
