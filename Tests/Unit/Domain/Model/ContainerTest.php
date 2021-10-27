<?php

declare(strict_types=1);
namespace B13\Container\Tests\Unit\Domain\Model;

/*
 * This file is part of TYPO3 CMS-based extension "container" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

use B13\Container\Domain\Model\Container;
use B13\Container\Tca\Registry;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ContainerTest extends UnitTestCase
{
    protected $allContainerColumns = [200, 201, 202];

    /**
     * @return array
     */
    public function setupDataProvider()
    {
        return [
            [
                'containerRecord' => ['uid' => 1, 'CType' => 'myCType'],
                'childRecords' => [],
                'targetColPos' => 200,
                'expectedTarget' => -1,
            ],
            [
                'containerRecord' => ['uid' => 1, 'CType' => 'myCType'],
                'childRecords' => [[200 => ['uid' => 10, 'colPos' => 200]]],
                'targetColPos' => 200,
                'expectedTarget' => -1,
            ],
            [
                'containerRecord' => ['uid' => 1, 'CType' => 'myCType'],
                'childRecords' => [
                    200 => [['uid' => 10, 'colPos' => 200]]
                ],
                'targetColPos' => 201,
                'expectedTarget' => -10,
            ],
            [
                'containerRecord' => ['uid' => 1, 'CType' => 'myCType'],
                'childRecords' => [
                    200 => [['uid' => 11, 'colPos' => 200]]
                ],
                'targetColPos' => 200,
                'expectedTarget' => -1,
            ]
        ];
    }

    /**
     * @test
     * @dataProvider setupDataProvider
     */
    public function getFirstNewContentElementTargetTest(array $containerRecord, array $childRecords, int $targetColPos, int $expectedTarget): void
    {
        $tcaRegistry = $this->prophesize(Registry::class);
        $tcaRegistry->getAvailableColumnsColPos('myCType')->willReturn($this->allContainerColumns);
        $container = new Container($containerRecord, $childRecords, 0);
        $target = $container->getFirstNewContentElementTarget($targetColPos, $tcaRegistry->reveal());
        self::assertSame($expectedTarget, $target);
    }
}
