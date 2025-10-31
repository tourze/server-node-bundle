# Server Node Bundle

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/server-node-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/server-node-bundle)
[![PHP Version](https://img.shields.io/packagist/php-v/tourze/server-node-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/server-node-bundle)
[![Build Status](https://img.shields.io/github/actions/workflow/status/tourze/php-monorepo/test.yml?branch=master&style=flat-square)](https://github.com/tourze/php-monorepo/actions)
[![License](https://img.shields.io/packagist/l/tourze/server-node-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/server-node-bundle)
[![Code Coverage](https://img.shields.io/codecov/c/github/tourze/php-monorepo.svg?style=flat-square)](https://codecov.io/gh/tourze/php-monorepo)

A comprehensive server node management bundle for Symfony applications, providing 
server monitoring, SSH management, and traffic statistics.

## Table of Contents

- [Features](#features)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Advanced Usage](#advanced-usage)
- [API Reference](#api-reference)
- [Security](#security)
- [Contributing](#contributing)
- [License](#license)

## Features

- **Server Node Management**: Complete CRUD operations for server nodes
- **SSH Connection Support**: Secure SSH connection management with password or key authentication
- **Traffic Monitoring**: Real-time monitoring of upload/download traffic and bandwidth
- **System Information**: Automatic collection of server hardware and system information
- **Status Management**: Track node status (online, offline, maintenance, etc.)
- **EasyAdmin Integration**: Ready-to-use admin interface with `tourze/easy-admin-menu-bundle`
- **API Key Management**: Secure API key generation and management
- **Multi-Country Support**: Built-in support for international server locations

## Installation

```bash
composer require tourze/server-node-bundle
```

## Quick Start

### Requirements

- PHP 8.1+
- Symfony 6.4+
- Doctrine ORM 3.0+
- EasyAdmin Bundle 4+

### Configuration

#### 1. Bundle Registration

Add the bundle to your `config/bundles.php`:

```php
return [
    // ...
    ServerNodeBundle\ServerNodeBundle::class => ['all' => true],
];
```

#### 2. Database Schema

Create the database table:

```bash
php bin/console doctrine:schema:update --force
```

### Basic Usage

#### Creating a Server Node

```php
use ServerNodeBundle\Entity\Node;
use ServerNodeBundle\Enum\NodeStatus;

$node = new Node();
$node->setName('Production Server 1');
$node->setSshHost('192.168.1.100');
$node->setSshPort(22);
$node->setSshUser('root');
$node->setSshPassword('your-password');
$node->setStatus(NodeStatus::ONLINE);
$node->setValid(true);

$entityManager->persist($node);
$entityManager->flush();
```

### Repository Usage

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

### Node Properties

The Node entity includes comprehensive server information:

- **Basic Info**: Name, country, domain, SSH credentials
- **System Info**: Hostname, OS version, kernel version, architecture
- **Hardware Info**: CPU model, frequency, core count, virtualization tech
- **Network Info**: Bandwidth, online IP, TCP congestion control
- **Traffic Stats**: Total, upload, download flow statistics
- **Status**: Current operational status and user count

## Advanced Usage

### Admin Interface

The bundle automatically provides admin menu integration when using 
`tourze/easy-admin-menu-bundle`. The menu includes:

- **Server Management**
  - Server Nodes (with full CRUD operations)

### Custom Node Status Handling

```php
use ServerNodeBundle\Enum\NodeStatus;

// Check node status
if ($node->getStatus() === NodeStatus::OFFLINE) {
    // Handle offline node
    $this->logger->warning('Node is offline', ['node' => $node->getName()]);
}

// Update node status
$node->setStatus(NodeStatus::MAINTAIN);
$this->entityManager->flush();
```

### SSH Connection Management

```php
use ServerNodeBundle\Exception\SshConnectionException;

try {
    // Your SSH connection logic here
    $connection = $this->sshService->connect($node);
} catch (SshConnectionException $e) {
    $this->logger->error('SSH connection failed', [
        'node' => $node->getName(),
        'error' => $e->getMessage()
    ]);
}
```

## API Reference

### Node Status Enum

```php
use ServerNodeBundle\Enum\NodeStatus;

// Available statuses
NodeStatus::INIT;           // 初始化
NodeStatus::ONLINE;         // 正常
NodeStatus::OFFLINE;        // 离线
NodeStatus::BANDWIDTH_OVER; // 流量用完
NodeStatus::MAINTAIN;       // 维护中
```

### SSH Connection Exception

```php
use ServerNodeBundle\Exception\SshConnectionException;

try {
    // SSH connection logic
} catch (SshConnectionException $e) {
    // Handle SSH connection errors
    echo $e->getMessage();
}
```

## Security

This bundle handles sensitive information like SSH credentials. Please ensure:

- Use environment variables for sensitive configuration
- Enable proper access controls on admin interfaces
- Regularly rotate SSH keys and passwords
- Monitor access logs for unauthorized attempts

For security vulnerabilities, please email security@tourze.com instead of using 
the issue tracker.

## Contributing

Please see [CONTRIBUTING.md](https://github.com/tourze/php-monorepo/blob/master/CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
