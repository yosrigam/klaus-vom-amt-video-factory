<?php

namespace App\Support;

use Illuminate\Support\Js;

class CopyToClipboardAlpine
{
    public static function handler(string $selector, string $emptyMessage, string $successMessage): string
    {
        $emptyMessage = Js::from($emptyMessage);
        $successMessage = Js::from($successMessage);
        $selector = Js::from($selector);

        return <<<JS
            (() => {
                const el = document.querySelector({$selector})
                const copyText = el?.value ?? el?.textContent ?? ''

                if (! copyText) {
                    \$tooltip({$emptyMessage}, {
                        theme: \$store.theme,
                        timeout: 2500,
                    })

                    return
                }

                if (navigator.clipboard?.writeText) {
                    navigator.clipboard.writeText(copyText)
                } else {
                    const textarea = document.createElement('textarea')
                    textarea.value = copyText
                    textarea.style.position = 'fixed'
                    textarea.style.opacity = '0'
                    document.body.appendChild(textarea)
                    textarea.select()
                    document.execCommand('copy')
                    document.body.removeChild(textarea)
                }

                \$tooltip({$successMessage}, {
                    theme: \$store.theme,
                    timeout: 2000,
                })
            })()
        JS;
    }

    public static function handlerForText(string $text, string $emptyMessage, string $successMessage): string
    {
        $encoded = Js::from($text);
        $emptyMessage = Js::from($emptyMessage);
        $successMessage = Js::from($successMessage);

        return <<<JS
            (() => {
                const copyText = {$encoded}

                if (! copyText) {
                    \$tooltip({$emptyMessage}, {
                        theme: \$store.theme,
                        timeout: 2500,
                    })

                    return
                }

                if (navigator.clipboard?.writeText) {
                    navigator.clipboard.writeText(copyText)
                } else {
                    const textarea = document.createElement('textarea')
                    textarea.value = copyText
                    textarea.style.position = 'fixed'
                    textarea.style.opacity = '0'
                    document.body.appendChild(textarea)
                    textarea.select()
                    document.execCommand('copy')
                    document.body.removeChild(textarea)
                }

                \$tooltip({$successMessage}, {
                    theme: \$store.theme,
                    timeout: 2000,
                })
            })()
        JS;
    }
}
