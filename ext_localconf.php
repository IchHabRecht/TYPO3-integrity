<?php
defined('TYPO3_MODE') or die();

$signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher');
$signalSlotDispatcher->connect(
    'TYPO3\\CMS\\Extensionmanager\\Service\\ExtensionManagementService',
    'hasInstalledExtensions',
    'IchHabRecht\\Integrity\\Slot\\ExtensionInstallationSlot',
    'addExtensionInformation'
);
