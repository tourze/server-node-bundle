#!/bin/bash

apt update
apt install unzip curl wget -y

yum update
yum install unzip curl wget -y

# 定义一个数组包含服务名称
services=("python-firewall" "firewalld" "firewalld-filesystem" "firewalld.service")
# 遍历数组停止并禁用每个服务
for service in "${services[@]}"; do
  systemctl stop $service
  systemctl disable $service
done
# 停止与禁用ufw服务
/etc/init.d/ufw stop
ufw disable
# 设置SELinux为宽容模式
setenforce 0
