const ENTITY_MAP: Record<string, string> = {
    '&amp;': '&',
    '&lt;': '<',
    '&gt;': '>',
    '&quot;': '"',
    '&#39;': "'",
};

export function decodeEntities(text: string): string {
    return text.replace(
        /&(?:amp|lt|gt|quot|#39);/g,
        (entity) => ENTITY_MAP[entity] ?? entity,
    );
}

function escapeRegExp(text: string): string {
    return text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

/**
 * Wraps search-term matches (including stemmed continuations) in <mark> tags,
 * leaving HTML tags and entities untouched. Message HTML itself is rendered
 * server-side by App\Support\SlackContent.
 */
export function highlightTerms(html: string, query: string): string {
    const tokens = query.trim().split(/\s+/).filter(Boolean).map(escapeRegExp);

    if (tokens.length === 0) {
        return html;
    }

    const pattern = new RegExp(`\\b(?:${tokens.join('|')})[\\w']*`, 'gi');

    return html
        .split(/(<[^>]+>|&[a-zA-Z]+;|&#\d+;)/g)
        .map((part, index) =>
            index % 2 === 1
                ? part
                : part.replace(
                      pattern,
                      (match) => `<mark class="msg-mark">${match}</mark>`,
                  ),
        )
        .join('');
}
