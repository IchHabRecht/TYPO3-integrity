<?php
namespace IchHabRecht\Integrity\Tests\Functional;

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
use IchHabRecht\Integrity\Storage\RegistryStorage;
use org\bovigo\vfs\vfsStream;
use TYPO3\CMS\Core\Package\Package;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Tests\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService;
use TYPO3\CMS\Extensionmanager\Utility\InstallUtility;

class ExtensionInformationRepositoryTest extends FunctionalTestCase
{
    /**
     * @var PackageManager
     */
    protected $packageManager;

    /**
     * @var array
     */
    protected $coreExtensionsToLoad = array(
        'extbase',
        'extensionmanager',
        'fluid',
    );

    /**
     * @var array
     */
    protected $testExtensionsToLoad = array(
        'typo3conf/ext/integrity',
    );

    /**
     * @var array
     */
    protected $expectedDifferences = array(
        'changed' => array(
            'ChangeLog',
        ),
        'removed' => array(
            'ext_icon.png',
        ),
        'new' => array(
            'NewFile.txt',
        ),
    );

    /**
     * @var string
     */
    protected $fixtureExtensionKey = 'integrity_test';

    public function setUp()
    {
        parent::setUp();

        $GLOBALS['LANG'] = GeneralUtility::makeInstance('TYPO3\\CMS\\Lang\\LanguageService');
        $GLOBALS['LANG']->init('default');

        $this->packageManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Package\\PackageManager');
    }

    public function tearDown()
    {
        parent::tearDown();

        GeneralUtility::rmdir($this->getInstancePath() . '/typo3conf/ext/' . $this->fixtureExtensionKey, true);
    }

    /**
     * @test
     */
    public function initialExtensionInformationAreClean()
    {
        $packages = $this->packageManager->getAvailablePackages();

        $repository = ExtensionInformationRepositoryFactory::create();
        $repository->addExtensionInformation($packages);

        // Recreate repository and its data
        $repository = ExtensionInformationRepositoryFactory::create();
        foreach ($packages as $package) {
            $this->assertSame(array(), $repository->findDifferentExtensionInformation($package));
        }
    }

    /**
     * @test
     */
    public function extensionInstallationWritesDatabaseData()
    {
        $this->assertSame(
            0,
            $this->getDatabaseConnection()->exec_SELECTcountRows(
                'uid',
                'sys_registry',
                'entry_namespace=' . $this->getDatabaseConnection()->fullQuoteStr(RegistryStorage::REGISTRY_NAMESPACE, 'sys_registry')
                . ' AND entry_key=' . $this->getDatabaseConnection()->fullQuoteStr($this->fixtureExtensionKey, 'sys_registry')
            ));

        $this->installFixtureExtension();

        $this->assertSame(
            1,
            $this->getDatabaseConnection()->exec_SELECTcountRows(
                'uid',
                'sys_registry',
                'entry_namespace=' . $this->getDatabaseConnection()->fullQuoteStr(RegistryStorage::REGISTRY_NAMESPACE, 'sys_registry')
                . ' AND entry_key=' . $this->getDatabaseConnection()->fullQuoteStr($this->fixtureExtensionKey, 'sys_registry')
            )
        );
    }

    /**
     * @test
     */
    public function extensionInformationChangesAreFound()
    {
        $this->installFixtureExtension();
        $this->changeFixtureExtensionFiles();

        $package = $this->packageManager->getPackage($this->fixtureExtensionKey);
        $repository = ExtensionInformationRepositoryFactory::create();

        $this->assertSame($this->expectedDifferences, $repository->findDifferentExtensionInformation($package));
    }

    /**
     * Copies and installs the fixture extension
     */
    protected function installFixtureExtension()
    {
        $instancePath = $this->getInstancePath();
        GeneralUtility::mkdir($instancePath . '/typo3conf/ext/' . $this->fixtureExtensionKey);
        GeneralUtility::copyDirectory(
            $instancePath . '/typo3conf/ext/integrity/Tests/Functional/Fixtures/Extensions/' . $this->fixtureExtensionKey,
            $instancePath . '/typo3conf/ext/' . $this->fixtureExtensionKey
        );

        /** @var ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');

        /** @var \TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension */
        $extension = $objectManager->get('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Extension');
        $extension->setExtensionKey($this->fixtureExtensionKey);
        $extension->setVersion('0.1.0');

        /** @var ExtensionManagementService $extensionManagementService */
        $extensionManagementService = $objectManager->get('TYPO3\\CMS\\Extensionmanager\\Service\\ExtensionManagementService');
        $extensionManagementService->installExtension($extension);
    }

    /**
     * Changes, removes and adds expected changes to fixture extension files
     */
    protected function changeFixtureExtensionFiles()
    {
        $package = $this->packageManager->getPackage($this->fixtureExtensionKey);
        $packagePath = $package->getPackagePath();

        if (!empty($this->expectedDifferences['changed'])) {
            foreach ($this->expectedDifferences['changed'] as $file) {
                file_put_contents($packagePath . $file, LF . LF . 'Hello World', FILE_APPEND);
            }
        }
        if (!empty($this->expectedDifferences['removed'])) {
            foreach ($this->expectedDifferences['removed'] as $file) {
                GeneralUtility::rmdir($packagePath . $file);
            }
        }
        if (!empty($this->expectedDifferences['new'])) {
            foreach ($this->expectedDifferences['new'] as $file) {
                file_put_contents($packagePath . $file, 'Hello World');
            }
        }
    }

    /**
     * @return string
     */
    protected function getInstancePath()
    {
        return property_exists($this, 'instancePath') ? $this->instancePath : parent::getInstancePath();
    }
}
