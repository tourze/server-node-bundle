<?php

namespace ServerNodeBundle\Tests\Enum;

use PHPUnit\Framework\TestCase;
use ServerNodeBundle\Enum\NodeStatus;

class NodeStatusTest extends TestCase
{
    public function testEnumValues(): void
    {
        // 测试枚举值是否正确
        $this->assertEquals('INIT', NodeStatus::INIT->value);
        $this->assertEquals('ON-LINE', NodeStatus::ONLINE->value);
        $this->assertEquals('OFF-LINE', NodeStatus::OFFLINE->value);
        $this->assertEquals('BANDWIDTH-OVER', NodeStatus::BANDWIDTH_OVER->value);
        $this->assertEquals('MAINTAIN', NodeStatus::MAINTAIN->value);
    }
    
    public function testGetLabel(): void
    {
        // 测试各状态的标签是否正确
        $this->assertEquals('初始化', NodeStatus::INIT->getLabel());
        $this->assertEquals('正常', NodeStatus::ONLINE->getLabel());
        $this->assertEquals('离线', NodeStatus::OFFLINE->getLabel());
        $this->assertEquals('流量用完', NodeStatus::BANDWIDTH_OVER->getLabel());
        $this->assertEquals('维护中', NodeStatus::MAINTAIN->getLabel());
    }
} 