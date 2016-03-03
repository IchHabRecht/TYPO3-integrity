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

use IchHabRecht\Integrity\ExtensionInformationRepositoryFactory;
use IchHabRecht\Integrity\Storage\RegistryStorage;
use org\bovigo\vfs\vfsStream;
use TYPO3\CMS\Core\Package\DependencyResolver;
use TYPO3\CMS\Core\Package\Package;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Tests\AccessibleObjectInterface;
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

    public function setUp()
    {
        parent::setUp();

        $this->packageManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Package\\PackageManager');
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
        $extensionKey = 'integrity_test';
        $this->assertSame(
            0,
            $this->getDatabaseConnection()->exec_SELECTcountRows(
                'uid',
                'sys_registry',
                'entry_namespace=' . $this->getDatabaseConnection()->fullQuoteStr(RegistryStorage::REGISTRY_NAMESPACE, 'sys_registry')
                . ' AND entry_key=' . $this->getDatabaseConnection()->fullQuoteStr($extensionKey, 'sys_registry')
            ));

        $this->installExtension($extensionKey);

        $this->assertSame(
            1,
            $this->getDatabaseConnection()->exec_SELECTcountRows(
                'uid',
                'sys_registry',
                'entry_namespace=' . $this->getDatabaseConnection()->fullQuoteStr(RegistryStorage::REGISTRY_NAMESPACE, 'sys_registry')
                . ' AND entry_key=' . $this->getDatabaseConnection()->fullQuoteStr($extensionKey, 'sys_registry')
            )
        );
    }

    /**
     * @test
     */
    public function extensionInformationChangesAreFound()
    {
        $extensionKey = 'integrity_test';
        $expectedDifferences = array(
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

        $this->installExtension($extensionKey);

        $package = $this->packageManager->getPackage($extensionKey);
        $this->changeExtensionFiles($package, $expectedDifferences);

        $repository = ExtensionInformationRepositoryFactory::create();
        $this->assertSame($expectedDifferences, $repository->findDifferentExtensionInformation($package));
    }

    /**
     * @param string $extensionKey
     */
    protected function installExtension($extensionKey)
    {
        if (!$this->packageManager instanceof \PHPUnit_Framework_MockObject_MockObject) {
            $this->packageManager = $this->getPackageManagerMock();
        }

        $currentPackageManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Package\\PackageManager');
        GeneralUtility::setSingletonInstance('TYPO3\\CMS\\Core\\Package\\PackageManager', $this->packageManager);

        mkdir('vfs://Test/Packages/' . $extensionKey);
        file_put_contents('vfs://Test/Packages/' . $extensionKey . '/ext_emconf.php', '<?php' . LF . '$EM_CONF[$_EXTKEY] = array();' . LF);
        file_put_contents('vfs://Test/Packages/' . $extensionKey . '/ChangeLog', '');
        file_put_contents('vfs://Test/Packages/' . $extensionKey . '/ext_icon.png', '');
        file_put_contents('vfs://Test/Packages/' . $extensionKey . '/ext_localconf.php', '');
        file_put_contents('vfs://Test/Packages/' . $extensionKey . '/ext_tables.php', '');

        // Set current extension path as additional packagesBasePaths
        // Needed since TYPO3 8 for symfony Finder
        $packagesBasePaths = $this->packageManager->_get('packagesBasePaths');
        $packagesBasePaths[$extensionKey] = 'vfs://Test/Packages/' . $extensionKey . '/';
        $this->packageManager->_set('packagesBasePaths', $packagesBasePaths);

        if (!isset($GLOBALS['LANG'])) {
            $GLOBALS['LANG'] = GeneralUtility::makeInstance('TYPO3\\CMS\\Lang\\LanguageService');
            $GLOBALS['LANG']->init('default');
        }

        /** @var InstallUtility|\PHPUnit_Framework_MockObject_MockObject $installUtilityMock */
        $installUtilityMock = $this->getMock('TYPO3\\CMS\\Extensionmanager\\Utility\\InstallUtility', array('install'));
        GeneralUtility::setSingletonInstance('TYPO3\\CMS\\Extensionmanager\\Utility\\InstallUtility', $installUtilityMock);

        /** @var ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');

        /** @var ExtensionManagementService|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject $extensionManagementService */
        $extensionManagementService = $objectManager->get('TYPO3\\CMS\\Extensionmanager\\Service\\ExtensionManagementService');

        /** @var \TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension */
        $extension = $objectManager->get('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Extension');
        $extension->setExtensionKey($extensionKey);
        $extension->setVersion('0.1.0');
        $extensionManagementService->installExtension($extension);

        GeneralUtility::removeSingletonInstance('TYPO3\\CMS\\Extensionmanager\\Utility\\InstallUtility', $installUtilityMock);
        GeneralUtility::setSingletonInstance('TYPO3\\CMS\\Core\\Package\\PackageManager', $currentPackageManager);
    }

    /**
     * @param Package $package
     * @param array $differences
     */
    protected function changeExtensionFiles(Package $package, array $differences)
    {
        $packagePath = $package->getPackagePath();

        if (!empty($differences['changed'])) {
            foreach ($differences['changed'] as $file) {
                file_put_contents($packagePath . $file, LF . LF . 'Hello World', FILE_APPEND);
            }
        }
        if (!empty($differences['removed'])) {
            foreach ($differences['removed'] as $file) {
                GeneralUtility::rmdir($packagePath . $file);
            }
        }
        if (!empty($differences['new'])) {
            foreach ($differences['new'] as $file) {
                file_put_contents($packagePath . $file, 'Hello World');
            }
        }
    }

    /**
     * @return PackageManager|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getPackageManagerMock() {
        vfsStream::setup('Test');

        /** @var DependencyResolver|\PHPUnit_Framework_MockObject_MockObject $dependencyResolverMock */
        $dependencyResolverMock = $this->getMock('TYPO3\\CMS\\Core\\Package\\DependencyResolver');
        $dependencyResolverMock->expects($this->any())->method('sortPackageStatesConfigurationByDependency')->willReturnArgument(0);

        /** @var PackageManager|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject $packageManagerMock */
        $packageManagerMock = $this->getAccessibleMock('TYPO3\\CMS\\Core\\Package\\PackageManager', array('dummy'));
        $packageManagerMock->injectDependencyResolver($dependencyResolverMock);

        mkdir('vfs://Test/Configuration');
        mkdir('vfs://Test/Packages');
        file_put_contents('vfs://Test/Configuration/PackageStates.php', "<?php return array ('packages' => array(), 'version' => 4); ");
        $packageManagerMock->_set('packageStatesPathAndFilename', 'vfs://Test/Configuration/PackageStates.php');
        $packageManagerMock->_set('packagesBasePath', 'vfs://Test/Packages/');
        $packageManagerMock->_set('packagesBasePaths', array('local' => 'vfs://Test/Packages'));

        return $packageManagerMock;
    }
}
