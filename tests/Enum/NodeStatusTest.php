<?php

declare(strict_types=1);

namespace ServerNodeBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use ServerNodeBundle\Enum\NodeStatus;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(NodeStatus::class)]
final class NodeStatusTest extends AbstractEnumTestCase
{
    #[TestWith(['INIT', '初始化', NodeStatus::INIT])]
    #[TestWith(['ON-LINE', '正常', NodeStatus::ONLINE])]
    #[TestWith(['OFF-LINE', '离线', NodeStatus::OFFLINE])]
    #[TestWith(['BANDWIDTH-OVER', '流量用完', NodeStatus::BANDWIDTH_OVER])]
    #[TestWith(['MAINTAIN', '维护中', NodeStatus::MAINTAIN])]
    public function testValueAndLabel(string $expectedValue, string $expectedLabel, NodeStatus $status): void
    {
        $this->assertEquals($expectedValue, $status->value);
        $this->assertEquals($expectedLabel, $status->getLabel());
    }

    public function testFromWithValidValue(): void
    {
        $status = NodeStatus::from('INIT');
        $this->assertSame(NodeStatus::INIT, $status);

        $status = NodeStatus::from('ON-LINE');
        $this->assertSame(NodeStatus::ONLINE, $status);
    }

    public function testTryFromWithValidValue(): void
    {
        $status = NodeStatus::tryFrom('INIT');
        $this->assertSame(NodeStatus::INIT, $status);

        $status = NodeStatus::tryFrom('ON-LINE');
        $this->assertSame(NodeStatus::ONLINE, $status);
    }

    public function testLabelUniqueness(): void
    {
        $labels = [];
        foreach (NodeStatus::cases() as $status) {
            $label = $status->getLabel();
            $this->assertNotContains($label, $labels, "Label '{$label}' is not unique");
            $labels[] = $label;
        }
    }

    public function testValueUniqueness(): void
    {
        $values = [];
        foreach (NodeStatus::cases() as $status) {
            $value = $status->value;
            $this->assertNotContains($value, $values, "Value '{$value}' is not unique");
            $values[] = $value;
        }
    }

    public function testToArray(): void
    {
        $nodeStatus = NodeStatus::INIT;
        $array = $nodeStatus->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('value', $array);
        $this->assertArrayHasKey('label', $array);
        $this->assertEquals('INIT', $array['value']);
        $this->assertEquals('初始化', $array['label']);

        $onlineArray = NodeStatus::ONLINE->toArray();
        $this->assertEquals('ON-LINE', $onlineArray['value']);
        $this->assertEquals('正常', $onlineArray['label']);
    }
}
