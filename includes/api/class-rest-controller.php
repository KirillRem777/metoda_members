<?php
/**
 * REST API Controller
 *
 * Modern REST API endpoints replacing admin-ajax.php
 * WordPress REST API Best Practices
 *
 * @package Metoda
 * @since 5.1.0
 */

namespace Metoda\API;

if (!defined('ABSPATH')) {
    exit;
}

class REST_Controller {

    /**
     * API namespace
     */
    const NAMESPACE = 'metoda/v1';

    /**
     * Rate limiter instance
     */
    private $rate_limiter;

    /**
     * Constructor
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
        $this->rate_limiter = new \Metoda\Security\Rate_Limiter();
    }

    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Auth endpoints
        $this->register_auth_routes();

        // Members endpoints
        $this->register_members_routes();

        // Dashboard endpoints
        $this->register_dashboard_routes();
    }

    /**
     * Register authentication routes
     */
    private function register_auth_routes() {
        // POST /metoda/v1/auth/login
        register_rest_route(self::NAMESPACE, '/auth/login', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array($this, 'handle_login'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'email' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'format'            => 'email',
                    'sanitize_callback' => 'sanitize_email',
                    'validate_callback' => function($value) {
                        return is_email($value);
                    },
                ),
                'password' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
            ),
        ));

        // POST /metoda/v1/auth/otp/request
        register_rest_route(self::NAMESPACE, '/auth/otp/request', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array($this, 'handle_otp_request'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'email' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'format'            => 'email',
                    'sanitize_callback' => 'sanitize_email',
                ),
            ),
        ));

        // POST /metoda/v1/auth/otp/verify
        register_rest_route(self::NAMESPACE, '/auth/otp/verify', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array($this, 'handle_otp_verify'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'email' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_email',
                ),
                'code' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function($value) {
                        return preg_match('/^\d{6}$/', $value);
                    },
                ),
            ),
        ));

        // POST /metoda/v1/auth/access-code/verify
        register_rest_route(self::NAMESPACE, '/auth/access-code/verify', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array($this, 'handle_access_code_verify'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'code' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));

        // POST /metoda/v1/auth/logout
        register_rest_route(self::NAMESPACE, '/auth/logout', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array($this, 'handle_logout'),
            'permission_callback' => 'is_user_logged_in',
        ));
    }

    /**
     * Register members routes
     */
    private function register_members_routes() {
        // GET /metoda/v1/members
        register_rest_route(self::NAMESPACE, '/members', array(
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => array($this, 'get_members'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'search' => array(
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'default'           => '',
                ),
                'city' => array(
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'default'           => '',
                ),
                'role' => array(
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'default'           => '',
                ),
                'type' => array(
                    'type'              => 'string',
                    'enum'              => array('member', 'expert', ''),
                    'default'           => '',
                ),
                'page' => array(
                    'type'              => 'integer',
                    'default'           => 1,
                    'minimum'           => 1,
                ),
                'per_page' => array(
                    'type'              => 'integer',
                    'default'           => 12,
                    'minimum'           => 1,
                    'maximum'           => 100,
                ),
            ),
        ));

        // GET /metoda/v1/members/{id}
        register_rest_route(self::NAMESPACE, '/members/(?P<id>\d+)', array(
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => array($this, 'get_member'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'id' => array(
                    'type'              => 'integer',
                    'required'          => true,
                    'validate_callback' => function($value) {
                        return is_numeric($value) && $value > 0;
                    },
                ),
            ),
        ));
    }

    /**
     * Register dashboard routes (requires authentication)
     */
    private function register_dashboard_routes() {
        // GET /metoda/v1/dashboard/profile
        register_rest_route(self::NAMESPACE, '/dashboard/profile', array(
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => array($this, 'get_profile'),
            'permission_callback' => array($this, 'check_member_permission'),
        ));

        // PATCH /metoda/v1/dashboard/profile
        register_rest_route(self::NAMESPACE, '/dashboard/profile', array(
            'methods'             => \WP_REST_Server::EDITABLE,
            'callback'            => array($this, 'update_profile'),
            'permission_callback' => array($this, 'check_member_permission'),
        ));

        // GET /metoda/v1/dashboard/messages
        register_rest_route(self::NAMESPACE, '/dashboard/messages', array(
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => array($this, 'get_messages'),
            'permission_callback' => array($this, 'check_member_permission'),
        ));

        // GET /metoda/v1/dashboard/materials
        register_rest_route(self::NAMESPACE, '/dashboard/materials', array(
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => array($this, 'get_materials'),
            'permission_callback' => array($this, 'check_member_permission'),
        ));
    }

    /**
     * Check if current user is a member
     *
     * @return bool|\WP_Error
     */
    public function check_member_permission() {
        if (!is_user_logged_in()) {
            return new \WP_Error(
                'rest_forbidden',
                __('Authentication required.', 'metoda-community-mgmt'),
                array('status' => 401)
            );
        }

        $user = wp_get_current_user();
        $allowed_roles = array('member', 'expert', 'manager', 'administrator');

        if (!array_intersect($allowed_roles, $user->roles)) {
            return new \WP_Error(
                'rest_forbidden',
                __('You do not have permission to access this resource.', 'metoda-community-mgmt'),
                array('status' => 403)
            );
        }

        return true;
    }

    /**
     * Handle password login
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function handle_login(\WP_REST_Request $request) {
        $ip = $this->get_client_ip();

        // Rate limiting check
        if ($this->rate_limiter->is_blocked($ip, 'login')) {
            return new \WP_Error(
                'rate_limit_exceeded',
                __('Too many login attempts. Please try again later.', 'metoda-community-mgmt'),
                array('status' => 429)
            );
        }

        $email = $request->get_param('email');
        $password = $request->get_param('password');

        $user = get_user_by('email', $email);

        if (!$user) {
            $this->rate_limiter->record_attempt($ip, 'login');
            return new \WP_Error(
                'invalid_credentials',
                __('Invalid email or password.', 'metoda-community-mgmt'),
                array('status' => 401)
            );
        }

        // Check login method
        $login_method = get_user_meta($user->ID, 'login_method', true);
        if ($login_method === 'otp') {
            return new \WP_Error(
                'wrong_method',
                __('Your account is configured for OTP login. Please use email code.', 'metoda-community-mgmt'),
                array('status' => 400, 'method' => 'otp')
            );
        }

        // Authenticate
        $authenticated = wp_authenticate($email, $password);

        if (is_wp_error($authenticated)) {
            $this->rate_limiter->record_attempt($ip, 'login');
            return new \WP_Error(
                'invalid_credentials',
                __('Invalid email or password.', 'metoda-community-mgmt'),
                array('status' => 401)
            );
        }

        // Success - clear rate limit
        $this->rate_limiter->clear_attempts($ip, 'login');

        // Set auth cookies
        wp_clear_auth_cookie();
        wp_set_current_user($authenticated->ID);
        wp_set_auth_cookie($authenticated->ID, true);

        // Get redirect URL
        $redirect_url = $this->get_redirect_url($authenticated);

        return rest_ensure_response(array(
            'success'  => true,
            'message'  => __('Login successful!', 'metoda-community-mgmt'),
            'redirect' => $redirect_url,
            'user'     => array(
                'id'           => $authenticated->ID,
                'display_name' => $authenticated->display_name,
                'email'        => $authenticated->user_email,
                'roles'        => $authenticated->roles,
            ),
        ));
    }

    /**
     * Handle OTP request
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function handle_otp_request(\WP_REST_Request $request) {
        $ip = $this->get_client_ip();

        if ($this->rate_limiter->is_blocked($ip, 'otp_request')) {
            return new \WP_Error(
                'rate_limit_exceeded',
                __('Too many OTP requests. Please wait before requesting another code.', 'metoda-community-mgmt'),
                array('status' => 429)
            );
        }

        $email = $request->get_param('email');
        $user = get_user_by('email', $email);

        if (!$user) {
            // Don't reveal if user exists
            return rest_ensure_response(array(
                'success' => true,
                'message' => __('If this email is registered, you will receive a code shortly.', 'metoda-community-mgmt'),
            ));
        }

        // Check login method
        $login_method = get_user_meta($user->ID, 'login_method', true);
        if ($login_method !== 'otp') {
            return new \WP_Error(
                'wrong_method',
                __('OTP login is not enabled for this account.', 'metoda-community-mgmt'),
                array('status' => 400)
            );
        }

        // Generate and send OTP
        if (class_exists('Member_OTP')) {
            $otp_handler = new \Member_OTP();
            $otp = $otp_handler->generate_otp($user->ID);

            if ($otp) {
                $otp_handler->send_otp_email($user->ID, $otp);
            }
        }

        $this->rate_limiter->record_attempt($ip, 'otp_request');

        return rest_ensure_response(array(
            'success' => true,
            'message' => __('If this email is registered, you will receive a code shortly.', 'metoda-community-mgmt'),
        ));
    }

    /**
     * Handle OTP verification
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function handle_otp_verify(\WP_REST_Request $request) {
        $ip = $this->get_client_ip();

        if ($this->rate_limiter->is_blocked($ip, 'otp_verify')) {
            return new \WP_Error(
                'rate_limit_exceeded',
                __('Too many verification attempts. Please request a new code.', 'metoda-community-mgmt'),
                array('status' => 429)
            );
        }

        $email = $request->get_param('email');
        $code = $request->get_param('code');

        $user = get_user_by('email', $email);

        if (!$user) {
            $this->rate_limiter->record_attempt($ip, 'otp_verify');
            return new \WP_Error(
                'invalid_code',
                __('Invalid or expired code.', 'metoda-community-mgmt'),
                array('status' => 401)
            );
        }

        // Verify OTP
        if (class_exists('Member_OTP')) {
            $otp_handler = new \Member_OTP();

            if (!$otp_handler->verify_otp($user->ID, $code)) {
                $this->rate_limiter->record_attempt($ip, 'otp_verify');
                return new \WP_Error(
                    'invalid_code',
                    __('Invalid or expired code.', 'metoda-community-mgmt'),
                    array('status' => 401)
                );
            }
        }

        // Success
        $this->rate_limiter->clear_attempts($ip, 'otp_verify');

        wp_clear_auth_cookie();
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, true);

        return rest_ensure_response(array(
            'success'  => true,
            'message'  => __('Login successful!', 'metoda-community-mgmt'),
            'redirect' => $this->get_redirect_url($user),
        ));
    }

    /**
     * Handle access code verification
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function handle_access_code_verify(\WP_REST_Request $request) {
        $code = $request->get_param('code');

        // Find member with this access code
        $members = get_posts(array(
            'post_type'   => 'members',
            'meta_key'    => '_access_code',
            'meta_value'  => $code,
            'numberposts' => 1,
        ));

        if (empty($members)) {
            return new \WP_Error(
                'invalid_code',
                __('Invalid access code.', 'metoda-community-mgmt'),
                array('status' => 404)
            );
        }

        $member = $members[0];

        // Check if already used
        $used = get_post_meta($member->ID, '_access_code_used', true);
        if ($used) {
            return new \WP_Error(
                'code_already_used',
                __('This access code has already been used.', 'metoda-community-mgmt'),
                array('status' => 400)
            );
        }

        return rest_ensure_response(array(
            'success'   => true,
            'member_id' => $member->ID,
            'name'      => get_post_meta($member->ID, 'member_name', true),
            'redirect'  => home_url('/member-onboarding/?member_id=' . $member->ID),
        ));
    }

    /**
     * Handle logout
     *
     * @return \WP_REST_Response
     */
    public function handle_logout() {
        wp_logout();

        return rest_ensure_response(array(
            'success'  => true,
            'message'  => __('Logged out successfully.', 'metoda-community-mgmt'),
            'redirect' => home_url('/member-login/'),
        ));
    }

    /**
     * Get members list
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function get_members(\WP_REST_Request $request) {
        $args = array(
            'post_type'      => 'members',
            'post_status'    => 'publish',
            'posts_per_page' => $request->get_param('per_page'),
            'paged'          => $request->get_param('page'),
            'orderby'        => 'title',
            'order'          => 'ASC',
        );

        // Search
        if ($search = $request->get_param('search')) {
            $args['s'] = $search;
        }

        // Meta query for filters
        $meta_query = array();

        if ($city = $request->get_param('city')) {
            $meta_query[] = array(
                'key'     => 'member_city',
                'value'   => $city,
                'compare' => 'LIKE',
            );
        }

        if ($type = $request->get_param('type')) {
            $meta_query[] = array(
                'key'     => 'member_type',
                'value'   => $type,
                'compare' => '=',
            );
        }

        if (!empty($meta_query)) {
            $args['meta_query'] = $meta_query;
        }

        $query = new \WP_Query($args);
        $members = array();

        foreach ($query->posts as $post) {
            $members[] = $this->format_member($post);
        }

        $response = rest_ensure_response(array(
            'members' => $members,
            'total'   => $query->found_posts,
            'pages'   => $query->max_num_pages,
        ));

        // Add pagination headers
        $response->header('X-WP-Total', $query->found_posts);
        $response->header('X-WP-TotalPages', $query->max_num_pages);

        return $response;
    }

    /**
     * Get single member
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_member(\WP_REST_Request $request) {
        $member_id = $request->get_param('id');
        $post = get_post($member_id);

        if (!$post || $post->post_type !== 'members') {
            return new \WP_Error(
                'not_found',
                __('Member not found.', 'metoda-community-mgmt'),
                array('status' => 404)
            );
        }

        return rest_ensure_response($this->format_member($post, true));
    }

    /**
     * Get current user profile
     *
     * @return \WP_REST_Response
     */
    public function get_profile() {
        $user = wp_get_current_user();
        $member_id = get_user_meta($user->ID, 'member_id', true);

        $data = array(
            'user_id'      => $user->ID,
            'email'        => $user->user_email,
            'display_name' => $user->display_name,
            'roles'        => $user->roles,
            'member_id'    => $member_id,
        );

        if ($member_id) {
            $member = get_post($member_id);
            if ($member) {
                $data['member'] = $this->format_member($member, true);
            }
        }

        return rest_ensure_response($data);
    }

    /**
     * Update current user profile
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function update_profile(\WP_REST_Request $request) {
        $user = wp_get_current_user();
        $member_id = get_user_meta($user->ID, 'member_id', true);

        if (!$member_id) {
            return new \WP_Error(
                'no_member',
                __('No member profile linked to this account.', 'metoda-community-mgmt'),
                array('status' => 400)
            );
        }

        $params = $request->get_json_params();
        $updated = array();

        // Update allowed fields
        $allowed_fields = array(
            'member_about'      => 'sanitize_textarea_field',
            'member_city'       => 'sanitize_text_field',
            'member_phone'      => 'sanitize_text_field',
            'member_telegram'   => 'sanitize_text_field',
            'member_linkedin'   => 'esc_url_raw',
        );

        foreach ($allowed_fields as $field => $sanitizer) {
            if (isset($params[$field])) {
                $value = call_user_func($sanitizer, $params[$field]);
                update_post_meta($member_id, $field, $value);
                $updated[$field] = $value;
            }
        }

        return rest_ensure_response(array(
            'success' => true,
            'message' => __('Profile updated successfully.', 'metoda-community-mgmt'),
            'updated' => $updated,
        ));
    }

    /**
     * Get user messages
     *
     * @return \WP_REST_Response
     */
    public function get_messages() {
        $user = wp_get_current_user();

        // Get messages from custom table or meta
        $messages = array(); // Implement your message retrieval logic

        return rest_ensure_response(array(
            'messages' => $messages,
            'unread'   => 0,
        ));
    }

    /**
     * Get materials for current user
     *
     * @return \WP_REST_Response
     */
    public function get_materials() {
        $user = wp_get_current_user();

        $args = array(
            'post_type'      => 'materials',
            'post_status'    => 'publish',
            'posts_per_page' => 20,
        );

        // Filter by user role/access
        // Add your access control logic here

        $query = new \WP_Query($args);
        $materials = array();

        foreach ($query->posts as $post) {
            $materials[] = array(
                'id'          => $post->ID,
                'title'       => $post->post_title,
                'excerpt'     => get_the_excerpt($post),
                'date'        => $post->post_date,
                'category'    => wp_get_post_terms($post->ID, 'material_category'),
                'access_type' => get_post_meta($post->ID, 'access_type', true),
            );
        }

        return rest_ensure_response(array(
            'materials' => $materials,
            'total'     => $query->found_posts,
        ));
    }

    /**
     * Format member data for API response
     *
     * @param \WP_Post $post Member post
     * @param bool $full Include all fields
     * @return array
     */
    private function format_member(\WP_Post $post, bool $full = false): array {
        $data = array(
            'id'    => $post->ID,
            'name'  => get_post_meta($post->ID, 'member_name', true),
            'city'  => get_post_meta($post->ID, 'member_city', true),
            'type'  => get_post_meta($post->ID, 'member_type', true),
            'photo' => get_the_post_thumbnail_url($post->ID, 'medium'),
            'url'   => get_permalink($post->ID),
        );

        if ($full) {
            $data['about']     = get_post_meta($post->ID, 'member_about', true);
            $data['company']   = get_post_meta($post->ID, 'member_company', true);
            $data['position']  = get_post_meta($post->ID, 'member_position', true);
            $data['telegram']  = get_post_meta($post->ID, 'member_telegram', true);
            $data['linkedin']  = get_post_meta($post->ID, 'member_linkedin', true);
            $data['expertise'] = get_post_meta($post->ID, 'member_expertise', true);
        }

        return $data;
    }

    /**
     * Get redirect URL based on user role
     *
     * @param \WP_User $user
     * @return string
     */
    private function get_redirect_url(\WP_User $user): string {
        if (in_array('administrator', $user->roles)) {
            return admin_url();
        }

        if (in_array('manager', $user->roles)) {
            return home_url('/manager-panel/');
        }

        if (class_exists('Member_Onboarding') && \Member_Onboarding::user_needs_onboarding($user->ID)) {
            return home_url('/member-onboarding/');
        }

        return home_url('/member-dashboard/');
    }

    /**
     * Get client IP address
     *
     * @return string
     */
    private function get_client_ip(): string {
        $ip_keys = array(
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        );

        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = explode(',', sanitize_text_field($_SERVER[$key]))[0];
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }
}
