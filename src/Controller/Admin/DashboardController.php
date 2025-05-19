<?php

namespace ServerNodeBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use ServerNodeBundle\Entity\Application;
use ServerNodeBundle\Entity\DailyTraffic;
use ServerNodeBundle\Entity\MinuteStat;
use ServerNodeBundle\Entity\Node;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    #[Route('/admin/server-node', name: 'server_node_admin')]
    public function index(): Response
    {
        return $this->render('server-node/admin/dashboard.html.twig', [
            'dashboard_controller_filepath' => (new \ReflectionClass(static::class))->getFileName(),
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('服务器节点管理系统')
            ->setFaviconPath('favicon.svg');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linktoDashboard('控制面板', 'fa fa-home');
        
        yield MenuItem::section('服务器管理');
        yield MenuItem::linkToCrud('节点列表', 'fa fa-server', Node::class);
        yield MenuItem::linkToCrud('应用列表', 'fa fa-cogs', Application::class);
        
        yield MenuItem::section('统计与监控');
        yield MenuItem::linkToCrud('节点统计', 'fa fa-chart-line', MinuteStat::class);
        yield MenuItem::linkToCrud('流量统计', 'fa fa-exchange-alt', DailyTraffic::class);
        
        yield MenuItem::section('系统');
        yield MenuItem::linkToRoute('返回主系统', 'fa fa-arrow-left', 'homepage');
    }
}
