#!/bin/bash

# 应用凭证和目标URL
API_KEY="{{ apiKey }}"
API_SECRET="{{ apiSecret }}"
COLLECT_URL="{{ collectUrl }}"
INTERFACE="{{ mainInterface }}" # 主网卡

# 自动获取脚本的完整路径
SCRIPT_PATH="$(realpath $0)"

# 检查是否在crontab中，如果不在，则添加
(crontab -l 2>/dev/null | grep -qF "$SCRIPT_PATH") || (crontab -l 2>/dev/null; echo "*/1 * * * * /bin/bash $SCRIPT_PATH") | crontab -

# 检查sha256sum是否存在，如果不存在则尝试使用openssl
if ! command -v sha256sum &> /dev/null && ! command -v openssl &> /dev/null; then
    echo "Error: No suitable command for generating a SHA256 hash."
    exit 1
fi

hash_with_sha256sum() {
    echo -n "$1" | sha256sum | awk '{print $1}'
}

hash_with_openssl() {
    echo -n "$1" | openssl dgst -sha256 | awk '{print $2}'
}

get_system_version() {
    if [ -f /etc/os-release ]; then
        . /etc/os-release
        echo "${NAME} ${VERSION}"
    elif [ -f /etc/lsb-release ]; then
        . /etc/lsb-release
        echo "${DISTRIB_ID} ${DISTRIB_RELEASE}"
    elif [ -f /etc/issue ]; then
        cat /etc/issue | cut -d '\\' -f 1
    elif [ -f /etc/debian_version ]; then
        echo "Debian $(cat /etc/debian_version)"
    else
        echo "Unknown OS"
    fi
}

# 选择合适的哈希命令
if command -v sha256sum &> /dev/null; then
    HASH_CMD="hash_with_sha256sum"
else
    HASH_CMD="hash_with_openssl"
fi

# 检查curl是否安装
if ! command -v curl &> /dev/null; then
    echo "curl could not be found, trying to install it."
    if [ -f /etc/debian_version ]; then
        apt-get update && apt-get install -y curl || exit
    elif [ -f /etc/redhat-release ]; then
        yum install -y curl || exit
    elif [ -f /etc/arch-release ]; then
        pacman -Sy curl || exit
    else
        echo "Could not install curl. Your distribution is not supported."
        exit 1
    fi
fi
# sysstat
if ! command -v iostat &> /dev/null; then
    echo "sysstat could not be found, trying to install it."
    if [ -f /etc/debian_version ]; then
        apt-get update && apt-get install -y sysstat || exit
    elif [ -f /etc/redhat-release ]; then
        yum install -y sysstat || exit
    elif [ -f /etc/arch-release ]; then
        pacman -Sy sysstat || exit
    else
        echo "Could not install sysstat. Your distribution is not supported."
        exit 1
    fi
fi
# jq
if ! command -v jq &> /dev/null; then
    echo "jq could not be found, trying to install it."
    if [ -f /etc/debian_version ]; then
        apt-get update && apt-get install -y jq || exit
    elif [ -f /etc/redhat-release ]; then
        yum install -y epel-release jq || exit
    elif [ -f /etc/arch-release ]; then
        pacman -Sy jq || exit
    else
        echo "Could not install jq. Your distribution is not supported."
        exit 1
    fi
fi
# vnstat
if ! command -v vnstat &> /dev/null; then
    echo "vnstat could not be found, trying to install it."
    if [ -f /etc/debian_version ]; then
        apt-get update && apt-get install -y vnstat || exit
    elif [ -f /etc/redhat-release ]; then
        yum install -y epel-release vnstat || exit
    elif [ -f /etc/arch-release ]; then
        pacman -Sy vnstat || exit
    else
        echo "Could not install vnstat. Your distribution is not supported."
        exit 1
    fi
fi

# 收集不可变/少变指标
hostname=$(hostname)
cpu_model=$(grep -m 1 'model name' /proc/cpuinfo | awk -F': ' '{print $2}')
cpu_max_freq=$(grep -m 1 'cpu MHz' /proc/cpuinfo | awk -F': ' '{print $2}')
cpu_count=$(nproc)
system_version=$(get_system_version)
kernel_version=$(uname -r)
system_arch=$(uname -m)
system_uuid=$(dmidecode -s system-uuid 2>/dev/null)
tcp_congestion_control=$(sysctl net.ipv4.tcp_congestion_control | awk '{print $3}')

