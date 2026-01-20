<?php
/**
 * Glapris login menu bottom sheet snippet.
 *
 * Place in Code Snippets / WPCode as a single-file snippet.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bootstrap: register shortcode + REST route.
 */
add_action('init', function () {
    add_shortcode('glp_login_menu', 'glp_render_login_menu_shortcode');
});

add_action('rest_api_init', function () {
    register_rest_route('glp/v1', '/login_menu', [
        'methods' => 'POST',
        'callback' => 'glp_login_menu_endpoint',
        'permission_callback' => 'glp_login_menu_permission_check',
    ]);
});

/**
 * Permission check for REST endpoint (nonce required).
 */
function glp_login_menu_permission_check(WP_REST_Request $request)
{
    $nonce = $request->get_header('X-WP-Nonce');
    if (!$nonce || !wp_verify_nonce($nonce, 'wp_rest')) {
        return new WP_Error('glp_invalid_nonce', 'Ugyldig forespørsel. Prøv igjen.', ['status' => 403]);
    }

    return true;
}

/**
 * Determine whether Diller plugin appears active.
 */
function glp_is_diller_active()
{
    return class_exists('DillerLoyalty') || function_exists('DillerLoyalty');
}

/**
 * Shortcode output.
 */
function glp_render_login_menu_shortcode($atts = [])
{
    if (!glp_is_diller_active()) {
        if (current_user_can('manage_options')) {
            return '<div class="glp-login-menu-admin-msg">Diller er ikke aktiv – glp_login_menu krever Diller.</div>';
        }

        return '';
    }

    $uid = uniqid('glp_login_menu_', true);
    $rest_url = esc_url_raw(rest_url('glp/v1/login_menu'));
    $nonce = wp_create_nonce('wp_rest');
    $diller_form = do_shortcode('[diller_enrollment_form]');

    ob_start();
    ?>
    <div class="glp-login-menu" id="<?php echo esc_attr($uid); ?>" data-rest-url="<?php echo esc_url($rest_url); ?>" data-nonce="<?php echo esc_attr($nonce); ?>">
        <button type="button" class="glp-login-trigger">Logg inn / Bli medlem</button>

        <div class="glp-sheet-overlay" data-glp-close></div>

        <div class="glp-bottom-sheet" role="dialog" aria-modal="true" aria-hidden="true">
            <div class="glp-sheet-header">
                <div>
                    <h2>Logg inn eller bli medlem</h2>
                    <p class="glp-subtitle">Skriv helst inn mobilnummer.</p>
                </div>
                <button type="button" class="glp-close" aria-label="Lukk" data-glp-close>&times;</button>
            </div>

            <div class="glp-sheet-body">
                <div class="glp-step" data-step="identifier">
                    <label for="<?php echo esc_attr($uid); ?>_identifier">Mobilnummer eller e-post</label>
                    <input id="<?php echo esc_attr($uid); ?>_identifier" type="text" inputmode="text" autocomplete="username" placeholder="91234567 eller din@epost.no">
                    <div class="glp-error" data-error="identifier"></div>
                    <button type="button" class="glp-primary" data-action="lookup">Neste</button>

                    <div class="glp-divider"><span>Andre metoder</span></div>
                    <button type="button" class="glp-secondary" disabled>
                        Telefon + SMS <span class="glp-soon">Kommer snart</span>
                    </button>
                    <button type="button" class="glp-secondary glp-vipps" disabled>
                        <span class="glp-vipps-logo">Vipps</span>
                        <span class="glp-soon">Kommer snart</span>
                    </button>
                </div>

                <div class="glp-step" data-step="password" hidden>
                    <div class="glp-step-heading">
                        <strong>Logg inn</strong>
                        <span class="glp-step-subtext" data-identifier-display></span>
                    </div>
                    <label for="<?php echo esc_attr($uid); ?>_password">Passord</label>
                    <input id="<?php echo esc_attr($uid); ?>_password" type="password" autocomplete="current-password" placeholder="Skriv inn passord">
                    <div class="glp-error" data-error="password"></div>
                    <button type="button" class="glp-primary" data-action="login_password">Logg inn</button>
                    <button type="button" class="glp-link" data-action="forgot_password">Glemt passord?</button>
                </div>

                <div class="glp-step" data-step="forgot" hidden>
                    <div class="glp-step-heading">
                        <strong>Glemt passord</strong>
                        <span class="glp-step-subtext">Vi sender deg en e-post for å lage nytt passord.</span>
                    </div>
                    <label for="<?php echo esc_attr($uid); ?>_forgot">E-post</label>
                    <input id="<?php echo esc_attr($uid); ?>_forgot" type="email" autocomplete="email" placeholder="din@epost.no">
                    <div class="glp-error" data-error="forgot"></div>
                    <button type="button" class="glp-primary" data-action="forgot_password_submit">Send</button>
                    <button type="button" class="glp-link" data-action="back_to_login">Tilbake til innlogging</button>
                </div>

                <div class="glp-step" data-step="register" hidden>
                    <div class="glp-step-heading">
                        <strong>Bli medlem</strong>
                        <span class="glp-step-subtext">Opprett medlemskap for å få fordeler.</span>
                    </div>
                    <div class="glp-diller-form" data-diller-form>
                        <?php echo $diller_form; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>
                </div>

                <div class="glp-step" data-step="success" hidden>
                    <div class="glp-success">Fullført! Vi oppdaterer siden...</div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .glp-login-menu {
            --glp-orange: #f58220;
            --glp-dark: #1f1f1f;
            --glp-border: #e6e6e6;
            --glp-bg: #ffffff;
            --glp-muted: #6b6b6b;
            position: relative;
            z-index: 9999;
        }

        .glp-login-trigger {
            background: var(--glp-orange);
            color: #fff;
            border: none;
            border-radius: 999px;
            padding: 12px 20px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 10px 24px rgba(245, 130, 32, 0.2);
        }

        .glp-sheet-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.45);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.25s ease;
        }

        .glp-bottom-sheet {
            position: fixed;
            left: 50%;
            bottom: 16px;
            transform: translate(-50%, 110%);
            width: min(680px, 100vw);
            max-height: 88vh;
            background: var(--glp-bg);
            border-radius: 16px 16px 16px 16px;
            border: 1px solid var(--glp-border);
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.2);
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease;
            overflow: hidden;
            pointer-events: none;
        }

        .glp-login-menu.is-open .glp-sheet-overlay {
            opacity: 1;
            pointer-events: auto;
        }

        .glp-login-menu.is-open .glp-bottom-sheet {
            transform: translate(-50%, 0);
            pointer-events: auto;
        }

        .glp-sheet-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 20px 24px 12px;
            border-bottom: 1px solid var(--glp-border);
        }

        .glp-sheet-header h2 {
            margin: 0 0 4px;
            font-size: 20px;
            color: var(--glp-dark);
        }

        .glp-subtitle {
            margin: 0;
            font-size: 14px;
            color: var(--glp-muted);
        }

        .glp-close {
            background: transparent;
            border: none;
            font-size: 26px;
            line-height: 1;
            cursor: pointer;
            color: var(--glp-dark);
        }

        .glp-sheet-body {
            padding: 20px 24px 24px;
            overflow-y: auto;
        }

        .glp-step {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .glp-step-heading {
            display: flex;
            flex-direction: column;
            gap: 4px;
            margin-bottom: 4px;
        }

        .glp-step-subtext {
            color: var(--glp-muted);
            font-size: 13px;
        }

        .glp-step label {
            font-weight: 600;
            font-size: 14px;
            color: var(--glp-dark);
        }

        .glp-step input[type="text"],
        .glp-step input[type="password"],
        .glp-step input[type="email"] {
            border: 1px solid var(--glp-border);
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 15px;
            font-family: inherit;
            outline: none;
        }

        .glp-step input:focus {
            border-color: var(--glp-orange);
            box-shadow: 0 0 0 3px rgba(245, 130, 32, 0.15);
        }

        .glp-primary {
            background: var(--glp-orange);
            color: #fff;
            border: none;
            border-radius: 999px;
            padding: 12px 18px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
        }

        .glp-secondary {
            background: #f5f5f5;
            color: #8a8a8a;
            border: 1px solid #e2e2e2;
            border-radius: 999px;
            padding: 12px 18px;
            font-weight: 600;
            font-size: 15px;
            cursor: not-allowed;
            display: inline-flex;
            justify-content: center;
            gap: 8px;
        }

        .glp-vipps {
            background: #ff5b24;
            color: #fff;
            border: none;
            justify-content: space-between;
        }

        .glp-vipps-logo {
            background: rgba(255, 255, 255, 0.2);
            padding: 4px 8px;
            border-radius: 10px;
            font-weight: 700;
        }

        .glp-soon {
            font-size: 12px;
            padding: 2px 8px;
            border-radius: 999px;
            background: rgba(0, 0, 0, 0.08);
        }

        .glp-divider {
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--glp-muted);
            font-size: 12px;
            text-transform: uppercase;
        }

        .glp-divider::before,
        .glp-divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background: var(--glp-border);
        }

        .glp-link {
            background: none;
            border: none;
            color: var(--glp-orange);
            font-weight: 600;
            cursor: pointer;
            text-align: left;
            padding: 0;
        }

        .glp-error {
            color: #c62828;
            font-size: 13px;
            min-height: 18px;
        }

        .glp-success {
            background: #f2f9f4;
            border: 1px solid #cde7d5;
            padding: 16px;
            border-radius: 12px;
            color: #1b6f36;
            font-weight: 600;
            text-align: center;
        }

        .glp-diller-form input,
        .glp-diller-form select,
        .glp-diller-form textarea {
            border-radius: 12px !important;
            border: 1px solid var(--glp-border) !important;
            padding: 12px 14px !important;
            font-size: 15px !important;
        }

        .glp-diller-form button,
        .glp-diller-form input[type="submit"] {
            background: var(--glp-orange) !important;
            color: #fff !important;
            border-radius: 999px !important;
            border: none !important;
            padding: 12px 18px !important;
            font-weight: 600 !important;
            font-size: 15px !important;
        }

        .glp-consent-error {
            color: #c62828;
            font-size: 13px;
            margin-bottom: 8px;
        }

        @media (max-width: 640px) {
            .glp-bottom-sheet {
                border-radius: 16px 16px 0 0;
                bottom: 0;
            }
        }

        body.glp-sheet-open {
            overflow: hidden;
        }
    </style>

    <script>
        (function () {
            var root = document.getElementById(<?php echo wp_json_encode($uid); ?>);
            if (!root) {
                return;
            }

            var restUrl = root.getAttribute('data-rest-url');
            var nonce = root.getAttribute('data-nonce');
            var overlay = root.querySelector('.glp-sheet-overlay');
            var sheet = root.querySelector('.glp-bottom-sheet');
            var trigger = root.querySelector('.glp-login-trigger');
            var closeButtons = root.querySelectorAll('[data-glp-close]');

            var state = {
                identifier: '',
                identifierType: '',
                phoneNorm: ''
            };

            function openSheet() {
                root.classList.add('is-open');
                document.body.classList.add('glp-sheet-open');
                sheet.setAttribute('aria-hidden', 'false');
                var input = root.querySelector('[data-step="identifier"] input');
                if (input) {
                    setTimeout(function () {
                        input.focus();
                    }, 80);
                }
            }

            function closeSheet() {
                root.classList.remove('is-open');
                document.body.classList.remove('glp-sheet-open');
                sheet.setAttribute('aria-hidden', 'true');
            }

            function setStep(step) {
                root.querySelectorAll('.glp-step').forEach(function (el) {
                    if (el.getAttribute('data-step') === step) {
                        el.hidden = false;
                    } else {
                        el.hidden = true;
                    }
                });
            }

            function setError(key, message) {
                var node = root.querySelector('[data-error="' + key + '"]');
                if (node) {
                    node.textContent = message || '';
                }
            }

            function clearErrors() {
                root.querySelectorAll('.glp-error').forEach(function (el) {
                    el.textContent = '';
                });
            }

            function normalizePhone(input) {
                if (!input) {
                    return '';
                }
                var digits = input.replace(/\D/g, '');
                if (digits.indexOf('0047') === 0) {
                    digits = digits.slice(4);
                } else if (digits.indexOf('47') === 0 && digits.length > 8) {
                    digits = digits.slice(2);
                }
                if (digits.length === 8) {
                    return digits;
                }
                return '';
            }

            function isEmail(value) {
                return /\S+@\S+\.\S+/.test(value);
            }

            async function postAction(payload) {
                var response = await fetch(restUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': nonce
                    },
                    body: JSON.stringify(payload)
                });

                var data = await response.json();
                if (!response.ok) {
                    throw data;
                }

                return data;
            }

            async function handleLookup() {
                clearErrors();
                var input = root.querySelector('[data-step="identifier"] input');
                var value = input ? input.value.trim() : '';
                if (!value) {
                    setError('identifier', 'Skriv inn mobilnummer eller e-post.');
                    return;
                }

                state.identifier = value;
                state.identifierType = isEmail(value) ? 'email' : 'phone';
                state.phoneNorm = normalizePhone(value);

                try {
                    var result = await postAction({ action: 'lookup', identifier: value });
                    state.identifierType = result.identifier_type || state.identifierType;
                    var display = root.querySelector('[data-identifier-display]');
                    if (display) {
                        display.textContent = result.identifier_display || value;
                    }
                    if (result.exists) {
                        setStep('password');
                    } else {
                        setStep('register');
                        setupDillerConsentGuard();
                    }
                } catch (error) {
                    setError('identifier', error.message || 'Noe gikk galt. Prøv igjen.');
                }
            }

            async function handlePasswordLogin() {
                clearErrors();
                var password = root.querySelector('[data-step="password"] input[type="password"]');
                if (!password || !password.value) {
                    setError('password', 'Skriv inn passordet ditt.');
                    return;
                }
                try {
                    await postAction({
                        action: 'login_password',
                        identifier: state.identifier,
                        password: password.value
                    });
                    setStep('success');
                    window.location.reload();
                } catch (error) {
                    setError('password', error.message || 'Ugyldig innlogging.');
                }
            }

            function showForgotPassword() {
                clearErrors();
                var emailInput = root.querySelector('[data-step="forgot"] input');
                if (emailInput && state.identifierType === 'email') {
                    emailInput.value = state.identifier || '';
                }
                setStep('forgot');
            }

            async function handleForgotPasswordSubmit() {
                clearErrors();
                var emailInput = root.querySelector('[data-step="forgot"] input');
                var value = emailInput ? emailInput.value.trim() : '';
                if (!value || !isEmail(value)) {
                    setError('forgot', 'Oppgi en gyldig e-post.');
                    return;
                }
                try {
                    await postAction({ action: 'forgot_password', identifier: value });
                    setStep('success');
                    window.location.reload();
                } catch (error) {
                    setError('forgot', error.message || 'Kunne ikke sende e-post.');
                }
            }

            function setupDillerConsentGuard() {
                var formWrapper = root.querySelector('[data-diller-form]');
                if (!formWrapper) {
                    return;
                }
                var form = formWrapper.querySelector('form');
                if (!form) {
                    return;
                }

                var cssEscape = (window.CSS && CSS.escape) ? CSS.escape : function (value) {
                    return String(value).replace(/[^a-zA-Z0-9_-]/g, '\\\\$&');
                };

                var errorNode = formWrapper.querySelector('.glp-consent-error');
                if (!errorNode) {
                    errorNode = document.createElement('div');
                    errorNode.className = 'glp-consent-error';
                    var submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
                    if (submitButton && submitButton.parentNode) {
                        submitButton.parentNode.insertBefore(errorNode, submitButton);
                    } else {
                        form.appendChild(errorNode);
                    }
                }

                function findConsentCheckbox(matchText) {
                    var labels = form.querySelectorAll('label');
                    for (var i = 0; i < labels.length; i++) {
                        var label = labels[i];
                        if (label.textContent && label.textContent.toLowerCase().indexOf(matchText) !== -1) {
                            var forId = label.getAttribute('for');
                            if (forId) {
                                var input = form.querySelector('#' + cssEscape(forId));
                                if (input) {
                                    return input;
                                }
                            }
                            var inputInLabel = label.querySelector('input[type="checkbox"]');
                            if (inputInLabel) {
                                return inputInLabel;
                            }
                        }
                    }
                    return null;
                }

                form.addEventListener('submit', function (event) {
                    var consentA = findConsentCheckbox('kundeklubb');
                    var consentB = findConsentCheckbox('preferanser');
                    var missing = [];
                    if (consentA && !consentA.checked) {
                        missing.push('kundeklubb');
                    }
                    if (consentB && !consentB.checked) {
                        missing.push('preferanser');
                    }
                    if (missing.length) {
                        event.preventDefault();
                        errorNode.textContent = 'Du må samtykke til begge markedsføringspunktene for å fortsette.';
                        errorNode.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    } else {
                        errorNode.textContent = '';
                    }
                });
            }

            trigger.addEventListener('click', openSheet);
            closeButtons.forEach(function (button) {
                button.addEventListener('click', closeSheet);
            });

            overlay.addEventListener('click', closeSheet);
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && root.classList.contains('is-open')) {
                    closeSheet();
                }
            });

            root.addEventListener('click', function (event) {
                var action = event.target.getAttribute('data-action');
                if (!action) {
                    return;
                }
                switch (action) {
                    case 'lookup':
                        handleLookup();
                        break;
                    case 'login_password':
                        handlePasswordLogin();
                        break;
                    case 'forgot_password':
                        showForgotPassword();
                        break;
                    case 'forgot_password_submit':
                        handleForgotPasswordSubmit();
                        break;
                    case 'back_to_login':
                        setStep('password');
                        break;
                    default:
                        break;
                }
            });

            root.glpOpen = openSheet;
            root.glpClose = closeSheet;
        })();
    </script>
    <?php

    return ob_get_clean();
}

