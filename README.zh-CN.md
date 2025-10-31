# 服务器节点管理包

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/server-node-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/server-node-bundle)
[![PHP Version](https://img.shields.io/packagist/php-v/tourze/server-node-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/server-node-bundle)
[![Build Status](https://img.shields.io/github/actions/workflow/status/tourze/php-monorepo/test.yml?branch=master&style=flat-square)](https://github.com/tourze/php-monorepo/actions)
[![License](https://img.shields.io/packagist/l/tourze/server-node-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/server-node-bundle)
[![Code Coverage](https://img.shields.io/codecov/c/github/tourze/php-monorepo.svg?style=flat-square)](https://codecov.io/gh/tourze/php-monorepo)

为 Symfony 应用程序提供全面的服务器节点管理功能，
包括服务器监控、SSH 管理和流量统计。

## 目录

- [功能特性](#功能特性)
- [安装](#安装)
- [快速开始](#快速开始)
- [高级用法](#高级用法)
- [API 参考](#api-参考)
- [安全性](#安全性)
- [贡献指南](#贡献指南)
- [许可证](#许可证)

## 功能特性

- **服务器节点管理**：完整的服务器节点 CRUD 操作
- **SSH 连接支持**：安全的 SSH 连接管理，支持密码或密钥认证
- **流量监控**：实时监控上传/下载流量和带宽
- **系统信息**：自动收集服务器硬件和系统信息
- **状态管理**：跟踪节点状态（在线、离线、维护等）
- **EasyAdmin 集成**：与 `tourze/easy-admin-menu-bundle` 开箱即用的管理界面
- **API 密钥管理**：安全的 API 密钥生成和管理
- **多国家支持**：内置支持国际服务器位置

## 安装

```bash
composer require tourze/server-node-bundle
```

## 快速开始

### 系统要求

- PHP 8.1+
- Symfony 6.4+
- Doctrine ORM 3.0+
- EasyAdmin Bundle 4+

### 配置

#### 1. 注册 Bundle

在 `config/bundles.php` 中添加：

```php
return [
    // ...
    ServerNodeBundle\ServerNodeBundle::class => ['all' => true],
];
```

#### 2. 数据库架构

创建数据库表：

```bash
php bin/console doctrine:schema:update --force
```

### 基本使用

#### 创建服务器节点

```php
use ServerNodeBundle\Entity\Node;
use ServerNodeBundle\Enum\NodeStatus;

$node = new Node();
$node->setName('生产服务器 1');
$node->setSshHost('192.168.1.100');
$node->setSshPort(22);
$node->setSshUser('root');
$node->setSshPassword('your-password');
$node->setStatus(NodeStatus::ONLINE);
$node->setValid(true);

$entityManager->persist($node);
$entityManager->flush();
```

### 仓库使用

```php
use ServerNodeBundle\Repository\NodeRepository;

class YourService
{
    public function __construct(private NodeRepository $nodeRepository)
    {
    }
    
    public function getActiveNodes(): array
    {
        return $this->nodeRepository->findBy(['valid' => true]);
    }
    
    public function getOnlineNodes(): array
    {
        return $this->nodeRepository->findBy(['status' => NodeStatus::ONLINE]);
    }
}
```

### 节点属性

Node 实体包含全面的服务器信息：

- **基本信息**：名称、国家、域名、SSH 凭据
- **系统信息**：主机名、操作系统版本、内核版本、架构
- **硬件信息**：CPU 型号、频率、核心数、虚拟化技术
- **网络信息**：带宽、在线 IP、TCP 拥塞控制
- **流量统计**：总流量、上传、下载流量统计
- **状态信息**：当前运行状态和用户数量

## 高级用法

### 管理界面

当使用 `tourze/easy-admin-menu-bundle` 时，该包会自动提供管理菜单集成。
菜单包括：

- **服务器管理**
  - 服务器节点（完整的 CRUD 操作）

### 自定义节点状态处理

```php
use ServerNodeBundle\Enum\NodeStatus;

// 检查节点状态
if ($node->getStatus() === NodeStatus::OFFLINE) {
    // 处理离线节点
    $this->logger->warning('节点离线', ['node' => $node->getName()]);
}

// 更新节点状态
$node->setStatus(NodeStatus::MAINTAIN);
$this->entityManager->flush();
```

### SSH 连接管理

```php
use ServerNodeBundle\Exception\SshConnectionException;

try {
    // 您的 SSH 连接逻辑
    $connection = $this->sshService->connect($node);
} catch (SshConnectionException $e) {
    $this->logger->error('SSH 连接失败', [
        'node' => $node->getName(),
        'error' => $e->getMessage()
    ]);
}
```

## API 参考

### 节点状态枚举

```php
use ServerNodeBundle\Enum\NodeStatus;

// 可用状态
NodeStatus::INIT;           // 初始化
NodeStatus::ONLINE;         // 正常
NodeStatus::OFFLINE;        // 离线
NodeStatus::BANDWIDTH_OVER; // 流量用完
NodeStatus::MAINTAIN;       // 维护中
```

### SSH 连接异常

```php
use ServerNodeBundle\Exception\SshConnectionException;

try {
    // SSH 连接逻辑
} catch (SshConnectionException $e) {
    // 处理 SSH 连接错误
    echo $e->getMessage();
}
```

## 安全性

此包处理 SSH 凭据等敏感信息。请确保：

- 使用环境变量存储敏感配置
- 在管理界面启用适当的访问控制
- 定期轮换 SSH 密钥和密码
- 监控访问日志以发现未授权尝试

如发现安全漏洞，请发送邮件至 security@tourze.com，而不是使用问题跟踪器。

## 贡献指南

详情请参阅 [CONTRIBUTING.md](https://github.com/tourze/php-monorepo/blob/master/CONTRIBUTING.md)。

## 许可证

MIT 许可证。详情请参阅 [License File](LICENSE)。
