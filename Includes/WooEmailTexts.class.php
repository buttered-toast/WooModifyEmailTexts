<?php

namespace WooModifyEmailTexts\Includes;

if (!defined('ABSPATH')) {
    die("Whoa! What are you doing here?<br>You like breaking the rules, don't you?");
}

if (!class_exists('\WooModifyEmailTexts\Includes\WooEmailTexts')) {
    /**
     * Handles the creation and modification of
     * localization texts in WooCommerce email
     * templates
     */
    class WooEmailTexts
    {
        /**
         * All the WooCommerce email classes
         *
         * @var array
         */
        protected $emailClasses = [];

        /**
         * The WooCommerce domain
         *
         * @var string
         */
        protected $wooDomain = 'woocommerce';

        /**
         * Initialize The two methods responsible
         * for creating and modifying the localization
         * texts
         *
         * @return void
         */
        public function init()
        {
            $this->prepareFields();
            $this->modifyEmails();
        }

        /**
         * Add the methods for setting email classes
         * and modifying the email texts callbacks into
         * the init action hook
         *
         * @return void
         */
        protected function modifyEmails()
        {
            add_action('init', [$this, 'setEmailClasses']);
            add_action('init', [$this, 'modifyEmailTexts'], 20);
        }

        /**
         * Add the method for creating the email
         * text fields that the user can use to modify the
         * email texts callback into the woocommerce_email_classes
         * filter hook
         *
         * @return void
         */
        protected function prepareFields()
        {
            add_filter('woocommerce_email_classes', [$this, 'createEmailTextsFields']);
        }

        /**
         * Set the WooCommerce email classes
         *
         * @return void
         */
        public function setEmailClasses()
        {
            // Checks that WooCommerce is active, if not, return
            if (!class_exists('woocommerce')) {
                return;
            }

            $this->emailClasses = wc()->mailer()->emails;
        }

        /**
         * Modify the email template fields based on the
         * settings the client make, if any
         *
         * @return void
         */
        public function modifyEmailTexts()
        {
            // Checks that WooCommerce is active, if not, return
            if (!class_exists('woocommerce')) {
                return;
            }

            add_filter('gettext', function ($translation, $text, $domain) {
                /**
                 * If in admin panel, excluding the email preview, return $translation
                 */
                if (
                    is_admin()
                    && (isset($_GET['page']) && $_GET['page'] !== 'codemanas-woocommerce-preview-emails')
                ) {
                    return $translation;
                }

                // return $translation if current domain is not WooCommerce
                if ($this->wooDomain !== $domain) {
                    return $translation;
                }

                foreach ($this->emailClasses as $emailClass) {
                    // Check that email template settings exist
                    if (empty($emailClassSettings = get_option("woocommerce_{$emailClass->id}_settings"))) {
                        continue;
                    }

                    // Check that email template text count exists
                    if (empty($emailClassSettings['emailTemplateTextsCount'])) {
                        continue;
                    }

                    $emailTemplateTextsCount = intval($emailClassSettings['emailTemplateTextsCount']);

                    /**
                     * Loop over all modified template texts and change the
                     * default value, if available
                     */
                    for ($i = 0; $i < $emailTemplateTextsCount; $i++) {
                        if (empty($emailClassSettings["emailTemplateModifiedText{$i}"])) {
                            continue;
                        }

                        if ($text === $emailClassSettings["emailTemplateOriginalText{$i}"]) {
                            return $emailClassSettings["emailTemplateModifiedText{$i}"];
                        }
                    }
                }

                return $translation;
            }, 3, 10);
        }

        /**
         * Create the email template text fields based on the
         * current found hardcoded text fields.
         * esc_html__, esc_html_e, __, etc.
         *
         * @param array $email_class_list
         * @return void
         */
        public function createEmailTextsFields($email_class_list)
        {
            // Checks that WooCommerce is active, if not, return
            if (!class_exists('woocommerce')) {
                return $email_class_list;
            }

            foreach ($email_class_list as $email_class) {
                // Email template name
                $template = "template_{$email_class->settings['email_type']}";

                // Get the email template
                $emailTemplate = file_get_contents("{$email_class->template_base}{$email_class->{$template}}");

                // Find all instances of localization functions
                preg_match_all('/(?:esc_html)?_[_e]\( ?\'(.*?)\', ?\'woocommerce\' ?\)/m', $emailTemplate, $matches, PREG_SET_ORDER);

                // Set the template text fields for each email class
                add_action("woocommerce_settings_api_form_fields_{$email_class->id}", function ($form_fields) use ($matches) {
                    /**
                     * If no localization text were found, return the default
                     * form fields
                     */
                    if (empty($matches)) {
                        return $form_fields;
                    }

                    // Get the total localication matches found
                    $totalMatches = count($matches);

                    // Set a hidden field for total localication matches
                    $form_fields['emailTemplateTextsCount'] = [
                        'title'       => esc_html__('Template hardcoded texts', 'bt-mwet'),
                        'description' => sprintf(esc_html__('Below you\'ll find all the hardcoded texts you can modify for this email. The total texts you can modify are %d', 'bt-mwet'), $totalMatches),
                        'type'        => 'hidden',
                        'default'     => $totalMatches,
                    ];

                    /**
                     * Create two new fields.
                     * One for the original localization text, and
                     * one for the new, modified text
                     */
                    foreach ($matches as $key => $match) {
                        $match = esc_html__($match[1], $this->wooDomain);

                        $keyNum = $key + 1;

                        $form_fields["emailTemplateOriginalText{$key}"] = [
                            'title'       => '',
                            'description' => '',
                            'type'        => 'hidden',
                            'default'     => esc_attr($match),
                        ];

                        $form_fields["emailTemplateModifiedText{$key}"] = [
                            'title'       => sprintf(esc_html__('Template text (%d)', 'bt-mwet'), $keyNum),
                            'description' => sprintf(esc_html__('Initial value: %s', 'bt-mwet'), $match),
                            'type'        => 'textarea',
                            'css'         => 'width:400px; height: 75px;',
                            'default'     => esc_attr($match),
                            'placeholder' => esc_attr($match),
                        ];
                    }

                    return $form_fields;
                });
            }

            return $email_class_list;
        }
    }
}
