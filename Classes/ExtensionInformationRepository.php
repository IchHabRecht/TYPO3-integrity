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

use IchHabRecht\Integrity\ChecksumGenerator\ChecksumGeneratorInterface;
use IchHabRecht\Integrity\ConfigurationReader\ConfigurationReaderInterface;
use IchHabRecht\Integrity\Storage\StorageInterface;
use TYPO3\CMS\Core\Package\Package;

class ExtensionInformationRepository
{
    /**
     * @var ChecksumGeneratorInterface
     */
    protected $checksumGenerator;

    /**
     * @var ConfigurationReaderInterface
     */
    protected $configurationReader;

    /**
     * @var array
     */
    protected $extensionInformation;

    /**
     * @var StorageInterface
     */
    protected $storage;

    /**
     * @param StorageInterface $storage
     * @param ConfigurationReaderInterface $configurationReader
     * @param ChecksumGeneratorInterface $checksumGenerator
     */
    public function __construct(StorageInterface $storage, ConfigurationReaderInterface $configurationReader, ChecksumGeneratorInterface $checksumGenerator)
    {
        $this->storage = $storage;
        $this->configurationReader = $configurationReader;
        $this->checksumGenerator = $checksumGenerator;

        $extensionInformation = $this->storage->getExtensionInformation();
        $this->validateExtensionInformation($extensionInformation);
        $this->extensionInformation = $extensionInformation;
    }

    /**
     * @param Package[] $packages
     */
    public function addExtensionInformation(array $packages)
    {
        /** @var Package $package */
        foreach ($packages as $package) {
            if (isset($this->extensionInformation[$package->getPackageKey()])) {
                continue;
            }
            $this->setExtensionInformationForPackage($package);
        }
        $this->storage->setExtensionInformation($this->extensionInformation);
    }

    /**
     * @param Package $package
     */
    protected function setExtensionInformationForPackage(Package $package)
    {
        $checksums = $this->configurationReader->getChecksumsForExtension($package);
        $this->extensionInformation[$package->getPackageKey()] = array(
            'timestamp' => time(),
            'checksums' => $checksums ?: $this->checksumGenerator->getChecksumsForPath($package->getPackagePath()),
        );
    }

    /**
     * @param array $extensionInformation
     */
    protected function validateExtensionInformation(array $extensionInformation)
    {
        foreach ($extensionInformation as $extensionKey => $information) {
            if (!isset($information['timestamp']) || !isset($information['checksums'])) {
                throw  new \UnexpectedValueException('Stored extension information for key "' . $extensionKey . '" are invalid',
                    1448388820129);
            }
        }
    }
}
