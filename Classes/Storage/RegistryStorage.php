<?php
namespace IchHabRecht\Integrity\Storage;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Nicole Cordes <typo3@cordes.co>, CPS-IT GmbH
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

use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class RegistryStorage implements StorageInterface
{
    const REGISTRY_NAMESPACE = 'integrity';

    const REGISTRY_KEY = 'extensionInformation';

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @param Registry|null $registry
     */
    public function __construct(Registry $registry = null)
    {
        $this->registry = $registry ?: GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Registry');
    }

    /**
     * @return array
     */
    public function getExtensionInformation()
    {
        $extensionInformation = array();
        $extensionKeys = $this->registry->get(static::REGISTRY_NAMESPACE, static::REGISTRY_KEY, array());
        foreach ($extensionKeys as $extensionKey) {
            $extensionInformation[$extensionKey] = $this->registry->get(static::REGISTRY_NAMESPACE, $extensionKey, array());
        }

        return $extensionInformation;
    }

    /**
     * @param array $extensionInformation
     */
    public function setExtensionInformation(array $extensionInformation)
    {
        $this->registry->set(static::REGISTRY_NAMESPACE, static::REGISTRY_KEY, array_keys($extensionInformation));
        foreach ($extensionInformation as $extensionKey => $information) {
            $this->registry->set(static::REGISTRY_NAMESPACE, $extensionKey, $information);
        }
    }
}
