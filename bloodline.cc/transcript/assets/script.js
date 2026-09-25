/**
 * Bloodline Transcript Website - Frontend JavaScript
 */

// Search functionality
async function searchTranscripts() {
    const query = document.getElementById('search').value.trim();
    const container = document.getElementById('transcripts');

    if (query.length < 2) {
        alert('Bitte mindestens 2 Zeichen eingeben.');
        return;
    }

    container.innerHTML = '<div class="empty-state"><p>Suche...</p></div>';

    try {
        const response = await fetch(`api/list.php?search=${encodeURIComponent(query)}`);
        const data = await response.json();

        if (!data.success) {
            container.innerHTML = '<div class="empty-state"><p>Fehler bei der Suche.</p></div>';
            return;
        }

        if (data.transcripts.length === 0) {
            container.innerHTML = '<div class="empty-state"><p>Keine Ergebnisse gefunden.</p></div>';
            return;
        }

        renderTranscripts(data.transcripts, data.is_admin);
    } catch (error) {
        console.error('Search error:', error);
        container.innerHTML = '<div class="empty-state"><p>Fehler bei der Suche.</p></div>';
    }
}

// Render transcript table
function renderTranscripts(transcripts, isAdmin) {
    const container = document.getElementById('transcripts');

    let html = `
        <table>
            <thead>
                <tr>
                    <th>Ticket</th>
                    ${isAdmin ? '<th>User</th>' : ''}
                    <th>Kategorie</th>
                    <th>Erstellt</th>
                    <th>Geschlossen</th>
                    <th>Aktion</th>
                </tr>
            </thead>
            <tbody>
    `;

    for (const t of transcripts) {
        const createdAt = formatDate(t.created_at);
        const closedAt = formatDate(t.closed_at);

        html += `
            <tr>
                <td><code>#${escapeHtml(t.ticket_number)}</code></td>
                ${isAdmin ? `<td>${escapeHtml(t.user_name || t.user_id)}</td>` : ''}
                <td><span class="category ${escapeHtml(t.category)}">${capitalize(t.category)}</span></td>
                <td>${createdAt}</td>
                <td>${closedAt}</td>
                <td>
                    <a href="view.php?id=${escapeHtml(t.id)}" class="btn btn-small">Ansehen</a>
                    <a href="download.php?id=${escapeHtml(t.id)}" class="btn btn-small btn-secondary">Download</a>
                </td>
            </tr>
        `;
    }

    html += '</tbody></table>';
    container.innerHTML = html;
}

// Helper functions
function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('de-DE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function capitalize(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}

// Event listeners
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('search');
    if (searchInput) {
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                searchTranscripts();
            }
        });
    }
});
