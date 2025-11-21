<?php
/**
 * AJAX Members
 *
 * Handles AJAX requests for member filtering and pagination
 *
 * @package Metoda
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Metoda_Ajax_Members {

    /**
     * Posts per page for member listings
     */
    const POSTS_PER_PAGE = 12;

    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_filter_members', array($this, 'filter'));
        add_action('wp_ajax_nopriv_filter_members', array($this, 'filter'));
        add_action('wp_ajax_load_more_members', array($this, 'load_more'));
        add_action('wp_ajax_nopriv_load_more_members', array($this, 'load_more'));
    }

    /**
     * AJAX handler for filtering members
     *
     * Returns initial set of filtered members
     */
    public function filter() {
        check_ajax_referer('public_members_nonce', 'nonce');

        $filters = $this->get_filters();
        $members = $this->query_members($filters, 0);
        $html = $this->render_member_cards($members['posts']);

        wp_send_json_success(array(
            'html' => $html,
            'shown' => count($members['posts']),
            'total' => $members['total'],
            'has_more' => $members['total'] > count($members['posts'])
        ));
    }

    /**
     * AJAX handler for loading more members
     *
     * Returns additional members based on offset
     */
    public function load_more() {
        check_ajax_referer('public_members_nonce', 'nonce');

        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        $filters = $this->get_filters();
        $members = $this->query_members($filters, $offset);
        $html = $this->render_member_cards($members['posts']);

        wp_send_json_success(array(
            'html' => $html,
            'count' => count($members['posts'])
        ));
    }

    /**
     * Get filters from POST request
     *
     * @return array Sanitized filter values
     */
    private function get_filters() {
        return array(
            'search' => isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '',
            'city' => isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '',
            'role' => isset($_POST['role']) ? sanitize_text_field($_POST['role']) : '',
            'member_type' => isset($_POST['member_type']) ? sanitize_text_field($_POST['member_type']) : ''
        );
    }

    /**
     * Query members based on filters
     *
     * Handles both merged queries (experts + members) and single type queries
     *
     * @param array $filters Filter parameters
     * @param int $offset Offset for pagination
     * @return array Array with 'posts' and 'total' keys
     */
    private function query_members($filters, $offset = 0) {
        // If no type filter - query experts and members separately and merge
        if (empty($filters['member_type'])) {
            return $this->query_merged_members($filters, $offset);
        } else {
            return $this->query_single_type_members($filters, $offset);
        }
    }

    /**
     * Query members of a specific type
     *
     * @param array $filters Filter parameters
     * @param int $offset Offset for pagination
     * @return array Array with 'posts' and 'total' keys
     */
    private function query_single_type_members($filters, $offset) {
        $args = array(
            'post_type' => 'members',
            'posts_per_page' => self::POSTS_PER_PAGE,
            'offset' => $offset,
            'orderby' => 'title',
            'order' => 'ASC'
        );

        // Add search
        if (!empty($filters['search'])) {
            $args['s'] = $filters['search'];
        }

        // Add meta query for city
        if (!empty($filters['city'])) {
            $args['meta_query'][] = array(
                'key' => 'member_city',
                'value' => $filters['city'],
                'compare' => 'LIKE'
            );
        }

        // Add tax queries
        $args['tax_query'] = array();
        if (!empty($filters['member_type'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'member_type',
                'field' => 'slug',
                'terms' => $filters['member_type']
            );
        }
        if (!empty($filters['role'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'member_role',
                'field' => 'slug',
                'terms' => $filters['role']
            );
        }

        $query = new WP_Query($args);

        return array(
            'posts' => $query->posts,
            'total' => $query->found_posts
        );
    }

    /**
     * Query experts and members separately, then merge results
     *
     * @param array $filters Filter parameters
     * @param int $offset Offset for pagination
     * @return array Array with 'posts' and 'total' keys
     */
    private function query_merged_members($filters, $offset) {
        // Query experts
        $experts = $this->query_member_type('ekspert', $filters);

        // Query members
        $members = $this->query_member_type('uchastnik', $filters);

        // Merge results
        $all_members = array_merge($experts, $members);
        $total = count($all_members);

        // Slice for pagination
        $paged_members = array_slice($all_members, $offset, self::POSTS_PER_PAGE);

        return array(
            'posts' => $paged_members,
            'total' => $total
        );
    }

    /**
     * Query members of specific type with filters
     *
     * @param string $type Member type slug (ekspert or uchastnik)
     * @param array $filters Filter parameters
     * @return array Array of WP_Post objects
     */
    private function query_member_type($type, $filters) {
        $args = array(
            'post_type' => 'members',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'tax_query' => array(
                array(
                    'taxonomy' => 'member_type',
                    'field' => 'slug',
                    'terms' => $type
                )
            )
        );

        // Add search
        if (!empty($filters['search'])) {
            $args['s'] = $filters['search'];
        }

        // Add meta query for city
        if (!empty($filters['city'])) {
            $args['meta_query'][] = array(
                'key' => 'member_city',
                'value' => $filters['city'],
                'compare' => 'LIKE'
            );
        }

        // Add role tax query
        if (!empty($filters['role'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'member_role',
                'field' => 'slug',
                'terms' => $filters['role']
            );
        }

        $query = new WP_Query($args);
        return $query->posts;
    }

    /**
     * Render HTML for member cards
     *
     * @param array $posts Array of WP_Post objects
     * @return string Generated HTML
     */
    private function render_member_cards($posts) {
        ob_start();

        foreach ($posts as $post) {
            setup_postdata($post);
            $member_id = $post->ID;
            include(METODA_PATH . 'templates/member-card.php');
        }

        wp_reset_postdata();
        return ob_get_clean();
    }
}
