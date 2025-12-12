<?php

// SPDX-FileCopyrightText: 2018-2025 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: GPL-3.0-or-later
namespace DynamicVisibilityForElementor\Extensions\DynamicVisibility\Triggers;

use Elementor\Controls_Manager;
class Random extends Base {
	/**
	 * @param \Elementor\Element_Base $element
	 * @return void
	 */
	public function register_controls( $element ) {
		$element->add_control(
			'dce_visibility_random',
			[
				'label' => esc_html__( 'Random', 'dynamic-visibility-for-elementor' ),
				'type' => Controls_Manager::SLIDER,
				'description' => esc_html__( 'Choose the percentage probability that the condition is true', 'dynamic-visibility-for-elementor' ),
				'size_units' => [ '%' ],
				'range' => [
					'%' => [
						'min' => 0,
						'max' => 100,
					],
				],
			]
		);
	}

	/**
	 * @param array<string,mixed> $settings
	 * @param array<string,mixed> &$triggers
	 * @param array<string,mixed> &$conditions
	 * @param int &$triggers_n
	 * @param \Elementor\Element_Base $element
	 * @return void
	 */
	public function check_conditions( $settings, &$triggers, &$conditions, &$triggers_n, $element ) {
		if ( ! empty( $settings['dce_visibility_random']['size'] ) ) {
			$triggers['dce_visibility_random'] = esc_html__( 'Random', 'dynamic-visibility-for-elementor' );
			$rand = mt_rand( 1, 100 );
			++$triggers_n;
			if ( $rand <= $settings['dce_visibility_random']['size'] ) {
				$conditions['dce_visibility_random'] = esc_html__( 'Random', 'dynamic-visibility-for-elementor' );
			}
		}
	}
}
