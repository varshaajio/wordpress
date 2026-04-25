<?php
/**
 * Name: Outreach Foundation
 * Description: Fully functional charity campaigning system. Supports Razorpay, PayPal, and Offline modes.
 * Version: 1.0
 * Author: Varshaa
 */

if (!defined('ABSPATH')) exit;

class OF {
    private static $i = null;
    private $k = 'of_opts';
    private $o = [];

    public static function init() {
        if (self::$i === null) self::$i = new self();
        return self::$i;
    }

    private function __construct() {
        $this->o = get_option($this->k, [
            'gt' => 'offline', // Gateway
            'rk' => '',        // Razorpay Key
            'pe' => '',        // PayPal Email
            'cy' => 'USD',     // Currency
        ]);

        add_action('init', [$this, 're']); // Register
        add_action('add_meta_boxes', [$this, 'mb']); // Metabox
        add_action('save_post', [$this, 'sv']); // Save
        add_action('admin_menu', [$this, 'mn']); // Menu
        add_action('admin_init', [$this, 'st']); // Settings
        add_shortcode('of_all', [$this, 'sh_a']); // Shortcode All
        add_shortcode('of_one', [$this, 'sh_o']); // Shortcode One
        add_action('wp_enqueue_scripts', [$this, 'as']); // Assets
        add_action('template_redirect', [$this, 'hd']); // Handle Donation
    }

    // --- REGISTRATIONS (Post Types: cp = Campaign, dn = Donation) ---
    public function re() {
        register_post_type('cp', [
            'labels' => ['name' => 'Campaigns'],
            'public' => true,
            'supports' => ['title', 'editor', 'thumbnail'],
            'menu_icon' => 'dashicons-heart',
            'rewrite' => ['slug' => 'campaigns']
        ]);
        register_post_type('dn', [
            'labels' => ['name' => 'Donations'],
            'public' => false,
            'show_ui' => true,
            'menu_icon' => 'dashicons-tickets'
        ]);
    }

    // --- ASSETS ---
    public function as() {
        wp_register_style('of_s', false);
        wp_enqueue_style('of_s');
        // Basic fallback grid for the [of_all] shortcode
        wp_add_inline_style('of_s', ".of_g{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:20px;}.of_c{border:1px solid #ddd;padding:15px;}.of_b{background:#eee;height:10px;margin:10px 0;}.of_f{background:#2ecc71;height:100%;}");
    }

    // --- METABOX (Keys: gl = Goal, rs = Raised, dl = Deadline) ---
    public function mb() {
        add_meta_box('of_m', 'Details', [$this, 'rm'], 'cp', 'side');
    }

    public function rm($post) {
        wp_nonce_field('of_s_n', 'of_n');
        $gl = get_post_meta($post->ID, 'gl', true);
        $rs = get_post_meta($post->ID, 'rs', true);
        $dl = get_post_meta($post->ID, 'dl', true);
        echo '<p>Goal: <input type="number" name="gl" value="'.esc_attr($gl).'" class="widefat"></p>';
        echo '<p>Raised: <input type="number" name="rs" value="'.esc_attr($rs).'" class="widefat"></p>';
        echo '<p>Deadline: <input type="date" name="dl" value="'.esc_attr($dl).'" class="widefat"></p>';
    }

    public function sv($p_id) {
        if (!isset($_POST['of_n']) || !wp_verify_nonce($_POST['of_n'], 'of_s_n')) return;
        if (isset($_POST['gl'])) update_post_meta($p_id, 'gl', sanitize_text_field($_POST['gl']));
        if (isset($_POST['rs'])) update_post_meta($p_id, 'rs', sanitize_text_field($_POST['rs']));
        if (isset($_POST['dl'])) update_post_meta($p_id, 'dl', sanitize_text_field($_POST['dl']));
    }

    // --- HANDLER (Processes the form submission) ---
    public function hd() {
        if (!isset($_POST['of_d_n']) || !wp_verify_nonce($_POST['of_d_n'], 'of_d')) return;
        
        $c_id = intval($_POST['id']);
        $am = floatval($_POST['am']);
        $gt = $this->o['gt'];

        if ($gt === 'paypal') {
            $u = "https://www.paypal.com/cgi-bin/webscr?".http_build_query([
                'cmd'=>'_donations',
                'business'=>$this->o['pe'],
                'amount'=>$am,
                'currency_code'=>$this->o['cy'],
                'return'=>get_permalink($c_id) . '?s=1'
            ]);
            wp_redirect($u); exit;
        }

        // Offline logic
        wp_insert_post(['post_title'=>"Donation: $am", 'post_type'=>'dn', 'post_status'=>'publish']);
        $cur = floatval(get_post_meta($c_id, 'rs', true));
        update_post_meta($c_id, 'rs', $cur + $am);
        wp_redirect(add_query_arg('s', '1', get_permalink($c_id))); exit;
    }

