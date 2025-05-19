# server-node-bundle

Server management bundle for Symfony applications.

## Installation

```bash
composer require tourze/server-node-bundle
```

## Usage

### Menu Integration

This bundle provides an AdminMenu service that integrates seamlessly with applications using `tourze/easy-admin-menu-bundle`. The service is automatically registered, requiring no additional configuration.

Menu items include:
- Server Management
  - Server Nodes
  - Applications
- Statistics & Monitoring
  - Node Statistics
  - Traffic Statistics

## Configuration

```yaml
# config/packages/server_node.yaml
server_node:
  # Add configuration options here
```

## Example

Coming soon.

## Documentation

- [Example link](https://example.com)
