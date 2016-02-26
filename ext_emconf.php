<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "integrity".
 *
 * Auto generated 21-11-2015 00:04
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array (
  'title' => 'Extension Integrity Check',
  'description' => 'Monitors the changes of your TYPO3 CMS extension files',
  'category' => 'be',
  'state' => 'stable',
  'uploadfolder' => 0,
  'createDirs' => '',
  'clearCacheOnLoad' => 0,
  'author' => 'Nicole Cordes',
  'author_email' => 'typo3@cordes.co',
  'author_company' => 'CPS-IT GmbH',
  'version' => '0.1.0',
  'constraints' => 
  array (
    'depends' => 
    array (
      'typo3' => '6.2.0-8.99.99',
    ),
    'conflicts' => 
    array (
    ),
    'suggests' => 
    array (
    ),
  ),
  'autoload' => array(
      'psr-4' => array(
          'IchHabRecht\\Integrity\\' => 'Classes',
      ),
  ),
  '_md5_values_when_last_written' => '',
);

