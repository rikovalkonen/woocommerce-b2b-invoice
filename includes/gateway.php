<?php
add_filter('woocommerce_payment_gateways', 'add_b2b_invoice_gateway');
function add_b2b_invoice_gateway($gateways)
{
    $gateways[] = 'WC_Gateway_B2B_Invoice';
    return $gateways;
}

add_action('woocommerce_loaded', 'init_b2b_invoice_gateway');
function init_b2b_invoice_gateway()
{
    class WC_Gateway_B2B_Invoice extends WC_Payment_Gateway
    {
        public function __construct()
        {
            $this->init_form_fields();
            $this->init_settings();

            $this->id = 'b2b_invoice';
            $this->method_title = 'Pay by Invoice';
            $this->method_description = 'Allow selected users to pay via invoice.';
            $this->has_fields = true;
            $this->enabled = $this->get_option('enabled');
            $this->title = $this->get_option('title');

            if (is_user_logged_in()) {
                $user_id = get_current_user_id();
                $allowed = get_user_meta($user_id, 'wc_b2b_ic_can_invoice_order', true) === 'yes';
                $disabled = get_user_meta($user_id, 'wc_b2b_ic_disable_invoice_order', true) === 'yes';

                // if (!is_admin()) {
                //     if (!$allowed || $disabled) {
                //$this->enabled = 'yes';
                //         $this->title .= ' (Unavailable)';
                //     }
                // }
            }

            add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        }

        public function is_available()
        {
            if (!is_user_logged_in()) {
                return false;
            }

            return parent::is_available();
        }

        function init_form_fields()
        {
            $this->form_fields = array(
                'enabled'     => array(
                    'title'       => __('Enable/Disable', 'woocommerce'),
                    'label'       => __('Enable Invoice Payment', 'woocommerce'),
                    'type'        => 'checkbox',
                    'default'     => 'yes'
                ),
                'title'       => array(
                    'title'       => __('Title', 'woocommerce'),
                    'type'        => 'text',
                    'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
                    'default'     => __('Lasku yritykselle', 'woocommerce')
                ),
                'description' => array(
                    'title'       => __('Description', 'woocommerce'),
                    'type'        => 'textarea',
                    'description' => __('This controls the description which the user sees during checkout.', 'woocommerce'),
                    'default'     => __("Maksa kätevästi verkkolaskulla", 'woocommerce')
                )
            );
        }

        public function process_payment($order_id)
        {
            $order = wc_get_order($order_id);
            $status = get_option('b2b_invoice_order_status', 'on-hold');
            $order->update_status($status, 'Invoice payment method selected');
            wc_reduce_stock_levels($order_id);
            WC()->cart->empty_cart();
            return [
                'result' => 'success',
                'redirect' => $this->get_return_url($order)
            ];
        }

        public function validate_fields()
        {
            $user_id = get_current_user_id();
            $allowed = get_user_meta($user_id, 'wc_b2b_ic_can_invoice_order', true) === 'yes';
            $disabled = get_user_meta($user_id, 'wc_b2b_ic_disable_invoice_order', true) === 'yes';

            if (!$allowed) {
                wc_add_notice(__('Et voi tällä hetkellä käyttää laskujen maksamista. Ota yhteyttä asiakaspalveluun.'), 'error');
                return false;
            }

            if ($disabled) {
                wc_add_notice(__('Laskulla maksu on tilapäisesti poistettu käytöstä tililläsi. Ota yhteyttä asiakaspalveluun.'), 'error');
                return false;
            }

            return true;
        }

        public function payment_fields()
        {
            if (!is_user_logged_in()) return;

            $user_id = get_current_user_id();
            $allowed = get_user_meta($user_id, 'wc_b2b_ic_can_invoice_order', true) === 'yes';
            $disabled = get_user_meta($user_id, 'wc_b2b_ic_disable_invoice_order', true) === 'yes';

            if ($allowed && $disabled) {
                echo '<div class="woocommerce-info">' . esc_attr__('Laskulla maksu on tilapäisesti poistettu käytöstä tililläsi. Ota yhteyttä asiakaspalveluun.', 'text-domain') . '</div>';
                return;
            }

            $companies = get_user_meta($user_id, 'wc_b2b_ic_invoice_companies', true);
            if (empty($companies)) {
                echo '<div class="woocommerce-info">' . esc_attr__('Tililtäsi ei löytynyt laskutusyrityksiä, ota yhteyttä asiakaspalveluun.', 'text-domain') . '</div>';
                return;
            }
            echo '<input type="text" name="invoice_customer_reference" id="customer_reference" class="input-text" style="width:100%;" placeholder="' . esc_attr__('Viite laskulle', 'text-domain') . '" /></p>';
            echo '<p><strong>' . esc_attr__('Valitse yritys', 'text-domain') . ':</strong></p>';
            echo '<select name="selected_invoice_company" id="selected_invoice_company" class="woocommerce-select" style="width:100%;">';
            echo '<option disabled selected value="">' . esc_attr__('Yritys', 'text-domain') . '</option>';
            foreach ($companies as $index => $company) {
                $label = esc_html($company['company_name'] . ' - ' . $company['y_tunnus']);
                echo "<option value=\"" . $company['y_tunnus'] . "\" 
                    data-verkkolaskutusosoite=\"" . esc_attr($company['verkkolaskutusosoite']) . "\" 
                    data-valittaja=\"" . esc_attr($company['valittaja']) . "\">{$label}</option>";
            }
            echo '</select>';

            echo '<div id="invoice-details" style="margin-top: 1em; display:none;">';
            echo '<p><strong>' . esc_attr__('Verkkolaskutusosoite', 'text-domain') . ':</strong> <span id="verkkolaskutusosoite"></span></p>';
            echo '<p><strong>' . esc_attr__('Välittäjä', 'text-domain') . ':</strong> <span id="valittaja"></span></p>';
            echo '</div>';

?>
            <script>
                jQuery(document).ready(function($) {
                    $('#selected_invoice_company').on('change', function() {
                        const selected = this.options[this.selectedIndex];
                        const verkkolasku = selected.dataset.verkkolaskutusosoite || '';
                        const val = selected.dataset.valittaja || '';

                        if (verkkolasku && val) {
                            $('#verkkolaskutusosoite').text(verkkolasku);
                            $('#valittaja').text(val);
                            $('#invoice-details').show();
                        } else {
                            $('#invoice-details').hide();
                        }
                    });
                });
            </script>
<?php
        }
    }
}
