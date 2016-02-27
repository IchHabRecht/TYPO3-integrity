<?php
namespace IchHabRecht\Integrity\Slot;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 Nicole Cordes <typo3@cordes.co>, CPS-IT GmbH
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use IchHabRecht\Integrity\ExtensionInformationRepository;
use IchHabRecht\Integrity\ExtensionInformationRepositoryFactory;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Package\Package;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ProcessAvailableActionsSlot implements SingletonInterface
{
    /**
     * @var ExtensionInformationRepository
     */
    protected $extensionInformationRepository;

    /**
     * @var PackageManager
     */
    protected $packageManager;

    /**
     * @var string
     */
    protected $emptyIcon;

    /**
     * @var string
     */
    protected $warningIcon;

    public function __construct(PackageManager $packageManager = null, ExtensionInformationRepository $extensionInformationRepository = null)
    {
        $this->packageManager = $packageManager ?: GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Package\\PackageManager');
        $this->extensionInformationRepository = $extensionInformationRepository ?: ExtensionInformationRepositoryFactory::create();
    }

    /**
     * @param array $extension
     * @param array $actions
     */
    public function checkExtensionInformation($extension, array &$actions)
    {
        /** @var Package $package */
        $package = $this->packageManager->getPackage($extension['key']);
        $differences = $this->extensionInformationRepository->findDifferentExtensionInformation($package);
        $actions = $this->addActionAccordingToTypo3Version(!empty($differences), $actions);
    }

    /**
     * @param bool $hasChanges
     * @param array $actions
     * @return array
     */
    protected function addActionAccordingToTypo3Version($hasChanges, array $actions)
    {
        if ($hasChanges) {
            $actions[] = $this->getWarningIcon();
        } else {
            $actions[] = $this->getEmptyIcon();
        }

        return $actions;
    }

    /**
     * @return string
     */
    protected function getWarningIcon()
    {
        if (version_compare(TYPO3_branch, '7.0', '<')) {
            $this->warningIcon = IconUtility::getSpriteIcon(
                'extensions-integrity-warning',
                array(
                    'title' => $GLOBALS['LANG']->sL('LLL:EXT:integrity/Resources/Private/Language/locallang.xlf:modifiedFilesFound'),
                )
            );
        }
        if ($this->warningIcon === null) {
            $iconFactory = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Imaging\\IconFactory');
            $this->warningIcon = '<span class="btn btn-default" title="' . $GLOBALS['LANG']->sL('LLL:EXT:integrity/Resources/Private/Language/locallang.xlf:modifiedFilesFound') . '">'
                . $iconFactory->getIcon('integrity-warning', Icon::SIZE_SMALL)->render() . '</span>';
        }

        return $this->warningIcon;
    }

    /**
     * @return string
     */
    protected function getEmptyIcon()
    {
        if (version_compare(TYPO3_branch, '7.0', '<')) {
            return '';
        }
        if ($this->emptyIcon === null) {
            $iconFactory = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Imaging\\IconFactory');
            $this->emptyIcon = '<span class="btn btn-default disabled">'
                . $iconFactory->getIcon('empty-empty', Icon::SIZE_SMALL)->render() . '</span>';
        }

        return $this->emptyIcon;
    }
}
