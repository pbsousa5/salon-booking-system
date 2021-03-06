<?php

class SLN_Admin_Settings {

    const PAGE = 'salon-settings';

    protected $plugin;
    protected $settings;
    public $settings_page = '';
    private $tabs = array(
        'homepage' => 'Home',
        'general' => 'General',
        'booking' => 'Booking Rules',
        'payments' => 'Payments',
        'gcalendar' => 'Google Calendar',
        'documentation' => 'Documentation'
    );

    public function __construct(SLN_Plugin $plugin) {
        $this->plugin = $plugin;
        $this->settings = $plugin->getSettings();
        add_action('admin_menu', array($this, 'admin_menu'));
    }

    public function admin_menu() {
        add_menu_page(
                __('Salon', 'sln'), __('Salon', 'sln'), 'manage_options', 'salon', array($this, 'settings_page'), SLN_PLUGIN_URL . '/img/admin_icon.png', 100
        );
        $this->settings_page = add_submenu_page(
                'salon', __('Salon Settings', 'sln'), __('Settings', 'sln'), apply_filters('salonviews/settings/capability', 'manage_options'), self::PAGE, array($this, 'show')
        );
    }

    function row_input_checkbox($key, $label, $settings = array()) {
        ?>
        <div class="form-group">
            <label for="salon_settings_<?php echo $key ?>"><?php echo $label ?></label></th>
        <?php
        SLN_Form::fieldCheckbox(
                "salon_settings[{$key}]", $this->getOpt($key), $settings
        )
        ?>
        <?php if (isset($settings['help'])) { ?><p class="help-block"><?php echo $settings['help'] ?></p><?php } ?>
        </div>
        <?php
    }

    function getOpt($key) {
        return $this->settings->get($key);
    }

    function row_input_text($key, $label, $settings = array()) {
        ?>
        <div class="form-group">
            <label for="salon_settings_<?php echo $key ?>"><?php echo $label ?></label></th>
        <?php echo SLN_Form::fieldText("salon_settings[$key]", $this->getOpt($key)) ?>
        <?php if (isset($settings['help'])) { ?><p class="help-block"><?php echo $settings['help'] ?></p><?php } ?>
        </div>
        <?php
        }
        
        function row_checkbox_text($key, $label, $settings = array()) {
        ?>
        <div class="form-group">
            <label for="salon_settings_<?php echo $key ?>"><?php echo $label ?></label></th>
        <?php echo SLN_Form::fieldCheckbox("salon_settings[$key]", $this->getOpt($key)) ?>
            <?php if (isset($settings['help'])) { ?><p class="help-block"><?php echo $settings['help'] ?></p><?php } ?>
        </div>
        <?php
    }

        function row_input_textarea($key, $label, $settings = array()) {
            if (!isset($settings['textarea'])) {
                $settings['textarea'] = array();
            }
            ?>
        <div class="form-group">
            <label for="salon_settings_<?php echo $key ?>"><?php echo $label ?></label></th>
        <?php SLN_Form::fieldTextarea("salon_settings[$key]", $this->getOpt($key), $settings['textarea']); ?>
        <?php if (isset($settings['help'])) { ?><p class="help-block"><?php echo $settings['help'] ?></p><?php } ?>
        </div>
        <?php
    }

    function row_input_page($key, $label, $settings = array()) {
        ?>
        <div class="form-group">
            <label for="<?php echo $key ?>"><?php echo $label ?></label>
        <?php
        wp_dropdown_pages(
                array(
                    'name' => 'salon_settings[' . $key . ']',
                    'selected' => $this->getOpt($key) ? $this->getOpt($key) : null,
                    'show_option_none' => 'Nessuna'
                )
        )
        ?>
        </div>
        <?php
        }

         /**
     * select_text
     * @param type $list
     * @param type $value
     * @param type $settings
     */
    function select_text($key, $label, $list, $settings = array()) {
        ?>
        <div class="form-group">
            <label for="salon_settings_<?php echo $key ?>"><?php echo $label ?></label></th>
        <select name="salon_settings[<?php echo $key ?>]">
            <?php
            foreach ($list as $k => $value) {
                $lbl = $value['label'];
                $sel = ($value['id'] == $this->getOpt($key)) ? "selected" : "";
                echo "<option value='$k' $sel>$lbl</option>";
            }
            ?>
        </select>
        </div>
        <?php
    }
    
