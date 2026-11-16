<?php

namespace App\Catalog;

use Illuminate\Support\Str;

/**
 * Draws placeholder images as SVG: game covers and hero banner art. No copyrighted artwork is used.
 *
 * The images are generated once by `php artisan catalog:generate-art`, committed, and served as static
 * files from public/ (docs/DECISIONS.md #19). The same name always gives the same picture: colours and
 * shapes come from a checksum of the name, not from a random generator.
 */
class PlaceholderArt
{
    private const FONT = 'Montserrat, Arial, Helvetica, sans-serif';

    // Base hues (degrees on the colour wheel) the art picks from. The site's own purple (about 260°)
    // is left out, so images stand out against the page instead of blending into it.
    private const HUES = [0, 20, 35, 50, 140, 170, 190, 205, 220, 330];

    /** Where a game's cover lives inside public/. All editions of a game share one cover. */
    public static function coverPath(string $gameName): string
    {
        return 'covers/'.Str::slug($gameName).'.svg';
    }

    /** Where a banner's art lives inside public/. */
    public static function bannerPath(string $bannerTitle): string
    {
        return 'banners/'.Str::slug($bannerTitle).'.svg';
    }

    /**
     * A 3:4 portrait cover with the game's name near the top. The bottom stays plain because the
     * frontend lays the platform label over it, as on eneba.com.
     */
    public static function cover(string $gameName): string
    {
        [$width, $height, $margin] = [300, 400, 24];
        $seed = crc32($gameName);
        $hue = self::HUES[$seed % count(self::HUES)];

        $titleLines = self::wrapWords(mb_strtoupper($gameName), maxCharacters: 12);
        $longestLine = max(array_map('mb_strlen', $titleLines));
        // Shrink long lines to fit the width. 0.72 is roughly the width of a bold capital letter, in em.
        $fontSize = min(38, (int) floor(($width - 2 * $margin) / ($longestLine * 0.72)));

        $tspans = '';
        foreach ($titleLines as $index => $line) {
            $dy = $index === 0 ? 0 : '1.1em';
            $tspans .= sprintf('<tspan x="%d" dy="%s">%s</tspan>', $margin, $dy, self::escape($line));
        }

        return self::svg($width, $height, implode("\n", [
            self::gradient('bg', self::hslToHex($hue, 65, 42), self::hslToHex(($hue + 40) % 360, 70, 14)),
            sprintf('<rect width="%d" height="%d" fill="url(#bg)"/>', $width, $height),
            self::circle(($seed >> 3) % $width, 120 + ($seed >> 11) % 200, 70 + ($seed >> 19) % 80, ($hue + 180) % 360, 0.22),
            self::circle(($seed >> 7) % $width, 200 + ($seed >> 15) % 200, 40 + ($seed >> 23) % 60, ($hue + 90) % 360, 0.16),
            sprintf('<path d="M0 %d L%d %d L%d %d L0 %d Z" fill="#000" opacity="0.28"/>', $height - 90, $width, $height - 170, $width, $height, $height),
            sprintf('<rect x="%d" y="30" width="44" height="6" fill="%s"/>', $margin, self::hslToHex(($hue + 180) % 360, 85, 62)),
            sprintf(
                '<text x="%d" y="%d" font-family="%s" font-size="%d" font-weight="800" fill="#fff">%s</text>',
                $margin, 60 + $fontSize, self::FONT, $fontSize, $tspans,
            ),
        ]));
    }

    /**
     * A wide (3.5:1) hero banner background without text: the frontend lays the banner's title,
     * subtitle and button over its left side, which is kept darker so the text stays readable.
     */
    public static function banner(string $bannerTitle): string
    {
        [$width, $height] = [1400, 400];
        $seed = crc32($bannerTitle);
        $hue = self::HUES[$seed % count(self::HUES)];

        return self::svg($width, $height, implode("\n", [
            self::gradient('bg', self::hslToHex($hue, 70, 40), self::hslToHex(($hue + 60) % 360, 75, 20)),
            '<linearGradient id="shade" x1="0" y1="0" x2="1" y2="0">'
                .'<stop offset="0" stop-color="#000" stop-opacity="0.55"/>'
                .'<stop offset="0.6" stop-color="#000" stop-opacity="0"/>'
                .'</linearGradient>',
            sprintf('<rect width="%d" height="%d" fill="url(#bg)"/>', $width, $height),
            self::circle(900 + ($seed >> 3) % 300, ($seed >> 11) % $height, 180 + ($seed >> 19) % 120, ($hue + 180) % 360, 0.25),
            self::circle(1100 + ($seed >> 7) % 250, 200 + ($seed >> 15) % 200, 90 + ($seed >> 23) % 80, ($hue + 120) % 360, 0.2),
            sprintf('<path d="M700 %d L1000 0 L1080 0 L780 %d Z" fill="#fff" opacity="0.07"/>', $height, $height),
            sprintf('<path d="M820 %d L1120 0 L1160 0 L860 %d Z" fill="#fff" opacity="0.05"/>', $height, $height),
            sprintf('<rect width="%d" height="%d" fill="url(#shade)"/>', $width, $height),
        ]));
    }

    private static function svg(int $width, int $height, string $content): string
    {
        return <<<SVG
            <?xml version="1.0" encoding="UTF-8"?>
            <svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}">
            {$content}
            </svg>

            SVG;
    }

    private static function gradient(string $id, string $fromColor, string $toColor): string
    {
        return sprintf(
            '<linearGradient id="%s" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="%s"/><stop offset="1" stop-color="%s"/></linearGradient>',
            $id, $fromColor, $toColor,
        );
    }

    private static function circle(int $centerX, int $centerY, int $radius, int $hue, float $opacity): string
    {
        return sprintf(
            '<circle cx="%d" cy="%d" r="%d" fill="%s" opacity="%s"/>',
            $centerX, $centerY, $radius, self::hslToHex($hue, 80, 60), $opacity,
        );
    }

    /**
     * Splits text into lines of at most $maxCharacters, breaking only between words.
     * A single word longer than that gets a line of its own.
     *
     * @return list<string>
     */
    private static function wrapWords(string $text, int $maxCharacters): array
    {
        $lines = [];
        $currentLine = '';

        foreach (explode(' ', $text) as $word) {
            $candidate = $currentLine === '' ? $word : "{$currentLine} {$word}";

            if ($currentLine !== '' && mb_strlen($candidate) > $maxCharacters) {
                $lines[] = $currentLine;
                $currentLine = $word;
            } else {
                $currentLine = $candidate;
            }
        }
        $lines[] = $currentLine;

        return $lines;
    }

    private static function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /** Standard HSL → RGB conversion, so the SVG only contains plain hex colours. */
    private static function hslToHex(int $hue, int $saturationPercent, int $lightnessPercent): string
    {
        $saturation = $saturationPercent / 100;
        $lightness = $lightnessPercent / 100;
        $chroma = (1 - abs(2 * $lightness - 1)) * $saturation;
        $secondary = $chroma * (1 - abs(fmod($hue / 60, 2) - 1));
        $match = $lightness - $chroma / 2;

        [$red, $green, $blue] = match (intdiv($hue, 60)) {
            0 => [$chroma, $secondary, 0],
            1 => [$secondary, $chroma, 0],
            2 => [0, $chroma, $secondary],
            3 => [0, $secondary, $chroma],
            4 => [$secondary, 0, $chroma],
            default => [$chroma, 0, $secondary],
        };

        return sprintf(
            '#%02x%02x%02x',
            (int) round(($red + $match) * 255),
            (int) round(($green + $match) * 255),
            (int) round(($blue + $match) * 255),
        );
    }
}
