<meta name="csrf-token" content="{{ csrf_token() }}">

<div>
    <div>
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" required value="Darkiex">
    </div>
    <div>
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required value="darkiex03@gmail.com">
    </div>

    <div style="margin-top:8px;">
        <button id="sendWelcomeBtn">Send Welcome Email</button>
        <button id="enable2faBtn" type="button">Enable 2FA</button>
        <button id="disable2faBtn" type="button">Disable 2FA</button>
    </div>

    <div>
        <label for="email">Verification Code:</label>
        <input type="text" id="verificationCode" name="verificationCode">
    </div>
    <button id="verify2fa" type="button">Verify 2FA code</button>

</div>

<script>
    // expose token and wire up handlers
    window.csrfToken = '{{ csrf_token() }}';

    document.getElementById('sendWelcomeBtn').addEventListener('click', sendWelcomeEmail);
    document.getElementById('enable2faBtn').addEventListener('click', enable2FA);
    document.getElementById('disable2faBtn').addEventListener('click', disable2FA);
    document.getElementById('verify2fa').addEventListener('click', verify2FA);

    function enable2FA() {
        const email = document.getElementById('email').value;
        const name = document.getElementById('name').value;

        if (!email || !name) {
            alert('Please enter both name and email to enable 2FA.');
            return;
        }

        const body = JSON.stringify({ email, name, will_be_enabled: true });
        sendRequest("/send-toggle-2fa-email", body);
    }

    function disable2FA() {
        const email = document.getElementById('email').value;
        const name = document.getElementById('name').value;

        if (!email || !name) {
            alert('Please enter both name and email to enable 2FA.');
            return;
        }

        const body = JSON.stringify({ email, name, will_be_enabled: false });
        sendRequest("/send-toggle-2fa-email", body);
    }

    function verify2FA() {
        const email = document.getElementById('email').value;
        const code = document.getElementById('verificationCode').value;

        if (!email || !code) {
            alert('Please enter both email and verification code to verify 2FA.');
            return;
        }

        const body = JSON.stringify({ email, verification_code: code });
        sendRequest("/verify-2fa", body);
    }

    async function sendWelcomeEmail() {
        const name = document.getElementById('name').value;
        const email = document.getElementById('email').value;

        if (!name || !email) {
            alert('Please enter both name and email.');
            return;
        }

        const body = JSON.stringify({ name, email });
        await sendRequest("/send-welcome-email", body);
    }

    async function sendRequest(url, body) {
        const token = window.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!token) {
            alert('CSRF token missing — cannot send request.');
            return;
        }

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: body
            });

            if (response.ok) {
                alert('Email sent!');
            } else {
                console.error('Server error response:', response.status, await response.text());
                alert('Failed to send email.');
            }
        } catch (err) {
            console.error('Request failed:', err);
            alert('An error occurred.');
        }
    }
</script>