<?php

namespace ServerNodeBundle\Service;

use ServerNodeBundle\Application\ApplicationInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Tourze\EnumExtra\SelectDataFetcher;

/**
 * 读取服务类型
 */
#[Autoconfigure(public: true)]
class ApplicationTypeFetcher implements SelectDataFetcher
{
    public function __construct(
        #[TaggedIterator('application.type.provider')] private readonly iterable $providers,
    ) {
    }

    /**
     * @return iterable<ApplicationInterface>
     */
    public function getProviders(): iterable
    {
        return $this->providers;
    }

    public function genSelectData(): array
    {
        $result = [];
        foreach ($this->getProviders() as $provider) {
            /** @var SelectDataFetcher $provider */
            $subData = $provider->genSelectData();

            /** @var SelectDataFetcher $provider */
            $result = array_merge($result, $subData);
        }

        return array_values($result);
    }

    public function getApplicationByCode(string $code): ApplicationInterface
    {
        foreach ($this->getProviders() as $provider) {
            /** @var ApplicationInterface $provider */
            if ($provider->getCode() === $code) {
                return $provider;
            }
        }
        throw new \RuntimeException('找不到类型:' . $code);
    }
}
