/** Barème partagé — doit rester aligné sur App\Support\ScoreScale. */
export const SCORE_SCALE = {
    1: { label: 'Critique', color: '#b3001b' },
    2: { label: 'Insuffisant', color: '#e8590c' },
    3: { label: 'Acceptable', color: '#b58100' },
    4: { label: 'Bon', color: '#2f6f4f' },
    5: { label: 'Excellent', color: '#1b5e3f' },
};

/**
 * Échappement systématique avant toute insertion dans l'aperçu. L'ancien
 * code injectait les valeurs du formulaire telles quelles via innerHTML :
 * un client nommé « Dupont & Fils <SARL> » cassait le rendu.
 */
export function escapeHtml(value) {
    if (value === null || value === undefined) return '';

    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/** Échappe puis restitue les retours à la ligne, qui étaient perdus. */
export function nl2br(value) {
    return escapeHtml(value).replace(/\r?\n/g, '<br>');
}

/**
 * Balisage léger — doit rester aligné sur App\Support\RichText, pour que
 * l'aperçu montre exactement ce que produira le PDF.
 */
export function rich(value) {
    if (!value) return '';

    const inline = (line) => escapeHtml(line)
        .replace(/`([^`\n]+)`/g, '<code>$1</code>')
        .replace(/\*\*([^*\n]+)\*\*/g, '<strong>$1</strong>')
        .replace(/\*([^*\n]+)\*/g, '<strong>$1</strong>');

    let html = '';
    let list = [];
    let paragraph = [];

    const flushList = () => {
        if (!list.length) return;
        html += `<ul>${list.map((i) => `<li>${i}</li>`).join('')}</ul>`;
        list = [];
    };

    const flushParagraph = () => {
        if (!paragraph.length) return;
        html += `<p>${paragraph.join('<br>')}</p>`;
        paragraph = [];
    };

    String(value).trim().split(/\r\n|\r|\n/).forEach((raw) => {
        const line = raw.trim();
        const bullet = line.match(/^(?:[·•]|-|\*)\s+(.*)$/) || line.match(/^\d+[.)]\s+(.*)$/);

        if (bullet) {
            flushParagraph();
            list.push(inline(bullet[1]));
            return;
        }

        flushList();

        if (line === '') {
            flushParagraph();
            return;
        }

        paragraph.push(inline(line));
    });

    flushList();
    flushParagraph();

    return html;
}

export function formatDate(value) {
    if (!value) return '';

    const [year, month, day] = String(value).split('-');
    if (!year || !month || !day) return value;

    return `${day}/${month}/${year}`;
}

export function formatScore(value) {
    return Number(value).toFixed(1).replace('.', ',');
}
