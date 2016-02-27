<?php
namespace IchHabRecht\Integrity\Tests\Unit\ChecksumGenerator;

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

use IchHabRecht\Integrity\ChecksumGenerator\Md5sumGenerator;
use org\bovigo\vfs\vfsStream;
use TYPO3\CMS\Core\Tests\UnitTestCase;

class Md5sumGeneratorTest extends UnitTestCase
{
    /**
     * @var Md5sumGenerator
     */
    protected $subject;

    protected function setUp()
    {
        $this->subject = new Md5sumGenerator();
        parent::setUp();
    }

    /**
     * @test
     */
    public function getChecksumsForPathReturnsChecksumForFile()
    {
        vfsStream::setup('Test');
        file_put_contents('vfs://Test/File.txt', 'Hello World!');
        $expected = array(
            'File.txt' => 'ed07',
        );

        $this->assertSame($expected, $this->subject->getChecksumsForPath('vfs://Test'));
        $this->assertSame($expected, $this->subject->getChecksumsForPath('vfs://Test/'));
    }
}