/**
 * REST endpoint handler.
 */
function glp_login_menu_endpoint(WP_REST_Request $request)
{
    $params = (array) $request->get_json_params();
    $action = isset($params['action']) ? sanitize_text_field($params['action']) : '';

    switch ($action) {
        case 'lookup':
            return glp_login_menu_lookup($params);
        case 'login_password':
            return glp_login_menu_password_login($params);
        case 'forgot_password':
            return glp_login_menu_forgot_password($params);
        default:
            return new WP_Error('glp_invalid_action', 'Ugyldig forespørsel.', ['status' => 400]);
    }
}

/**
 * Lookup step (exists or not, Diller-aware).
 */
function glp_login_menu_lookup(array $params)
{
    $rate_limit = glp_login_menu_rate_limit('lookup', 30, 600);
    if (is_wp_error($rate_limit)) {
        return $rate_limit;
    }

    $identifier_raw = isset($params['identifier']) ? sanitize_text_field($params['identifier']) : '';
    if (!$identifier_raw) {
        return new WP_Error('glp_missing_identifier', 'Skriv inn mobilnummer eller e-post.', ['status' => 400]);
    }

    $identifier_type = is_email($identifier_raw) ? 'email' : 'phone';
    $phone_norm = glp_normalize_phone($identifier_raw);

    $user = null;
    if ($identifier_type === 'email') {
        $user = get_user_by('email', $identifier_raw);
    } else {
        if ($phone_norm) {
            $user = glp_find_user_by_phone($phone_norm);
        }
    }

    $exists = (bool) $user;

    if (!$exists && glp_is_diller_active()) {
        $exists = glp_check_diller_follower($identifier_type, $identifier_raw, $phone_norm);
    }

    $display = $identifier_raw;
    if ($identifier_type === 'phone' && $phone_norm) {
        $display = $phone_norm;
    }

    return [
        'exists' => $exists,
        'identifier_type' => $identifier_type,
        'identifier_display' => $display,
    ];
}

