<?php

namespace DMS\UI\CalendarBuilder;

/**
 * Calendar event color definition
 * 
 * @author Lukas Velek
 */
class CalendarEventColors {
    private array $colors;
    private array $bgColors;

    /**
     * Class constructor
     */
    public function __construct() {
        $v = '0.3';

        $this->colors = [
            'RED' => '#FF0000',
            'GREEN' => '#00FF00',
            'BLUE' => '#0000FF',
            'CYAN' => '#00FFFF',
            'MAGENTA' => '#FF00FF',
            'YELLOW' => '#DD7711',
            'BLACK' => '#000000',
            'PINK' => '#DD22DD',
            'ORANGE' => '#DD7711'
        ];

        $this->bgColors = [
            'RED' => 'rgba(255, 0, 0, ' . $v . ')',
            'GREEN' => 'rgba(0, 255, 0, ' . $v . ')',
            'BLUE' => 'rgba(0, 0, 255, ' . $v . ')',
            'CYAN' => 'rgba(0, 255, 255, ' . $v . ')',
            'MAGENTA' => 'rgba(255, 0, 255, ' . $v . ')',
            'YELLOW' => 'rgba(255, 255, 0, '. $v . ')',
            'BLACK' => 'rgba(0, 0, 0, ' . $v . ')',
            'PINK' => 'rgba(221, 34, 221, ' . $v . ')',
            'ORANGE' => 'rgba(221, 119, 17, ' . $v . ')'
        ];
    }

    /**
     * Returns background color by foreground color
     * 
     * @param string $key Color key
     * @return string Background color
     */
    public function getBackgroundColorByForegroundColorKey(string $key) {
        return $this->bgColors[$key];
    }

    /**
     * Returns foreground color by color key
     * 
     * @param string $key Color key
     * @return string Foreground color
     */
    public function getColor(string $key) {
        return $this->colors[$key];
    }
}

?>