        public function showTab($tab) {
            include $this->plugin->getViewFile('settings/tab_' . $tab);
        }

        public function showTabHomepage() {
            include SLN_PLUGIN_BASENAME . '/views/settings/homepage.php';
        }

        public function processTabHomepage() {
            if ($_POST['reset-settings'] == 'reset') {
                $this->settings->clear();
                SLN_Action_Install::execute(true);
                $this->showAlert(
                        'success', __('remember to customize your settings', 'sln'), __('Reset completed with success', 'sln')
                );
            }
        }

        public function showTabGeneral() {
            include SLN_PLUGIN_URL . '/views/settings/general.php';
        }

        public function processTabGeneral() {
            foreach (array(
        'gen_name',
        'gen_email',
        'gen_phone',
        'gen_address',
        'gen_timetable',
        'ajax_enabled',
        'attendant_enabled',
        'hide_prices',
        'attendant_email',
        'sms_enabled',
        'sms_account',
        'sms_password',
        'sms_prefix',
        'sms_provider',
        'sms_from',
        'sms_new',
        'sms_new_number',
        'sms_new_attendant',
        'sms_remind',
        'sms_remind_interval',
        'soc_facebook',
        'soc_twitter',
        'soc_google',
        'date_format',
        'time_format',
        'no_bootstrap'
            ) as $k) {
                $val = isset($_POST['salon_settings'][$k]) ? $_POST['salon_settings'][$k] : '';
                $this->settings->set($k, stripcslashes($val));
            }
            if (isset($_POST['salon_settings']['hide_prices'])) {
                $this->settings->set('pay_enabled', '');
            }
            wp_clear_scheduled_hook('sln_sms_reminder');
            if ($_POST['salon_settings']['sms_remind']) {
                wp_schedule_event(time(), 'hourly', 'sln_sms_reminder');
            }
            $this->settings->save();
            $this->showAlert(
                    'success', __('general settings are updated', 'sln'), __('Update completed with success', 'sln')
            );
            if ($_POST['salon_settings']['sms_test_number'] && $_POST['salon_settings']['sms_test_message']) {
                SLN_Enum_SmsProvider::getService(
                        $this->settings->get('sms_provider'), $this->plugin
                )->send($_POST['salon_settings']['sms_test_number'], $_POST['salon_settings']['sms_test_message']);
                $this->showAlert(
                        'success', __('Test sms sent with success', 'sln'), ''
                );
            }
        }

        public function showTabBooking() {
            include SLN_PLUGIN_URL . '/views/settings/booking.php';
        }

        public function processTabBooking() {
            $tmp = array();
            foreach ($_POST['salon_settings']['availabilities'] as $row) {
                $tmp[] = $row;
            }
            $_POST['salon_settings']['availabilities'] = $tmp;
            foreach (array(
        'confirmation',
        'thankyou',
        'availabilities',
        'availability_mode',
        'disabled',
        'disabled_message',
        'confirmation',
        'parallels_day',
        'parallels_hour',
        'hours_before_from',
        'hours_before_to',
        'interval'
            ) as $k) {
                $this->settings->set($k, isset($_POST['salon_settings'][$k]) ? $_POST['salon_settings'][$k] : '');
            }
            $this->settings->save();
            $this->showAlert(
                    'success', __('booking settings are updated', 'sln'), __('Update completed with success', 'sln')
            );
        }

        public function showTabPayments() {
            include SLN_PLUGIN_URL . '/views/settings/payments.php';
        }

        public function processTabPayments() {
            foreach (array(
        'pay_currency',
        'pay_currency_pos',
        'pay_paypal_email',
        'pay_paypal_test',
        'pay_cash',
        'pay_enabled',
        'pay_deposit'
            ) as $k) {
                $data = isset($_POST['salon_settings'][$k]) ? $_POST['salon_settings'][$k] : '';
                $this->settings->set($k, $data);
            }
            $this->settings->save();
            $this->showAlert(
                    'success', __('payments settings are updated', 'sln'), __('Update completed with success', 'sln')
            );
        }

