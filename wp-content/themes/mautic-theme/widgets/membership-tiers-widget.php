<?php
/**
 * Corporate membership tiers comparison table.
 *
 * Renders the full benefit matrix server-side as a semantic <table>, with all three
 * regional price sets embedded on each price element. The accompanying script only
 * swaps the visible price string when the visitor changes the country selector, so
 * the table markup (and therefore its accessibility tree) never gets rebuilt.
 *
 * Tier prices, Stripe payment links and the benefit matrix are deliberately kept in
 * this file rather than in Elementor settings: a wrong edit here costs the project
 * money, so changes should go through a pull request. Each data set is filterable if
 * it ever needs to move to an options page.
 *
 * @package MauticTheme
 */

namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

use Elementor\Controls_Manager;

class Membership_Tiers_Widget extends Widget_Base {

    /**
     * Corporate tiers, in display order.
     *
     * prices: [ Tier 1, Tier 2, Tier 3 ] in whole US dollars. Stripe holds the same
     * three amounts as currency_options on each product, so a visitor in a Tier 2 or
     * Tier 3 country is charged the local equivalent automatically at checkout.
     */
    public static function tiers() {
        return apply_filters( 'mautic_membership_tiers', [
            [
                'name'   => 'Community',
                'prices' => [ 1200, 950, 500 ],
                'url'    => 'https://donate.stripe.com/aFa14neS350l6PV4Ov0oM0d',
            ],
            [
                'name'   => 'Growth',
                'prices' => [ 3000, 2350, 1250 ],
                'url'    => 'https://donate.stripe.com/4gMeVd25hfEZgqv6WD0oM08',
            ],
            [
                'name'   => 'Bronze',
                'prices' => [ 5000, 3900, 2100 ],
                'url'    => 'https://donate.stripe.com/14A28rcJV8cx2zF3Kr0oM0a',
            ],
            [
                'name'   => 'Silver',
                'prices' => [ 10000, 7800, 4200 ],
                'url'    => 'https://donate.stripe.com/8x2eVd8tFakF8Y394L0oM09',
            ],
            [
                'name'   => 'Gold',
                'prices' => [ 15000, 11700, 6300 ],
                'url'    => 'https://donate.stripe.com/aFa5kD5ht2Sdfmrft90oM0b',
            ],
            [
                'name'   => 'Platinum',
                'prices' => [ 20000, 15600, 8400 ],
                'url'    => 'https://donate.stripe.com/7sYcN59xJ64p0rx80H0oM0c',
            ],
            [
                'name'   => 'Diamond',
                'prices' => [ 30000, 23400, 12600 ],
                'url'    => 'https://donate.stripe.com/6oU9ATbFR2Sd0rxft90oM05',
            ],
        ] );
    }

