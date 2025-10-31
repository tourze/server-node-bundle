<?php

declare(strict_types=1);

namespace ServerNodeBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminAction;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use phpseclib3\Crypt\Common\PrivateKey;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SSH2;
use ServerNodeBundle\Entity\Node;
use ServerNodeBundle\Enum\NodeStatus;
use ServerNodeBundle\Exception\InvalidEntityException;
use ServerNodeBundle\Exception\SshConnectionException;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tourze\GBT2659\Alpha2Code as GBT_2659_2000;

#[AdminCrud(routePath: '/server-node/node', routeName: 'server_node_node')]
final class NodeCrudController extends AbstractCrudController
{
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
            ->setPageTitle('detail', fn (Node $node) => sprintf('节点: %s', $node->getName()))
            ->setPageTitle('edit', fn (Node $node) => sprintf('编辑节点: %s', $node->getName()))
            ->setPageTitle('new', '新增服务器节点')
            ->setHelp('index', '管理系统中的服务器节点，包括服务器基本信息、网络配置和运行状态等')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['id', 'name', 'domainName', 'sshHost', 'onlineIp', 'hostname'])
            ->showEntityActionsInlined()
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        if (Crud::PAGE_INDEX === $pageName) {
            yield from $this->configureIndexFields();

            return;
        }

        yield from $this->configureFormFields($pageName);
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function configureIndexFields(): iterable
    {
        yield IdField::new('id', 'ID')->setMaxLength(9999);
        yield TextField::new('name', '名称');
        yield from $this->configureCountryField();
        yield TextField::new('domainName', '唯一域名');
        yield TextField::new('sshHost', 'SSH主机');
        yield TextField::new('sshUser', 'SSH用户名');
        yield from $this->configureStatusField();
        yield TextField::new('onlineIp', '在线IP');
        yield NumberField::new('rxBandwidth', '入带宽')->setNumDecimals(2);
        yield NumberField::new('txBandwidth', '出带宽')->setNumDecimals(2);
        yield BooleanField::new('valid', '有效');
        yield DateTimeField::new('createTime', '创建时间');
        yield DateTimeField::new('updateTime', '更新时间');
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function configureFormFields(string $pageName): iterable
    {
        yield from $this->configureBasicInfoTab($pageName);
        yield from $this->configureSshTab();
        yield from $this->configureMonitoringTab();
        yield from $this->configureSystemInfoTab();

        if (Crud::PAGE_DETAIL === $pageName) {
            yield from $this->configureTimestampTab();
        }
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function configureBasicInfoTab(string $pageName): iterable
    {
        yield FormField::addTab('基本信息')
            ->setIcon('fa fa-server')
            ->setHelp('服务器的基本配置信息')
        ;

        if (Crud::PAGE_DETAIL === $pageName) {
            yield IdField::new('id', 'ID')->setMaxLength(9999);
        }

        yield TextField::new('name', '名称');
        yield from $this->configureCountryField();
        yield TextField::new('domainName', '唯一域名');
        yield BooleanField::new('valid', '有效');
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function configureSshTab(): iterable
    {
        yield FormField::addTab('SSH连接')
            ->setIcon('fa fa-terminal')
            ->setHelp('服务器SSH连接配置')
        ;

        yield TextField::new('sshHost', 'SSH主机');
        yield IntegerField::new('sshPort', 'SSH端口');
        yield TextField::new('sshUser', 'SSH用户名');
        yield TextField::new('sshPassword', 'SSH密码');
        yield TextareaField::new('sshPrivateKey', 'SSH私钥')
            ->setNumOfRows(8)
            ->setHelp('SSH私钥内容，用于密钥认证。如果设置了私钥，将优先使用密钥认证而非密码认证。')
            ->hideOnIndex()
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function configureMonitoringTab(): iterable
    {
        yield FormField::addTab('状态监控')
            ->setIcon('fa fa-info-circle')
            ->setHelp('服务器状态信息')
        ;

        yield from $this->configureStatusField();
        yield TextField::new('onlineIp', '在线IP');
        yield NumberField::new('rxBandwidth', '入带宽')->setNumDecimals(2);
        yield NumberField::new('txBandwidth', '出带宽')->setNumDecimals(2);
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function configureSystemInfoTab(): iterable
    {
        yield FormField::addTab('系统信息')
            ->setIcon('fa fa-microchip')
            ->setHelp('服务器硬件和系统信息')
        ;

        yield TextField::new('hostname', '主机名');
        yield TextField::new('systemVersion', '系统版本');
        yield TextField::new('kernelVersion', '内核版本');
        yield TextField::new('systemArch', '系统架构');
        yield TextField::new('cpuModel', 'CPU型号');
        yield IntegerField::new('cpuCount', 'CPU核心数');
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function configureTimestampTab(): iterable
    {
        yield FormField::addTab('关联信息')->setIcon('fa fa-link');
        yield DateTimeField::new('createTime', '创建时间');
        yield DateTimeField::new('updateTime', '更新时间');
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function configureCountryField(): iterable
    {
        yield ChoiceField::new('country', '国家')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions(['class' => GBT_2659_2000::class])
            ->formatValue(function ($value) {
                return $value instanceof GBT_2659_2000 ? $value->value : $value;
            })
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function configureStatusField(): iterable
    {
        yield ChoiceField::new('status', '状态')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions(['class' => NodeStatus::class])
            ->formatValue(function ($value) {
                return $value instanceof NodeStatus ? $value->name : $value;
            })
        ;
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
                    array_map(fn ($case) => $case->name, NodeStatus::cases()),
                    array_map(fn ($case) => $case->value, NodeStatus::cases())
                ))
            )
            ->add(ChoiceFilter::new('country', '国家')
                ->setChoices(array_combine(
                    array_map(fn ($case) => $case->value, GBT_2659_2000::cases()),
                    array_map(fn ($case) => $case->value, GBT_2659_2000::cases())
                ))
            )
            ->add(BooleanFilter::new('valid', '有效'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(DateTimeFilter::new('updateTime', '更新时间'))
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $testSsh = Action::new('testSsh', '测试SSH')
            ->setIcon('fa fa-terminal')
            ->linkToCrudAction('testSsh')
            ->setCssClass('btn btn-primary')
            ->displayIf(function (Node $node) {
                return null !== $node->getSshHost() && $node->getSshPort() > 0 && null !== $node->getSshUser()
                       && (null !== $node->getSshPassword() || null !== $node->getSshPrivateKey());
            })
        ;

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $testSsh)
            ->add(Crud::PAGE_DETAIL, $testSsh)
            ->add(Crud::PAGE_EDIT, $testSsh)
            ->reorder(Crud::PAGE_INDEX, [Action::DETAIL, 'testSsh'])
        ;
    }

    /**
     * 测试SSH连接
     */
    #[AdminAction(routePath: '{entityId}/test-ssh', routeName: 'test_ssh')]
    public function testSsh(AdminContext $context, Request $request, AdminUrlGenerator $adminUrlGenerator): Response
    {
        $node = $context->getEntity()->getInstance();
        if (!$node instanceof Node) {
            throw new InvalidEntityException('Entity must be an instance of Node');
        }

        try {
            $result = $this->performSshTest($node);
            $this->addFlash('success', $result);
        } catch (\Throwable $e) {
            $this->addFlash('danger', 'SSH连接失败: ' . $e->getMessage());
        }

        return $this->redirectToRefererOrIndex($request, $adminUrlGenerator);
    }

    private function performSshTest(Node $node): string
    {
        $ssh = $this->createSshConnection($node);
        $authMethod = $this->authenticateSsh($ssh, $node);

        $result = $ssh->exec('echo "SSH连接测试成功"');

        return "SSH连接成功！认证方式：{$authMethod}，服务器响应: " . trim((string) $result);
    }

    private function createSshConnection(Node $node): SSH2
    {
        $ssh = new SSH2($node->getSshHost(), $node->getSshPort());
        $ssh->setTimeout(5);

        return $ssh;
    }

    private function authenticateSsh(SSH2 $ssh, Node $node): string
    {
        $authMethod = $this->tryPrivateKeyAuth($ssh, $node);
        if (null !== $authMethod) {
            return $authMethod;
        }

        $authMethod = $this->tryPasswordAuth($ssh, $node);
        if (null !== $authMethod) {
            return $authMethod;
        }

        throw new SshConnectionException("主机[{$node->getSshHost()}:{$node->getSshPort()}]连接失败，请检查SSH配置");
    }

    private function tryPrivateKeyAuth(SSH2 $ssh, Node $node): ?string
    {
        $privateKey = $node->getSshPrivateKey();
        if (null === $privateKey) {
            return null;
        }

        $key = PublicKeyLoader::load($privateKey);
        if (!$key instanceof PrivateKey) {
            return null;
        }

        if ($ssh->login($node->getSshUser() ?? '', $key)) {
            return '私钥认证';
        }

        return null;
    }

    private function tryPasswordAuth(SSH2 $ssh, Node $node): ?string
    {
        $password = $node->getSshPassword();
        if (null === $password) {
            return null;
        }

        if ($ssh->login($node->getSshUser() ?? '', $password)) {
            return '密码认证';
        }

        return null;
    }

    private function redirectToRefererOrIndex(Request $request, AdminUrlGenerator $adminUrlGenerator): Response
    {
        $referer = $request->headers->get('referer');
        if (null !== $referer) {
            return $this->redirect($referer);
        }

        $url = $adminUrlGenerator
            ->setController(self::class)
            ->setAction(Crud::PAGE_INDEX)
            ->generateUrl()
        ;

        return $this->redirect($url);
    }
}
