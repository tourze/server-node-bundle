<?php

namespace ServerNodeBundle\Controller;

use Carbon\Carbon;
use Psr\Log\LoggerInterface;
use ServerNodeBundle\Application\StatsCollector;
use ServerNodeBundle\Repository\ApplicationRepository;
use ServerNodeBundle\Repository\NodeRepository;
use ServerNodeBundle\Service\ApplicationTypeFetcher;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class ReportController extends AbstractController
{
    public function __construct(
        private readonly NodeRepository $nodeRepository,
        private readonly ApplicationTypeFetcher $typeFetcher,
        private readonly ApplicationRepository $applicationRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * 收集上报信息
     */
    #[Route(path: '/server/node/report', name: 'server-node-report')]
    public function reportAction(Request $request): Response
    {
        $key = $request->headers->get('apikey');
        if (!str_contains($key, '_')) {
            throw new NotFoundHttpException('key不正确');
        }
        $key = explode('_', $key);

        $node = $this->nodeRepository->findOneBy([
            'apiKey' => $key[0],
        ]);
        if (empty($node)) {
            throw new NotFoundHttpException('找不到节点');
        }

        $application = $this->applicationRepository->findOneBy([
            'node' => $node,
            'id' => $key[1],
        ]);
        if (!$application) {
            throw new NotFoundHttpException('找不到应用');
        }

        $now = Carbon::now();
        try {
            $type = $this->typeFetcher->getApplicationByCode($application->getType());

            $params = $request->request->all();
            // 有一些NAT机器，ssh的ip跟访问的ip不一定一致
            $params['online_ip'] = $request->getClientIp();
            $type->collectStats($application, $now, $params);

            // 服务上线
            $this->applicationRepository->makeServiceOnline($node, [
                StatsCollector::NAME,
            ]);
        } catch (\Throwable $exception) {
            $this->logger->error('收集上报信息时发生错误', [
                'exception' => $exception,
            ]);
        }

        return $this->json([
            'time' => $now->getTimestamp(),
        ]);
    }
}
