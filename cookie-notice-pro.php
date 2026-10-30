<?php
/**
 * Plugin Name: Cookie Notice Pro
 * Description: Responsywny baner zgód cookies z kategoriami, panelem ustawień i blokowaniem oznaczonych skryptów do czasu uzyskania zgody.
 * Version: 1.0.0
 * Author: Custom
 * Text Domain: cookie-notice-pro
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) { exit; }

final class CNP_Cookie_Notice {
    const VERSION = '1.0.0';
    const OPTION = 'cnp_options';
    const COOKIE = 'cnp_consent';

    public function __construct() {
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        // Render before WordPress prints footer scripts (normally priority 20).
        add_action('wp_footer', [$this, 'render_banner'], 5);
        add_shortcode('cookie_settings', [$this, 'settings_shortcode']);
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'action_links']);
    }

    public static function defaults() {
        return [
            'enabled' => 1,
            'title' => 'Dbamy o Twoją prywatność',
            'message' => 'Ta strona wykorzystuje pliki cookies, aby działać prawidłowo oraz — za Twoją zgodą — analizować ruch i dopasowywać treści. Możesz zaakceptować wszystkie pliki, odrzucić opcjonalne lub dostosować ustawienia.',
            'accept_text' => 'Akceptuję wszystkie',
            'reject_text' => 'Odrzucam opcjonalne',
            'settings_text' => 'Ustawienia',
            'save_text' => 'Zapisz moje wybory',
            'privacy_text' => 'Polityka prywatności',
            'privacy_url' => '',
            'necessary_title' => 'Niezbędne',
            'necessary_desc' => 'Wymagane do prawidłowego działania strony. Nie można ich wyłączyć.',
            'preferences_title' => 'Preferencyjne',
            'preferences_desc' => 'Zapamiętują wybrane ustawienia i preferencje.',
            'analytics_title' => 'Analityczne',
            'analytics_desc' => 'Pomagają zrozumieć, w jaki sposób odwiedzający korzystają ze strony.',
            'marketing_title' => 'Marketingowe',
            'marketing_desc' => 'Służą do mierzenia skuteczności reklam i dopasowywania marketingu.',
            'duration' => 180,
            'position' => 'bottom',
            'layout' => 'bar',
            'accent' => '#1769aa',
            'background' => '#ffffff',
            'text_color' => '#222222',
            'show_reopen' => 1,
        ];
    }

    public function options() {
        return wp_parse_args(get_option(self::OPTION, []), self::defaults());
    }

    public function admin_menu() {
        add_options_page('Cookie Notice Pro', 'Cookie Notice Pro', 'manage_options', 'cookie-notice-pro', [$this, 'settings_page']);
    }

    public function register_settings() {
        register_setting('cnp_settings_group', self::OPTION, [$this, 'sanitize_options']);
    }

    public function sanitize_options($input) {
        $d = self::defaults();
        $out = [];
        $text_fields = ['title','message','accept_text','reject_text','settings_text','save_text','privacy_text','necessary_title','necessary_desc','preferences_title','preferences_desc','analytics_title','analytics_desc','marketing_title','marketing_desc'];
        foreach ($text_fields as $key) {
            $out[$key] = isset($input[$key]) ? sanitize_textarea_field($input[$key]) : $d[$key];
        }
        $out['privacy_url'] = isset($input['privacy_url']) ? esc_url_raw($input['privacy_url']) : '';
        $out['enabled'] = !empty($input['enabled']) ? 1 : 0;
        $out['show_reopen'] = !empty($input['show_reopen']) ? 1 : 0;
        $out['duration'] = isset($input['duration']) ? max(1, min(3650, absint($input['duration']))) : $d['duration'];
        $out['position'] = (isset($input['position']) && in_array($input['position'], ['bottom','top'], true)) ? $input['position'] : 'bottom';
        $out['layout'] = (isset($input['layout']) && in_array($input['layout'], ['bar','box'], true)) ? $input['layout'] : 'bar';
        foreach (['accent','background','text_color'] as $key) {
            $out[$key] = isset($input[$key]) ? sanitize_hex_color($input[$key]) : $d[$key];
            if (!$out[$key]) $out[$key] = $d[$key];
        }
        return $out;
    }

    public function settings_page() {
        if (!current_user_can('manage_options')) return;
        $o = $this->options();
        ?>
        <div class="wrap cnp-admin-wrap">
            <h1>Cookie Notice Pro</h1>
            <p>Skonfiguruj komunikat i wygląd banera zgód na pliki cookies.</p>
            <form method="post" action="options.php">
                <?php settings_fields('cnp_settings_group'); ?>
                <table class="form-table" role="presentation">
                    <tr><th scope="row">Włącz baner</th><td><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[enabled]" value="1" <?php checked($o['enabled']); ?>> Wyświetlaj baner odwiedzającym</label></td></tr>
                    <tr><th scope="row">Nagłówek</th><td><input class="regular-text" name="<?php echo esc_attr(self::OPTION); ?>[title]" value="<?php echo esc_attr($o['title']); ?>"></td></tr>
                    <tr><th scope="row">Treść komunikatu</th><td><textarea class="large-text" rows="4" name="<?php echo esc_attr(self::OPTION); ?>[message]"><?php echo esc_textarea($o['message']); ?></textarea></td></tr>
                    <tr><th scope="row">Przyciski</th><td>
                        <p><label>Akceptacja: <input class="regular-text" name="<?php echo esc_attr(self::OPTION); ?>[accept_text]" value="<?php echo esc_attr($o['accept_text']); ?>"></label></p>
                        <p><label>Odrzucenie: <input class="regular-text" name="<?php echo esc_attr(self::OPTION); ?>[reject_text]" value="<?php echo esc_attr($o['reject_text']); ?>"></label></p>
                        <p><label>Ustawienia: <input class="regular-text" name="<?php echo esc_attr(self::OPTION); ?>[settings_text]" value="<?php echo esc_attr($o['settings_text']); ?>"></label></p>
                        <p><label>Zapisz wybór: <input class="regular-text" name="<?php echo esc_attr(self::OPTION); ?>[save_text]" value="<?php echo esc_attr($o['save_text']); ?>"></label></p>
                    </td></tr>
                    <tr><th scope="row">Polityka prywatności</th><td><p><input type="url" class="regular-text" name="<?php echo esc_attr(self::OPTION); ?>[privacy_url]" placeholder="https://..." value="<?php echo esc_attr($o['privacy_url']); ?>"></p><p><input class="regular-text" name="<?php echo esc_attr(self::OPTION); ?>[privacy_text]" value="<?php echo esc_attr($o['privacy_text']); ?>"></p></td></tr>
                    <tr><th scope="row">Kategorie cookies</th><td>
                        <?php foreach (['necessary'=>'Niezbędne','preferences'=>'Preferencyjne','analytics'=>'Analityczne','marketing'=>'Marketingowe'] as $key=>$label): ?>
                            <fieldset style="margin:0 0 14px;padding:10px;border:1px solid #ccd0d4;max-width:650px"><strong><?php echo esc_html($label); ?></strong>
                                <p><label>Nazwa<br><input class="regular-text" name="<?php echo esc_attr(self::OPTION); ?>[<?php echo esc_attr($key); ?>_title]" value="<?php echo esc_attr($o[$key.'_title']); ?>"></label></p>
                                <p><label>Opis<br><input class="large-text" name="<?php echo esc_attr(self::OPTION); ?>[<?php echo esc_attr($key); ?>_desc]" value="<?php echo esc_attr($o[$key.'_desc']); ?>"></label></p>
                            </fieldset>
                        <?php endforeach; ?>
                    </td></tr>
                    <tr><th scope="row">Wygląd</th><td>
                        <p><label>Układ <select name="<?php echo esc_attr(self::OPTION); ?>[layout]"><option value="bar" <?php selected($o['layout'],'bar'); ?>>Pasek na całą szerokość</option><option value="box" <?php selected($o['layout'],'box'); ?>>Pływające okno</option></select></label></p>
                        <p><label>Położenie <select name="<?php echo esc_attr(self::OPTION); ?>[position]"><option value="bottom" <?php selected($o['position'],'bottom'); ?>>Dół</option><option value="top" <?php selected($o['position'],'top'); ?>>Góra</option></select></label></p>
                        <p><label>Kolor przycisków <input type="color" name="<?php echo esc_attr(self::OPTION); ?>[accent]" value="<?php echo esc_attr($o['accent']); ?>"></label></p>
                        <p><label>Tło <input type="color" name="<?php echo esc_attr(self::OPTION); ?>[background]" value="<?php echo esc_attr($o['background']); ?>"></label></p>
                        <p><label>Kolor tekstu <input type="color" name="<?php echo esc_attr(self::OPTION); ?>[text_color]" value="<?php echo esc_attr($o['text_color']); ?>"></label></p>
                    </td></tr>
                    <tr><th scope="row">Zapamiętywanie zgody</th><td><label>Liczba dni <input type="number" min="1" max="3650" name="<?php echo esc_attr(self::OPTION); ?>[duration]" value="<?php echo esc_attr($o['duration']); ?>"></label><p class="description">Po tym czasie baner ponownie poprosi o wybór.</p></td></tr>
                    <tr><th scope="row">Zmiana ustawień</th><td><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[show_reopen]" value="1" <?php checked($o['show_reopen']); ?>> Pokaż mały przycisk „Ustawienia cookies” po zapisaniu zgody</label></td></tr>
                </table>
                <?php submit_button('Zapisz ustawienia'); ?>
            </form>
            <hr><h2>Jak wstrzymać skrypty analityczne lub marketingowe?</h2>
            <p>Skrypt, który wymaga zgody, oznacz w kodzie strony atrybutami <code>type="text/plain" data-cookie-category="analytics"</code> (lub <code>marketing</code>, <code>preferences</code>). Wtyczka uruchomi go dopiero po udzieleniu odpowiedniej zgody. Przykład:</p>
            <pre>&lt;script type="text/plain" data-cookie-category="analytics"&gt;
// kod narzędzia analitycznego
&lt;/script&gt;</pre>
            <p><strong>Ważne:</strong> samo wyświetlanie banera nie gwarantuje zgodności z RODO/ePrivacy. Należy oznaczyć wszystkie odpowiednie skrypty i integracje, opisać rzeczywiście używane cookies oraz dostosować politykę prywatności.</p>
            <p>Możesz umieścić link do ponownego otwierania ustawień w dowolnym miejscu strony za pomocą shortcode <code>[cookie_settings]</code>.</p>
        </div>
        <?php
    }

    public function enqueue_assets() {
        $o = $this->options();
        if (!$o['enabled']) return;
        wp_enqueue_style('cnp-style', plugin_dir_url(__FILE__) . 'assets/cookie-notice.css', [], self::VERSION);
        wp_enqueue_script('cnp-script', plugin_dir_url(__FILE__) . 'assets/cookie-notice.js', [], self::VERSION, true);
        wp_localize_script('cnp-script', 'CNPSettings', [
            'cookieName' => self::COOKIE,
            'days' => (int) $o['duration'],
            'showReopen' => (bool) $o['show_reopen'],
            'labels' => ['accept'=>$o['accept_text'],'reject'=>$o['reject_text'],'settings'=>$o['settings_text'],'save'=>$o['save_text']],
        ]);
        $custom = ':root{--cnp-accent:' . $o['accent'] . ';--cnp-bg:' . $o['background'] . ';--cnp-text:' . $o['text_color'] . ';}';
        wp_add_inline_style('cnp-style', $custom);
    }

    public function render_banner() {
        $o = $this->options();
        if (!$o['enabled'] || is_admin()) return;
        $privacy = $o['privacy_url'] ? '<a class="cnp-privacy" href="' . esc_url($o['privacy_url']) . '" target="_blank" rel="noopener noreferrer">' . esc_html($o['privacy_text']) . '</a>' : '';
        ?>
        <section id="cnp-banner" class="cnp-banner cnp-<?php echo esc_attr($o['layout']); ?> cnp-<?php echo esc_attr($o['position']); ?>" role="dialog" aria-modal="false" aria-labelledby="cnp-title" aria-describedby="cnp-message" hidden>
            <div class="cnp-main">
                <div class="cnp-copy"><h2 id="cnp-title"><?php echo esc_html($o['title']); ?></h2><p id="cnp-message"><?php echo esc_html($o['message']); ?> <?php echo $privacy; // escaped above ?></p></div>
                <div class="cnp-actions">
                    <button type="button" class="cnp-btn cnp-primary" data-cnp-action="accept"><?php echo esc_html($o['accept_text']); ?></button>
                    <button type="button" class="cnp-btn cnp-secondary" data-cnp-action="reject"><?php echo esc_html($o['reject_text']); ?></button>
                    <button type="button" class="cnp-btn cnp-link" data-cnp-action="open-settings"><?php echo esc_html($o['settings_text']); ?></button>
                </div>
            </div>
            <div class="cnp-preferences" hidden>
                <h3><?php echo esc_html($o['settings_text']); ?></h3>
                <?php foreach (['necessary','preferences','analytics','marketing'] as $key): ?>
                    <label class="cnp-category"><span><strong><?php echo esc_html($o[$key.'_title']); ?></strong><small><?php echo esc_html($o[$key.'_desc']); ?></small></span><input type="checkbox" data-cnp-category="<?php echo esc_attr($key); ?>" <?php checked($key === 'necessary'); ?> <?php disabled($key === 'necessary'); ?>></label>
                <?php endforeach; ?>
                <div class="cnp-actions"><button type="button" class="cnp-btn cnp-primary" data-cnp-action="save"><?php echo esc_html($o['save_text']); ?></button><button type="button" class="cnp-btn cnp-link" data-cnp-action="back"><?php echo esc_html($o['settings_text']); ?> — zamknij</button></div>
            </div>
        </section>
        <?php if ($o['show_reopen']): ?><button type="button" id="cnp-reopen" class="cnp-reopen" hidden>Ustawienia cookies</button><?php endif; ?>
        <?php
    }

    public function settings_shortcode() {
        return '<button type="button" class="cnp-shortcode-trigger" onclick="window.CNPOpenSettings && window.CNPOpenSettings();">Ustawienia cookies</button>';
    }

    public function action_links($links) {
        array_unshift($links, '<a href="' . esc_url(admin_url('options-general.php?page=cookie-notice-pro')) . '">Ustawienia</a>');
        return $links;
    }
}

new CNP_Cookie_Notice();