# 初始化虚拟化变量为空
virtualization_tech=""
# 方法1: 使用systemd-detect-virt（当系统使用systemd时）
if command -v systemd-detect-virt &> /dev/null; then
    VIRT_TECH=$(systemd-detect-virt)
    if [ "$VIRT_TECH" != "none" ] && [ -n "$VIRT_TECH" ]; then
        virtualization_tech=$VIRT_TECH
    fi
fi
# 方法2: 检查/proc/cpuinfo
if grep -q 'hypervisor' /proc/cpuinfo; then
    virtualization_tech="Generic Hypervisor detected"
fi
# 方法3: 检查dmesg
if dmesg | grep -i 'hypervisor' &> /dev/null; then
    virtualization_tech="Generic Hypervisor detected from dmesg"
fi
# 方法4: 检查DMI信息
if [ -f /sys/class/dmi/id/product_name ]; then
    PRODUCT_NAME=$(cat /sys/class/dmi/id/product_name)
    case $PRODUCT_NAME in
        *KVM*)
            virtualization_tech="KVM"
            ;;
        *VirtualBox*)
            virtualization_tech="VirtualBox"
            ;;
        *VMware*)
            virtualization_tech="VMware"
            ;;
        *Xen*)
            virtualization_tech="Xen"
            ;;
#        *Microsoft Virtual PC*)
#            virtualization_tech="Microsoft Virtual PC"
#            ;;
        *Hyper-V*)
            virtualization_tech="Hyper-V"
            ;;
    esac
fi
# 方法5: 使用virt-what工具（需要root权限）
if command -v virt-what &> /dev/null; then
    VIRT_WHAT=$(virt-what)
    if [ -n "$VIRT_WHAT" ]; then
        virtualization_tech=$VIRT_WHAT
    fi
fi

echo "主机名: $hostname";
echo "CPU型号: $cpu_model";
echo "CPU频率: $cpu_max_freq";
echo "CPU核心: $cpu_count";
echo "操作系统: $system_version";
echo "内核版本: $kernel_version";
echo "系统架构: $system_arch";
echo "系统UUID: $system_uuid";
echo "TCP阻塞算法: $tcp_congestion_control";
echo "虚拟化技术: $virtualization_tech";

# 收集会变化的指标

# 读取 /proc/loadavg 文件内容
read load_one_minute load_five_minutes load_fifteen_minutes process_running_total last_pid < /proc/loadavg
# 将运行队列中的进程数和活跃的进程总数分开
process_running=${process_running_total%/*}
process_total=${process_running_total#*/}
echo "过去1分钟的平均负载: $load_one_minute"
echo "过去5分钟的平均负载: $load_five_minutes"
echo "过去15分钟的平均负载: $load_fifteen_minutes"
echo "当前运行队列中的进程数: $process_running"
echo "系统中活跃的进程总数: $process_total"
echo "最近运行的进程ID: $last_pid"

# 运行 vmstat 并通过管道传递给 awk 命令以获取最后一行
vmstat_output=$(vmstat | awk 'NR==3')
# 使用 set 命令将输出分配到位置参数中
set -- $vmstat_output
# 分配变量
process_waiting_for_run=$1
process_uninterruptible_sleep=$2
memory_swap_used=$3
memory_free=$4
memory_buffer=$5
memory_cache=$6
#si=$7
#so=$8
#bi=$9
#bo=${10}
#in=${11}
#cs=${12}
cpu_user_percent=${13}
cpu_system_percent=${14}
cpu_idle_percent=${15}
cpu_disk_io_wait_time_percent=${16}
cpu_stolen_percent=${17}
# 输出变量，用于验证
echo "Number of processes waiting for run time: $process_waiting_for_run"
echo "Number of processes in uninterruptible sleep: $process_uninterruptible_sleep"
echo "Amount of swap used: $memory_swap_used"
echo "Amount of free memory: $memory_free"
echo "Amount of buffer memory: $memory_buffer"
echo "Amount of cache memory: $memory_cache"
#echo "Amount of memory swapped in from disk: $si"
#echo "Amount of memory swapped to disk: $so"
#echo "Blocks received from a block device (blocks in): $bi"
#echo "Blocks sent to a block device (blocks out): $bo"
#echo "Number of interrupts per second: $in"
#echo "Number of context switches per second: $cs"
echo "User time as a percentage of total CPU time: $cpu_user_percent"
echo "System time as a percentage of total CPU time: $cpu_system_percent"
echo "Idle time as a percentage of total CPU time: $cpu_idle_percent"
echo "I/O wait time as a percentage of total CPU time: $cpu_disk_io_wait_time_percent"
echo "Stolen time as a percentage of total CPU time: $cpu_stolen_percent"

