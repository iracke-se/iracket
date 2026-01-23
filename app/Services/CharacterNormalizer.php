<?php

namespace App\Services;

class CharacterNormalizer
{
    /**
     * Character mapping for Nordic/special characters to ASCII equivalents
     *
     * @var array
     */
    protected array $characterMap = [
        // Swedish/Norwegian characters
        'å' => 'a',
        'ä' => 'a',
        'ö' => 'o',
        'Å' => 'A',
        'Ä' => 'A',
        'Ö' => 'O',

        // Additional Nordic characters
        'æ' => 'ae',
        'ø' => 'o',
        'Æ' => 'AE',
        'Ø' => 'O',

        // Common European characters
        'é' => 'e',
        'è' => 'e',
        'ê' => 'e',
        'ë' => 'e',
        'É' => 'E',
        'È' => 'E',
        'Ê' => 'E',
        'Ë' => 'E',
    ];

    /**
     * Normalize a string by converting Nordic/special characters to ASCII
     *
     * @param  string  $text
     * @return string
     */
    public function normalize(string $text): string
    {
        return strtr($text, $this->characterMap);
    }

    /**
     * Check if a string contains any Nordic/special characters
     *
     * @param  string  $text
     * @return bool
     */
    public function hasSpecialCharacters(string $text): bool
    {
        foreach (array_keys($this->characterMap) as $char) {
            if (str_contains($text, $char)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get both original and normalized versions of a string
     *
     * @param  string  $text
     * @return array{original: string, normalized: string}
     */
    public function getVariants(string $text): array
    {
        $normalized = $this->normalize($text);

        return [
            'original' => $text,
            'normalized' => $normalized,
        ];
    }
}