        public function show() {
            $current = $this->getCurrentTab();
            if ($_POST) {
                $method = "processTab" . ucwords($current);
                if (!method_exists($this, $method)) {
                    throw new Exception('method not found ' . $method);
                }
                if (empty($_POST[self::PAGE . $current]) || !wp_verify_nonce($_POST[self::PAGE . $current])) {
                    $this->$method();
                } else {
                    $this->showAlert('error', __('try again', 'sln'), __('Page verification failed', 'sln'));
                }
            }
            ?>
        <div class="wrap sln-bootstrap">
        <?php screen_icon(); ?>
            <h2><?php _e('Salon Settings', 'sln'); ?></h2>

        <?php settings_errors(); ?>
        <?php $this->showTabsBar(); ?>
            <form method="post" action="<?php admin_url('admin.php?page=' . self::PAGE); ?>">
        <?php
        $this->showTab($current);
        wp_nonce_field(self::PAGE . $current);
        if ($current != 'homepage') {
            submit_button(esc_attr__('Update Settings', 'sln'), 'primary');
        }
        ?>
            </form>

        </div><!-- wrap -->
        <?php
    }

    private function showTabsBar() {
        echo '<h2 class="nav-tab-wrapper"><img src="' . SLN_PLUGIN_URL . '/img/settings_icon.png' . '"/>';
        $page = self::PAGE;
        $current = $this->getCurrentTab();
        foreach ($this->tabs as $tab => $name) {
            $class = ($tab == $current) ? ' nav-tab-active' : '';
            echo "<a class='nav-tab$class' href='?page=$page&tab=$tab'>$name</a>";
        }
        echo '</h2>';
    }

    private function showAlert($type, $txt, $title = null) {
        ?>
        <div id="sln-setting-<?php echo $type ?>" class="updated settings-<?php echo $type ?>">
        <?php if (!empty($title)) { ?>
                <p><strong><?php echo $title ?></strong></p>
        <?php } ?>
            <p><?php echo $txt ?></p>
        </div> 
        <?php
    }

    public function showTabGcalendar() {
        
    }

    public function processTabGcalendar() {
        $gcalendar_array = array(
            'google_calendar_enabled',
            'google_outh2_client_id',
            'google_outh2_client_secret',
            'google_outh2_redirect_uri',
            'google_client_calendar'
        );

        foreach ($gcalendar_array as $k) {
            $old_value[$k] = $this->settings->get($k);
            $data = isset($_POST['salon_settings'][$k]) ? trim($_POST['salon_settings'][$k]) : '';
            $this->settings->set($k, $data);
        }
        $this->settings->save();
        $params = array();
        foreach ($gcalendar_array as $k) {
            $v = $this->settings->get($k);
            $k = str_replace('google_', '', $k);
            $params[$k] = $v;
        }

        if ($old_value['google_calendar_enabled'] != $this->settings->get('google_calendar_enabled') ||
                $old_value['google_outh2_client_id'] != $this->settings->get('google_outh2_client_id') ||
                $old_value['google_outh2_client_secret'] != $this->settings->get('google_outh2_client_secret')
        )
            header("Location: " . admin_url('admin.php?page=salon-settings&tab=gcalendar&revoketoken=1'));

        if (isset($_GET['revoketoken']) && $_GET['revoketoken'] == 1)
            header("Location: " . admin_url('admin.php?page=salon-settings&tab=gcalendar'));

        $this->showAlert(
                'success', __('Google Calendar settings are updated', 'sln'), __('Update completed with success', 'sln')
        );
    }

    function getCurrentTab() {
        return isset($_GET['tab']) ? $_GET['tab'] : 'homepage';
    }

    function hidePriceSettings() {
        return $this->getOpt('hide_prices') == 1 ? array('attrs' => array('disabled' => 'disabled', 'title' => 'Please disable hide prices from general settings to enable online payment.')) : array();
    }

}
