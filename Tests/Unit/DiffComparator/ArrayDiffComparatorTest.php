<?php
namespace IchHabRecht\Integrity\Tests\Unit\DiffComparator;

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

use IchHabRecht\Integrity\DiffComparator\ArrayDiffComparator;
use TYPO3\CMS\Core\Tests\UnitTestCase;

class ArrayDiffComparatorTest extends UnitTestCase
{
    /**
     * @var ArrayDiffComparator
     */
    protected $comparator;

    protected function setUp()
    {
        parent::setUp();

        $this->comparator = new ArrayDiffComparator();
    }

    /**
     * @return array
     */
    public function getDifferencesReturnsExpectedResultsDataProvider()
    {
        return array(
            'no changes' => array(
                array(
                    'fileA' => '0815',
                    'foo/fileB' => '0815',
                    'foo/fileC' => '0815',
                    'foo/bar/fileD' => '0815',
                    'foo/bar/baz/fileE' => '0815',
                    'foo/bar/baz/fileF' => '0815',
                    'foo/bar/foobar/fileG' => '0815',
                ),
                array(
                    'fileA' => '0815',
                    'foo/fileB' => '0815',
                    'foo/fileC' => '0815',
                    'foo/bar/fileD' => '0815',
                    'foo/bar/baz/fileE' => '0815',
                    'foo/bar/baz/fileF' => '0815',
                    'foo/bar/foobar/fileG' => '0815',
                ),
                array(),
            ),
            'changed files' => array(
                array(
                    'fileA' => '0815',
                    'foo/fileB' => '0815',
                    'foo/fileC' => '0815',
                    'foo/bar/fileD' => '0815',
                    'foo/bar/baz/fileE' => '0815',
                    'foo/bar/baz/fileF' => '0815',
                    'foo/bar/foobar/fileG' => '0815',
                ),
                array(
                    'fileA' => '4242',
                    'foo/fileB' => '0815',
                    'foo/fileC' => '0815',
                    'foo/bar/fileD' => '4242',
                    'foo/bar/baz/fileE' => '0815',
                    'foo/bar/baz/fileF' => '0815',
                    'foo/bar/foobar/fileG' => '4242',
                ),
                array(
                    'changed' => array(
                        'fileA',
                        'foo/bar/fileD',
                        'foo/bar/foobar/fileG',
                    ),
                ),
            ),
            'removed files' => array(
                array(
                    'fileA' => '0815',
                    'foo/fileB' => '0815',
                    'foo/fileC' => '0815',
                    'foo/bar/fileD' => '0815',
                    'foo/bar/baz/fileE' => '0815',
                    'foo/bar/baz/fileF' => '0815',
                    'foo/bar/foobar/fileG' => '0815',
                ),
                array(
                    'fileA' => '0815',
                    'foo/fileB' => '0815',
                    'foo/bar/fileD' => '0815',
                    'foo/bar/baz/fileF' => '0815',
                    'foo/bar/foobar/fileG' => '0815',
                ),
                array(
                    'removed' => array(
                        'foo/fileC',
                        'foo/bar/baz/fileE',
                    ),
                ),
            ),
            'new files' => array(
                array(
                    'fileA' => '0815',
                    'foo/fileB' => '0815',
                    'foo/fileC' => '0815',
                    'foo/bar/fileD' => '0815',
                    'foo/bar/baz/fileE' => '0815',
                    'foo/bar/baz/fileF' => '0815',
                    'foo/bar/foobar/fileG' => '0815',
                ),
                array(
                    'fileA' => '0815',
                    'fileH' => '4242',
                    'foo/fileB' => '0815',
                    'foo/fileC' => '0815',
                    'foo/bar/fileD' => '0815',
                    'foo/bar/fileI' => '4242',
                    'foo/bar/baz/fileE' => '0815',
                    'foo/bar/baz/fileF' => '0815',
                    'foo/bar/foobar/fileG' => '0815',
                ),
                array(
                    'new' => array(
                        'fileH',
                        'foo/bar/fileI',
                    ),
                ),
            ),
            'all' => array(
                array(
                    'fileA' => '0815',
                    'foo/fileB' => '0815',
                    'foo/fileC' => '0815',
                    'foo/bar/fileD' => '0815',
                    'foo/bar/baz/fileE' => '0815',
                    'foo/bar/baz/fileF' => '0815',
                    'foo/bar/foobar/fileG' => '0815',
                ),
                array(
                    'fileA' => '4242',
                    'fileH' => '4242',
                    'foo/fileB' => '0815',
                    'foo/bar/fileD' => '4242',
                    'foo/bar/fileI' => '4242',
                    'foo/bar/baz/fileF' => '0815',
                    'foo/bar/foobar/fileG' => '4242',
                ),
                array(
                    'changed' => array(
                        'fileA',
                        'foo/bar/fileD',
                        'foo/bar/foobar/fileG',
                    ),
                    'removed' => array(
                        'foo/fileC',
                        'foo/bar/baz/fileE',
                    ),
                    'new' => array(
                        'fileH',
                        'foo/bar/fileI',
                    ),
                ),
            ),
        );
    }

    /**
     * @test
     * @param array $storedInformation
     * @param array $currentInformation
     * @param array $expected
     * @dataProvider getDifferencesReturnsExpectedResultsDataProvider
     */
    public function getDifferencesReturnsExpectedResults(array $storedInformation, array $currentInformation, array $expected)
    {
        $this->assertSame($expected, $this->comparator->getDifferences($storedInformation, $currentInformation));
    }
}
