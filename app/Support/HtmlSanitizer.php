<?php

namespace App\Support;

use DOMAttr;
use DOMComment;
use DOMDocument;
use DOMElement;
use DOMNode;
use Illuminate\Support\Str;

class HtmlSanitizer
{
    /**
     * @var list<string>
     */
    private const ALLOWED_TAGS = [
        'p',
        'br',
        'h1',
        'h2',
        'h3',
        'strong',
        'b',
        'em',
        'i',
        'u',
        's',
        'strike',
        'ul',
        'ol',
        'li',
        'blockquote',
        'a',
    ];

    /**
     * @var list<string>
     */
    private const DROP_TAGS_WITH_CONTENT = [
        'script',
        'style',
        'iframe',
        'object',
        'embed',
        'form',
        'input',
        'button',
        'textarea',
        'select',
        'option',
        'meta',
        'link',
        'base',
    ];

    public static function sanitizeRichText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $previousUseInternalErrors = libxml_use_internal_errors(true);

        $dom->loadHTML(
            '<?xml encoding="utf-8" ?><div>'.$value.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING,
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previousUseInternalErrors);

        /** @var DOMElement|null $root */
        $root = $dom->getElementsByTagName('div')->item(0);

        if (! $root) {
            return null;
        }

        self::sanitizeChildren($root);

        $result = '';

        foreach ($root->childNodes as $child) {
            $result .= $dom->saveHTML($child);
        }

        $result = trim($result);

        return $result !== '' ? $result : null;
    }

    public static function sanitizeText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', '', $value) ?? $value;
        $value = strip_tags($value);
        $value = Str::squish($value);

        return $value !== '' ? $value : null;
    }

    private static function sanitizeChildren(DOMNode $parent): void
    {
        for ($index = $parent->childNodes->length - 1; $index >= 0; $index--) {
            $node = $parent->childNodes->item($index);

            if (! $node) {
                continue;
            }

            self::sanitizeNode($node);
        }
    }

    private static function sanitizeNode(DOMNode $node): void
    {
        if ($node instanceof DOMComment) {
            $node->parentNode?->removeChild($node);

            return;
        }

        if (! $node instanceof DOMElement) {
            return;
        }

        $tag = strtolower($node->tagName);

        if (in_array($tag, self::DROP_TAGS_WITH_CONTENT, true)) {
            $node->parentNode?->removeChild($node);

            return;
        }

        if (! in_array($tag, self::ALLOWED_TAGS, true)) {
            $parent = $node->parentNode;

            if (! $parent) {
                return;
            }

            while ($node->firstChild) {
                $parent->insertBefore($node->firstChild, $node);
            }

            $parent->removeChild($node);
            self::sanitizeChildren($parent);

            return;
        }

        self::sanitizeAttributes($node, $tag);
        self::sanitizeChildren($node);
    }

    private static function sanitizeAttributes(DOMElement $element, string $tag): void
    {
        if ($element->hasAttributes()) {
            for ($index = $element->attributes->length - 1; $index >= 0; $index--) {
                $attribute = $element->attributes->item($index);

                if (! $attribute) {
                    continue;
                }

                if (! $attribute instanceof DOMAttr) {
                    continue;
                }

                $name = strtolower($attribute->name);

                if (str_starts_with($name, 'on')) {
                    $element->removeAttributeNode($attribute);

                    continue;
                }

                if ($tag !== 'a' || ! in_array($name, ['href', 'target', 'rel'], true)) {
                    $element->removeAttributeNode($attribute);
                }
            }
        }

        if ($tag !== 'a') {
            return;
        }

        if ($element->hasAttribute('href')) {
            $href = trim(html_entity_decode($element->getAttribute('href')));

            if (! self::isSafeUrl($href)) {
                $element->removeAttribute('href');
            } else {
                $element->setAttribute('href', $href);
            }
        }

        $target = strtolower(trim($element->getAttribute('target')));

        if ($target !== '_blank') {
            $element->removeAttribute('target');
            $element->removeAttribute('rel');

            return;
        }

        $element->setAttribute('target', '_blank');
        $element->setAttribute('rel', 'noopener noreferrer');
    }

    private static function isSafeUrl(string $url): bool
    {
        if ($url === '') {
            return false;
        }

        $lowerUrl = strtolower($url);

        if (str_starts_with($lowerUrl, 'javascript:') || str_starts_with($lowerUrl, 'data:')) {
            return false;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        if ($scheme === null || $scheme === '') {
            return true;
        }

        return in_array(strtolower($scheme), ['http', 'https', 'mailto', 'tel'], true);
    }
}
