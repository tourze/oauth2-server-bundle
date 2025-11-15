<?php

namespace Tourze\OAuth2ServerBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
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
    public function __construct(
        private readonly OAuth2AccessLogRepository $accessLogRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * 记录成功的访问日志
     */
    public function logSuccess(
        string $endpoint,
        Request $request,
        ?OAuth2Client $client = null,
        ?UserInterface $user = null,
        ?int $responseTime = null,
    ): OAuth2AccessLog {
        $log = $this->createLog($endpoint, $request, 'success', $client, $user, $responseTime);
        $this->entityManager->persist($log);
        $this->entityManager->flush();

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
        ?int $responseTime = null,
    ): OAuth2AccessLog {
        $log = $this->createLog($endpoint, $request, 'error', $client, $user, $responseTime);
        $log->setErrorCode($errorCode);
        $log->setErrorMessage($errorMessage);
        $this->entityManager->persist($log);
        $this->entityManager->flush();

        return $log;
    }

    /**
     * 批量记录访问日志
     *
     * @param array<OAuth2AccessLog> $logs
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
        ?int $responseTime = null,
    ): OAuth2AccessLog {
        $clientId = $client?->getClientId() ?? (string) ($request->request->get('client_id') ?? $request->query->get('client_id') ?? '');
        $userId = null !== $user ? $this->getUserIdentifier($user) : null;
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
            /** @var mixed $id */
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
            'REMOTE_ADDR',
        ];

        foreach ($ipKeys as $key) {
            $ip = $request->server->get($key);
            if (is_string($ip) && '' !== $ip && $this->isValidIp($ip)) {
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
        return false !== filter_var(trim($ip), FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
            || false !== filter_var(trim($ip), FILTER_VALIDATE_IP);
    }

    /**
     * 清理请求参数（移除敏感信息）
     *
     * @return array<string, mixed>
     */
    private function sanitizeRequestParams(Request $request): array
    {
        /** @var array<string, mixed> $params */
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
     *
     * @return array<string, mixed>
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
     *
     * @return array<string, mixed>
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
        $from ??= new \DateTime('-1 hour');
        $count = $this->accessLogRepository->getAccessCountByIp($ipAddress, $from);

        return $count > $threshold;
    }

    /**
     * 获取可疑IP列表
     *
     * @return array<array{ipAddress: string, access_count: int}>
     */
    public function getSuspiciousIps(int $threshold = 100, ?\DateTimeInterface $from = null): array
    {
        return $this->accessLogRepository->getSuspiciousIps($threshold, $from);
    }

    /**
     * 获取错误日志
     *
     * @return array<OAuth2AccessLog>
     */
    public function getErrorLogs(int $limit = 100, ?\DateTimeInterface $from = null): array
    {
        return $this->accessLogRepository->getErrorLogs($limit, $from);
    }

    /**
     * 获取热门端点
     *
     * @return array<array<string, mixed>>
     */
    public function getPopularEndpoints(int $limit = 10, ?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null): array
    {
        return $this->accessLogRepository->getPopularEndpoints($limit, $from, $to);
    }

    /**
     * 获取热门客户端
     *
     * @return array<array<string, mixed>>
     */
    public function getPopularClients(int $limit = 10, ?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null): array
    {
        return $this->accessLogRepository->getPopularClients($limit, $from, $to);
    }

    /**
     * 获取日访问量统计
     *
     * @return array<array<string, mixed>>
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
     *
     * @param array<string, mixed> $logData
     */
    public function logAsync(
        string $endpoint,
        array $logData,
        string $status = 'success',
        ?string $errorCode = null,
        ?string $errorMessage = null,
    ): void {
        // 这里可以实现异步日志记录，如写入队列、消息总线等
        // 当前为同步实现，后续可以根据需要改为异步

        $log = OAuth2AccessLog::create(
            endpoint: $endpoint,
            ipAddress: $this->extractRequiredStringValue($logData, 'ip_address', '127.0.0.1'),
            method: $this->extractRequiredStringValue($logData, 'method', 'POST'),
            status: $status,
            clientId: $this->extractStringValue($logData, 'client_id'),
            userId: $this->extractStringValue($logData, 'user_id'),
            userAgent: $this->extractStringValue($logData, 'user_agent'),
            requestParams: $this->extractArrayValue($logData, 'request_params'),
            errorCode: $errorCode,
            errorMessage: $errorMessage,
            responseTime: $this->extractIntValue($logData, 'response_time')
        );

        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    /**
     * 从数组中安全提取必需的字符串值
     *
     * @param array<string, mixed> $data
     */
    private function extractRequiredStringValue(array $data, string $key, string $default): string
    {
        return isset($data[$key]) && is_string($data[$key]) ? $data[$key] : $default;
    }

    /**
     * 从数组中安全提取字符串值
     *
     * @param array<string, mixed> $data
     */
    private function extractStringValue(array $data, string $key, ?string $default = null): ?string
    {
        return isset($data[$key]) && is_string($data[$key]) ? $data[$key] : $default;
    }

    /**
     * 从数组中安全提取整数值
     *
     * @param array<string, mixed> $data
     */
    private function extractIntValue(array $data, string $key, ?int $default = null): ?int
    {
        return isset($data[$key]) && is_int($data[$key]) ? $data[$key] : $default;
    }

    /**
     * 从数组中安全提取数组值
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>|null
     */
    private function extractArrayValue(array $data, string $key): ?array
    {
        if (!isset($data[$key])) {
            return null;
        }

        $value = $data[$key];

        if (!is_array($value)) {
            return null;
        }

        /** @var array<string, mixed> $value */
        return $value;
    }
}
