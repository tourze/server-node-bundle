<?php

namespace ServerNodeBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminAction;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use phpseclib3\Net\SSH2;
use ServerNodeBundle\Entity\Node;
use ServerNodeBundle\Enum\NodeStatus;
use ServerStatsBundle\Service\NodeMonitorService;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tourze\GBT2659\Alpha2Code as GBT_2659_2000;

class NodeCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly NodeMonitorService $nodeMonitorService,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Node::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('服务器节点')
            ->setEntityLabelInPlural('服务器节点列表')
            ->setPageTitle('index', '服务器节点管理')
            ->setPageTitle('detail', fn(Node $node) => sprintf('节点: %s', $node->getName()))
            ->setPageTitle('edit', fn(Node $node) => sprintf('编辑节点: %s', $node->getName()))
            ->setPageTitle('new', '新增服务器节点')
            ->setHelp('index', '管理系统中的服务器节点，包括服务器基本信息、网络配置和运行状态等')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['id', 'name', 'domainName', 'sshHost', 'onlineIp', 'hostname'])
            ->showEntityActionsInlined();
    }

    public function configureFields(string $pageName): iterable
    {
        // 列表页面特殊处理
        if (Crud::PAGE_INDEX === $pageName) {
            yield IdField::new('id')
                ->setMaxLength(9999);
            yield TextField::new('name', '名称');
            yield ChoiceField::new('country', '国家')
                ->setFormType(EnumType::class)
                ->setFormTypeOptions(['class' => GBT_2659_2000::class])
                ->formatValue(function ($value) {
                    return $value instanceof GBT_2659_2000 ? $value->value : $value;
                });
            yield TextField::new('domainName', '唯一域名');
            yield TextField::new('sshHost', 'SSH主机');
            yield TextField::new('sshUser', 'SSH用户名');
            yield ChoiceField::new('status', '状态')
                ->setFormType(EnumType::class)
                ->setFormTypeOptions(['class' => NodeStatus::class])
                ->formatValue(function ($value) {
                    return $value instanceof NodeStatus ? $value->name : $value;
                });
            yield TextField::new('onlineIp', '在线IP');
            yield NumberField::new('rxBandwidth', '入带宽')
                ->setNumDecimals(2);
            yield NumberField::new('txBandwidth', '出带宽')
                ->setNumDecimals(2);
            yield BooleanField::new('valid', '有效');
            yield AssociationField::new('applications', '应用')
                ->setTemplatePath('@ServerNode/admin/field/applications.html.twig');
            yield DateTimeField::new('createTime', '创建时间');
            yield DateTimeField::new('updateTime', '更新时间');
            return;
        }

        // 详情页面特殊处理 - 只有详情页面需要展示ID字段
        if (Crud::PAGE_DETAIL === $pageName) {
            yield FormField::addTab('基本信息')
                ->setIcon('fa fa-server')
                ->setHelp('服务器的基本配置信息');

            yield IdField::new('id')
                ->setMaxLength(9999);
        } else {
            // 编辑和新建页面的基本信息选项卡
            yield FormField::addTab('基本信息')
                ->setIcon('fa fa-server')
                ->setHelp('服务器的基本配置信息');
        }

        yield TextField::new('name', '名称');
        yield ChoiceField::new('country', '国家')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions(['class' => GBT_2659_2000::class])
            ->formatValue(function ($value) {
                return $value instanceof GBT_2659_2000 ? $value->value : $value;
            });
        yield TextField::new('domainName', '唯一域名');
        yield BooleanField::new('valid', '有效');

        // SSH连接选项卡
        yield FormField::addTab('SSH连接')
            ->setIcon('fa fa-terminal')
            ->setHelp('服务器SSH连接配置');

        yield TextField::new('sshHost', 'SSH主机');
        yield IntegerField::new('sshPort', 'SSH端口');
        yield TextField::new('sshUser', 'SSH用户名');
        yield TextField::new('sshPassword', 'SSH密码');
        yield TextField::new('mainInterface', '主网卡');

        // API访问选项卡
        yield FormField::addTab('API访问')
            ->setIcon('fa fa-key')
            ->setHelp('API访问凭证配置');

        yield TextField::new('apiKey', 'API Key');
        yield TextField::new('apiSecret', 'API Secret');

        // 状态监控选项卡
        yield FormField::addTab('状态监控')
            ->setIcon('fa fa-info-circle')
            ->setHelp('服务器状态信息');

        yield ChoiceField::new('status', '状态')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions(['class' => NodeStatus::class])
            ->formatValue(function ($value) {
                return $value instanceof NodeStatus ? $value->name : $value;
            });
        yield TextField::new('onlineIp', '在线IP');
        yield NumberField::new('rxBandwidth', '入带宽')
            ->setNumDecimals(2);
        yield NumberField::new('txBandwidth', '出带宽')
            ->setNumDecimals(2);

        // 系统信息选项卡
        yield FormField::addTab('系统信息')
            ->setIcon('fa fa-microchip')
            ->setHelp('服务器硬件和系统信息');

        yield TextField::new('hostname', '主机名');
        yield TextField::new('systemVersion', '系统版本');
        yield TextField::new('kernelVersion', '内核版本');
        yield TextField::new('systemArch', '系统架构');
        yield TextField::new('cpuModel', 'CPU型号');
        yield IntegerField::new('cpuCount', 'CPU核心数');

        // 应用管理选项卡
        yield FormField::addTab('应用管理')
            ->setIcon('fa fa-cubes')
            ->setHelp('管理节点上的应用');

        yield CollectionField::new('applications', '应用列表')
            ->allowAdd()
            ->allowDelete()
            ->setEntryIsComplex(true)
            ->setFormTypeOptions([
                'by_reference' => false,
            ])
            ->setTemplatePath('@ServerNode/admin/field/applications.html.twig');

        // 应用和时间戳字段 - 不可编辑
        if (Crud::PAGE_DETAIL === $pageName) {
            yield FormField::addTab('关联信息')
                ->setIcon('fa fa-link');

            yield DateTimeField::new('createTime', '创建时间');
            yield DateTimeField::new('updateTime', '更新时间');
        }
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('name', '名称'))
            ->add(TextFilter::new('domainName', '唯一域名'))
            ->add(TextFilter::new('sshHost', 'SSH主机'))
            ->add(TextFilter::new('onlineIp', '在线IP'))
            ->add(ChoiceFilter::new('status', '状态')
                ->setChoices(array_combine(
                    array_map(fn($case) => $case->name, NodeStatus::cases()),
                    array_map(fn($case) => $case->value, NodeStatus::cases())
                ))
            )
            ->add(ChoiceFilter::new('country', '国家')
                ->setChoices(array_combine(
                    array_map(fn($case) => $case->value, GBT_2659_2000::cases()),
                    array_map(fn($case) => $case->value, GBT_2659_2000::cases())
                ))
            )
            ->add(BooleanFilter::new('valid', '有效'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(DateTimeFilter::new('updateTime', '更新时间'));
    }

    public function configureActions(Actions $actions): Actions
    {
        $testSsh = Action::new('testSsh', '测试SSH')
            ->setIcon('fa fa-terminal')
            ->linkToCrudAction('testSsh')
            ->setCssClass('btn btn-primary')
            ->displayIf(function (Node $node) {
                return $node->getSshHost() && $node->getSshPort() && $node->getSshUser() && $node->getSshPassword();
            });

        $networkMonitor = Action::new('networkMonitor', '网络监控')
            ->setIcon('fa fa-chart-line')
            ->linkToCrudAction('networkMonitor')
            ->setCssClass('btn btn-info');

        $loadMonitor = Action::new('loadMonitor', '负载监控')
            ->setIcon('fa fa-server')
            ->linkToCrudAction('loadMonitor')
            ->setCssClass('btn btn-warning');

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $testSsh)
            ->add(Crud::PAGE_INDEX, $networkMonitor)
            ->add(Crud::PAGE_INDEX, $loadMonitor)
            ->add(Crud::PAGE_DETAIL, $testSsh)
            ->add(Crud::PAGE_DETAIL, $networkMonitor)
            ->add(Crud::PAGE_DETAIL, $loadMonitor)
            ->add(Crud::PAGE_EDIT, $testSsh)
            ->update(Crud::PAGE_INDEX, Action::NEW, fn(Action $action) => $action->setIcon('fa fa-plus')->setLabel('新增节点'))
            ->update(Crud::PAGE_INDEX, Action::EDIT, fn(Action $action) => $action->setIcon('fa fa-edit'))
            ->update(Crud::PAGE_INDEX, Action::DETAIL, fn(Action $action) => $action->setIcon('fa fa-eye'))
            ->update(Crud::PAGE_INDEX, Action::DELETE, fn(Action $action) => $action->setIcon('fa fa-trash'))
            ->reorder(Crud::PAGE_INDEX, [Action::DETAIL, Action::EDIT, Action::DELETE]);
    }

    /**
     * 测试SSH连接
     */
    #[AdminAction('{entityId}/test-ssh', 'test_ssh')]
    public function testSsh(AdminContext $context, Request $request, AdminUrlGenerator $adminUrlGenerator): Response
    {
        $node = $context->getEntity()->getInstance();

        // 获取SSH连接信息
        $host = $node->getSshHost();
        $port = $node->getSshPort();
        $username = $node->getSshUser();
        $password = $node->getSshPassword();

        // 测试结果消息
        $message = '';
        $type = 'success';

        try {
            // 创建SSH连接
            $ssh = new SSH2($host, $port);
            $ssh->setTimeout(5);

            // 尝试登录
            if (!$ssh->login($username, $password)) {
                throw new \Exception("主机[{$host}:{$port}]连接失败，请检查SSH配置");
            }

            // 执行简单命令测试
            $result = $ssh->exec('echo "SSH连接测试成功"');
            $message = "SSH连接成功！服务器响应: " . trim($result);

        } catch (\Exception $e) {
            $message = "SSH连接失败: " . $e->getMessage();
            $type = 'danger';
        }

        // 添加闪存消息
        $this->addFlash($type, $message);

        // 获取来源页面
        $referer = $request->headers->get('referer');
        // 如果有来源页面，则重定向回来源页面
        if ($referer) {
            return $this->redirect($referer);
        }

        // 如果没有来源页面，则默认重定向到列表页
        $url = $adminUrlGenerator
            ->setController(self::class)
            ->setAction(Crud::PAGE_INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }

    /**
     * 网络监控
     */
    #[AdminAction('{entityId}/network-monitor', 'network_monitor')]
    public function networkMonitor(AdminContext $context, Request $request): Response
    {
        $node = $context->getEntity()->getInstance();
        
        // 获取网络监控数据
        $monitorData = $this->nodeMonitorService->getNetworkMonitorData($node);

        // 返回视图
        return $this->render('@ServerNode/admin/network_monitor.html.twig', array_merge([
            'node' => $node,
            'referer' => $request->headers->get('referer'),
        ], $monitorData));
    }

    /**
     * 负载监控
     */
    #[AdminAction('{entityId}/load-monitor', 'load_monitor')]
    public function loadMonitor(AdminContext $context, Request $request): Response
    {
        $node = $context->getEntity()->getInstance();

        // 获取负载监控数据
        $monitorData = $this->nodeMonitorService->getLoadMonitorData($node);

        // 返回视图
        return $this->render('@ServerNode/admin/load_monitor.html.twig', array_merge([
            'node' => $node,
            'referer' => $request->headers->get('referer'),
        ], $monitorData));
    }
}
