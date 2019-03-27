<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

printf(
	'<a href="%1$s" data-id="%2$d" data-backup-src="%3$s" data-full-src="%4$s" data-full-width="%5$d" data-full-height="%6$d" data-target="#imagify-comparison-%2$d" class="imagify-compare-images imagify-modal-trigger hide-if-no-js">%7$s</a>',
	esc_url( $data['url'] ),
	$data['media_id'],
	esc_url( $data['backup_url'] ),
	esc_url( $data['original_url'] ),
	$data['width'],
	$data['height'],
	esc_html__( 'Compare Original VS Optimized', 'imagify' )
);
