<?php

namespace BBElementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Scheme_Typography;
use Elementor\Scheme_Color;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Border;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Elementor Header Bar
 *
 * Elementor widget for header bar.
 *
 * @since 1.0.0
 */
class Header_Bar extends Widget_Base {

	/**
	 * Retrieve the widget name.
	 *
	 * @return string Widget name.
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function get_name() {
		return 'header-bar';
	}

	/**
	 * Retrieve the widget title.
	 *
	 * @return string Widget title.
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function get_title() {
		return __( 'Header Bar', 'buddyboss-theme' );
	}

	/**
	 * Retrieve the widget icon.
	 *
	 * @return string Widget icon.
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function get_icon() {
		return 'eicon-select';
	}

	/**
	 * Retrieve the list of categories the widget belongs to.
	 *
	 * Used to determine where to display the widget in the editor.
	 *
	 * Note that currently Elementor supports only one category.
	 * When multiple categories passed, Elementor uses the first one.
	 *
	 * @return array Widget categories.
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function get_categories() {
		return array( 'buddyboss-elements' );
	}

	/**
	 * Retrieve the list of scripts the widget depended on.
	 *
	 * Used to set scripts dependencies required to run the widget.
	 *
	 * @return array Widget scripts dependencies.
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function get_script_depends() {
		return array( 'elementor-bb-frontend' );
	}

	/**
	 * Register the widget controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @since 1.0.0
	 *
	 * @access protected
	 */
	protected function _register_controls() {
		$this->start_controls_section(
			'section_content',
			array(
				'label' => __( 'Content', 'buddyboss-theme' ),
			)
		);

		$this->add_control(
			'profile_dropdown',
			array(
				'label'        => __( 'Profile Dropdown', 'buddyboss-theme' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'buddyboss-theme' ),
				'label_off'    => __( 'Off', 'buddyboss-theme' ),
				'return_value' => 'inline-block',
				'default'      => 'inline-block',
				'selectors'    => array(
					'{{WRAPPER}} .user-wrap' => 'display: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'element_separator',
			array(
				'label'        => __( 'Separator', 'buddyboss-theme' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'buddyboss-theme' ),
				'label_off'    => __( 'Off', 'buddyboss-theme' ),
				'return_value' => 'inline-block',
				'default'      => 'inline-block',
				'selectors'    => array(
					'{{WRAPPER}} .bb-separator' => 'display: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'search_icon_switch',
			array(
				'label'        => __( 'Search', 'buddyboss-theme' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'buddyboss-theme' ),
				'label_off'    => __( 'Off', 'buddyboss-theme' ),
				'return_value' => 'flex',
				'default'      => 'flex',
				'selectors'    => array(
					'{{WRAPPER}} .header-search-link' => 'display: {{VALUE}};',
				),
			)
		);

		if ( function_exists( 'bp_is_active' ) && bp_is_active( 'messages' ) ) :
			$this->add_control(
				'messages_icon_switch',
				array(
					'label'        => __( 'Messages', 'buddyboss-theme' ),
					'type'         => Controls_Manager::SWITCHER,
					'label_on'     => __( 'On', 'buddyboss-theme' ),
					'label_off'    => __( 'Off', 'buddyboss-theme' ),
					'return_value' => 'inline-block',
					'default'      => 'inline-block',
					'selectors'    => array(
						'{{WRAPPER}} #header-messages-dropdown-elem' => 'display: {{VALUE}};',
					),
				)
			);
		endif;

		if ( function_exists( 'bp_is_active' ) && bp_is_active( 'notifications' ) ) :
			$this->add_control(
				'notifications_icon_switch',
				array(
					'label'        => __( 'Notifications', 'buddyboss-theme' ),
					'type'         => Controls_Manager::SWITCHER,
					'label_on'     => __( 'On', 'buddyboss-theme' ),
					'label_off'    => __( 'Off', 'buddyboss-theme' ),
					'return_value' => 'inline-block',
					'default'      => 'inline-block',
					'selectors'    => array(
						'{{WRAPPER}} #header-notifications-dropdown-elem' => 'display: {{VALUE}};',
					),
				)
			);
		endif;

		if ( class_exists( 'WooCommerce' ) ) :
			$this->add_control(
				'cart_icon_switch',
				array(
					'label'        => __( 'Cart', 'buddyboss-theme' ),
					'type'         => Controls_Manager::SWITCHER,
					'label_on'     => __( 'On', 'buddyboss-theme' ),
					'label_off'    => __( 'Off', 'buddyboss-theme' ),
					'return_value' => 'inline-block',
					'default'      => 'inline-block',
					'selectors'    => array(
						'{{WRAPPER}} .header-cart-link-wrap' => 'display: {{VALUE}};',
					),
				)
			);
		endif;

		if ( class_exists( 'SFWD_LMS' ) ) :
			$this->add_control(
				'dark_icon_switch',
				array(
					'label'        => __( 'Dark Mode', 'buddyboss-theme' ),
					'type'         => Controls_Manager::SWITCHER,
					'label_on'     => __( 'On', 'buddyboss-theme' ),
					'label_off'    => __( 'Off', 'buddyboss-theme' ),
					'return_value' => 'inline-block',
					'default'      => 'inline-block',
					'selectors'    => array(
						'{{WRAPPER}} #bb-toggle-theme' => 'display: {{VALUE}};',
					),
				)
			);

			$this->add_control(
				'sidebartoggle_icon_switch',
				array(
					'label'        => __( 'Sidebar Toggle', 'buddyboss-theme' ),
					'type'         => Controls_Manager::SWITCHER,
					'label_on'     => __( 'On', 'buddyboss-theme' ),
					'label_off'    => __( 'Off', 'buddyboss-theme' ),
					'return_value' => 'inline-block',
					'default'      => 'inline-block',
					'selectors'    => array(
						'{{WRAPPER}} .header-minimize-link' => 'display: {{VALUE}};',
						'{{WRAPPER}} .header-maximize-link' => 'display: {{VALUE}};',
					),
				)
			);
		endif;

		$this->end_controls_section();

		$this->start_controls_section(
			'section_icons',
			array(
				'label' => __( 'Icons', 'buddyboss-theme' ),
			)
		);

		$this->add_control(
			'search_icon',
			array(
				'label'                  => __( 'Search Icon', 'buddyboss-theme' ),
				'description'            => __( 'Replace default search icon with one of your choice.', 'buddyboss-theme' ),
				'type'                   => \Elementor\Controls_Manager::ICONS,
				'skin'                   => 'inline',
				'exclude_inline_options' => array(
					'svg',
				),
			)
		);

		if ( function_exists( 'bp_is_active' ) && bp_is_active( 'messages' ) ) :
			$this->add_control(
				'messages_icon',
				array(
					'label'                  => __( 'Messages Icon', 'buddyboss-theme' ),
					'description'            => __( 'Replace default messages icon with one of your choice.', 'buddyboss-theme' ),
					'type'                   => \Elementor\Controls_Manager::ICONS,
					'skin'                   => 'inline',
					'exclude_inline_options' => array(
						'svg',
					),
				)
			);
		endif;

		if ( function_exists( 'bp_is_active' ) && bp_is_active( 'notifications' ) ) :
			$this->add_control(
				'notifications_icon',
				array(
					'label'                  => __( 'Notifications Icon', 'buddyboss-theme' ),
					'description'            => __( 'Replace default notifications icon with one of your choice.', 'buddyboss-theme' ),
					'type'                   => \Elementor\Controls_Manager::ICONS,
					'skin'                   => 'inline',
					'exclude_inline_options' => array(
						'svg',
					),
				)
			);
		endif;

		if ( class_exists( 'WooCommerce' ) ) :
			$this->add_control(
				'cart_icon',
				array(
					'label'                  => __( 'Cart Icon', 'buddyboss-theme' ),
					'description'            => __( 'Replace default cart icon with one of your choice.', 'buddyboss-theme' ),
					'type'                   => \Elementor\Controls_Manager::ICONS,
					'skin'                   => 'inline',
					'exclude_inline_options' => array(
						'svg',
					),
				)
			);
		endif;

		if ( class_exists( 'SFWD_LMS' ) ) :
			$this->add_control(
				'dark_icon',
				array(
					'label'                  => __( 'Dark Mode Icon', 'buddyboss-theme' ),
					'description'            => __( 'Replace default dark mode icon with one of your choice.', 'buddyboss-theme' ),
					'type'                   => \Elementor\Controls_Manager::ICONS,
					'skin'                   => 'inline',
					'exclude_inline_options' => array(
						'svg',
					),
				)
			);

			$this->add_control(
				'sidebartoggle_icon',
				array(
					'label'                  => __( 'Toggle Sidebar Icon', 'buddyboss-theme' ),
					'description'            => __( 'Replace default toggle sidebar icon with one of your choice.', 'buddyboss-theme' ),
					'type'                   => \Elementor\Controls_Manager::ICONS,
					'skin'                   => 'inline',
					'exclude_inline_options' => array(
						'svg',
					),
				)
			);
		endif;

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_layout',
			array(
				'label' => __( 'Layout', 'buddyboss-theme' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'content_align',
			array(
				'label'   => __( 'Alignment', 'buddyboss-theme' ),
				'type'    => \Elementor\Controls_Manager::CHOOSE,
				'options' => array(
					'left'   => array(
						'title' => __( 'Left', 'buddyboss-theme' ),
						'icon'  => 'fa fa-align-left',
					),
					'center' => array(
						'title' => __( 'Center', 'buddyboss-theme' ),
						'icon'  => 'fa fa-align-center',
					),
					'right'  => array(
						'title' => __( 'Right', 'buddyboss-theme' ),
						'icon'  => 'fa fa-align-right',
					),
				),
				'default' => 'right',
				'toggle'  => true,
			)
		);

		$this->add_control(
			'space_between',
			array(
				'label'      => __( 'Space Between', 'buddyboss-theme' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 5,
						'max'  => 50,
						'step' => 1,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 10,
				),
				'selectors'  => array(
					'{{WRAPPER}} .header-aside-inner > *:not(.bb-separator)' => 'padding: 0 {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} #header-messages-dropdown-elem'             => 'padding: 0 {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} #header-notifications-dropdown-elem'        => 'padding: 0 {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'separator',
			array(
				'label'     => __( 'Separator', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'separator_width',
			array(
				'label'      => __( 'Separator Width', 'buddyboss-theme' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 1,
						'max'  => 10,
						'step' => 1,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 1,
				),
				'selectors'  => array(
					'{{WRAPPER}} .bb-separator' => 'width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'separator_color',
			array(
				'label'     => __( 'Separator Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'rgba(0, 0, 0, 0.1)',
				'selectors' => array(
					'{{WRAPPER}} .bb-separator' => 'background-color: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'tooltips_options',
			array(
				'label'     => __( 'Tooltips', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography_tooltips',
				'label'    => __( 'Typography Tooltips', 'buddyboss-theme' ),
				'selector' => '{{WRAPPER}} [data-balloon]:after',
			)
		);

		$this->add_control(
			'counter_options',
			array(
				'label'     => __( 'Counter', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'count_bgcolor',
			array(
				'label'     => __( 'Counter Background Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#EF3E46',
				'selectors' => array(
					'{{WRAPPER}} .notification-wrap span.count' => 'background-color: {{VALUE}}',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'counter_shadow',
				'label'    => __( 'Counter Shadow', 'buddyboss-theme' ),
				'selector' => '{{WRAPPER}} .notification-wrap span.count',
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_icons',
			array(
				'label' => __( 'Icons', 'buddyboss-theme' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'icons_size',
			array(
				'label'      => __( 'Icons Size', 'buddyboss-theme' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 15,
						'max'  => 40,
						'step' => 1,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 21,
				),
				'selectors'  => array(
					'{{WRAPPER}} .header-aside .header-search-link i'                => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .header-aside .messages-wrap > a i'                 => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .header-aside span[data-balloon="Notifications"] i' => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .header-aside a.header-cart-link i'                 => 'font-size: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			array(
				'name'     => 'icons_shadow',
				'label'    => __( 'Icons Shadow', 'buddyboss-theme' ),
				'selector' => '{{WRAPPER}} .header-aside i:not(.bb-icon-angle-down)',
			)
		);

		$this->add_control(
			'separator_icons',
			array(
				'label'     => __( 'Icons Colors', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'icons_color',
			array(
				'label'     => __( 'All Icons', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#939597',
				'selectors' => array(
					'{{WRAPPER}} #header-aside.header-aside .header-search-link i'                => 'color: {{VALUE}}',
					'{{WRAPPER}} #header-aside.header-aside .messages-wrap > a i'                 => 'color: {{VALUE}}',
					'{{WRAPPER}} #header-aside.header-aside span[data-balloon="Notifications"] i' => 'color: {{VALUE}}',
					'{{WRAPPER}} #header-aside.header-aside a.header-cart-link i'                 => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'search_icon_color',
			array(
				'label'     => __( 'Search Icon', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => array(
					'{{WRAPPER}} .header-aside .header-search-link i' => 'color: {{VALUE}} !important',
				),
			)
		);

		if ( function_exists( 'bp_is_active' ) && bp_is_active( 'messages' ) ) :
			$this->add_control(
				'messages_icon_color',
				array(
					'label'     => __( 'Messages Icon', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'default'   => '',
					'selectors' => array(
						'{{WRAPPER}} .header-aside .messages-wrap > a i' => 'color: {{VALUE}} !important',
					),
				)
			);
		endif;

		if ( function_exists( 'bp_is_active' ) && bp_is_active( 'notifications' ) ) :
			$this->add_control(
				'notifications_icon_color',
				array(
					'label'     => __( 'Notifications Icon', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'default'   => '',
					'selectors' => array(
						'{{WRAPPER}} .header-aside span[data-balloon="Notifications"] i' => 'color: {{VALUE}} !important',
					),
				)
			);
		endif;

		if ( class_exists( 'WooCommerce' ) ) :
			$this->add_control(
				'cart_icon_color',
				array(
					'label'     => __( 'Cart Icon', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'default'   => '',
					'selectors' => array(
						'{{WRAPPER}} .header-aside a.header-cart-link i' => 'color: {{VALUE}} !important',
					),
				)
			);
		endif;

		if ( class_exists( 'SFWD_LMS' ) ) :
			$this->add_control(
				'dark_icon_color',
				array(
					'label'     => __( 'Dark Icon', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'default'   => '',
					'selectors' => array(
						'{{WRAPPER}} .header-aside a#bb-toggle-theme i' => 'color: {{VALUE}} !important',
					),
				)
			);

			$this->add_control(
				'sidebartoggle_icon_color',
				array(
					'label'     => __( 'Sidebar Toggle Icon', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'default'   => '',
					'selectors' => array(
						'{{WRAPPER}} .header-aside a.course-toggle-view i' => 'color: {{VALUE}} !important',
					),
				)
			);
		endif;

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_profile',
			array(
				'label' => __( 'Profile Navigation', 'buddyboss-theme' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'separator_user_name',
			array(
				'label'     => __( 'Display Name', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography_user_link',
				'label'    => __( 'Typography Display Name', 'buddyboss-theme' ),
				'selector' => '{{WRAPPER}} .site-header--elementor .user-wrap a span.user-name',
			)
		);

		$this->start_controls_tabs(
			'color_name_tabs'
		);

		$this->start_controls_tab(
			'color_name_normal_tab',
			array(
				'label' => __( 'Normal', 'buddyboss-theme' ),
			)
		);

		$this->add_control(
			'user_name_item_color',
			array(
				'label'     => __( 'Display Name Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .site-header--elementor .user-wrap > a.user-link span.user-name' => 'color: {{VALUE}}',
					'{{WRAPPER}} .site-header--elementor #header-aside .user-wrap > a.user-link i' => 'color: {{VALUE}}',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'color_name_hover_tab',
			array(
				'label' => __( 'Hover', 'buddyboss-theme' ),
			)
		);

		$this->add_control(
			'user_name_item_color_hover',
			array(
				'label'     => __( 'Display Name Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#007CFF',
				'selectors' => array(
					'{{WRAPPER}} .site-header--elementor .user-wrap > a.user-link:hover span.user-name' => 'color: {{VALUE}}',
					'{{WRAPPER}} .site-header--elementor #header-aside .user-wrap > a.user-link:hover i' => 'color: {{VALUE}}',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'separator_avatar',
			array(
				'label'     => __( 'Avatar', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'avatar_size',
			array(
				'label'      => __( 'Width', 'buddyboss-theme' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 25,
						'max'  => 50,
						'step' => 1,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 36,
				),
				'selectors'  => array(
					'{{WRAPPER}} .user-link img' => 'max-width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'avatar_border_style',
			array(
				'label'   => __( 'Border Style', 'buddyboss-theme' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'none',
				'options' => array(
					'solid'  => __( 'Solid', 'buddyboss-theme' ),
					'dashed' => __( 'Dashed', 'buddyboss-theme' ),
					'dotted' => __( 'Dotted', 'buddyboss-theme' ),
					'double' => __( 'Double', 'buddyboss-theme' ),
					'none'   => __( 'None', 'buddyboss-theme' ),
				),
			)
		);

		$this->add_control(
			'avatar_border_width',
			array(
				'label'      => __( 'Border Width', 'buddyboss-theme' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 1,
						'max'  => 5,
						'step' => 1,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 1,
				),
				'selectors'  => array(
					'{{WRAPPER}} .user-link img' => 'border-width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'avatar_border_color',
			array(
				'label'     => __( 'Border Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#939597',
				'selectors' => array(
					'{{WRAPPER}} .user-link img' => 'border-color: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'avatar_border_radius',
			array(
				'label'      => __( 'Border Radius', 'buddyboss-theme' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '%' ),
				'range'      => array(
					'%' => array(
						'min'  => 0,
						'max'  => 50,
						'step' => 1,
					),
				),
				'default'    => array(
					'unit' => '%',
					'size' => 50,
				),
				'selectors'  => array(
					'{{WRAPPER}} .user-link img' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'separator_dropdown',
			array(
				'label'     => __( 'Dropdown', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'separator_dropdown_user_name',
			array(
				'label'     => __( 'Display Name', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->start_controls_tabs(
			'color_dropdown_name_tabs'
		);

		$this->start_controls_tab(
			'color_dropdown_name_normal_tab',
			array(
				'label' => __( 'Normal', 'buddyboss-theme' ),
			)
		);

		$this->add_control(
			'dropdown_user_name_item_color',
			array(
				'label'     => __( 'Display Name Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#122b46',
				'selectors' => array(
					'{{WRAPPER}}  .site-header--elementor .user-wrap .sub-menu a.user-link span.user-name' => 'color: {{VALUE}}',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'color_dropdown_name_hover_tab',
			array(
				'label' => __( 'Hover', 'buddyboss-theme' ),
			)
		);

		$this->add_control(
			'dropdown_user_name_item_color_hover',
			array(
				'label'     => __( 'Display Name Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#007CFF',
				'selectors' => array(
					'{{WRAPPER}}  .site-header--elementor .user-wrap .sub-menu a.user-link:hover span.user-name' => 'color: {{VALUE}}',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography_menu',
				'label'    => __( 'Typography Menu', 'buddyboss-theme' ),
				'selector' => '{{WRAPPER}} .site-header--elementor .sub-menu a:not(.user-link), {{WRAPPER}} .site-header--elementor .sub-menu a span.user-mention',
			)
		);

		$this->add_control(
			'dropdown_bgcolor',
			array(
				'label'     => __( 'Dropdown Background Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .site-header .sub-menu' => 'background-color: {{VALUE}}',
					'{{WRAPPER}} .user-wrap-container > .sub-menu:before' => 'border-color: {{VALUE}} {{VALUE}} transparent transparent',
					'{{WRAPPER}} .header-aside .wrapper li .wrapper' => 'background-color: {{VALUE}}',
					'{{WRAPPER}} .user-wrap-container .sub-menu .ab-sub-wrapper .ab-submenu' => 'background-color: {{VALUE}}',
					'{{WRAPPER}} .header-aside .wrapper li .wrapper:before' => 'background: {{VALUE}}',
				),
			)
		);

		$this->start_controls_tabs(
			'dropdown_menu_tabs'
		);

		$this->start_controls_tab(
			'dropdown_normal_tab',
			array(
				'label' => __( 'Normal', 'buddyboss-theme' ),
			)
		);

		$this->add_control(
			'dropdown_menu_item_bgcolor',
			array(
				'label'     => __( 'Menu Item Background Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'transparent',
				'selectors' => array(
					'{{WRAPPER}} .site-header .sub-menu a' => 'background-color: {{VALUE}}',
					'{{WRAPPER}} .site-header .sub-menu .ab-submenu a' => 'background-color: transparent',
				),
			)
		);

		$this->add_control(
			'dropdown_menu_item_color',
			array(
				'label'     => __( 'Menu Item Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#939597',
				'selectors' => array(
					'{{WRAPPER}} .site-header .sub-menu a' => 'color: {{VALUE}}',
					'{{WRAPPER}} .site-header .sub-menu a .user-mention' => 'color: {{VALUE}}',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'dropdown_hover_tab',
			array(
				'label' => __( 'Hover', 'buddyboss-theme' ),
			)
		);

		$this->add_control(
			'dropdown_menu_item_bgcolor_hover',
			array(
				'label'     => __( 'Menu Item Background Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .site-header .sub-menu a:hover'             => 'background-color: {{VALUE}}',
					'{{WRAPPER}} .site-header .sub-menu .ab-submenu a:hover' => 'background-color: transparent',
				),
			)
		);

		$this->add_control(
			'dropdown_menu_item_color_hover',
			array(
				'label'     => __( 'Menu Item Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#939597',
				'selectors' => array(
					'{{WRAPPER}} .site-header .sub-menu a:hover' => 'color: {{VALUE}}',
					'{{WRAPPER}} .site-header .sub-menu a:hover .user-mention' => 'color: {{VALUE}}',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_signout',
			array(
				'label' => __( 'Logged Out', 'buddyboss-theme' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'separator_sign_in',
			array(
				'label'     => __( 'Sign In', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography_sign_in',
				'label'    => __( 'Typography Sign In', 'buddyboss-theme' ),
				'selector' => '{{WRAPPER}} .site-header--elementor .bb-header-buttons a.signin-button',
			)
		);

		$this->start_controls_tabs(
			'color_signin_tabs'
		);

		$this->start_controls_tab(
			'color_signin_normal_tab',
			array(
				'label' => __( 'Normal', 'buddyboss-theme' ),
			)
		);

		$this->add_control(
			'signin_item_color',
			array(
				'label'     => __( 'Sign In Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}}  .site-header--elementor .bb-header-buttons a.signin-button' => 'color: {{VALUE}}',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'color_signin_hover_tab',
			array(
				'label' => __( 'Hover', 'buddyboss-theme' ),
			)
		);

		$this->add_control(
			'signin_item_color_hover',
			array(
				'label'     => __( 'Sign In Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}}  .site-header--elementor .bb-header-buttons a.signin-button:hover' => 'color: {{VALUE}}',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'separator_sign_up',
			array(
				'label'     => __( 'Sign Up', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography_sign_up',
				'label'    => __( 'Typography Sign Up', 'buddyboss-theme' ),
				'selector' => '{{WRAPPER}} .site-header--elementor .bb-header-buttons a.singup',
			)
		);

		$this->start_controls_tabs(
			'color_signup_tabs'
		);

		$this->start_controls_tab(
			'color_signup_normal_tab',
			array(
				'label' => __( 'Normal', 'buddyboss-theme' ),
			)
		);

		$this->add_control(
			'signup_item_color',
			array(
				'label'     => __( 'Sign Up Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .site-header--elementor .bb-header-buttons a.singup' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'signup_item_bgr_color',
			array(
				'label'     => __( 'Sign Up Background Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .site-header--elementor .bb-header-buttons a.singup' => 'background-color: {{VALUE}}',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'color_signup_hover_tab',
			array(
				'label' => __( 'Hover', 'buddyboss-theme' ),
			)
		);

		$this->add_control(
			'signup_item_color_hover',
			array(
				'label'     => __( 'Sign Up Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .site-header--elementor .bb-header-buttons a.singup:hover' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'signup_item_bgr_color_hover',
			array(
				'label'     => __( 'Sign Up Background Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .site-header--elementor .bb-header-buttons a.singup:hover' => 'background-color: {{VALUE}}',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'        => 'signup_border',
				'label'       => __( 'Border', 'buddyboss-theme' ),
				'placeholder' => '1px',
				'default'     => '1px',
				'selector'    => '{{WRAPPER}} .site-header--elementor .bb-header-buttons a.singup',
				'separator'   => 'before',
			]
		);

		$this->add_control(
			'signup_border_radius',
			[
				'label'      => __( 'Border Radius', 'buddyboss-theme' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .site-header--elementor .bb-header-buttons a.singup' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

	}

	/**
	 * Render the widget output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @since 1.0.0
	 *
	 * @access protected
	 */
	protected function render() {
		$settings = $this->get_settings();

		$settings_align = $settings['content_align'];

		$settings_search_ico = $settings['search_icon']['value'];
		$settings_messages_icon = ( function_exists( 'bp_is_active' ) && bp_is_active( 'messages' ) ) ? $settings['messages_icon']['value'] : '';
		$settings_notifications_icon = ( function_exists( 'bp_is_active' ) && bp_is_active( 'notifications' ) ) ? $settings['notifications_icon']['value'] : '';
		$settings_cart_icon = ( class_exists( 'WooCommerce' ) ) ? $settings['cart_icon']['value'] : '';
		$settings_dark_icon = ( class_exists( 'SFWD_LMS' ) ) ? $settings['dark_icon']['value'] : '';
		$settings_sidebartoggle_icon = ( class_exists( 'SFWD_LMS' ) ) ? $settings['sidebartoggle_icon']['value'] : '';
		$settings_avatar_border = $settings['avatar_border_style'];

		echo '<div class="site-header site-header--elementor site-header--align-' . esc_attr( $settings_align ) . ' avatar-' . esc_attr( $settings_avatar_border ) . '" data-search-icon="' . esc_attr( $settings_search_ico ) . '" data-messages-icon="' . esc_attr( $settings_messages_icon ) . '" data-notifications-icon="' . esc_attr( $settings_notifications_icon ) . '" data-cart-icon="' . esc_attr( $settings_cart_icon ) . '" data-dark-icon="' . esc_attr( $settings_dark_icon ) . '" data-sidebartoggle-icon="' . esc_attr( $settings_sidebartoggle_icon ) . '">';
		get_template_part( 'template-parts/header-aside' );
		?>
		<div class="header-search-wrap header-search-wrap--elementor">
			<div class="container">
				<?php
				add_filter( 'search_placeholder', 'buddyboss_search_input_placeholder_text' );
				get_search_form();
				remove_filter( 'search_placeholder', 'buddyboss_search_input_placeholder_text' );
				?>
				<a href="#" class="close-search"><i class="bb-icon-close-circle"></i></a>
			</div>
		</div>
		<?php
		echo '</div>';

	}

	/**
	 * Render the widget output in the editor.
	 *
	 * Written as a Backbone JavaScript template and used to generate the live preview.
	 *
	 * @since 1.0.0
	 *
	 * @access protected
	 */
	/*
	protected function _content_template() {

	}
	*/
}