/**
 * Password login step.
 */
function glp_login_menu_password_login(array $params)
{
    $rate_limit = glp_login_menu_rate_limit('login', 20, 600);
    if (is_wp_error($rate_limit)) {
        return $rate_limit;
    }

    $identifier_raw = isset($params['identifier']) ? sanitize_text_field($params['identifier']) : '';
    $password = isset($params['password']) ? $params['password'] : '';

    if (!$identifier_raw || !$password) {
        return new WP_Error('glp_missing_login', 'Skriv inn innloggingsdetaljer.', ['status' => 400]);
    }

    $identifier_type = is_email($identifier_raw) ? 'email' : 'phone';

    if ($identifier_type === 'email') {
        $user = get_user_by('email', $identifier_raw);
    } else {
        $phone_norm = glp_normalize_phone($identifier_raw);
        $user = $phone_norm ? glp_find_user_by_phone($phone_norm) : null;
    }

    if (!$user) {
        return new WP_Error('glp_invalid_login', 'Ugyldig innlogging. Prøv igjen.', ['status' => 403]);
    }

    $credentials = [
        'user_login' => $user->user_login,
        'user_password' => $password,
        'remember' => true,
    ];

    $signon = wp_signon($credentials, is_ssl());
    if (is_wp_error($signon)) {
        return new WP_Error('glp_invalid_login', 'Ugyldig innlogging. Prøv igjen.', ['status' => 403]);
    }

    return [
        'success' => true,
    ];
}

