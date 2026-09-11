/** JT Navi | Copyright (C) 2026 JoomTheme | GPL-2.0-or-later */
(() => {
    'use strict';
    const init = () => document.querySelectorAll('.jtnavi[data-endpoint]').forEach((root) => {
        if (root.dataset.ready) return;
        root.dataset.ready = 'true';
        const strings = JSON.parse(root.dataset.strings || '{}');
        const form = root.querySelector('.jtnavi__form');
        const input = form.elements.question;
        const status = root.querySelector('.jtnavi__status');
        const results = root.querySelector('.jtnavi__results');
        const panel = root.querySelector('.jtnavi__body');
        const toggle = root.querySelector('.jtnavi__toggle');
        let busy = false;
        const node = (tag, className, text) => {
            const item = document.createElement(tag);
            if (className) item.className = className;
            if (text !== undefined) item.textContent = String(text);
            return item;
        };
        const safeUrl = (value) => {
            if (typeof value !== 'string' || !value.trim()) return null;
            try {
                const url = new URL(value, window.location.href);
                return ['http:', 'https:'].includes(url.protocol) && !url.username && !url.password ? url.href : null;
            } catch { return null; }
        };
        const render = (data) => {
            results.replaceChildren();
            results.append(node('span', 'jtnavi__badge', data.mode === 'ai' ? strings.ai_result : strings.local_result));
            results.append(node('p', 'jtnavi__answer', data.answer || ''));
            if (data.guide && Array.isArray(data.guide.steps)) {
                results.append(node('h3', 'jtnavi__guide-title', data.guide.title));
                const steps = node('ol', 'jtnavi__steps');
                for (const step of data.guide.steps.slice(0, 6)) steps.append(node('li', '', step));
                results.append(steps);
            }
            const choices = node('div', 'jtnavi__choices');
            for (const choice of (Array.isArray(data.choices) ? data.choices.slice(0, 8) : [])) {
                const button = node('button', 'jtnavi__chip', choice.label);
                button.type = 'button';
                button.dataset.query = choice.query;
                choices.append(button);
            }
            if (choices.childElementCount) results.append(choices);
            const list = node('ul', 'jtnavi__source-list');
            for (const source of (Array.isArray(data.sources) ? data.sources.slice(0, 8) : [])) {
                const url = safeUrl(source.url);
                if (!url) continue;
                const item = node('li', 'jtnavi__source');
                const link = node('a', '', source.title);
                link.href = url;
                // Regular navigation avoids unexpected new windows.
                item.append(link, node('p', '', source.summary || ''));
                item.hidden = list.childElementCount >= 2;
                list.append(item);
            }
            if (list.childElementCount) {
                list.id = root.id + '-sources';
                results.append(node('h3', 'jtnavi__sr-only', strings.sources), list);
                if (list.childElementCount > 2) {
                    const more = node('button', 'jtnavi__chip jtnavi__more', strings.show_more);
                    more.type = 'button';
                    more.setAttribute('aria-controls', list.id);
                    more.setAttribute('aria-expanded', 'false');
                    more.addEventListener('click', () => {
                        const expanded = more.getAttribute('aria-expanded') !== 'true';
                        [...list.children].forEach((item, i) => { item.hidden = !expanded && i >= 2; });
                        more.setAttribute('aria-expanded', String(expanded));
                        more.textContent = expanded ? strings.show_less : strings.show_more;
                    });
                    results.append(more);
                }
            }
            if (data.notice) results.append(node('p', 'jtnavi__notice', data.notice));
            results.hidden = false;
            results.focus({preventScroll: true});
        };
        if (toggle) {
            toggle.addEventListener('click', () => {
                panel.hidden = !panel.hidden;
                toggle.setAttribute('aria-expanded', String(!panel.hidden));
                if (!panel.hidden) input.focus();
            });
            panel.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    panel.hidden = true;
                    toggle.setAttribute('aria-expanded', 'false');
                    toggle.focus();
                }
            });
        }
        root.addEventListener('click', (event) => {
            const button = event.target.closest('button[data-query]');
            if (button && root.contains(button) && !busy) {
                input.value = button.dataset.query;
                form.requestSubmit();
            }
        });
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (busy || !form.reportValidity()) return;
            busy = true;
            results.hidden = true;
            status.textContent = strings.loading;
            root.setAttribute('aria-busy', 'true');
            root.querySelectorAll('button:not(.jtnavi__toggle)').forEach((b) => { b.disabled = true; });
            const abort = new AbortController();
            const timer = window.setTimeout(() => abort.abort(), 35000);
            try {
                const endpoint = new URL(root.dataset.endpoint, window.location.href);
                if (endpoint.origin !== window.location.origin) throw new Error(strings.error);
                const bootstrap = new URL(endpoint);
                bootstrap.searchParams.set('task', 'assistant.session');
                const tokenResponse = await fetch(bootstrap, {credentials: 'same-origin', cache: 'no-store', signal: abort.signal, headers: {'Accept': 'application/json'}});
                const tokenData = await tokenResponse.json();
                if (!tokenResponse.ok || !tokenData.success || typeof tokenData.data?.token !== 'string' || !/^[a-f0-9]{32}$/i.test(tokenData.data.token)) {
                    throw new Error(tokenData.message || strings.error);
                }
                const body = new URLSearchParams();
                body.set(tokenData.data.token, '1');
                body.set('question', input.value.trim());
                body.set('language', root.dataset.language || 'en-GB');
                body.set('ai', form.elements.ai?.checked ? '1' : '0');
                const response = await fetch(endpoint, {method: 'POST', credentials: 'same-origin', cache: 'no-store', body,
                    headers: {'Accept': 'application/json'}, signal: abort.signal});
                const json = await response.json();
                if (!response.ok || !json.success || !json.data) throw new Error(json.message || strings.error);
                render(json.data);
                status.textContent = '';
            } catch (error) {
                status.textContent = error.name === 'AbortError' ? strings.timeout : (error.message || strings.error);
            } finally {
                window.clearTimeout(timer);
                busy = false;
                root.removeAttribute('aria-busy');
                root.querySelectorAll('button:not(.jtnavi__toggle)').forEach((b) => { b.disabled = false; });
            }
        });
    });
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
