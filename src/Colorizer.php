<?php
// src/Colorizer.php
namespace App;

class Colorizer
{
    // ANSI escape codes for text colors
    const BLACK = "\033[0;30m";
    const RED = "\033[0;31m";
    const GREEN = "\033[0;32m";
    const YELLOW = "\033[0;33m";
    const BLUE = "\033[0;34m";
    const MAGENTA = "\033[0;35m";
    const CYAN = "\033[0;36m";
    const WHITE = "\033[0;37m";

    // ANSI escape codes for background colors
    const BG_BLACK = "\033[40m";
    const BG_RED = "\033[41m";
    const BG_GREEN = "\033[42m";
    const BG_YELLOW = "\033[43m";
    const BG_BLUE = "\033[44m";
    const BG_MAGENTA = "\033[45m";
    const BG_CYAN = "\033[46m";
    const BG_WHITE = "\033[47m";

    // ANSI escape codes for styles
    const BOLD = "\033[1m";
    const UNDERLINE = "\033[4m";
    const INVERT = "\033[7m";
    const RESET = "\033[0m"; // Resets all formatting

    /**
     * Applies color and/or style to a string.
     *
     * @param string $text The text to colorize.
     * @param string $color The text color constant (e.g., Colorizer::GREEN).
     * @param string $bgColor The background color constant (e.g., Colorizer::BG_BLUE).
     * @param string $style The style constant (e.g., Colorizer::BOLD).
     * @return string The colorized string.
     */
    public static function apply(string $text, string $color = '', string $bgColor = '', string $style = ''): string
    {
        return $style . $color . $bgColor . $text . self::RESET;
    }

    // Helper methods for common colors
    public static function green(string $text): string { return self::apply($text, self::GREEN); }
    public static function red(string $text): string { return self::apply($text, self::RED); }
    public static function blue(string $text): string { return self::apply($text, self::BLUE); }
    public static function yellow(string $text): string { return self::apply($text, self::YELLOW); }
    public static function bold(string $text): string { return self::apply($text, '', '', self::BOLD); }
}
