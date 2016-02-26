<?php
defined('TYPO3_MODE') or die();

if (version_compare(TYPO3_branch, '7.0', '<')) {
    \TYPO3\CMS\Backend\Sprite\SpriteManager::addSingleIcons(
        array(
            'warning' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'Resources/Public/Images/Icons/warning.png',
        ),
        $_EXTKEY
    );
} else {
    $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Imaging\\IconRegistry');
    $iconRegistry->registerIcon(
        'integrity-warning',
        'TYPO3\\CMS\\Core\\Imaging\\IconProvider\\SvgIconProvider',
        array(
            'source' => 'EXT:integrity/Resources/Public/Images/Icons/warning.svg',
        )
    );
}
