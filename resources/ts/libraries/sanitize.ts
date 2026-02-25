import DOMPurify from 'dompurify';

/**
 * Sanitize an HTML string using DOMPurify to prevent XSS attacks.
 * Strips dangerous tags and attributes while preserving safe markup.
 */
export function sanitizeHTML( html: string | number ): string {
    return DOMPurify.sanitize( String( html ) );
}