# 使用free命令获取内存状态，并使用awk抽取数据行和列
read memory_total memory_used memory_free memory_shared memory_buffer memory_available < <(free | awk '/^Mem:/{print $2,$3,$4,$5,$6,$7}')
# 打印变量值，确认赋值成功
echo "内存总量: $memory_total KB"
echo "已使用量: $memory_used KB"
echo "空闲量: $memory_free KB"
echo "共享量: $memory_shared KB"
echo "缓存/缓冲区量: $memory_buffer KB"
echo "可用量: $memory_available KB"

# 统计TCP连接情况 初始化所有状态的计数为0
declare -A tcp_state_count=(
    [ESTAB]=0
    [LISTEN]=0
    [SYN_SENT]=0
    [SYN_RECV]=0
    [FIN_WAIT_1]=0
    [FIN_WAIT_2]=0
    [TIME_WAIT]=0
    [CLOSE_WAIT]=0
    [CLOSING]=0
    [LAST_ACK]=0
)
# 使用ss命令获取TCP连接状态并计数
while read number state; do
    # 替换状态名称中的'-'为'_'
    state=${state//-/_}
    tcp_state_count[$state]=$number
done < <(ss -t -a | awk '$1 ~ /^(ESTAB|LISTEN|SYN_SENT|SYN_RECV|FIN_WAIT_1|FIN_WAIT_2|TIME_WAIT|CLOSE_WAIT|CLOSING|LAST_ACK)$/ { gsub("-","_",$1); print $1 }' | sort | uniq -c)
# 输出变量，用于验证
for state in "${!tcp_state_count[@]}"; do
    echo "TCP $state: ${tcp_state_count[$state]}"
done
udp_count=$(ss -u -a | grep -vc 'State')
echo "TCP_ESTABLISH: ${tcp_state_count[ESTAB]}"
echo "TCP_LISTEN: ${tcp_state_count[LISTEN]}"
echo "TCP_SYN_SENT: ${tcp_state_count[SYN_SENT]}"
echo "TCP_SYN_RECEIVED: ${tcp_state_count[SYN_RECV]}"
echo "TCP_FIN_WAIT_1: ${tcp_state_count[FIN_WAIT_1]}"
echo "TCP_FIN_WAIT_2: ${tcp_state_count[FIN_WAIT_2]}"
echo "TCP_TIME_WAIT: ${tcp_state_count[TIME_WAIT]}"
echo "TCP_CLOSE_WAIT: ${tcp_state_count[CLOSE_WAIT]}"
echo "TCP_CLOSING: ${tcp_state_count[CLOSING]}"
echo "TCP_LAST_ACK: ${tcp_state_count[LAST_ACK]}"
echo "当前UDP连接数: $udp_count"

# 统计发包量/带宽情况 /sys/class/net/<interface>/statistics/ 目录下的文件显示的是自系统启动以来的总计数
RX_BYTES_FILE="/sys/class/net/${INTERFACE}/statistics/rx_bytes"
TX_BYTES_FILE="/sys/class/net/${INTERFACE}/statistics/tx_bytes"
RX_PACKETS_FILE="/sys/class/net/${INTERFACE}/statistics/rx_packets"
TX_PACKETS_FILE="/sys/class/net/${INTERFACE}/statistics/tx_packets"
# 读取初始值
initial_rx_bytes=$(cat $RX_BYTES_FILE)
initial_tx_bytes=$(cat $TX_BYTES_FILE)
initial_rx_packets=$(cat $RX_PACKETS_FILE)
initial_tx_packets=$(cat $TX_PACKETS_FILE)
# 等待一定时间，比如1秒
sleep 1
# 读取1秒后的值
final_rx_bytes=$(cat $RX_BYTES_FILE)
final_tx_bytes=$(cat $TX_BYTES_FILE)
final_rx_packets=$(cat $RX_PACKETS_FILE)
final_tx_packets=$(cat $TX_PACKETS_FILE)
# 计算每秒的入/出带宽和包量
rx_bandwidth=$(( (final_rx_bytes - initial_rx_bytes) * 8 ))
tx_bandwidth=$(( (final_tx_bytes - initial_tx_bytes) * 8 ))
rx_packets=$(( final_rx_packets - initial_rx_packets ))
tx_packets=$(( final_tx_packets - initial_tx_packets ))
# 输出结果
echo "网卡名: $INTERFACE"
echo "入带宽: $rx_bandwidth bits/sec"
echo "出带宽: $tx_bandwidth bits/sec"
echo "入包量: $rx_packets packets/sec"
echo "出包量: $tx_packets packets/sec"

vnstat_total_rx=$(vnstat -i eth0  --json --limit 1 | jq '.interfaces[0].traffic.total.rx')
vnstat_total_tx=$(vnstat -i eth0  --json --limit 1 | jq '.interfaces[0].traffic.total.tx')
vnstat_fiveminute_rx=$(vnstat -i eth0  --json --limit 1 | jq '.interfaces[0].traffic.fiveminute[0].rx')
vnstat_fiveminute_tx=$(vnstat -i eth0  --json --limit 1 | jq '.interfaces[0].traffic.fiveminute[0].tx')
vnstat_hour_rx=$(vnstat -i eth0  --json --limit 1 | jq '.interfaces[0].traffic.hour[0].rx')
vnstat_hour_tx=$(vnstat -i eth0  --json --limit 1 | jq '.interfaces[0].traffic.hour[0].tx')
vnstat_day_rx=$(vnstat -i eth0  --json --limit 1 | jq '.interfaces[0].traffic.day[0].rx')
vnstat_day_tx=$(vnstat -i eth0  --json --limit 1 | jq '.interfaces[0].traffic.day[0].tx')
vnstat_month_rx=$(vnstat -i eth0  --json --limit 1 | jq '.interfaces[0].traffic.month[0].rx')
vnstat_month_tx=$(vnstat -i eth0  --json --limit 1 | jq '.interfaces[0].traffic.month[0].tx')
vnstat_year_rx=$(vnstat -i eth0  --json --limit 1 | jq '.interfaces[0].traffic.year[0].rx')
vnstat_year_tx=$(vnstat -i eth0  --json --limit 1 | jq '.interfaces[0].traffic.year[0].tx')
echo "vnstat_total_rx: $vnstat_total_rx bits/sec"
echo "vnstat_total_tx: $vnstat_total_tx bits/sec"
echo "vnstat_fiveminute_rx: $vnstat_fiveminute_rx bits/sec"
echo "vnstat_fiveminute_tx: $vnstat_fiveminute_tx bits/sec"
echo "vnstat_hour_rx: $vnstat_hour_rx bits/sec"
echo "vnstat_hour_tx: $vnstat_hour_tx bits/sec"
echo "vnstat_day_rx: $vnstat_day_rx bits/sec"
echo "vnstat_day_tx: $vnstat_day_tx bits/sec"
echo "vnstat_month_rx: $vnstat_month_rx bits/sec"
echo "vnstat_month_tx: $vnstat_month_tx bits/sec"
echo "vnstat_year_rx: $vnstat_year_rx bits/sec"
echo "vnstat_year_tx: $vnstat_year_tx bits/sec"

# 获取挂载在根目录的文件系统的设备名，移除路径前缀
system_disk=$(df --output=source / | tail -n +2 | xargs basename)
# 使用 iostat 获取系统盘的性能数据
disk_stats=$(iostat -dxm "$system_disk" | sed '1,/^Device/d')
# 从iostat报告中读取指定的性能指标
disk_read_iops=$(echo "$disk_stats" | awk -v disk="$system_disk" '$1==disk {print $4}')
disk_write_iops=$(echo "$disk_stats" | awk -v disk="$system_disk" '$1==disk {print $5}')
disk_io_wait=$(echo "$disk_stats" | awk -v disk="$system_disk" '$1==disk {print $10}')
disk_avg_io_time=$(echo "$disk_stats" | awk -v disk="$system_disk" '$1==disk {print $13}')
disk_busy_percent=$(echo "$disk_stats" | awk -v disk="$system_disk" '$1==disk {print $14}')
# 打印结果
echo "系统盘: $system_disk"
echo "硬盘读IOPS: $disk_read_iops"
echo "硬盘写IOPS: $disk_write_iops"
echo "硬盘IO等待时间: $disk_io_wait ms"
echo "硬盘平均每次I/O操作时间: $disk_avg_io_time ms"
echo "硬盘有IO操作的时间与总时间的百分比: $disk_busy_percent%"

get_bbr_status() {
  #为了处理环境变量避免定时任务里面命令报错
  source /etc/profile

  kernel_version=`uname -r | awk -F "-" '{print $1}'`
  kernel_version_full=`uname -r`
  if [[ ${kernel_version_full} =~ "bbrplus" ]]; then
          kernel_status="BBRplus"
  elif [[ ${kernel_version} = "3.10.0" || ${kernel_version} = "3.16.0" || ${kernel_version} = "3.2.0" || ${kernel_version} = "4.4.0" || ${kernel_version} = "3.13.0"  || ${kernel_version} = "2.6.32" ]]; then
          kernel_status="Lotserver"
  elif [[ `echo ${kernel_version} | awk -F'.' '{print $1}'` == "4" ]] && [[ `echo ${kernel_version} | awk -F'.' '{print $2}'` -ge 9 ]]; then
          kernel_status="BBR"
  else
          kernel_status="noinstall"
  fi

  if [[ ${kernel_status} == "Lotserver" ]]; then
  	if [[ -e /appex/bin/serverSpeeder.sh ]]; then
  		run_status=`bash /appex/bin/serverSpeeder.sh status | grep "ServerSpeeder" | awk  '{print $3}'`
  		if [[ ${run_status} = "running!" ]]; then
  			run_status="启动成功"
  		else
  			run_status="启动失败"
  		fi
  	else
  		run_status="未安装加速模块"
  	fi
  elif [[ ${kernel_status} == "BBR" ]]; then
  	run_status=`grep "net.ipv4.tcp_congestion_control" /etc/sysctl.conf | awk -F "=" '{print $2}'`
  	if [[ ${run_status} == "bbr" ]]; then
  		run_status=`lsmod | grep "bbr" | awk '{print $1}'`
  		if [[ ${run_status} == "tcp_bbr" ]]; then
  			run_status="BBR启动成功"
  		else
  			run_status="BBR启动失败"
  		fi
  	elif [[ ${run_status} == "tsunami" ]]; then
  		run_status=`lsmod | grep "tsunami" | awk '{print $1}'`
  		if [[ ${run_status} == "tcp_tsunami" ]]; then
  			run_status="BBR魔改版启动成功"
  		else
  			run_status="BBR魔改版启动失败"
  		fi
  	elif [[ ${run_status} == "nanqinlang" ]]; then
  		run_status=`lsmod | grep "nanqinlang" | awk '{print $1}'`
  		if [[ ${run_status} == "tcp_nanqinlang" ]]; then
  			run_status="暴力BBR魔改版启动成功"
  		else
  			run_status="暴力BBR魔改版启动失败"
  		fi
  	else
  		run_status="未安装加速模块"
  	fi
  elif [[ ${kernel_status} == "BBRplus" ]]; then
          run_status=`grep "net.ipv4.tcp_congestion_control" /etc/sysctl.conf | awk -F "=" '{print $2}'`
          if [[ ${run_status} == "bbrplus" ]]; then
                  run_status=`lsmod | grep "bbrplus" | awk '{print $1}'`
                  if [[ ${run_status} == "tcp_bbrplus" ]]; then
                          run_status="BBRplus启动成功"
                  else
                          run_status="BBRplus启动失败"
                  fi
          else
                  run_status="未安装加速模块"
          fi
  fi

  echo "$run_status"
}
bbr_status=$(get_bbr_status)

# 数据准备
TIMESTAMP=$(date +%s)
DATA="hostname=${hostname}&\
virtualization_tech=${virtualization_tech}&\
cpu_model=${cpu_model}&\
cpu_max_freq=${cpu_max_freq}&\
cpu_count=${cpu_count}&\
system_version=${system_version}&\
kernel_version=${kernel_version}&\
system_arch=${system_arch}&\
system_uuid=${system_uuid}&\
tcp_congestion_control=${tcp_congestion_control}&\
load_one_minute=${load_one_minute}&\
load_five_minutes=${load_five_minutes}&\
load_fifteen_minutes=${load_fifteen_minutes}&\
process_running=${process_running}&\
process_waiting_for_run=${process_waiting_for_run}&\
process_uninterruptible_sleep=${process_uninterruptible_sleep}&\
process_total=${process_total}&\
cpu_user_percent=${cpu_user_percent}&\
cpu_system_percent=${cpu_system_percent}&\
cpu_idle_percent=${cpu_idle_percent}&\
cpu_disk_io_wait_time_percent=${cpu_disk_io_wait_time_percent}&\
cpu_stolen_percent=${cpu_stolen_percent}&\
memory_total=${memory_total}&\
memory_used=${memory_used}&\
memory_free=${memory_free}&\
memory_shared=${memory_shared}&\
memory_buffer=${memory_buffer}&\
memory_available=${memory_available}&\
memory_swap_used=${memory_swap_used}&\
memory_cache=${memory_cache}&\
rx_bandwidth=${rx_bandwidth}&\
tx_bandwidth=${tx_bandwidth}&\
rx_packets=${rx_packets}&\
tx_packets=${tx_packets}&\
vnstat_total_rx=${vnstat_total_rx}&\
vnstat_total_tx=${vnstat_total_tx}&\
vnstat_fiveminute_rx=${vnstat_fiveminute_rx}&\
vnstat_fiveminute_tx=${vnstat_fiveminute_tx}&\
vnstat_hour_rx=${vnstat_hour_rx}&\
vnstat_hour_tx=${vnstat_hour_tx}&\
vnstat_day_rx=${vnstat_day_rx}&\
vnstat_day_tx=${vnstat_day_tx}&\
vnstat_month_rx=${vnstat_month_rx}&\
vnstat_month_tx=${vnstat_month_tx}&\
vnstat_year_rx=${vnstat_year_rx}&\
vnstat_year_tx=${vnstat_year_tx}&\
disk_read_iops=${disk_read_iops}&\
disk_write_iops=${disk_write_iops}&\
disk_io_wait=${disk_io_wait}&\
disk_avg_io_time=${disk_avg_io_time}&\
disk_busy_percent=${disk_busy_percent}&\
udp_count=${udp_count}&\
tcp_estab=${tcp_state_count[ESTAB]}&\
tcp_listen=${tcp_state_count[LISTEN]}&\
tcp_syn_sent=${tcp_state_count[SYN_SENT]}&\
tcp_syn_recv=${tcp_state_count[SYN_RECV]}&\
tcp_fin_wait_1=${tcp_state_count[FIN_WAIT_1]}&\
tcp_fin_wait_2=${tcp_state_count[FIN_WAIT_2]}&\
tcp_time_wait=${tcp_state_count[TIME_WAIT]}&\
tcp_close_wait=${tcp_state_count[CLOSE_WAIT]}&\
tcp_closing=${tcp_state_count[CLOSING]}&\
tcp_last_ack=${tcp_state_count[LAST_ACK]}&\
bbr_status=${bbr_status}&\
timestamp=${TIMESTAMP}"
SIGN=$($HASH_CMD "${DATA}${API_KEY}${API_SECRET}")

echo "最终发送数据： $DATA";
echo "COLLECT_URL： $COLLECT_URL";

# 使用curl发送数据
curl -X POST "$COLLECT_URL" \
     -H "Content-Type: application/x-www-form-urlencoded" \
     -H "apikey: ${API_KEY}" \
     -H "sign: ${SIGN}" \
     -d "${DATA}"

# 因为部分节点混用，然后经常因为日志满导致启动失败，我们在这里做一下兼容
rm -rf /root/*_node/*/shadowsocksr/ssserver.log.*
rm -rf /data/*/*/ssserver.log.*
