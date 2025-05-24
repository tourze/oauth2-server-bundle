<?php

namespace Tourze\OAuth2ServerBundle\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OAuth2ServerBundle\Entity\OAuth2AccessLog;
use Tourze\OAuth2ServerBundle\Entity\OAuth2Client;
use Tourze\OAuth2ServerBundle\Repository\OAuth2AccessLogRepository;

/**
 * OAuth2访问日志服务
 *
 * 负责记录和管理OAuth2端点的访问日志，
 * 提供访问统计、异常检测和审计功能
 */
class AccessLogService
{
    public function __construct(private readonly OAuth2AccessLogRepository $accessLogRepository)
    {
    }

    /**
     * 记录成功的访问日志
     */
    public function logSuccess(
        string $endpoint,
        Request $request,
        ?OAuth2Client $client = null,
        ?UserInterface $user = null,
        ?int $responseTime = null
    ): OAuth2AccessLog {
        $log = $this->createLog($endpoint, $request, 'success', $client, $user, $responseTime);
        $this->accessLogRepository->save($log);
        return $log;
    }

    /**
     * 记录错误的访问日志
     */
    public function logError(
        string $endpoint,
        Request $request,
        string $errorCode,
        string $errorMessage,
        ?OAuth2Client $client = null,
        ?UserInterface $user = null,
        ?int $responseTime = null
    ): OAuth2AccessLog {
        $log = $this->createLog($endpoint, $request, 'error', $client, $user, $responseTime);
        $log->setErrorCode($errorCode);
        $log->setErrorMessage($errorMessage);
        $this->accessLogRepository->save($log);
        return $log;
    }

    /**
     * 批量记录访问日志
     */
    public function logBatch(array $logs): void
    {
        $this->accessLogRepository->saveBatch($logs);
    }

    /**
     * 创建访问日志对象
     */
    private function createLog(
        string $endpoint,
        Request $request,
        string $status,
        ?OAuth2Client $client = null,
        ?UserInterface $user = null,
        ?int $responseTime = null
    ): OAuth2AccessLog {
        $clientId = $client?->getClientId() ?? $request->request->get('client_id') ?? $request->query->get('client_id');
        $userId = $user ? $this->getUserIdentifier($user) : null;
        $ipAddress = $this->getClientIp($request);
        $userAgent = $request->headers->get('User-Agent');
        $requestParams = $this->sanitizeRequestParams($request);

        return OAuth2AccessLog::create(
            endpoint: $endpoint,
            ipAddress: $ipAddress,
            method: $request->getMethod(),
            status: $status,
            clientId: $clientId,
            client: $client,
            userId: $userId,
            userAgent: $userAgent,
            requestParams: $requestParams,
            responseTime: $responseTime
        );
    }

    /**
     * 获取用户标识符
     */
    private function getUserIdentifier(UserInterface $user): string
    {
        // 优先使用用户ID（如果用户对象有getId方法）
        if (method_exists($user, 'getId')) {
            $id = call_user_func([$user, 'getId']);
            return is_numeric($id) ? (string) $id : $user->getUserIdentifier();
        }
        
        return $user->getUserIdentifier();
    }

    /**
     * 获取客户端真实IP地址
     */
    private function getClientIp(Request $request): string
    {
        $ipKeys = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CF_CONNECTING_IP',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];

        foreach ($ipKeys as $key) {
            $ip = $request->server->get($key);
            if ($ip && $this->isValidIp($ip)) {
                // 如果是逗号分隔的IP列表，取第一个
                if (str_contains($ip, ',')) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if ($this->isValidIp($ip)) {
                    return $ip;
                }
            }
        }

        return $request->getClientIp() ?? '127.0.0.1';
    }

