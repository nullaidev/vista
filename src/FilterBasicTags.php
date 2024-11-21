<?php

namespace Nullai\Vista;

class FilterBasicTags extends FilterTags
{
    public array $globalAttributes = ['class','id','title'];

    public array $tags = [
        // Text formatting
        'a' => ['href', 'target', 'rel'], // Links
        'p' => [], // Paragraph
        'br' => [], // Line breaks
        'strong' => [], // Bold text
        'b' => [], // Bold text (alternative)
        'em' => [], // Italics
        'i' => [], // Italics (alternative)
        'u' => [], // Underlined text
        'span' => [], // Inline container
        'div' => [], // Block container

        // Headings
        'h1' => [], // Heading level 1
        'h2' => [], // Heading level 2
        'h3' => [], // Heading level 3
        'h4' => [], // Heading level 4
        'h5' => [], // Heading level 5
        'h6' => [], // Heading level 6

        // Lists
        'ul' => [], // Unordered list
        'ol' => [], // Ordered list
        'li' => [], // List items

        // Images and media
        'img' => ['src', 'alt', 'title', 'width', 'height'], // Images
        'figure' => [], // Figure container
        'figcaption' => [], // Figure caption
        'video' => ['src', 'controls', 'autoplay', 'loop', 'muted', 'poster', ], // Video
        'audio' => ['src', 'controls', 'autoplay', 'loop', 'muted', ], // Audio

        // Table container and structure
        'table' => ['summary', 'border', 'cellspacing', 'cellpadding'], // Table container
        'caption' => [], // Table caption

        // Table groupings
        'thead' => [], // Table head
        'tbody' => [], // Table body
        'tfoot' => [], // Table footer

        // Rows and cells
        'tr' => [], // Table rows
        'th' => ['scope', 'colspan', 'rowspan', 'abbr', 'align', 'valign', 'width'], // Table headers
        'td' => ['colspan', 'rowspan', 'headers', 'align', 'valign', 'width'], // Table data cells

        // Colgroup and col elements
        'colgroup' => ['span'], // Column group
        'col' => ['span', 'width'], // Column definition

        // Semantic HTML5 elements
        'section' => [], // Section
        'article' => [], // Article
        'aside' => [], // Aside
        'nav' => [], // Navigation
        'header' => [], // Header
        'footer' => [], // Footer
        'main' => [], // Main content area

        // Inline and block containers
        'code' => [], // Inline code
        'pre' => [], // Preformatted text
        'blockquote' => ['cite', ], // Block quotes

        // Modern interactive elements
        'details' => [], // Details element for collapsible content
        'summary' => [], // Summary element for collapsible content
        'mark' => [], // Highlighted text
        'time' => ['datetime', ], // Time element
        'progress' => ['value', 'max', ], // Progress bar
        'meter' => ['value', 'min', 'max', 'low', 'high', 'optimum', ], // Gauge
    ];
}