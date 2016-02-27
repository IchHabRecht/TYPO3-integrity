<?php
namespace IchHabRecht\Integrity\ChecksumGenerator;

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

class Md5sumGenerator implements ChecksumGeneratorInterface
{
    /**
     * @param string $path
     * @param array $excludePattern
     * @return array
     */
    public function getChecksumsForPath($path, array $excludePattern = array())
    {
        // Always ensure trailing slash
        // This is needed for relative path calculation
        $path = rtrim($path, '/') . '/';

        $md5ChecksumArray = array();
        $filesArray = GeneralUtility::getAllFilesAndFoldersInPath(
            array(),
            $path,
            '',
            false,
            99,
            $this->generateExcludeExpression($excludePattern)
        );
        foreach ($filesArray as $file) {
            $relativeFileName = substr($file, strlen($path));
            $fileContent = GeneralUtility::getUrl($file);
            $md5ChecksumArray[$relativeFileName] = substr(md5($fileContent), 0, 4);
        }

        return $md5ChecksumArray;
    }

    /**
     * @param array $excludePattern
     * @return string
     */
    protected function generateExcludeExpression(array $excludePattern)
    {
        array_walk($excludePattern, function(&$item) {
            $item = preg_quote($item, '/');
        });

        return '(' . implode('|', $excludePattern) . ')';

    }
}