/**
 * Forgot password step.
 */
function glp_login_menu_forgot_password(array $params)
{
    $identifier_raw = isset($params['identifier']) ? sanitize_text_field($params['identifier']) : '';
    if (!$identifier_raw) {
        return new WP_Error('glp_missing_identifier', 'Oppgi e-postadressen din.', ['status' => 400]);
    }

    $email = '';
    if (is_email($identifier_raw)) {
        $email = $identifier_raw;
    } else {
        $phone_norm = glp_normalize_phone($identifier_raw);
        if ($phone_norm) {
            $user = glp_find_user_by_phone($phone_norm);
            if ($user) {
                $email = $user->user_email;
            }
        }
    }

    if (!$email || !is_email($email)) {
        return new WP_Error('glp_invalid_email', 'Oppgi en gyldig e-postadresse.', ['status' => 400]);
    }

    $result = retrieve_password($email);
    if (is_wp_error($result)) {
        return new WP_Error('glp_reset_failed', 'Kunne ikke sende e-post. Prøv igjen.', ['status' => 500]);
    }

    return [
        'success' => true,
    ];
}

/**
 * Rate limiting (per IP).
 */
function glp_login_menu_rate_limit($action, $max, $window)
{
    $ip = glp_get_ip_address();
    $key = 'glp_login_' . $action . '_' . md5($ip);
    $data = get_transient($key);
    if (!is_array($data)) {
        $data = [
            'count' => 0,
            'start' => time(),
        ];
    }

    if ((time() - $data['start']) > $window) {
        $data = [
            'count' => 0,
            'start' => time(),
        ];
    }

    $data['count']++;
    set_transient($key, $data, $window);

    if ($data['count'] > $max) {
        return new WP_Error('glp_rate_limit', 'For mange forsøk. Vent litt og prøv igjen.', ['status' => 429]);
    }

    return true;
}

