<?php

namespace B13\Container\Domain\Factory;

/*
 * This file is part of TYPO3 CMS-based extension "container" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

use B13\Container\Domain\Model\Container;
use B13\Container\Tca\Registry;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ContainerFactory implements SingletonInterface
{
    /**
     * @var Database
     */
    protected $database;

    /**
     * @var Registry
     */
    protected $tcaRegistry;



    public function __construct(Database $database = null, Registry $tcaRegistry = null)
    {
        if ($database === null) {
            $database = GeneralUtility::makeInstance(Database::class);
        }
        $this->database = $database;
        if ($tcaRegistry === null) {
            $tcaRegistry = GeneralUtility::makeInstance(Registry::class);
        }
        $this->tcaRegistry = $tcaRegistry;
    }

    protected function containerByUid($uid)
    {
        return $this->database->fetchOneRecord($uid);
    }

    protected function defaultContainer(array $localizedContainer)
    {
        return $this->database->fetchOneDefaultRecord($localizedContainer);
    }

    /**
     * @param int $uid
     * @return Container
     */
    public function buildContainer($uid)
    {
        $record = $this->containerByUid($uid);
        #var_dump($record['CType']);
        if ($record === null) {
            throw new Exception('cannot fetch record with uid ' . $uid, 1576572850);
        }
        if (!$this->tcaRegistry->isContainerElement($record['CType'])) {
            throw new Exception('not a container element with uid ' . $uid, 1576572851);
        }

        $defaultRecord = null;
        $language = (int)$record['sys_language_uid'];
        if ($language > 0) {
            $defaultRecord = $this->defaultContainer($record);
            if ($defaultRecord === null) {
                // free mode
                $childRecords = $this->children($record, $language);
            } else {
                // connected mode
                $defaultRecords = $this->children($defaultRecord, 0);
                $childRecords = $this->localizedRecordsByDefaultRecords($defaultRecords, $language);
            }
        } else {
            $childRecords = $this->children($record, $language);
        }
        $childRecordByColPosKey = $this->recordsByColPosKey($childRecords);
        if ($defaultRecord === null) {
            $container = GeneralUtility::makeInstance(Container::class, $record, $childRecordByColPosKey, $language);
        } else {
            $container = GeneralUtility::makeInstance(Container::class, $defaultRecord, $childRecordByColPosKey, $language);
        }
        return $container;
    }

    protected function localizedRecordsByDefaultRecords(array $defaultRecords, $language)
    {
        $localizedRecords = $this->database->fetchOverlayRecords($defaultRecords, $language);
        $childRecords = $this->sortLocalizedRecordsByDefaultRecords($defaultRecords, $localizedRecords);
        return $childRecords;
    }

    protected function children(array $containerRecord, $language)
    {
        return $this->database->fetchRecordsByParentAndLanguage($containerRecord['uid'], $language);
    }

    /**
     * @param array $defaultRecords
     * @param array $localizedRecords
     * @return array
     */
    protected function sortLocalizedRecordsByDefaultRecords(array $defaultRecords, array $localizedRecords)
    {
        $sorted = [];
        foreach ($defaultRecords as $defaultRecord) {
            foreach ($localizedRecords as $localizedRecord) {
                if ($localizedRecord['l18n_parent'] === $defaultRecord['uid'] ||
                    $localizedRecord['l18n_parent'] === $defaultRecord['t3ver_oid']
                ) {
                    $sorted[] = $localizedRecord;
                }
            }
        }
        return $sorted;
    }

    /**
     * @param array $records
     * @return array
     */
    protected function recordsByColPosKey(array $records)
    {
        $recordsByColPosKey = [];
        foreach ($records as $record) {
            if (empty($recordsByColPosKey[$record['colPos']])) {
                $recordsByColPosKey[$record['colPos']] = [];
            }
            $recordsByColPosKey[$record['colPos']][] = $record;
        }
        return $recordsByColPosKey;
    }
}
