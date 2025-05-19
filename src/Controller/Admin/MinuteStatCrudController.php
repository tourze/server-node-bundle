<?php

namespace ServerNodeBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use ServerNodeBundle\Entity\MinuteStat;

class MinuteStatCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return MinuteStat::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('节点统计')
            ->setEntityLabelInPlural('节点统计列表')
            ->setPageTitle('index', '节点统计数据')
            ->setHelp('index', '查看服务器节点的性能统计数据，包括CPU、内存、网络等监控指标')
            ->setDefaultSort(['datetime' => 'DESC'])
            ->setSearchFields(['id', 'node.name'])
            ->setPaginatorPageSize(50);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->setMaxLength(9999)
            ->hideOnForm();
            
        yield AssociationField::new('node', '节点')
            ->setFormTypeOption('disabled', $pageName !== Crud::PAGE_NEW);
            
        yield DateTimeField::new('datetime', '时间点')
            ->setFormTypeOptions([
                'html5' => true,
                'widget' => 'single_text',
            ]);
            
        yield FormField::addPanel('CPU指标')
            ->setIcon('fa fa-microchip');
            
        yield IntegerField::new('cpuSystemPercent', '系统CPU%');
        yield IntegerField::new('cpuUserPercent', '用户CPU%');
        yield IntegerField::new('cpuStolenPercent', '被偷CPU%')
            ->hideOnIndex();
        yield IntegerField::new('cpuIdlePercent', '空闲CPU%');
        
        yield FormField::addPanel('负载指标')
            ->setIcon('fa fa-chart-line');
            
        yield NumberField::new('loadOneMinute', '1分钟负载')
            ->setNumDecimals(2);
        yield NumberField::new('loadFiveMinutes', '5分钟负载')
            ->setNumDecimals(2)
            ->hideOnIndex();
        yield NumberField::new('loadFifteenMinutes', '15分钟负载')
            ->setNumDecimals(2)
            ->hideOnIndex();
            
        yield FormField::addPanel('进程')
            ->setIcon('fa fa-tasks')
            ->hideOnIndex();
            
        yield IntegerField::new('processRunning', '运行进程数');
        yield IntegerField::new('processTotal', '总进程数');
        
        yield FormField::addPanel('内存')
            ->setIcon('fa fa-memory');
            
        yield IntegerField::new('memoryTotal', '总内存')
            ->formatValue(function ($value) {
                if (!$value) {
                    return null;
                }
                return $this->formatBytes($value);
            });
            
        yield IntegerField::new('memoryUsed', '已用内存')
            ->formatValue(function ($value) {
                if (!$value) {
                    return null;
                }
                return $this->formatBytes($value);
            });
            
        yield IntegerField::new('memoryFree', '空闲内存')
            ->hideOnIndex()
            ->formatValue(function ($value) {
                if (!$value) {
                    return null;
                }
                return $this->formatBytes($value);
            });
            
        yield IntegerField::new('memoryAvailable', '可用内存')
            ->hideOnIndex()
            ->formatValue(function ($value) {
                if (!$value) {
                    return null;
                }
                return $this->formatBytes($value);
            });
            
        yield FormField::addPanel('网络')
            ->setIcon('fa fa-network-wired');
            
        yield IntegerField::new('rxBandwidth', '入带宽')
            ->formatValue(function ($value) {
                if (!$value) {
                    return null;
                }
                return $this->formatBandwidth($value);
            });
            
        yield IntegerField::new('txBandwidth', '出带宽')
            ->formatValue(function ($value) {
                if (!$value) {
                    return null;
                }
                return $this->formatBandwidth($value);
            });
            
        yield IntegerField::new('rxPackets', '入包量')
            ->hideOnIndex();
            
        yield IntegerField::new('txPackets', '出包量')
            ->hideOnIndex();
            
        yield FormField::addPanel('TCP连接')
            ->setIcon('fa fa-plug')
            ->hideOnIndex();
            
        yield IntegerField::new('tcpEstab', 'TCP连接数');
        yield IntegerField::new('tcpListen', 'TCP监听数');
        yield IntegerField::new('udpCount', 'UDP监听数');
        
        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('node', '节点'))
            ->add(DateTimeFilter::new('datetime', '时间点'))
            ->add(DateTimeFilter::new('createTime', '创建时间'));
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT)
            ->update(Crud::PAGE_INDEX, Action::DELETE, fn (Action $action) => $action->setIcon('fa fa-trash'));
    }
    
    /**
     * 格式化字节大小
     */
    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max((int)$bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
    
    /**
     * 格式化带宽
     */
    private function formatBandwidth($bps, $precision = 2): string
    {
        $units = ['bps', 'Kbps', 'Mbps', 'Gbps', 'Tbps'];
        
        $bps = max((int)$bps, 0);
        $pow = floor(($bps ? log($bps) : 0) / log(1000));
        $pow = min($pow, count($units) - 1);
        
        $bps /= (1000 ** $pow);
        
        return round($bps, $precision) . ' ' . $units[$pow];
    }
}
