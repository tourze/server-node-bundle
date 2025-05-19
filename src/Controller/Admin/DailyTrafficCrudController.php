<?php

namespace ServerNodeBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use ServerNodeBundle\Entity\DailyTraffic;

class DailyTrafficCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return DailyTraffic::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('日流量')
            ->setEntityLabelInPlural('日流量统计')
            ->setPageTitle('index', '服务器日流量统计')
            ->setHelp('index', '查看服务器节点的每日流量统计数据，包括上行流量和下行流量')
            ->setDefaultSort(['date' => 'DESC'])
            ->setSearchFields(['ip', 'node.name'])
            ->setPaginatorPageSize(30);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->setMaxLength(9999)
            ->hideOnForm();

        yield FormField::addPanel('基本信息')
            ->setIcon('fa fa-info-circle');

        yield AssociationField::new('node', '节点');
        
        yield TextField::new('ip', 'IP地址');
        
        yield DateField::new('date', '日期')
            ->setFormTypeOptions([
                'html5' => true,
                'widget' => 'single_text',
            ]);
            
        yield FormField::addPanel('流量数据')
            ->setIcon('fa fa-exchange-alt');
            
        yield TextField::new('rx', '下行流量')
            ->formatValue(function ($value) {
                if (!$value) {
                    return null;
                }
                return $this->formatBytes((int)$value);
            });
            
        yield TextField::new('tx', '上行流量')
            ->formatValue(function ($value) {
                if (!$value) {
                    return null;
                }
                return $this->formatBytes((int)$value);
            });
            
        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm();
            
        yield DateTimeField::new('updateTime', '更新时间')
            ->hideOnForm();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('node', '节点'))
            ->add(TextFilter::new('ip', 'IP地址'))
            ->add(DateTimeFilter::new('date', '日期'));
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW)
            ->update(Crud::PAGE_INDEX, Action::EDIT, fn (Action $action) => $action->setIcon('fa fa-edit'))
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
}
