<?php

namespace Andremellow\Tasks\Services;

use DOMDocument;
use DOMElement;
use DOMNode;
use Illuminate\Support\Str;

class TaskRichTextRenderer
{
    /** @var array<string, list<string>> */
    private const ALLOWED = [
        'p' => [], 'h1' => [], 'h2' => [], 'h3' => [], 'strong' => [], 'em' => [],
        'u' => [], 's' => [], 'ul' => [], 'ol' => [], 'li' => [], 'blockquote' => [],
        'br' => [], 'hr' => [], 'pre' => [], 'code' => [], 'a' => ['href', 'target', 'rel'],
    ];

    public function render(string $content): string
    {
        if (trim($content) === '') {
            return '';
        }

        $html = $content === strip_tags($content)
            ? Str::markdown($content, ['html_input' => 'strip', 'allow_unsafe_links' => false])
            : $content;

        return $this->sanitize($html);
    }

    private function sanitize(string $html): string
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8" ?><div data-task-root>'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementsByTagName('div')->item(0);
        if (! $root instanceof DOMElement) {
            return '';
        }

        $this->cleanChildren($root);

        return collect(iterator_to_array($root->childNodes))
            ->map(fn (DOMNode $node): string => (string) $document->saveHTML($node))
            ->implode('');
    }

    private function cleanChildren(DOMNode $parent): void
    {
        foreach (iterator_to_array($parent->childNodes) as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($node->tagName);
            if (! array_key_exists($tag, self::ALLOWED)) {
                if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed'], true)) {
                    $node->parentNode?->removeChild($node);

                    continue;
                }

                $this->unwrap($node);

                continue;
            }

            foreach (iterator_to_array($node->attributes) as $attribute) {
                if (! in_array(strtolower($attribute->name), self::ALLOWED[$tag], true)) {
                    $node->removeAttribute($attribute->name);
                }
            }

            if ($tag === 'a') {
                $href = $node->getAttribute('href');
                if (! $this->safeUrl($href)) {
                    $node->removeAttribute('href');
                }
                $node->setAttribute('rel', 'noopener noreferrer');
            }

            $this->cleanChildren($node);
        }
    }

    private function safeUrl(string $url): bool
    {
        return (str_starts_with($url, '/') && ! str_starts_with($url, '//'))
            || (filter_var($url, FILTER_VALIDATE_URL) !== false && str_starts_with($url, 'https://'));
    }

    private function unwrap(DOMElement $element): void
    {
        $parent = $element->parentNode;
        if ($parent === null) {
            return;
        }

        while ($element->firstChild !== null) {
            $parent->insertBefore($element->firstChild, $element);
        }

        $parent->removeChild($element);
        $this->cleanChildren($parent);
    }
}
