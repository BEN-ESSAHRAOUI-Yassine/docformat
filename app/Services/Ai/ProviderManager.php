<?php

namespace App\Services\Ai;

use Exception;

class ProviderManager
{
    /**
     * @var array<string, AiProvider>
     */
    private array $providers = [];

    public function __construct(
        private ?string $default = null,
    ) {}

    public function add(AiProvider $provider): void
    {
        $this->providers[$provider->name()] = $provider;
    }

    public function has(string $name): bool
    {
        return isset($this->providers[$name]);
    }

    public function get(string $name): AiProvider
    {
        if (! $this->has($name)) {
            throw new Exception("Provider [{$name}] is not registered.");
        }

        return $this->providers[$name];
    }

    public function default(): AiProvider
    {
        $name = $this->default ?: (config('services.ai.default') ?? 'local');

        if ($this->has($name)) {
            return $this->get($name);
        }

        return $this->get('local');
    }

    /**
     * Try providers in order; return the first provider whose call succeeds.
     */
    public function withFallback(string $providerName, callable $operation): mixed
    {
        $names = $this->orderedNames($providerName);

        $lastException = null;
        foreach ($names as $name) {
            try {
                $provider = $this->get($name);

                return $operation($provider);
            } catch (Exception $e) {
                $lastException = $e;
            }
        }

        throw $lastException ?: new Exception('No AI provider available.');
    }

    /**
     * @return array<int, string>
     */
    private function orderedNames(string $primary): array
    {
        $names = [$primary];

        if ($primary !== 'local' && $this->has('local')) {
            $names[] = 'local';
        }

        if (! $this->has($primary)) {
            $default = $this->default ?: (config('services.ai.default') ?? 'local');
            $names = [$default];
            if ($default !== 'local' && $this->has('local')) {
                $names[] = 'local';
            }
        }

        return array_values(array_unique($names));
    }
}
