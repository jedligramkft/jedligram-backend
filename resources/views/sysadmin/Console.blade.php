<style>
    #console_output {
        position: fixed;
        right: 0;
        top: 0;

        background-color: #0c0c0c;
        width: 50%;
        max-width: 50%;
        height: 100dvh;

        color: #eee;
        padding: 10px;
        overflow-y: auto;
        font-family: 'Consolas', 'Courier New', monospace;
        font-size: 13px;
        white-space: pre-wrap;
        word-wrap: break-word;
    }

    #console_output .cmd-line {
        color: #5af;
        font-weight: bold;
        margin-top: 8px;
    }

    #console_output .cmd-time {
        color: #888;
        font-size: 11px;
    }

    #console_output .cmd-output {
        color: #ccc;
        margin-bottom: 4px;
    }

    #console_output .cmd-error {
        color: #f55;
    }

    #console_output .cmd-success {
        color: #5f5;
    }

    #console_output .cmd-separator {
        border-bottom: 1px solid #333;
        margin: 6px 0;
    }

    #console_header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-bottom: 6px;
        border-bottom: 1px solid #333;
        margin-bottom: 8px;
    }

    #console_header span {
        color: #888;
        font-size: 12px;
    }

    #clear_console {
        background: #333;
        color: #aaa;
        border: none;
        padding: 2px 8px;
        cursor: pointer;
        font-size: 11px;
        border-radius: 3px;
    }

    #clear_console:hover {
        background: #555;
        color: #eee;
    }
</style>

<div id="console_output">
    <div id="console_header">
        <span>Console</span>
        <button id="clear_console">Clear</button>
    </div>
    <div id="console_log"></div>
</div>

<script>
    const csrfToken = '{{ csrf_token() }}';
    const consoleLog = document.getElementById('console_log');
    const consoleOutput = document.getElementById('console_output');

    // ── Rendering helpers ────────────────────────────────────────────────────

    function renderEntry(command, output, success, timestamp) {
        const entry = document.createElement('div');

        const timeLine = document.createElement('div');
        timeLine.className = 'cmd-time';
        timeLine.textContent = timestamp ?? new Date().toLocaleTimeString('hu-HU', { hour: '2-digit', minute: '2-digit', second: '2-digit' });

        const cmdLine = document.createElement('div');
        cmdLine.className = 'cmd-line';
        cmdLine.textContent = '$ php artisan ' + command;

        const outputLine = document.createElement('div');
        outputLine.className = success ? 'cmd-output' : 'cmd-error';
        outputLine.textContent = output || '(no output)';

        const statusLine = document.createElement('div');
        statusLine.className = success ? 'cmd-success' : 'cmd-error';
        statusLine.textContent = success ? '✓ Completed successfully' : '✗ Command failed';

        const separator = document.createElement('div');
        separator.className = 'cmd-separator';

        entry.appendChild(timeLine);
        entry.appendChild(cmdLine);
        entry.appendChild(outputLine);
        entry.appendChild(statusLine);
        entry.appendChild(separator);
        return entry;
    }

    function appendRunning(command) {
        const entry = document.createElement('div');

        const timeLine = document.createElement('div');
        timeLine.className = 'cmd-time';
        timeLine.textContent = new Date().toLocaleTimeString('hu-HU', { hour: '2-digit', minute: '2-digit', second: '2-digit' });

        const cmdLine = document.createElement('div');
        cmdLine.className = 'cmd-line';
        cmdLine.textContent = '$ php artisan ' + command;

        const statusLine = document.createElement('div');
        statusLine.className = 'cmd-output';
        statusLine.textContent = '⏳ Running...';

        entry.appendChild(timeLine);
        entry.appendChild(cmdLine);
        entry.appendChild(statusLine);

        consoleLog.appendChild(entry);
        consoleOutput.scrollTop = consoleOutput.scrollHeight;
        return entry;
    }

    function updateEntry(entry, command, output, success) {
        const timestamp = entry.querySelector('.cmd-time')?.textContent;
        const finished = renderEntry(command, output, success, timestamp);
        entry.replaceWith(finished);
        consoleOutput.scrollTop = consoleOutput.scrollHeight;
    }

    function clearConsole() {
        consoleLog.innerHTML = '';
        fetch('/sysadmin/console_clear', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        });
    }

    // ── Restore history from server ──────────────────────────────────────────

    @foreach($consoleHistory as $entry)
        consoleLog.appendChild(renderEntry(
            @js($entry['command']),
            @js($entry['output']),
            {{ $entry['success'] ? 'true' : 'false' }},
            @js($entry['timestamp'])
        ));
    @endforeach
    consoleOutput.scrollTop = consoleOutput.scrollHeight;

    // ── Clear button ─────────────────────────────────────────────────────────

    document.getElementById('clear_console').addEventListener('click', clearConsole);

    // ── AJAX form submissions ────────────────────────────────────────────────

    document.querySelectorAll('form[data-ajax]').forEach(form => {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            const btn = form.querySelector('button[type="submit"]');
            const originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = '⏳ Running...';

            const url = form.getAttribute('action');
            const cmd = new URL(url, window.location.origin).searchParams.get('cmd')?.replace(/\+/g, ' ') ?? url;

            const runningEntry = appendRunning(cmd);

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                });

                const data = await response.json();
                updateEntry(runningEntry, data.command, data.output, data.success);
            } catch (err) {
                updateEntry(runningEntry, cmd, 'Network error: ' + err.message, false);
            } finally {
                btn.disabled = false;
                btn.textContent = originalText;
            }
        });
    });
</script>
