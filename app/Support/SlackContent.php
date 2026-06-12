<?php

namespace App\Support;

class SlackContent
{
    /**
     * Converts stored Slack message content (anchors and mention tags baked in
     * at import time, entities pre-escaped, raw mrkdwn characters and newlines)
     * into display-ready HTML.
     */
    public static function render(string $content): string
    {
        $masks = [];

        $mask = function (string $rendered) use (&$masks): string {
            $token = "\x00".count($masks)."\x00";
            $masks[] = $rendered;

            return $token;
        };

        $html = preg_replace_callback(
            '/<strong>@([^<\n]+)<\/strong>/',
            fn (array $matches): string => $mask(
                '<a href="/users?q='.rawurlencode($matches[1]).'" class="msg-mention" data-inertia>@'.$matches[1].'</a>'
            ),
            $content
        );

        $html = preg_replace_callback(
            '/<a\b[^>]*>.*?<\/a>/s',
            fn (array $matches): string => $mask($matches[0]),
            $html
        );

        $html = preg_replace_callback(
            '/```(.*?)```/s',
            fn (array $matches): string => $mask(
                '<pre class="msg-pre"><code>'.preg_replace('/^\n+|\n+$/', '', $matches[1]).'</code></pre>'
            ),
            $html
        );

        $html = preg_replace_callback(
            '/`([^`\n]+)`/',
            fn (array $matches): string => $mask('<code class="msg-code">'.$matches[1].'</code>'),
            $html
        );

        $html = preg_replace_callback(
            '/(?:^|\n)((?:&gt;[^\n]*(?:\n|$))+)/',
            function (array $matches) use ($mask): string {
                $lines = collect(explode("\n", $matches[1]))
                    ->filter(fn (string $line): bool => str_starts_with($line, '&gt;'))
                    ->map(fn (string $line): string => preg_replace('/^&gt;\s?/', '', $line))
                    ->implode('<br>');

                return $mask('<blockquote class="msg-quote">'.$lines.'</blockquote>');
            },
            $html
        );

        $html = preg_replace('/(^|[\s([{\x00])\*([^*\n]*\S)\*(?=$|[\s)\]}.,!?:;\x00])/m', '$1<strong>$2</strong>', $html);
        $html = preg_replace('/(^|[\s([{\x00])_([^_\n]*\S)_(?=$|[\s)\]}.,!?:;\x00])/m', '$1<em>$2</em>', $html);
        $html = preg_replace('/(^|[\s([{\x00])~([^~\n]*\S)~(?=$|[\s)\]}.,!?:;\x00])/m', '$1<del>$2</del>', $html);

        $html = preg_replace("/\n{3,}/", "\n\n", $html);
        $html = str_replace("\n", '<br>', $html);

        for ($index = count($masks) - 1; $index >= 0; $index--) {
            $html = str_replace("\x00".$index."\x00", $masks[$index], $html);
        }

        return $html;
    }
}
