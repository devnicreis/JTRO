<?php

class CartaContentSanitizer
{
    private const ALLOWED_TAGS = ['p', 'br', 'strong', 'em', 'u', 'ol', 'ul', 'li', 'a'];
    private const REMOVE_WITH_CONTENT = ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math'];

    public static function sanitizeHtml(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        if (!class_exists('DOMDocument')) {
            return trim(strip_tags($html, '<p><br><strong><em><u><ol><ul><li><a>'));
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $wrappedHtml = '<div>' . $html . '</div>';

        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $wrappedHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $container = $dom->getElementsByTagName('div')->item(0);
        if (!$container instanceof DOMElement) {
            return '';
        }

        self::sanitizeNode($container);

        $sanitizedHtml = '';
        foreach ($container->childNodes as $child) {
            $sanitizedHtml .= $dom->saveHTML($child);
        }

        return trim($sanitizedHtml);
    }

    public static function sanitizeExternalUrl(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (!in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        return $url;
    }

    private static function sanitizeNode(DOMNode $node): void
    {
        for ($i = $node->childNodes->length - 1; $i >= 0; $i--) {
            $child = $node->childNodes->item($i);
            if ($child === null) {
                continue;
            }

            if ($child instanceof DOMComment) {
                $node->removeChild($child);
                continue;
            }

            if (!$child instanceof DOMElement) {
                continue;
            }

            $tagName = strtolower($child->tagName);

            if (in_array($tagName, self::REMOVE_WITH_CONTENT, true)) {
                $node->removeChild($child);
                continue;
            }

            self::sanitizeNode($child);

            if (!in_array($tagName, self::ALLOWED_TAGS, true)) {
                self::unwrapElement($child);
                continue;
            }

            self::sanitizeAttributes($child);
        }
    }

    private static function sanitizeAttributes(DOMElement $element): void
    {
        $tagName = strtolower($element->tagName);
        $attributeNames = [];

        foreach ($element->attributes as $attribute) {
            $attributeNames[] = $attribute->name;
        }

        foreach ($attributeNames as $attributeName) {
            $attributeValue = $element->getAttribute($attributeName);

            if ($tagName === 'a' && strtolower($attributeName) === 'href') {
                $sanitizedUrl = self::sanitizeExternalUrl($attributeValue);

                if ($sanitizedUrl === null) {
                    $element->removeAttribute($attributeName);
                    continue;
                }

                $element->setAttribute('href', $sanitizedUrl);
                $element->setAttribute('rel', 'noopener noreferrer');
                $element->setAttribute('target', '_blank');
                continue;
            }

            $element->removeAttribute($attributeName);
        }
    }

    private static function unwrapElement(DOMElement $element): void
    {
        $parent = $element->parentNode;
        if ($parent === null) {
            return;
        }

        while ($element->firstChild !== null) {
            $parent->insertBefore($element->firstChild, $element);
        }

        $parent->removeChild($element);
    }
}
