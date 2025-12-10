<?php
/**
 * AJAX Handler
 *
 * Handles AJAX requests for contact form and directions
 *
 * @package Mobile_Bottom_Bar
 */

if (!defined('ABSPATH')) {
    exit;
}

class MBB_Ajax {
    
    private $settings;

    public function __construct(MBB_Settings $settings) {
        $this->settings = $settings;
    }

    public function init(): void {
        add_action('wp_ajax_mbb_contact_form', [$this, 'handle_contact_form']);
        add_action('wp_ajax_nopriv_mbb_contact_form', [$this, 'handle_contact_form']);
        add_action('wp_ajax_mbb_get_directions', [$this, 'handle_get_directions']);
        add_action('wp_ajax_nopriv_mbb_get_directions', [$this, 'handle_get_directions']);
    }

    public function handle_contact_form(): void {
        check_ajax_referer('wp_rest', 'nonce');

        $name = sanitize_text_field($_POST['name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $recipient = sanitize_email($_POST['recipient'] ?? '');

        if (empty($name) || empty($email) || empty($message)) {
            wp_send_json_error(['message' => 'Please fill in all required fields.'], 400);
        }

        if (!is_email($email)) {
            wp_send_json_error(['message' => 'Please enter a valid email address.'], 400);
        }

        $all_settings = $this->settings->get_settings();
        $form_settings = $all_settings['contactFormSettings'] ?? $this->settings->get_default_contact_form_settings();

        $to_email = !empty($recipient) && is_email($recipient) ? $recipient : $form_settings['fromEmail'];
        
        $subject = str_replace('{name}', $name, $form_settings['subject']);
        $subject = '[' . get_bloginfo('name') . '] ' . $subject;

        $body = sprintf(
            "Name: %s\nEmail: %s\nPhone: %s\n\nMessage:\n%s",
            $name,
            $email,
            $phone ?: 'Not provided',
            $message
        );

        $headers = [
            'From: ' . $form_settings['fromName'] . ' <' . $form_settings['fromEmail'] . '>',
            'Reply-To: ' . $email,
        ];

        $sent = false;
        if ($form_settings['smtpEnabled']) {
            $sent = $this->send_email_via_smtp($to_email, $subject, $body, $headers, $form_settings);
        }

        if (!$sent) {
            $sent = wp_mail($to_email, $subject, $body, $headers);
        }

        if ($sent) {
            wp_send_json_success(['message' => $form_settings['successMessage']]);
        } else {
            wp_send_json_error(['message' => $form_settings['errorMessage']], 500);
        }
    }

    public function handle_get_directions(): void {
        check_ajax_referer('wp_rest', 'nonce');

        $origin = sanitize_text_field($_POST['origin'] ?? '');
        $destination = sanitize_text_field($_POST['destination'] ?? '');

        if (empty($origin) || empty($destination)) {
            wp_send_json_error(['message' => 'Origin and destination are required.'], 400);
        }

        $all_settings = $this->settings->get_settings();
        $api_key = $all_settings['contactFormSettings']['googleApiKey'] ?? '';

        if (empty($api_key)) {
            wp_send_json_error(['message' => 'Google API key is not configured.'], 400);
        }

        $api_url = add_query_arg([
            'origin' => $origin,
            'destination' => $destination,
            'mode' => 'driving',
            'language' => 'nl',
            'key' => $api_key
        ], 'https://maps.googleapis.com/maps/api/directions/json');

        $response = wp_remote_get($api_url, [
            'timeout' => 15,
            'sslverify' => true
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => 'Failed to fetch directions: ' . $response->get_error_message()], 500);
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!$data) {
            wp_send_json_error(['message' => 'Invalid response from Google Directions API.'], 500);
        }

        wp_send_json_success(['result' => $data]);
    }

    private function send_email_via_smtp(string $to, string $subject, string $body, array $headers, array $smtp_settings): bool {
        if (!$smtp_settings['smtpEnabled'] || empty($smtp_settings['smtpHost'])) {
            return false;
        }

        require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
        require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
        require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';

        try {
            $mailer = new \PHPMailer\PHPMailer\PHPMailer(true);
            
            $mailer->isSMTP();
            $mailer->Host = $smtp_settings['smtpHost'];
            $mailer->Port = (int) $smtp_settings['smtpPort'];
            $mailer->SMTPAuth = !empty($smtp_settings['smtpUsername']);
            $mailer->Username = $smtp_settings['smtpUsername'];
            $mailer->Password = $smtp_settings['smtpPassword'];
            
            $secure_type = $smtp_settings['smtpSecure'] ?? 'tls';
            if ($secure_type === 'ssl') {
                $mailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($secure_type === 'tls') {
                $mailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mailer->SMTPSecure = false;
            }

            $from_email = $smtp_settings['fromEmail'] ?? get_option('admin_email');
            $from_name = $smtp_settings['fromName'] ?? get_bloginfo('name');
            $mailer->setFrom($from_email, $from_name);

            $mailer->addAddress($to);

            foreach ($headers as $header) {
                if (stripos($header, 'Reply-To:') === 0) {
                    $reply_to = trim(str_ireplace('Reply-To:', '', $header));
                    if (is_email($reply_to)) {
                        $mailer->addReplyTo($reply_to);
                    }
                }
            }

            $mailer->Subject = $subject;
            $mailer->Body = $body;
            $mailer->isHTML(false);

            return $mailer->send();
        } catch (\Exception $e) {
            error_log('SMTP Email Error: ' . $e->getMessage());
            return false;
        }
    }
}
