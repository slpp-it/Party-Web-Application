<?php
declare(strict_types=1);

if (!function_exists('render_language_toggle')) {
    function render_language_toggle(string $activeLanguage = 'en', bool $compact = false): void
    {
        $languages = [
            'en' => ['label' => 'English', 'short' => 'EN'],
            'si' => ['label' => 'Sinhala', 'short' => 'සි'],
            'ta' => ['label' => 'Tamil', 'short' => 'த'],
        ];

        $activeLanguage = strtolower(trim($activeLanguage));
        if (!array_key_exists($activeLanguage, $languages)) {
            $activeLanguage = 'en';
        }
        $toggleClass = 'language-toggle' . ($compact ? ' language-toggle--compact' : '');
        ?>
        <div class="<?php echo htmlspecialchars($toggleClass, ENT_QUOTES, 'UTF-8'); ?>" data-language-toggle translate="no">
            <span class="language-toggle-label">Translate</span>
            <div class="language-toggle-group" role="group" aria-label="Select page language">
                <?php foreach ($languages as $code => $language): ?>
                    <button
                        class="language-toggle-button<?php echo $code === $activeLanguage ? ' is-active' : ''; ?>"
                        type="button"
                        data-language-toggle-button
                        data-language="<?php echo htmlspecialchars($code, ENT_QUOTES, 'UTF-8'); ?>"
                        aria-pressed="<?php echo $code === $activeLanguage ? 'true' : 'false'; ?>"
                        aria-label="<?php echo htmlspecialchars($language['label'], ENT_QUOTES, 'UTF-8'); ?>"
                    >
                        <?php echo htmlspecialchars($language['short'], ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
        <div id="google_translate_element" class="language-toggle-widget" aria-hidden="true"></div>
        <script>
            (function () {
                if (window.__partyLanguageToggleReady) {
                    return;
                }

                window.__partyLanguageToggleReady = true;

                const DEFAULT_LANGUAGE = 'en';
                const SUPPORTED_LANGUAGES = ['en', 'si', 'ta'];
                const root = document.querySelector('[data-language-toggle]');
                const buttons = root ? Array.from(root.querySelectorAll('[data-language-toggle-button]')) : [];
                const widgetHostId = 'google_translate_element';
                let currentLanguage = DEFAULT_LANGUAGE;
                let translateScriptLoaded = false;
                let translateWidgetReady = false;
                let pendingLanguage = DEFAULT_LANGUAGE;
                let widgetRetryTimer = 0;

                const setCookie = (language) => {
                    const cookieValue = language === DEFAULT_LANGUAGE ? '/en/en' : `/en/${language}`;
                    document.cookie = `googtrans=${cookieValue};path=/;SameSite=Lax`;
                };

                const updateButtonState = (language) => {
                    buttons.forEach((button) => {
                        const isActive = button.dataset.language === language;
                        button.classList.toggle('is-active', isActive);
                        button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                    });
                };

                const applyToWidget = (language) => {
                    const select = document.querySelector(`#${widgetHostId} .goog-te-combo`) || document.querySelector('.goog-te-combo');
                    if (!select) {
                        return false;
                    }

                    if (select.value !== language) {
                        select.value = language;
                        select.dispatchEvent(new Event('change'));
                    }

                    return true;
                };

                const stopWidgetRetry = () => {
                    if (widgetRetryTimer) {
                        window.clearInterval(widgetRetryTimer);
                        widgetRetryTimer = 0;
                    }
                };

                const syncLanguage = (language) => {
                    const normalizedLanguage = SUPPORTED_LANGUAGES.includes(language) ? language : DEFAULT_LANGUAGE;
                    const wasTranslated = currentLanguage !== DEFAULT_LANGUAGE;
                    currentLanguage = normalizedLanguage;
                    updateButtonState(normalizedLanguage);
                    document.documentElement.lang = normalizedLanguage;
                    setCookie(normalizedLanguage);

                    if (normalizedLanguage === DEFAULT_LANGUAGE) {
                        stopWidgetRetry();
                        if (wasTranslated) {
                            window.location.reload();
                        }
                        return;
                    }

                    pendingLanguage = normalizedLanguage;

                    if (!translateScriptLoaded) {
                        loadTranslateScript();
                        return;
                    }

                    if (!translateWidgetReady) {
                        startWidgetRetry();
                        return;
                    }

                    applyToWidget(normalizedLanguage);
                };

                const startWidgetRetry = () => {
                    if (widgetRetryTimer) {
                        return;
                    }

                    widgetRetryTimer = window.setInterval(() => {
                        if (applyToWidget(pendingLanguage)) {
                            stopWidgetRetry();
                        }
                    }, 250);
                };

                const loadTranslateScript = () => {
                    if (translateScriptLoaded) {
                        return;
                    }

                    translateScriptLoaded = true;
                    window.partyTranslateInit = () => {
                        if (!window.google || !window.google.translate || !window.google.translate.TranslateElement) {
                            return;
                        }

                        translateWidgetReady = true;

                        new google.translate.TranslateElement(
                            {
                                pageLanguage: 'en',
                                includedLanguages: 'en,si,ta',
                                autoDisplay: false,
                            },
                            widgetHostId
                        );

                        if (pendingLanguage !== DEFAULT_LANGUAGE) {
                            startWidgetRetry();
                        }
                    };

                    const script = document.createElement('script');
                    script.src = 'https://translate.google.com/translate_a/element.js?cb=partyTranslateInit';
                    script.async = true;
                    script.defer = true;
                    script.onerror = () => {
                        translateScriptLoaded = false;
                    };
                    document.head.appendChild(script);
                };

                buttons.forEach((button) => {
                    button.addEventListener('click', () => {
                        syncLanguage(button.dataset.language || DEFAULT_LANGUAGE);
                    });
                });

                updateButtonState(DEFAULT_LANGUAGE);
                setCookie(DEFAULT_LANGUAGE);

            })();
        </script>
        <style>
            .goog-te-banner-frame.skiptranslate,
            iframe.goog-te-banner-frame {
                display: none !important;
            }

            body {
                top: 0 !important;
            }

            .goog-te-gadget,
            .goog-te-combo,
            .goog-logo-link,
            .goog-te-banner-frame,
            .goog-te-balloon-frame,
            .skiptranslate {
                display: none !important;
            }
        </style>
        <?php
    }
}
