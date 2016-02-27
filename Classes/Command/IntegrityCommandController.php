<?php
namespace IchHabRecht\Integrity\Command;

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

use IchHabRecht\Integrity\ExtensionInformationRepositoryFactory;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Package\Package;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;

class IntegrityCommandController extends CommandController
{
    /**
     * Finds current extension changes in your system and sends information email.
     *
     * @param string $recipients Comma separated email addresses of recipients
     * @param string $senderEmail Sender email address
     * @param string $senderName Sender name
     * @param string $subject Email subject
     */
    public function checkCommand($recipients, $senderEmail = '', $senderName = '', $subject = 'TYPO3 Integrity Warning Notification')
    {
        $packageManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Package\\PackageManager');
        $extensionInformationRepository = ExtensionInformationRepositoryFactory::create();

        $diffArray = array();
        /** @var Package $package */
        foreach ($packageManager->getAvailablePackages() as $package) {
            $differences = $extensionInformationRepository->findDifferentExtensionInformation($package);
            if (empty($differences)) {
                continue;
            }
            $diffArray[$package->getPackageKey()] = array(
                'package' => $package,
                'differences' => $differences,
            );
        }

        if (empty($diffArray)) {
            return;
        }

        $this->sendNotificationMail($diffArray, $recipients, $senderEmail, $senderName, $subject);
    }

    /**
     * @param array $diffArray
     * @param string $recipients
     * @param string $senderEmail
     * @param string $senderName
     * @param string $subject
     */
    protected function sendNotificationMail(array $diffArray, $recipients, $senderEmail = '', $senderName = '', $subject = '')
    {
        /** @var MailMessage $mailMessage */
        $mailMessage = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Mail\\MailMessage');
        $mailMessage->setFrom($this->getSenderEmail($senderEmail), $this->getSenderName($senderName));
        $mailMessage->setTo($this->prepareEmailAddresses($recipients));
        $mailMessage->setSubject($subject);
        $mailBody = 'Dear TYPO3 administrator,' . PHP_EOL . PHP_EOL
            . 'Some extension changes were found in your system "' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] . '":.' . PHP_EOL;
        foreach ($diffArray as $diff) {
            $extensionKey = $diff['package']->getPackageKey();
            $mailBody .= PHP_EOL . "\t- " . $extensionKey . ' was changed.'
                . PHP_EOL . "\t  Find detailed information in attached file \"" . $extensionKey . '.txt"';

            $fileContent = 'Extension:' . PHP_EOL . "\t" . $extensionKey . PHP_EOL;
            $fileContent .= 'Changes since:' . PHP_EOL . "\t" . date('r', $diff['storedExtensionInformation']['timestamp']) . PHP_EOL;

            if (!empty($diff['differences']['changed'])) {
                $fileContent .= PHP_EOL . 'Changed files:' . PHP_EOL;
                array_walk($diff['differences']['changed'], array(
                    'TYPO3\\CMS\\Core\\Utility\\GeneralUtility',
                    'fixWindowsFilePath',
                ));
                usort($diff['differences']['changed'], array($this, 'sortFileArray'));
                foreach ($diff['differences']['changed'] as $file) {
                    $fileContent .= "\t" . $file . PHP_EOL;
                }
            }
            if (!empty($diff['differences']['removed'])) {
                $fileContent .= PHP_EOL . 'Removed files:' . PHP_EOL;
                usort($diff['differences']['removed'], array($this, 'sortFileArray'));
                foreach ($diff['differences']['removed'] as $file) {
                    $fileContent .= "\t" . $file . PHP_EOL;
                }
            }
            if (!empty($diff['differences']['new'])) {
                $fileContent .= PHP_EOL . 'New files:' . PHP_EOL;
                usort($diff['differences']['new'], array($this, 'sortFileArray'));
                foreach ($diff['differences']['new'] as $file) {
                    $fileContent .= "\t" . $file . PHP_EOL;
                }
            }
            $mailMessage->attach(\Swift_Attachment::newInstance($fileContent, $extensionKey . '.txt', 'text/plain'));
        }

        $mailMessage->setBody($mailBody, 'text/plain');
        $mailMessage->send();
    }

    /**
     * @param string $senderEmail
     * @return string
     */
    protected function getSenderEmail($senderEmail)
    {
        if (!empty($senderEmail) && GeneralUtility::validEmail($senderEmail)) {
            return $senderEmail;
        }
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'])
            && GeneralUtility::validEmail($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'])
        ) {
            return $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'];
        }

        $hostName = gethostname();

        return 'integrity@' . (!empty($hostName) ? $hostName : '172.0.0.1');
    }

    /**
     * @param string $senderName
     * @return string
     */
    protected function getSenderName($senderName)
    {
        if (!empty($senderName)) {
            return $senderName;
        }
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'])) {
            return $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'];
        }

        $hostName = gethostname();

        return !empty($hostName) ? $hostName . '@' . gethostbyname($hostName) : 'integrity@127.0.0.1';
    }

    /**
     * @param string $emailAddresses
     * @return array
     */
    protected function prepareEmailAddresses($emailAddresses)
    {
        $emailAddresses = GeneralUtility::trimExplode(',', $emailAddresses, true);
        $emailAddresses = array_unique($emailAddresses);

        $validEmailAddresses = array();
        foreach ($emailAddresses as $emailAddress) {
            if (GeneralUtility::validEmail($emailAddress)) {
                $validEmailAddresses[] = $emailAddress;
            }
        }

        return $validEmailAddresses;
    }

    /**
     * @param string $file1
     * @param string $file2
     * @return int
     */
    protected function sortFileArray($file1, $file2)
    {
        $strposFile1 = strpos($file1, '/');
        $strposFile2 = strpos($file2, '/');
        if ($strposFile1 === false && $strposFile2 === false
            || $strposFile1 !== false && $strposFile2 !== false
        ) {
            return strcmp($file1, $file2);
        } else {
            return $strposFile1 === false ? -1 : 1;
        }
    }
}