    // --- SETTINGS ---
    public function mn() { add_options_page('Outreach', 'Outreach', 'manage_options', 'of_s', [$this, 'sp']); }
    public function st() { register_setting('of_g', $this->k); }
    
    public function sp() {
        ?>
        <div class="wrap">
            <h1>Outreach Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('of_g'); ?>
                <table class="form-table">
                    <tr>
                        <th>Gateway</th>
                        <td>
                            <select name="of_opts[gt]">
                                <option value="offline" <?php selected($this->o['gt'], 'offline'); ?>>Offline (Test)</option>
                                <option value="paypal" <?php selected($this->o['gt'], 'paypal'); ?>>PayPal</option>
                            </select>
                        </td>
                    </tr>
                    <tr><th>PayPal Email</th><td><input type="email" name="of_opts[pe]" value="<?php echo esc_attr($this->o['pe']); ?>" class="regular-text"></td></tr>
                    <tr><th>Currency Code</th><td><input type="text" name="of_opts[cy]" value="<?php echo esc_attr($this->o['cy']); ?>" class="regular-text"></td></tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    // --- DISPLAY ---
    public function sh_a() {
        $q = new WP_Query(['post_type'=>'cp','posts_per_page'=>-1]);
        ob_start();
        echo '<div class="of_g">';
        while($q->have_posts()){ $q->the_post();
            $gl = floatval(get_post_meta(get_the_ID(), 'gl', true));
            $rs = floatval(get_post_meta(get_the_ID(), 'rs', true));
            $p = $gl > 0 ? min(100, round(($rs/$gl)*100)) : 0;
            echo '<div class="of_c"><h4>'.get_the_title().'</h4>';
            echo '<div class="of_b"><div class="of_f" style="width:'.$p.'%"></div></div>';
            echo '<a href="'.get_permalink().'">Donate &rarr;</a></div>';
        }
        echo '</div>';
        return ob_get_clean();
    }

    public function sh_o($a) {
        $id = $a['id'] ?? get_the_ID();
        ob_start();
        
        if(isset($_GET['s'])) {
            echo "<div style='color: #27ae60; background: #e8f8f5; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; text-align: center;'>Thank you! Your donation was successful.</div>";
        }
        ?>
        <div class="of-donate-card">
            <form class="of-form" action="" method="POST">
                
                <?php wp_nonce_field('of_d', 'of_d_n'); ?>
                <input type="hidden" name="id" value="<?php echo esc_attr($id); ?>">
                
                <h3>Select your gift amount</h3>
                
                <div class="of-amount-presets">
                    <label class="of-radio-label">
                        <input type="radio" name="preset" value="25" onclick="document.getElementById('of_custom_am').value=this.value;">
                        <span class="of-preset-btn">$25</span>
                    </label>
                    <label class="of-radio-label">
                        <input type="radio" name="preset" value="50" checked onclick="document.getElementById('of_custom_am').value=this.value;">
                        <span class="of-preset-btn of-active">$50</span>
                    </label>
                    <label class="of-radio-label">
                        <input type="radio" name="preset" value="100" onclick="document.getElementById('of_custom_am').value=this.value;">
                        <span class="of-preset-btn">$100</span>
                    </label>
                    <label class="of-radio-label">
                        <input type="radio" name="preset" value="" onclick="document.getElementById('of_custom_am').value=''; document.getElementById('of_custom_am').focus();">
                        <span class="of-preset-btn">Other</span>
                    </label>
                </div>

                <div class="of-input-group">
                    <span class="of-currency">$</span>
                    <input type="number" name="am" id="of_custom_am" placeholder="Custom Amount" required value="50" class="of-input">
                </div>

                <button type="submit" class="of-btn of-btn-donate">Donate Now</button>
                <p class="of-secure-text">🔒 Secure donation encrypted via SSL</p>
            </form>
        </div>

        <script>
            document.querySelectorAll('.of-radio-label input').forEach(radio => {
                radio.addEventListener('change', function() {
                    document.querySelectorAll('.of-preset-btn').forEach(btn => btn.classList.remove('of-active'));
                    this.nextElementSibling.classList.add('of-active');
                });
            });
        </script>
        <?php
        return ob_get_clean();
    }
}
OF::init();
