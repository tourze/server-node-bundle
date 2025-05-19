<?php

namespace ServerNodeBundle\Service;

use BizUserBundle\Entity\BizUser;
use ServerNodeBundle\Entity\Application;
use ServerNodeBundle\Entity\Node;
use ServerNodeBundle\Repository\ApplicationRepository;
use ServerNodeBundle\Repository\NodeRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class NodeService
{
    public function __construct(
        private readonly NodeRepository $nodeRepository,
        private readonly ApplicationRepository $applicationRepository,
    ) {
    }

    public function getApplicationFromRequest(Request $request): ?Application
    {
        $authorization = $request->headers->get('authorization');
        if (!$authorization) {
            throw new HttpException('验证失败01');
        }

        [$apiKey, $nonceStr, $signature, $timestamp] = explode('|', $authorization);

        // 时间戳校验
        if (abs(time() - $timestamp) > 60 * 10) {
            return null;
        }

        if (!str_contains($apiKey, '_')) {
            return null;
        }
        [$apiKey, $applicationId] = explode('_', $apiKey);
        // 也可能是 apiKey_应用ID 这种格式
        $application = $this->applicationRepository->find($applicationId);
        if (!$application) {
            return null;
        }

        return $application;
    }

    /**
     * 查看请求的是那个Node
     */
    public function getNodeFromRequest(Request $request): ?Node
    {
        if ($request->query->has('node_id')) {
            return $this->nodeRepository->find($request->query->get('node_id'));
        }

        $authorization = $request->headers->get('authorization');
        if (!$authorization) {
            throw new HttpException('验证失败01');
        }

        [$apiKey, $nonceStr, $signature, $timestamp] = explode('|', $authorization);

        // 时间戳校验
        if (abs(time() - $timestamp) > 60 * 10) {
            return null;
        }

        $node = $this->nodeRepository->findOneBy(['apiKey' => $apiKey]);
        if (!$node && str_contains($apiKey, '_')) {
            [$apiKey, $applicationId] = explode('_', $apiKey);
            // 也可能是 apiKey_应用ID 这种格式
            $node = $this->nodeRepository->findOneBy(['apiKey' => $apiKey]);
        }
        if (!$node) {
            return null;
        }

        // 校验签名是否正确
        //        $signStr = "{$apiKey}|{$nonceStr}|{$node->getApiSecret()}|{$timestamp}";
        //        if (md5($signStr) !== $signature) {
        //            return null;
        //        }
        return $node;
    }

    public function getConnectPasswordByUserId(string $userId): string
    {
        return substr(md5($userId . sha1('_for_ssr_')), 1, 10);
    }

    /**
     * 一个用户，在每个应用上的连接密码都不一样的
     */
    public function getBizUserConnectPassword(BizUser $user): string
    {
        return $this->getConnectPasswordByUserId(strval($user->getId()));
    }
}