    /**
     * 验证IP地址格式
     */
    private function isValidIp(string $ip): bool
    {
        return filter_var(trim($ip), FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false
            || filter_var(trim($ip), FILTER_VALIDATE_IP) !== false;
    }

    /**
     * 清理请求参数（移除敏感信息）
     */
    private function sanitizeRequestParams(Request $request): array
    {
        $params = array_merge($request->query->all(), $request->request->all());

        // 移除敏感参数
        $sensitiveKeys = ['client_secret', 'password', 'code_verifier', 'code'];
        foreach ($sensitiveKeys as $key) {
            if (isset($params[$key])) {
                $params[$key] = '[FILTERED]';
            }
        }

        return $params;
    }

    /**
     * 获取端点访问统计
     */
    public function getEndpointStats(string $endpoint, ?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null): array
    {
        $totalCount = $this->accessLogRepository->getAccessCountByEndpoint($endpoint, $from, $to);
        $avgResponseTime = $this->accessLogRepository->getAverageResponseTime($endpoint, $from, $to);

        return [
            'endpoint' => $endpoint,
            'total_count' => $totalCount,
            'average_response_time' => $avgResponseTime,
            'period' => [
                'from' => $from?->format('Y-m-d H:i:s'),
                'to' => $to?->format('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * 获取客户端访问统计
     */
    public function getClientStats(OAuth2Client $client, ?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null): array
    {
        $totalCount = $this->accessLogRepository->getAccessCountByClient($client, $from, $to);

        return [
            'client_id' => $client->getClientId(),
            'client_name' => $client->getName(),
            'total_count' => $totalCount,
            'period' => [
                'from' => $from?->format('Y-m-d H:i:s'),
                'to' => $to?->format('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * 检测IP是否可疑（高频访问）
     */
    public function isSuspiciousIp(string $ipAddress, int $threshold = 100, ?\DateTimeInterface $from = null): bool
    {
        $from = $from ?? new \DateTime('-1 hour');
        $count = $this->accessLogRepository->getAccessCountByIp($ipAddress, $from);
        return $count > $threshold;
    }

    /**
     * 获取可疑IP列表
     */
    public function getSuspiciousIps(int $threshold = 100, ?\DateTimeInterface $from = null): array
    {
        return $this->accessLogRepository->getSuspiciousIps($threshold, $from);
    }

    /**
     * 获取错误日志
     */
    public function getErrorLogs(int $limit = 100, ?\DateTimeInterface $from = null): array
    {
        return $this->accessLogRepository->getErrorLogs($limit, $from);
    }

    /**
     * 获取热门端点
     */
    public function getPopularEndpoints(int $limit = 10, ?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null): array
    {
        return $this->accessLogRepository->getPopularEndpoints($limit, $from, $to);
    }

    /**
     * 获取热门客户端
     */
    public function getPopularClients(int $limit = 10, ?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null): array
    {
        return $this->accessLogRepository->getPopularClients($limit, $from, $to);
    }

    /**
     * 获取日访问量统计
     */
    public function getDailyStats(?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null): array
    {
        return $this->accessLogRepository->getDailyStats($from, $to);
    }

    /**
     * 清理过期日志
     */
    public function cleanupOldLogs(int $daysToKeep = 90): int
    {
        $before = new \DateTime("-{$daysToKeep} days");
        return $this->accessLogRepository->cleanupOldLogs($before);
    }

    /**
     * 异步记录访问日志（用于高并发场景）
     */
    public function logAsync(
        string $endpoint,
        array $logData,
        string $status = 'success',
        ?string $errorCode = null,
        ?string $errorMessage = null
    ): void {
        // 这里可以实现异步日志记录，如写入队列、消息总线等
        // 当前为同步实现，后续可以根据需要改为异步
        
        $log = OAuth2AccessLog::create(
            endpoint: $endpoint,
            ipAddress: $logData['ip_address'],
            method: $logData['method'],
            status: $status,
            clientId: $logData['client_id'] ?? null,
            userId: $logData['user_id'] ?? null,
            userAgent: $logData['user_agent'] ?? null,
            requestParams: $logData['request_params'] ?? null,
            errorCode: $errorCode,
            errorMessage: $errorMessage,
            responseTime: $logData['response_time'] ?? null
        );

        $this->accessLogRepository->save($log);
    }
}
