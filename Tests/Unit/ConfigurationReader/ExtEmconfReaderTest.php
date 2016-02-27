<?php
namespace IchHabRecht\Integrity\Tests\Unit\ConfigurationReader;

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

use IchHabRecht\Integrity\ConfigurationReader\ExtEmconfReader;
use org\bovigo\vfs\vfsStream;
use TYPO3\CMS\Core\Package\Package;
use TYPO3\CMS\Core\Tests\UnitTestCase;

class ExtEmconfReaderTest extends UnitTestCase
{
    /**
     * @var string
     */
    protected $baseDirectory = 'typo3conf/ext';

    /**
     * @var ExtEmconfReader
     */
    protected $reader;

    protected function setUp()
    {
        parent::setUp();
        vfsStream::setup($this->baseDirectory);

        $this->reader = new ExtEmconfReader();
    }

    /**
     * @test
     */
    public function getChecksumsForExtensionReturnsEmptyArray()
    {
        $package = $this->createPackage('package');

        $this->assertSame(array(), $this->reader->getChecksumsForExtension($package));
    }

    /**
     * @test
     */
    public function getChecksumsForExtensionReturnsUnserializedArray() {
        $package = $this->createPackage('package');
        file_put_contents(
            $package->getPackagePath() . 'ext_emconf.php',
            '<?php' . LF
            . '$EM_CONF[$_EXTKEY] = array (' . LF
            . '\'_md5_values_when_last_written\' => \'' . serialize(array('test')) . '\'' . LF
            . ');' . LF
        );

        $this->assertSame(array('test'), $this->reader->getChecksumsForExtension($package));
    }

    /**
     * @param string $packageKey
     * @return Package
     */
    protected function createPackage($packageKey)
    {
        $packagePath = vfsStream::url($this->baseDirectory) . '/' . $packageKey . '/';
        mkdir($packagePath);
        file_put_contents($packagePath . 'composer.json', '{"name": "ichhabrecht/'. $packageKey . '", "type": "typo3-cms-test-extension"}');
        file_put_contents($packagePath . 'ext_emconf.php', '');

        $packageManager = $this->getMock('TYPO3\\CMS\\Core\\Package\\PackageManager');
        $packageManager->expects($this->any())->method('isPackageKeyValid')->willReturn(true);

        return new Package($packageManager, $packageKey, $packagePath);
    }
}
