/**
 * CookieNod Admin JavaScript
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Verify API Key
        $('#verify-api-key').on('click', function(e) {
            e.preventDefault();

            var $button = $(this);
            var $status = $('#api-key-status');
            var apiKey = $('#api_key').val();

            if (!apiKey) {
                $status.text(cookienodWp.strings.error).addClass('error').removeClass('success');
                return;
            }

            $button.prop('disabled', true).text(cookienodWp.strings.verifying);
            $status.text('').removeClass('success error');

            $.ajax({
                url: cookienodWp.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cookienod_verify_api_key',
                    nonce: cookienodWp.nonce,
                    api_key: apiKey
                },
                success: function(response) {
                    if (response.success) {
                        $status.text(cookienodWp.strings.verified).addClass('success');
                        // Reload to show updated site info
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        $status.text(cookienodWp.strings.invalid).addClass('error');
                    }
                },
                error: function() {
                    $status.text(cookienodWp.strings.error).addClass('error');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Verify Key');
                }
            });
        });

        // Start Cookie Scan
        $('#start-cookie-scan').on('click', function(e) {
            e.preventDefault();

            var $button = $(this);
            var $progressContainer = $('.cookienod-progress');
            var $progressBar = $('.cookienod-progress-bar');

            $button.prop('disabled', true).text('Scanning...');
            $progressContainer.show();

            // Collect cookies from current browser
            var cookies = [];
            if (document.cookie) {
                var cookieArray = document.cookie.split(';');
                for (var i = 0; i < cookieArray.length; i++) {
                    var cookie = cookieArray[i].trim();
                    var eqPos = cookie.indexOf('=');
                    var name = eqPos > -1 ? cookie.substr(0, eqPos) : cookie;
                    var value = eqPos > -1 ? cookie.substr(eqPos + 1) : '';
                    cookies.push({
                        name: name,
                        value: value.substring(0, 50),
                        type: 'javascript',
                        source: 'Browser'
                    });
                }
            }

            // Simulate progress
            var progress = 0;
            var interval = setInterval(function() {
                progress += Math.random() * 20;
                if (progress >= 90) {
                    progress = 90;
                    clearInterval(interval);

                    // Send cookies to server
                    $.ajax({
                        url: cookienodWp.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'cookienod_scan_cookies',
                            nonce: cookienodWp.nonce,
                            cookies: cookies
                        },
                        success: function(response) {
                            $progressBar.css('width', '100%');
                            if (response.success) {
                                setTimeout(function() {
                                    alert('Scan complete! Found ' + response.data.cookies_found + ' cookies.');
                                    location.reload();
                                }, 500);
                            } else {
                                alert('Scan failed: ' + (response.data || 'Unknown error'));
                                $button.prop('disabled', false).text('Start New Scan');
                                $progressContainer.hide();
                            }
                        },
                        error: function() {
                            alert('An error occurred during scan.');
                            $button.prop('disabled', false).text('Start New Scan');
                            $progressContainer.hide();
                        }
                    });
                }
                $progressBar.css('width', progress + '%');
            }, 200);
        });

        // Export Consent Log
        $('#export-consent-log').on('click', function(e) {
            e.preventDefault();

            var format = $('#export-format').val();
            var $button = $(this);

            $button.prop('disabled', true).text('Exporting...');

            $.ajax({
                url: cookienodWp.ajaxUrl,
                type: 'GET',
                data: {
                    action: 'cookienod_export_consent_log',
                    format: format,
                    nonce: cookienodWp.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        // Create download from CSV content
                        var blob = new Blob([response.data.content], { type: 'text/csv' });
                        var url = window.URL.createObjectURL(blob);
                        var a = document.createElement('a');
                        a.href = url;
                        a.download = response.data.filename;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        window.URL.revokeObjectURL(url);
                    } else {
                        alert('Export failed: ' + (response.data || 'Unknown error'));
                    }
                },
                error: function() {
                    alert('An error occurred during export.');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Export Log');
                }
            });
        });

        // Clear Consent Log
        $('#clear-consent-log').on('click', function(e) {
            e.preventDefault();

            if (!confirm('Are you sure you want to clear all consent logs? This cannot be undone.')) {
                return;
            }

            $.ajax({
                url: cookienodWp.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cookienod_clear_consent_log',
                    nonce: cookienodWp.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Consent log cleared successfully.');
                        location.reload();
                    } else {
                        alert('Failed to clear consent log.');
                    }
                },
                error: function() {
                    alert('An error occurred.');
                }
            });
        });

        // Tab switching for scan page
        $('.nav-tab').on('click', function(e) {
            e.preventDefault();

            var target = $(this).attr('href');

            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');

            $('.cookienod-tab-content').hide();
            $(target).show();
        });

        // Initialize tabs
        if ($('.nav-tab-active').length === 0) {
            $('.nav-tab:first').addClass('nav-tab-active');
            $('.cookienod-tab-content:first').show();
        } else {
            $('.cookienod-tab-content').hide();
            $($('.nav-tab-active').attr('href')).show();
        }

        // Category filter for cookies
        $('#cookie-category-filter').on('change', function() {
            var category = $(this).val();

            if (category === 'all') {
                $('#cookie-scan-table tbody tr').show();
            } else {
                $('#cookie-scan-table tbody tr').hide();
                $('#cookie-scan-table tbody tr[data-category="' + category + '"]').show();
            }
        });

        // Search cookies
        $('#cookie-search').on('input', function() {
            var search = $(this).val().toLowerCase();

            $('#cookie-scan-table tbody tr').each(function() {
                var text = $(this).text().toLowerCase();
                if (text.indexOf(search) >= 0) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });

        // Update preview on settings change
        $('#banner_position, #banner_theme').on('change', function() {
            updateBannerPreview();
        });

        function updateBannerPreview() {
            var position = $('#banner_position').val();
            var theme = $('#banner_theme').val();

            $('#banner-preview').attr('class', 'cookienod-preview-banner position-' + position + ' theme-' + theme);
        }

        // Initialize preview
        updateBannerPreview();

        // A/B Testing - Create Test Form
        $('#create-test-form').on('submit', function(e) {
            e.preventDefault();

            var $button = $(this).find('button[type="submit"]');
            var $form = $(this);

            $button.prop('disabled', true).text('Creating...');

            // Collect variant data
            var variants = [];
            $('#test-variants .test-variant').each(function() {
                variants.push({
                    id: $(this).data('id'),
                    name: $(this).find('.variant-name').val(),
                    position: $(this).find('.variant-position').val(),
                    primary_color: $(this).find('.variant-primary-color').val()
                });
            });

            // Get traffic split
            var splitValue = $('.split-slider').val();
            var trafficSplit = splitValue + ',' + (100 - splitValue);

            $.ajax({
                url: cookienodWp.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cookienod_create_test',
                    nonce: cookienodWp.nonce,
                    name: $('#test-name').val(),
                    variants: JSON.stringify(variants),
                    traffic_split: trafficSplit
                },
                success: function(response) {
                    if (response.success) {
                        alert('Test created successfully!');
                        location.reload();
                    } else {
                        alert(response.data || 'Failed to create test');
                        $button.prop('disabled', false).text('Create Test');
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                    $button.prop('disabled', false).text('Create Test');
                }
            });
        });

        // A/B Testing - Add Variant
        $('#add-variant').on('click', function() {
            var variantCount = $('#test-variants .test-variant').length + 1;
            var $newVariant = $('<div class="test-variant" data-id="' + variantCount + '">' +
                '<h4>Variant ' + String.fromCharCode(64 + variantCount) + '</h4>' +
                '<label>Name</label>' +
                '<input type="text" class="variant-name" value="Variant ' + variantCount + '" />' +
                '<label>Banner Position</label>' +
                '<select class="variant-position">' +
                    '<option value="bottom">Bottom</option>' +
                    '<option value="top">Top</option>' +
                    '<option value="center" selected>Center</option>' +
                '</select>' +
                '<label>Accept Button Color</label>' +
                '<input type="color" class="variant-primary-color" value="#' + Math.floor(Math.random()*16777215).toString(16) + '" />' +
            '</div>');

            $('#test-variants').append($newVariant);

            // Update traffic split display
            updateTrafficSplit();
        });

        // A/B Testing - Traffic Split Slider
        $('.split-slider').on('input', function() {
            updateTrafficSplit();
        });

        function updateTrafficSplit() {
            var value = $('.split-slider').val();
            $('.split-display').text(value + '% / ' + (100 - value) + '%');
        }

        // A/B Testing - Set Winner
        $('.set-winner').on('click', function() {
            var variantId = $(this).data('variant');
            var testId = $(this).closest('.cookienod-ab-variant').parent().data('test-id');

            if (!testId) {
                // Try to get from the active test container
                testId = $('#active-tests').data('test-id');
            }

            if (!confirm('Are you sure you want to set this variant as the winner? This will stop the test.')) {
                return;
            }

            var $button = $(this);
            $button.prop('disabled', true).text('Setting...');

            $.ajax({
                url: cookienodWp.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cookienod_set_winner',
                    nonce: cookienodWp.nonce,
                    test_id: testId,
                    winner_id: variantId
                },
                success: function(response) {
                    if (response.success) {
                        alert('Winner set successfully!');
                        location.reload();
                    } else {
                        alert(response.data || 'Failed to set winner');
                        $button.prop('disabled', false).text('Set as Winner');
                    }
                },
                error: function() {
                    alert('An error occurred.');
                    $button.prop('disabled', false).text('Set as Winner');
                }
            });
        });

        // A/B Testing - Start Test
        $(document).on('click', '.start-test-btn', function() {
            if (!confirm('Are you sure you want to start this test? It will become active immediately.')) {
                return;
            }

            var testId = $(this).data('test');
            var $button = $(this);
            $button.prop('disabled', true).text('Starting...');

            $.ajax({
                url: cookienodWp.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cookienod_update_test',
                    nonce: cookienodWp.nonce,
                    test_id: testId,
                    status: 'active'
                },
                success: function(response) {
                    if (response.success) {
                        alert('Test started successfully!');
                        location.reload();
                    } else {
                        alert(response.data || 'Failed to start test');
                        $button.prop('disabled', false).text('Start Test');
                    }
                },
                error: function() {
                    alert('An error occurred.');
                    $button.prop('disabled', false).text('Start Test');
                }
            });
        });

        // A/B Testing - Stop Test (from All Tests tab)
        $(document).on('click', '.stop-test-btn', function() {
            if (!confirm('Are you sure you want to stop this test?')) {
                return;
            }

            var testId = $(this).data('test');
            var $button = $(this);
            $button.prop('disabled', true).text('Stopping...');

            $.ajax({
                url: cookienodWp.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cookienod_update_test',
                    nonce: cookienodWp.nonce,
                    test_id: testId,
                    status: 'completed'
                },
                success: function(response) {
                    if (response.success) {
                        alert('Test stopped successfully!');
                        location.reload();
                    } else {
                        alert(response.data || 'Failed to stop test');
                        $button.prop('disabled', false).text('Stop');
                    }
                },
                error: function() {
                    alert('An error occurred.');
                    $button.prop('disabled', false).text('Stop');
                }
            });
        });

        // A/B Testing - Stop Test (Active Test tab)
        $('#stop-test').on('click', function() {
            if (!confirm('Are you sure you want to stop this test?')) {
                return;
            }

            var testId = $('#active-tests').data('test-id');
            if (!testId) {
                alert('Test ID not found');
                return;
            }

            var $button = $(this);
            $button.prop('disabled', true).text('Stopping...');

            $.ajax({
                url: cookienodWp.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cookienod_update_test',
                    nonce: cookienodWp.nonce,
                    test_id: testId,
                    status: 'completed'
                },
                success: function(response) {
                    if (response.success) {
                        alert('Test stopped successfully!');
                        location.reload();
                    } else {
                        alert(response.data || 'Failed to stop test');
                        $button.prop('disabled', false).text('Stop Test');
                    }
                },
                error: function() {
                    alert('An error occurred.');
                    $button.prop('disabled', false).text('Stop Test');
                }
            });
        });

        // Custom CSS - Load Theme
        $('#load-theme').on('click', function() {
            var theme = $('#theme-selector').val();
            if (!theme) {
                alert('Please select a theme first');
                return;
            }

            var $button = $(this);
            $button.prop('disabled', true).text('Loading...');

            $.ajax({
                url: cookienodWp.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cookienod_load_theme',
                    nonce: cookienodWp.nonce,
                    theme: theme
                },
                success: function(response) {
                    if (response.success) {
                        $('#custom-css-editor').val(response.data.css);
                        updateCssPreview();
                    } else {
                        alert(response.data || 'Failed to load theme');
                    }
                },
                error: function() {
                    alert('An error occurred');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Load Theme');
                }
            });
        });

        // Custom CSS - Preview
        $('#preview-css').on('click', function() {
            updateCssPreview();
        });

        function updateCssPreview() {
            var css = $('#custom-css-editor').val();
            var position = $('#banner_position').val() || 'bottom';
            var baseTheme = $('.cookienod-custom-css').data('banner-theme') || $('#banner_theme').val() || 'light';
            var bannerBaseCss = baseTheme === 'dark'
                ? '#cs-consent-banner{left:0;right:0;background:#1f2937;border-top:1px solid #374151;padding:20px;box-shadow:0 -2px 10px rgba(0,0,0,0.25);color:#f9fafb;}#cs-consent-banner .cs-btn{padding:8px 16px;border:1px solid #4b5563;background:#111827;color:#f9fafb;cursor:pointer;border-radius:4px;}#cs-consent-banner .cs-btn-primary{background:#2563eb;color:#fff;border-color:#2563eb;}#cs-consent-banner .cs-btn-secondary{background:transparent;color:#f9fafb;border-color:#9ca3af;}#cs-consent-banner .cs-btn-tertiary{background:#374151;color:#f9fafb;border-color:#4b5563;}'
                : '#cs-consent-banner{left:0;right:0;background:#fff;border-top:1px solid #ddd;padding:20px;box-shadow:0 -2px 10px rgba(0,0,0,0.1);color:#1d2327;}#cs-consent-banner .cs-btn{padding:8px 16px;border:1px solid #ccc;background:#f6f7f7;color:#1d2327;cursor:pointer;border-radius:4px;}#cs-consent-banner .cs-btn-primary{background:#2271b1;color:#fff;border-color:#2271b1;}#cs-consent-banner .cs-btn-secondary{background:transparent;}#cs-consent-banner .cs-btn-tertiary{background:#f0f0f1;}';

            // Build preview HTML with user's CSS
            var previewHtml = '<!DOCTYPE html><html><head><meta charset="UTF-8">' +
                '<style>' +
                'body{margin:0;padding:20px;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;background:#f0f0f0;min-height:100vh;}' +
                '.preview-container{background:#fff;min-height:500px;position:relative;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,0.1);overflow:hidden;}' +
                '.preview-content{padding:40px;}' +
                bannerBaseCss +
                '#cs-consent-banner.position-top{position:absolute;top:0;}' +
                '#cs-consent-banner.position-bottom{position:absolute;bottom:0;}' +
                '#cs-consent-banner .cs-banner-title{margin:0 0 8px;font-size:20px;color:inherit;}' +
                '#cs-consent-banner .cs-banner-description{margin:0;color:inherit;}' +
                '#cs-consent-banner .cs-banner-actions{margin-top:15px;display:flex;gap:10px;flex-wrap:wrap;}' +
                css +
                '</style></head><body>' +
                '<div class="preview-container">' +
                '<div class="preview-content"><h1>Your Website</h1><p>This is a preview of how the consent banner will appear.</p></div>' +
                '<div id="cs-consent-banner" class="position-' + position + '">' +
                '<div class="cs-banner-content">' +
                '<h3 class="cs-banner-title">Cookie Preferences</h3>' +
                '<p class="cs-banner-description">We use cookies to enhance your experience.</p>' +
                '<div class="cs-banner-actions">' +
                '<button class="cs-btn cs-btn-secondary">Reject</button>' +
                '<button class="cs-btn cs-btn-tertiary">Customize</button>' +
                '<button class="cs-btn cs-btn-primary">Accept All</button>' +
                '</div></div></div></div></body></html>';

            var blob = new Blob([previewHtml], { type: 'text/html' });
            var url = URL.createObjectURL(blob);

            var $frame = $('#css-preview-frame');
            $frame.attr('src', url);

            // Clean up previous blob URL after load
            $frame.off('load').on('load', function() {
                if ($frame.data('old-src')) {
                    URL.revokeObjectURL($frame.data('old-src'));
                }
                $frame.data('old-src', url);
            });
        }

        // Custom CSS - Validate
        $('#validate-css').on('click', function() {
            var css = $('#custom-css-editor').val();
            var $button = $(this);
            var $status = $('#css-validation-status');

            $button.prop('disabled', true).text('Validating...');
            $status.removeClass('success error').hide();

            $.ajax({
                url: cookienodWp.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cookienod_validate_css',
                    nonce: cookienodWp.nonce,
                    css: css
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data.rules_removed > 0) {
                            $status.addClass('error').text('Warning: ' + response.data.rules_removed + ' unsafe CSS rules were removed').show();
                        } else {
                            $status.addClass('success').text('CSS is valid!').show();
                        }
                    } else {
                        $status.addClass('error').text(response.data || 'Validation failed').show();
                    }
                },
                error: function() {
                    $status.addClass('error').text('Validation error').show();
                },
                complete: function() {
                    $button.prop('disabled', false).text('Validate CSS');
                }
            });
        });

        // Custom CSS - Reset
        $('#reset-css').on('click', function() {
            if (!confirm('Are you sure you want to reset the CSS to default?')) {
                return;
            }
            $('#custom-css-editor').val('');
            updateCssPreview();
        });

        // Initialize CSS preview on page load
        if ($('#css-preview-frame').length) {
            updateCssPreview();
        }

        // Policy Generator - Template selection
        $('.policy-template').on('click', function() {
            var $card = $(this);
            var $radio = $card.find('input[type="radio"]');

            $('.policy-template').removeClass('selected');
            $card.addClass('selected');
            $radio.prop('checked', true).trigger('change');
        });

        $('input[name="policy_template"]').on('change', function() {
            $('.policy-template').removeClass('selected');
            $(this).closest('.policy-template').addClass('selected');
        });

        $('input[name="policy_template"]:checked').closest('.policy-template').addClass('selected');

        // Policy Generator - Generate preview
        $('#generate-policy').on('click', function() {
            var template = $('input[name="policy_template"]:checked').val() || 'combined';
            var $button = $(this);
            var originalText = $button.text();

            $button.prop('disabled', true).text('Generating...');

            $.ajax({
                url: cookienodWp.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cookienod_generate_policy',
                    nonce: cookienodWp.nonce,
                    template: template
                },
                success: function(response) {
                    if (response.success && response.data && response.data.content) {
                        $('#policy-preview').html(response.data.content);
                        $('#policy-preview-container').show();
                    } else {
                        alert(response.data || 'Failed to generate policy');
                    }
                },
                error: function() {
                    alert('An error occurred while generating the policy.');
                },
                complete: function() {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        });

        // Policy Generator - Create policy page
        $('#create-policy-page').on('click', function() {
            var template = $('input[name="policy_template"]:checked').val() || 'combined';
            var $button = $(this);
            var originalText = $button.text();

            $button.prop('disabled', true).text('Creating...');

            $.ajax({
                url: cookienodWp.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cookienod_create_policy_page',
                    nonce: cookienodWp.nonce,
                    template: template
                },
                success: function(response) {
                    if (response.success) {
                        alert('Policy page created successfully.');
                        location.reload();
                    } else {
                        alert(response.data || 'Failed to create policy page');
                    }
                },
                error: function() {
                    alert('An error occurred while creating the policy page.');
                },
                complete: function() {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        });

        // Policy Generator - Update policy page
        $('#update-policy-page').on('click', function() {
            var content = $('#policy-preview').html();
            var pageId = $(this).data('page-id');
            var $button = $(this);
            var originalText = $button.text();

            if (!content) {
                alert('Generate a policy preview first.');
                return;
            }

            $button.prop('disabled', true).text('Updating...');

            $.ajax({
                url: cookienodWp.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cookienod_update_policy',
                    nonce: cookienodWp.nonce,
                    page_id: pageId,
                    content: content
                },
                success: function(response) {
                    if (response.success) {
                        alert('Policy page updated successfully.');
                        location.reload();
                    } else {
                        alert(response.data || 'Failed to update policy page');
                    }
                },
                error: function() {
                    alert('An error occurred while updating the policy page.');
                },
                complete: function() {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        });
    });

})(jQuery);