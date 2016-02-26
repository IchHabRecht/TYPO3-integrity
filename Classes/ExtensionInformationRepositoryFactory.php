<?php
namespace IchHabRecht\Integrity;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

class ExtensionInformationRepositoryFactory
{
    /**
     * @return ExtensionInformationRepository
     */
    static public function create()
    {
        $storage = GeneralUtility::makeInstance('IchHabRecht\\Integrity\\Storage\\RegistryStorage');
        $configurationReader = GeneralUtility::makeInstance('IchHabRecht\\Integrity\\ConfigurationReader\\ExtEmconfReader');
        $checksumGenerator = GeneralUtility::makeInstance('IchHabRecht\\Integrity\\ChecksumGenerator\\Md5sumGenerator');

        return GeneralUtility::makeInstance('IchHabRecht\\Integrity\\ExtensionInformationRepository', $storage, $configurationReader, $checksumGenerator);
    }
}