function glp_get_ip_address()
{
    $keys = ['HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER[$key]));
            if (strpos($ip, ',') !== false) {
                $parts = explode(',', $ip);
                $ip = trim($parts[0]);
            }
            return $ip;
        }
    }

    return 'unknown';
}

/**
 * Normalize Norwegian phone to 8 digits.
 */
function glp_normalize_phone($input)
{
    $digits = preg_replace('/\D+/', '', (string) $input);
    if (strpos($digits, '0047') === 0) {
        $digits = substr($digits, 4);
    } elseif (strpos($digits, '47') === 0 && strlen($digits) > 8) {
        $digits = substr($digits, 2);
    }

    if (strlen($digits) === 8) {
        return $digits;
    }

    return '';
}

/**
 * Find WP user by normalized phone.
 */
function glp_find_user_by_phone($phone_norm)
{
    if (!$phone_norm) {
        return null;
    }

    $user_query = new WP_User_Query([
        'meta_key' => 'glp_phone_norm',
        'meta_value' => $phone_norm,
        'number' => 1,
        'fields' => 'all',
    ]);

    $users = $user_query->get_results();
    if (!empty($users)) {
        return $users[0];
    }

    global $wpdb;
    $like = '%' . $wpdb->esc_like($phone_norm) . '%';
    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value LIKE %s",
            'billing_phone',
            $like
        )
    );

    if ($results) {
        foreach ($results as $row) {
            $normalized = glp_normalize_phone($row->meta_value);
            if ($normalized === $phone_norm) {
                $user = get_user_by('id', (int) $row->user_id);
                if ($user) {
                    update_user_meta($user->ID, 'glp_phone_norm', $phone_norm);
                    return $user;
                }
            }
        }
    }

    return null;
}

/**
 * Check Diller follower existence (best-effort).
 */
function glp_check_diller_follower($identifier_type, $identifier_raw, $phone_norm)
{
    if (!glp_is_diller_active()) {
        return false;
    }

    try {
        $diller = function_exists('DillerLoyalty') ? DillerLoyalty() : null;
        if (!$diller || !method_exists($diller, 'get_api')) {
            return false;
        }
        $api = $diller->get_api();
        if (!$api) {
            return false;
        }

        if ($identifier_type === 'phone' && $phone_norm && method_exists($api, 'get_follower')) {
            $response = $api->get_follower('NO', $phone_norm);
            if (is_wp_error($response)) {
                return false;
            }
            return !empty($response);
        }

        if ($identifier_type === 'email') {
            $email_methods = ['get_follower_by_email', 'get_follower_by_email_address'];
            foreach ($email_methods as $method) {
                if (method_exists($api, $method)) {
                    $response = $api->{$method}($identifier_raw);
                    if (is_wp_error($response)) {
                        return false;
                    }
                    return !empty($response);
                }
            }
        }
    } catch (Exception $exception) {
        return false;
    }

    return false;
}
