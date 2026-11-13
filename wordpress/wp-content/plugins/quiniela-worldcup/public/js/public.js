/**
 * QWC Public JS - Actualización de marcadores en vivo
 */
(function() {
    'use strict';

    if (typeof qwcPublic === 'undefined') return;

    const interval = (parseInt(qwcPublic.updateInterval) || 30) * 1000;
    const restUrl = qwcPublic.restUrl + 'qwc/v1/live-scores?tournament=' + qwcPublic.tournamentId;

    function updateLiveScores() {
        fetch(restUrl)
            .then(function(response) { return response.json(); })
            .then(function(matches) {
                matches.forEach(function(m) {
                    const card = document.querySelector('[data-match-id="' + m.id + '"]');
                    if (!card) return;

                    const scoreEl = card.querySelector('.qwc-live-score');
                    const statusEl = card.querySelector('.qwc-live-status');
                    if (!scoreEl || !statusEl) return;

                    if (m.status === 'live') {
                        const hs = (m.home_score !== null && m.home_score !== undefined) ? m.home_score : 0;
                        const as = (m.away_score !== null && m.away_score !== undefined) ? m.away_score : 0;
                        const min = m.current_minute ? m.current_minute : 0;
                        scoreEl.textContent = hs + ' - ' + as + "  (" + min + "')";
                        statusEl.textContent = '🔴 EN VIVO';
                        statusEl.className = 'qwc-badge qwc-badge-live';
                    } else if (m.status === 'finished') {
                        const hs = m.home_score !== null ? m.home_score : 0;
                        const as = m.away_score !== null ? m.away_score : 0;
                        scoreEl.textContent = hs + ' - ' + as;
                        statusEl.textContent = '✅ Finalizado';
                        statusEl.className = 'qwc-badge qwc-badge-finished';
                    }
                });
            })
            .catch(function(err) {
                console.error('QWC live update error:', err);
            });
    }

    // Primera actualización y luego cada intervalo
    updateLiveScores();
    setInterval(updateLiveScores, interval);

})();