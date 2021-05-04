<?php
namespace Imagify\Webp\RewriteRules;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Add and remove rewrite rules to the web.config file to display WebP images on the site.
 *
 * @since  1.9
 * @author Grégory Viguier
 */
class IIS extends \Imagify\WriteFile\AbstractIISDirConfFile {

	/**
	 * Name of the tag used as block delemiter.
	 *
	 * @var    string
	 * @since  1.9
	 * @author Grégory Viguier
	 */
	const TAG_NAME = 'Imagify: rewrite rules for webp';

	/**
	 * Get unfiltered new contents to write into the file.
	 *
	 * @since  1.9
	 * @access protected
	 * @source https://github.com/igrigorik/webp-detect/blob/master/iis.config
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	protected function get_raw_new_contents() {
		$extensions = $this->get_extensions_pattern();
		$home_root  = wp_parse_url( home_url( '/' ) );
		$home_root  = $home_root['path'];

		return trim( '
<!-- @parent /configuration/system.webServer/rewrite/rules -->
<rule name="' . esc_attr( static::TAG_NAME ) . ' 2">
	<match url="^(' . $home_root . '.+)\.(' . $extensions . ')$" ignoreCase="true" />
	<conditions logicalGrouping="MatchAll">
		<add input="{HTTP_ACCEPT}" pattern="image/webp" ignoreCase="false" />
		<add input="{DOCUMENT_ROOT}/{R:1}{R:2}.webp" matchType="IsFile" />
	</conditions>
	<action type="Rewrite" url="{R:1}{R:2}.webp" logRewrittenUrl="true" />
	<serverVariables>
		<set name="ACCEPTS_WEBP" value="true" />
	</serverVariables>
</rule>

<!-- @parent /configuration/system.webServer/rewrite/outboundRules -->
<rule preCondition="IsWebp" name="' . esc_attr( static::TAG_NAME ) . ' 3">
	<match serverVariable="RESPONSE_Vary" pattern=".*" />
	<action type="Rewrite" value="Accept"/>
</rule>
<preConditions name="' . esc_attr( static::TAG_NAME ) . ' 4">
	<preCondition name="IsWebp">
		<add input="{ACCEPTS_WEBP}" pattern="true" ignoreCase="false" />
	</preCondition>
</preConditions>' );
	}
}
