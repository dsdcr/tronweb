<?php

declare(strict_types=1);

namespace Dsdcr\TronWeb\Support;

use Dsdcr\TronWeb\Exception\TronException;

class Ethabi
{
    private array $types;

    public function __construct(array $types = [])
    {
        $this->types = $types;
    }

    /**
     * Encode parameters for function call
     *
     * @param array $functionAbi
     * @param array $params
     * @return string
     * @throws TronException
     */
    public function encodeParameters(array $functionAbi, array $params): string
    {
        $types = [];
        $values = [];

        if (isset($functionAbi['inputs'])) {
            foreach ($functionAbi['inputs'] as $index => $input) {
                $types[] = $input['type'];
                $values[] = $params[$index] ?? null;
            }
        }

        $encoded = AbiEncoder::encodeParameters($types, $values);
        return '0x' . $encoded;
    }

    /**
     * Decode parameters from function result
     *
     * @param array $functionAbi
     * @param string $data
     * @return array
     * @throws TronException
     */
    public function decodeParameters(array $functionAbi, string $data): array
    {
        $types = [];
        $names = [];

        if (isset($functionAbi['outputs'])) {
            foreach ($functionAbi['outputs'] as $output) {
                $types[] = $output['type'];
                $names[] = $output['name'] ?? '';
            }
        }

        $decoded = AbiEncoder::decodeParameters($types, $data);

        // Return associative array with names if available
        $result = [];
        foreach ($decoded as $index => $value) {
            $name = $names[$index] ?? $index;
            $result[$name] = $value;
        }

        return $result;
    }

    /**
     * Encode function signature
     *
     * @param string $functionName
     * @param array $inputs
     * @return string
     */
    public function encodeFunctionSignature(string $functionName, array $inputs): string
    {
        $types = array_map(function ($input) {
            return $input['type'];
        }, $inputs);

        $signature = $functionName . '(' . implode(',', $types) . ')';
        return substr(hash('sha3-256', $signature), 0, 8);
    }

    /**
     * Get function selector (first 4 bytes of function signature hash)
     *
     * @param array $functionAbi
     * @return string
     */
    public function getFunctionSelector(array $functionAbi): string
    {
        $inputs = $functionAbi['inputs'] ?? [];
        return $this->encodeFunctionSignature($functionAbi['name'], $inputs);
    }
}
