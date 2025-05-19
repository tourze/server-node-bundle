<?php

namespace ServerNodeBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use ServerNodeBundle\Entity\Application;
use ServerNodeBundle\Service\ApplicationTypeFetcher;

class ApplicationCrudController extends AbstractCrudController
{
    public function __construct(private readonly ApplicationTypeFetcher $applicationTypeFetcher)
    {
    }

    public static function getEntityFqcn(): string
    {
        return Application::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('应用')
            ->setEntityLabelInPlural('应用列表')
            ->setPageTitle('index', '服务器应用管理')
            ->setPageTitle('detail', fn(Application $application) => sprintf('应用: %s (端口: %d)', $application->getType(), $application->getPort()))
            ->setPageTitle('edit', fn(Application $application) => sprintf('编辑应用: %s (端口: %d)', $application->getType(), $application->getPort()))
            ->setPageTitle('new', '新增应用')
            ->setHelp('index', '管理服务器节点上运行的各种应用，包括应用类型、端口配置和运行状态等')
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setSearchFields(['type', 'port', 'node.name']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->setMaxLength(9999)
            ->hideOnForm();

        yield AssociationField::new('node', '节点')
            ->setRequired(true);

        yield ChoiceField::new('type', '应用类型')
            ->setChoices($this->getApplicationTypeChoices())
            ->setRequired(true);

        yield IntegerField::new('port', '端口号')
            ->setRequired(true);

        yield TextareaField::new('config', '配置')
            ->setHelp('应用配置参数，可以是JSON格式或其他配置格式')
            ->hideOnIndex();

        yield BooleanField::new('online', '在线状态')
            ->renderAsSwitch(false);

        yield DateTimeField::new('activeTime', '最后活跃时间')
            ->hideOnForm();

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm();

        yield DateTimeField::new('updateTime', '更新时间')
            ->hideOnForm();
    }

    /**
     * 获取应用类型选项
     */
    private function getApplicationTypeChoices(): array
    {
        $selectData = $this->applicationTypeFetcher->genSelectData();
        $choices = [];

        foreach ($selectData as $data) {
            if (isset($data['value'], $data['label'])) {
                $choices[$data['label']] = $data['value'];
            }
        }

        return $choices;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('node', '节点'))
            ->add(TextFilter::new('type', '应用类型'))
            ->add(TextFilter::new('port', '端口号'))
            ->add(BooleanFilter::new('online', '在线状态'))
            ->add(DateTimeFilter::new('activeTime', '最后活跃时间'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(DateTimeFilter::new('updateTime', '更新时间'));
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::NEW, fn(Action $action) => $action->setIcon('fa fa-plus')->setLabel('新增应用'))
            ->update(Crud::PAGE_INDEX, Action::EDIT, fn(Action $action) => $action->setIcon('fa fa-edit'))
            ->update(Crud::PAGE_INDEX, Action::DETAIL, fn(Action $action) => $action->setIcon('fa fa-eye'))
            ->update(Crud::PAGE_INDEX, Action::DELETE, fn(Action $action) => $action->setIcon('fa fa-trash'))
            ->reorder(Crud::PAGE_INDEX, [Action::DETAIL, Action::EDIT, Action::DELETE]);
    }
}
