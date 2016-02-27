<?php
namespace IchHabRecht\Integrity\DiffComparator;

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

class ArrayDiffComparator implements DiffComparatorInterface
{
    /**
     * @param array $storedInformation
     * @param array $currentInformation
     * @return array
     */
    public function getDifferences(array $storedInformation, array $currentInformation)
    {
        $resultArray = array();

        $changed = array_diff_assoc($storedInformation, $currentInformation);
        if (!empty($changed)) {
            $resultArray['changed'] = array_flip($changed);
        }

        $storedInformationKeys = array_keys($storedInformation);
        $currentInformationKeys = array_keys($currentInformation);

        $removed = array_diff($storedInformationKeys, $currentInformationKeys);
        if (!empty($removed)) {
            $resultArray['removed'] = $removed;
        }

        $new = array_diff($currentInformationKeys, $storedInformationKeys);
        if (!empty($new)) {
            $resultArray['new'] = $new;
        }

        return $resultArray;
    }
}
