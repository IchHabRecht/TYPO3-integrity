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
     * @var string
     */
    protected $baseDirectory = 'test';

    /**
     * @var Md5sumGenerator
     */
    protected $generator;

    protected function setUp()
    {
        parent::setUp();
        vfsStream::setup($this->baseDirectory);

        $this->generator = new Md5sumGenerator();
    }

    /**
     * @return array
     */
    public function getChecksumsForPathReturnsChecksumForFilesDataProvider()
    {
        $fileContent = 'Hello World!';
        $fileContentHash = 'ed07';

        return array(
            'one file' => array(
                array(
                    'file.txt' => $fileContent,
                ),
                array(),
                array(
                    'file.txt' => $fileContentHash,
                ),
            ),
            'empty directories' => array(
                array(
                    'foo' => array(
                        'bar' => array(
                            'baz' => array(),
                        ),
                    ),
                ),
                array(),
                array(),
            ),
            'complex structure' => array(
                array(
                    'fileA' => $fileContent,
                    'foo' => array(
                        'fileB' => $fileContent,
                        'fileC' => $fileContent,
                        'bar' => array(
                            'fileD' => $fileContent,
                            'baz' => array(
                                'fileE' => $fileContent,
                                'fileF' => $fileContent,
                            ),
                            'foobar' => array(
                                'fileG' => $fileContent,
                            ),
                        ),
                    ),
                ),
                array(),
                array(
                    'fileA' => $fileContentHash,
                    'foo/fileB' => $fileContentHash,
                    'foo/fileC' => $fileContentHash,
                    'foo/bar/fileD' => $fileContentHash,
                    'foo/bar/baz/fileE' => $fileContentHash,
                    'foo/bar/baz/fileF' => $fileContentHash,
                    'foo/bar/foobar/fileG' => $fileContentHash,
                ),
            ),
            'exclude pattern' => array(
                array(
                    'fileA' => $fileContent,
                    '.git' => array(
                        'index' => $fileContent,
                        'objects' => array(
                            '0a' => array(
                                '0815' => $fileContent,
                            ),
                        ),
                    ),
                    '.svn' => array(
                        'entries' => $fileContent,
                        'pristine' => array(
                            '0a' => array(
                                '0815' => $fileContent,
                            ),
                        ),
                    ),
                    'foo' => array(
                        'fileB' => $fileContent,
                        'fileC' => $fileContent,
                        'bar' => array(
                            'fileD' => $fileContent,
                            'baz' => array(
                                'fileE' => $fileContent,
                                'fileF' => $fileContent,
                            ),
                            'foobar' => array(
                                'fileG' => $fileContent,
                            ),
                        ),
                    ),
                ),
                array(
                    '.git',
                    '.svn',
                ),
                array(
                    'fileA' => $fileContentHash,
                    'foo/fileB' => $fileContentHash,
                    'foo/fileC' => $fileContentHash,
                    'foo/bar/fileD' => $fileContentHash,
                    'foo/bar/baz/fileE' => $fileContentHash,
                    'foo/bar/baz/fileF' => $fileContentHash,
                    'foo/bar/foobar/fileG' => $fileContentHash,
                ),
            ),
        );
    }

    /**
     * @test
     * @param array $structure
     * @param array $excludePattern
     * @param array $expected
     * @dataProvider getChecksumsForPathReturnsChecksumForFilesDataProvider
     */
    public function getChecksumsForPathReturnsChecksumForFiles(array $structure, array$excludePattern, array $expected)
    {
        $baseDirectory = vfsStream::url($this->baseDirectory);
        $this->createTestStructure($baseDirectory, $structure);

        $this->assertSame($expected, $this->generator->getChecksumsForPath($baseDirectory, $excludePattern));
        $this->assertSame($expected, $this->generator->getChecksumsForPath($baseDirectory . '/', $excludePattern));
    }

    /**
     * @param string $baseDirectory
     * @param array $structure
     */
    protected function createTestStructure($baseDirectory, array $structure)
    {
        if (!@is_dir($baseDirectory)) {
            mkdir($baseDirectory);
        }
        foreach ($structure as $key => $value) {
            if (is_array($value)) {
                $this->createTestStructure($baseDirectory . '/' . $key, $value);
            } else {
                file_put_contents($baseDirectory . '/' . $key, $value);
            }
        }
    }
}