    /**
     * Benefit matrix, grouped.
     *
     * Cell values: 'Y' included, 'N' not included, anything else printed verbatim.
     * Each row's values array must stay in the same order as self::tiers().
     */
    public static function benefit_groups() {
        return apply_filters( 'mautic_membership_benefit_groups', [
            [
                'group' => 'Recognition & voting',
                'rows'  => [
                    [ 'Logo + link in members directory',        [ 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y' ] ],
                    [ 'Member badge with tier',                  [ 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y' ] ],
                    [ 'Social media thank-you shout-out',        [ 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y' ] ],
                    [ 'Vote in elections & voting processes',    [ '1', '1', '1', '1', '1', '1', '1' ] ],
                    [ 'Exclusive forum badge for all staff',     [ 'N', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y' ] ],
                ],
            ],
            [
                'group' => 'Visibility & SEO',
                'rows'  => [
                    [ 'Do-follow link on sponsors page',            [ 'N', 'N', 'N', 'Y', 'Y', 'Y', 'Y' ] ],
                    [ 'Logo on printed banners at in-person events', [ 'N', 'N', 'Y', 'Y', 'Y', 'Y', 'Y' ] ],
                    [ 'Forum ad campaigns per year',                [ 'N', 'N', '1', '2', '2', '3', '4' ] ],
                    [ 'Website ad campaigns per year',              [ 'N', 'N', 'N', '1', '2', '3', '4' ] ],
                ],
            ],
            [
                'group' => 'Content & promotion',
                'rows'  => [
                    [ 'Sponsored blog posts per year',                  [ 'N', 'N', 'N', '1', '2', '2', '3' ] ],
                    [ 'Featured case study on the mautic.org homepage', [ 'N', 'N', 'N', 'N', '1', '1', '2' ] ],
                    [ 'Promotion of your case study, webinar or course', [ 'N', 'N', 'N', 'N', '1', '2', '2' ] ],
                    [ 'Newsletter feature per year',                    [ 'N', 'N', 'N', 'N', 'N', '1', '2' ] ],
                    [ 'Featured webinar per year',                      [ 'N', 'N', 'N', 'N', 'N', '1', '2' ] ],
                    [ 'Joint press release with Mautic',                [ 'N', 'N', 'N', 'N', 'Y', 'Y', 'Y' ] ],
                ],
            ],
            [
                'group' => 'Events & MautiCon',
                'rows'  => [
                    [ 'Complimentary MautiCon tickets',                    [ '1', '2', '3', '4', '5', '7', '10' ] ],
                    [ 'Discount code for extra conference tickets',        [ 'N', 'N', 'N', 'N', '5%', '10%', '15%' ] ],
                    [ "Sponsors' dinner ticket at one conference per year", [ 'N', 'N', 'N', 'N', 'Y', 'Y', 'Y' ] ],
                    [ 'Discount on sponsoring an official conference',     [ 'N', 'N', 'N', 'N', 'N', '25%', '50%' ] ],
                    [ 'Sponsor a Mautic Awards category',                  [ 'N', 'N', 'N', 'N', 'Y', 'Y', 'Y' ] ],
                ],
            ],
        ] );
    }

    /**
     * Regional pricing tiers, keyed by the country name shown in the selector.
     *
     * Tier 1 is the standard price. Countries absent from tiers 2 and 3 fall back to
     * tier 1. "Euro area" is intentionally listed as a single option, matching how
     * the pricing scheme is published.
     */
    public static function country_tiers() {
        return apply_filters( 'mautic_membership_country_tiers', [
            1 => [
                'Australia', 'Bahrain', 'Canada', 'Denmark', 'Euro area', 'Kuwait',
                'New Zealand', 'Norway', 'Singapore', 'Sweden', 'Switzerland',
                'United Kingdom', 'United States',
            ],
            2 => [
                'Brazil', 'Chile', 'Costa Rica', 'Czech Republic', 'Hungary', 'Israel',
                'Malaysia', 'Mexico', 'Oman', 'Peru', 'Poland', 'Romania',
                'Saudi Arabia', 'South Korea', 'Taiwan', 'Thailand', 'UAE', 'Uruguay',
            ],
            3 => [
                'Argentina', 'Azerbaijan', 'China', 'Colombia', 'DR Congo', 'Egypt',
                'Guatemala', 'Honduras', 'India', 'Indonesia', 'Jordan', 'Kenya',
                'Lebanon', 'Moldova', 'Nicaragua', 'Nigeria', 'Pakistan', 'Philippines',
                'South Africa', 'Turkey', 'Ukraine', 'Venezuela', 'Vietnam',
            ],
        ] );
    }

    public function get_name() {
        return 'mautic_membership_tiers';
    }

    public function get_title() {
        return __( 'Membership Tiers Table', 'mautic-theme' );
    }

    public function get_icon() {
        return 'eicon-table';
    }

    public function get_categories() {
        return [ 'mautic-widgets' ];
    }

    public function get_style_depends() {
        return [ 'mautic-membership', 'mautic-membership-tiers' ];
    }

    public function get_script_depends() {
        return [ 'mautic-membership-tiers' ];
    }

    public function get_keywords() {
        return [ 'membership', 'pricing', 'tiers', 'table', 'corporate' ];
    }

    protected function register_controls() {

        $tier_names = [];
        foreach ( self::tiers() as $tier ) {
            $tier_names[ $tier['name'] ] = $tier['name'];
        }

        $countries = [];
        foreach ( self::country_tiers() as $list ) {
            foreach ( $list as $country ) {
                $countries[ $country ] = $country;
            }
        }
        ksort( $countries );

        $this->start_controls_section( 'content', [ 'label' => __( 'Content', 'mautic-theme' ) ] );

        $this->add_control( 'popular_tier', [
            'label'       => __( 'Highlighted tier', 'mautic-theme' ),
            'type'        => Controls_Manager::SELECT,
            'options'     => $tier_names,
            'default'     => 'Bronze',
            'description' => __( 'Shown with the "Most popular" badge.', 'mautic-theme' ),
        ] );

        $this->add_control( 'default_country', [
            'label'   => __( 'Country selected on load', 'mautic-theme' ),
            'type'    => Controls_Manager::SELECT,
            'options' => $countries,
            'default' => 'United States',
        ] );

        $this->add_control( 'selector_label', [
            'label'       => __( 'Selector label', 'mautic-theme' ),
            'type'        => Controls_Manager::TEXT,
            'default'     => __( 'Company HQ country', 'mautic-theme' ),
            'label_block' => true,
        ] );

        $this->add_control( 'table_caption', [
            'label'       => __( 'Table caption', 'mautic-theme' ),
            'type'        => Controls_Manager::TEXT,
            'default'     => __( 'Corporate membership tiers, prices and benefits', 'mautic-theme' ),
            'label_block' => true,
            'description' => __( 'Read out by screen readers to introduce the table. Visually hidden.', 'mautic-theme' ),
        ] );

        $this->add_control( 'smallprint', [
            'label'   => __( 'Small print', 'mautic-theme' ),
            'type'    => Controls_Manager::WYSIWYG,
            'default' => __(
                '<p>Where inherited benefits are of the same type, they are replaced rather than added together. For example, if Bronze includes 1 forum ad campaign per year and Silver includes 2, a Silver member receives 2 in total.</p>'
                . '<p>Tier 2 and Tier 3 pricing requires evidence that your company headquarters is in the country selected. Need an invoice, or can\'t see your country or currency? Email <a href="mailto:membership@mautic.org">membership@mautic.org</a> or <a href="https://mau.tc/corporate-tiers">download the full tier chart</a>.</p>',
                'mautic-theme'
            ),
        ] );

        $this->end_controls_section();

        $this->start_controls_section( 'anchor', [ 'label' => __( 'Anchor', 'mautic-theme' ) ] );

        $this->add_control( 'anchor_id', [
            'label'       => __( 'Anchor ID', 'mautic-theme' ),
            'type'        => Controls_Manager::TEXT,
            'default'     => 'tiers',
            'description' => __( 'Links elsewhere on the page scroll here. Leave blank for none.', 'mautic-theme' ),
        ] );

        $this->end_controls_section();
    }

    /**
     * Render one benefit cell, with a text equivalent for the tick and dash glyphs.
     */
    private function render_cell( $value, $is_popular ) {
        $classes = [ 'mautic-tiers__cell' ];
        if ( $is_popular ) {
            $classes[] = 'is-popular';
        }

        if ( 'Y' === $value ) {
            $classes[] = 'is-yes';
            $inner = '<span aria-hidden="true">&#10003;</span><span class="mautic-sr-only">' . esc_html__( 'Included', 'mautic-theme' ) . '</span>';
        } elseif ( 'N' === $value ) {
            $classes[] = 'is-no';
            $inner = '<span aria-hidden="true">&mdash;</span><span class="mautic-sr-only">' . esc_html__( 'Not included', 'mautic-theme' ) . '</span>';
        } else {
            $classes[] = 'is-value';
            $inner = esc_html( $value );
        }

        printf(
            '<td class="%1$s">%2$s</td>',
            esc_attr( implode( ' ', $classes ) ),
            $inner // phpcs:ignore WordPress.Security.EscapeOutput -- assembled from escaped parts above.
        );
    }

    protected function render() {
        $settings = $this->get_settings_for_display();

        $tiers     = self::tiers();
        $groups    = self::benefit_groups();
        $countries = self::country_tiers();
        $count     = count( $tiers );

        // Which column carries the "Most popular" badge.
        $popular_index = -1;
        foreach ( $tiers as $index => $tier ) {
            if ( $tier['name'] === ( $settings['popular_tier'] ?? '' ) ) {
                $popular_index = $index;
            }
        }

        // country name => regional tier index (0, 1 or 2).
        $country_index = [];
        foreach ( $countries as $tier_number => $list ) {
            foreach ( $list as $country ) {
                $country_index[ $country ] = (int) $tier_number - 1;
            }
        }
        ksort( $country_index );

        $default_country = $settings['default_country'] ?? 'United States';
        if ( ! isset( $country_index[ $default_country ] ) ) {
            $default_country = 'United States';
        }
        $default_index = $country_index[ $default_country ] ?? 0;

        $uid          = 'mautic-tiers-' . $this->get_id();
        $select_id    = $uid . '-country';
        $note_id      = $uid . '-note';
        $caption_id   = $uid . '-caption';
        $anchor_id    = ! empty( $settings['anchor_id'] ) ? sanitize_title( $settings['anchor_id'] ) : '';
        $new_tab_note = esc_html__( '(opens in a new tab)', 'mautic-theme' );
        ?>
        <div class="mautic-tiers mautic-anchor"<?php echo $anchor_id ? ' id="' . esc_attr( $anchor_id ) . '"' : ''; ?> data-mautic-tiers>

            <div class="mautic-tiers__picker">
                <label class="mautic-tiers__label" for="<?php echo esc_attr( $select_id ); ?>">
                    <?php echo esc_html( $settings['selector_label'] ?? '' ); ?>
                </label>
                <select class="mautic-tiers__select" id="<?php echo esc_attr( $select_id ); ?>" data-mautic-tiers-select>
                    <?php foreach ( $country_index as $country => $index ) : ?>
                        <option value="<?php echo esc_attr( $index ); ?>" <?php selected( $country, $default_country ); ?>>
                            <?php echo esc_html( $country ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="mautic-tiers__note" id="<?php echo esc_attr( $note_id ); ?>" role="status" data-mautic-tiers-note
                    data-note-0="<?php esc_attr_e( 'Standard pricing', 'mautic-theme' ); ?>"
                    data-note-1="<?php esc_attr_e( 'Tier 2 regional pricing (evidence of HQ required)', 'mautic-theme' ); ?>"
                    data-note-2="<?php esc_attr_e( 'Tier 3 regional pricing (evidence of HQ required)', 'mautic-theme' ); ?>">
                    <?php
                    $notes = [
                        __( 'Standard pricing', 'mautic-theme' ),
                        __( 'Tier 2 regional pricing (evidence of HQ required)', 'mautic-theme' ),
                        __( 'Tier 3 regional pricing (evidence of HQ required)', 'mautic-theme' ),
                    ];
                    echo esc_html( $notes[ $default_index ] );
                    ?>
                </p>
            </div>

            <div class="mautic-tiers__scroll" tabindex="0" role="region" aria-labelledby="<?php echo esc_attr( $caption_id ); ?>">
                <table class="mautic-tiers__table">
                    <caption class="mautic-sr-only" id="<?php echo esc_attr( $caption_id ); ?>">
                        <?php echo esc_html( $settings['table_caption'] ?? '' ); ?>
                    </caption>
                    <thead>
                        <tr>
                            <th scope="col" class="mautic-tiers__corner"><span class="mautic-sr-only"><?php esc_html_e( 'Benefit', 'mautic-theme' ); ?></span></th>
                            <?php foreach ( $tiers as $index => $tier ) : ?>
                                <th scope="col" class="mautic-tiers__head<?php echo $index === $popular_index ? ' is-popular' : ''; ?>">
                                    <?php if ( $index === $popular_index ) : ?>
                                        <span class="mautic-tiers__badge"><?php esc_html_e( 'Most popular', 'mautic-theme' ); ?></span>
                                    <?php endif; ?>
                                    <span class="mautic-tiers__name"><?php echo esc_html( $tier['name'] ); ?></span>
                                    <span class="mautic-tiers__price"
                                        data-mautic-tiers-price
                                        data-price-0="<?php echo esc_attr( '$' . number_format( $tier['prices'][0] ) ); ?>"
                                        data-price-1="<?php echo esc_attr( '$' . number_format( $tier['prices'][1] ) ); ?>"
                                        data-price-2="<?php echo esc_attr( '$' . number_format( $tier['prices'][2] ) ); ?>">
                                        <?php echo esc_html( '$' . number_format( $tier['prices'][ $default_index ] ) ); ?>
                                    </span>
                                    <span class="mautic-tiers__per"><?php esc_html_e( 'per year', 'mautic-theme' ); ?></span>
                                    <a class="mautic-tiers__join" href="<?php echo esc_url( $tier['url'] ); ?>" target="_blank" rel="noopener">
                                        <?php esc_html_e( 'Join', 'mautic-theme' ); ?>
                                        <span class="mautic-sr-only">
                                            <?php
                                            /* translators: %s: membership tier name. */
                                            printf( esc_html__( 'the %s tier', 'mautic-theme' ), esc_html( $tier['name'] ) );
                                            echo ' ' . esc_html( $new_tab_note );
                                            ?>
                                        </span>
                                    </a>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>

                    <?php foreach ( $groups as $group ) : ?>
                        <tbody class="mautic-tiers__group">
                            <tr class="mautic-tiers__grouprow">
                                <th scope="rowgroup" colspan="<?php echo esc_attr( $count + 1 ); ?>">
                                    <?php echo esc_html( $group['group'] ); ?>
                                </th>
                            </tr>
                            <?php foreach ( $group['rows'] as $row ) : ?>
                                <tr>
                                    <th scope="row" class="mautic-tiers__rowhead"><?php echo esc_html( $row[0] ); ?></th>
                                    <?php foreach ( $row[1] as $index => $value ) : ?>
                                        <?php $this->render_cell( $value, $index === $popular_index ); ?>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    <?php endforeach; ?>
                </table>
            </div>

            <?php if ( ! empty( $settings['smallprint'] ) ) : ?>
                <div class="mautic-tiers__smallprint">
                    <?php echo $this->parse_text_editor( $settings['smallprint'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}
